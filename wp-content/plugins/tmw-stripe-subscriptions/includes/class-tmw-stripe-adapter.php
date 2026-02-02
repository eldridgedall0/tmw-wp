<?php
/**
 * Stripe Membership Adapter
 *
 * Implements TMW_Membership_Adapter_Interface for Stripe subscriptions.
 * This class integrates with the theme's adapter pattern.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define the interface if it doesn't exist (theme may not be loaded)
if (!interface_exists('TMW_Membership_Adapter_Interface')) {
    interface TMW_Membership_Adapter_Interface {
        public function get_user_tier($user_id);
        public function is_active($user_id);
        public function get_expiry_date($user_id);
        public function get_level_id($user_id);
        public function is_plugin_active();
    }
}

class TMW_Stripe_Adapter implements TMW_Membership_Adapter_Interface {

    /**
     * Check if the Stripe plugin is active and configured
     *
     * @return bool
     */
    public function is_plugin_active() {
        return TMW_Stripe_API::is_configured();
    }

    /**
     * Get user's subscription tier
     *
     * @param int $user_id
     * @return string Tier slug ('free', 'paid', 'fleet', etc.) or 'none'
     */
    public function get_user_tier($user_id) {
        if (!$user_id) {
            return 'none';
        }

        // Check database first
        $subscription = $this->get_subscription_record($user_id);

        if ($subscription) {
            // Check if subscription is active
            if ($this->is_subscription_active($subscription)) {
                return $subscription->tier_slug;
            }

            // Check if canceled but still in grace period
            if ($subscription->status === 'canceled' && !empty($subscription->current_period_end)) {
                if (strtotime($subscription->current_period_end) > time()) {
                    return $subscription->tier_slug;
                }
            }
        }

        // Check user meta as fallback
        $tier = get_user_meta($user_id, 'tmw_subscription_tier', true);
        if ($tier && $this->is_valid_tier($tier)) {
            return $tier;
        }

        // Return free tier
        return $this->get_free_tier_slug();
    }

    /**
     * Check if subscription is active
     *
     * @param int $user_id
     * @return bool
     */
    public function is_active($user_id) {
        if (!$user_id) {
            return false;
        }

        $subscription = $this->get_subscription_record($user_id);

        if (!$subscription) {
            // Free tier is always "active"
            $tier = $this->get_user_tier($user_id);
            return $this->is_free_tier($tier);
        }

        return $this->is_subscription_active($subscription);
    }

    /**
     * Get subscription expiry/renewal date
     *
     * @param int $user_id
     * @return string|null Date string (Y-m-d) or null
     */
    public function get_expiry_date($user_id) {
        if (!$user_id) {
            return null;
        }

        $subscription = $this->get_subscription_record($user_id);

        if ($subscription && !empty($subscription->current_period_end)) {
            return date('Y-m-d', strtotime($subscription->current_period_end));
        }

        // Fallback to user meta
        $expiry = get_user_meta($user_id, 'tmw_subscription_current_period_end', true);
        if ($expiry) {
            return date('Y-m-d', strtotime($expiry));
        }

        return null;
    }

    /**
     * Get user's membership level ID
     * For Stripe, we use the tier order as a pseudo-level ID
     *
     * @param int $user_id
     * @return int|null
     */
    public function get_level_id($user_id) {
        if (!$user_id || !function_exists('tmw_get_tiers')) {
            return null;
        }

        $tier_slug = $this->get_user_tier($user_id);
        $tiers = tmw_get_tiers();

        if (isset($tiers[$tier_slug])) {
            // Return SWPM level ID for backward compatibility
            return (int) ($tiers[$tier_slug]['swpm_level_id'] ?? $tiers[$tier_slug]['order'] ?? 0);
        }

        return null;
    }

    /**
     * Get membership level name
     *
     * @param int $user_id
     * @return string|null
     */
    public function get_level_name($user_id) {
        if (!$user_id || !function_exists('tmw_get_tier')) {
            return null;
        }

        $tier_slug = $this->get_user_tier($user_id);
        $tier = tmw_get_tier($tier_slug);

        return $tier ? ($tier['name'] ?? ucfirst($tier_slug)) : null;
    }

    // =========================================================================
    // SUBSCRIPTION RECORD METHODS
    // =========================================================================

    /**
     * Get subscription record from database
     *
     * @param int $user_id
     * @return object|null
     */
    public function get_subscription_record($user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Create or update subscription record
     *
     * @param int   $user_id
     * @param array $data
     * @return bool
     */
    public function save_subscription_record($user_id, $data) {
        global $wpdb;

        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';
        $existing = $this->get_subscription_record($user_id);

        $record = array(
            'user_id'                 => $user_id,
            'stripe_customer_id'      => $data['stripe_customer_id'] ?? '',
            'stripe_subscription_id'  => $data['stripe_subscription_id'] ?? null,
            'tier_slug'               => $data['tier_slug'] ?? 'free',
            'status'                  => $data['status'] ?? 'active',
            'current_period_start'    => $data['current_period_start'] ?? null,
            'current_period_end'      => $data['current_period_end'] ?? null,
            'trial_used'              => $data['trial_used'] ?? 0,
            'canceled_at'             => $data['canceled_at'] ?? null,
        );

        $format = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s');

        if ($existing) {
            unset($record['user_id']); // Don't update user_id
            array_shift($format);

            $result = $wpdb->update(
                $table,
                $record,
                array('user_id' => $user_id),
                $format,
                array('%d')
            );
        } else {
            $result = $wpdb->insert($table, $record, $format);
        }

        // Also update user meta for quick access
        update_user_meta($user_id, 'tmw_subscription_tier', $record['tier_slug']);
        update_user_meta($user_id, 'tmw_subscription_status', $record['status']);
        
        if (!empty($record['stripe_customer_id'])) {
            update_user_meta($user_id, 'tmw_stripe_customer_id', $record['stripe_customer_id']);
        }
        if (!empty($record['stripe_subscription_id'])) {
            update_user_meta($user_id, 'tmw_stripe_subscription_id', $record['stripe_subscription_id']);
        }
        if (!empty($record['current_period_end'])) {
            update_user_meta($user_id, 'tmw_subscription_current_period_end', $record['current_period_end']);
        }

        return $result !== false;
    }

    /**
     * Delete subscription record
     *
     * @param int $user_id
     * @return bool
     */
    public function delete_subscription_record($user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';

        return $wpdb->delete($table, array('user_id' => $user_id), array('%d')) !== false;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if a subscription record is active
     *
     * @param object $subscription
     * @return bool
     */
    private function is_subscription_active($subscription) {
        $active_statuses = array('active', 'trialing');
        return in_array($subscription->status, $active_statuses, true);
    }

    /**
     * Check if tier slug is valid
     *
     * @param string $tier_slug
     * @return bool
     */
    private function is_valid_tier($tier_slug) {
        if (!function_exists('tmw_get_tiers')) {
            return false;
        }

        $tiers = tmw_get_tiers();
        return isset($tiers[$tier_slug]);
    }

    /**
     * Check if tier is a free tier
     *
     * @param string $tier_slug
     * @return bool
     */
    private function is_free_tier($tier_slug) {
        if (!function_exists('tmw_get_tier')) {
            return $tier_slug === 'free';
        }

        $tier = tmw_get_tier($tier_slug);
        return $tier ? !empty($tier['is_free']) : false;
    }

    /**
     * Get the free tier slug
     *
     * @return string
     */
    private function get_free_tier_slug() {
        if (!function_exists('tmw_get_tiers')) {
            return 'free';
        }

        $tiers = tmw_get_tiers();

        foreach ($tiers as $slug => $tier) {
            if (!empty($tier['is_free'])) {
                return $slug;
            }
        }

        return 'free';
    }

    /**
     * Get Stripe customer ID for user
     *
     * @param int $user_id
     * @return string|null
     */
    public function get_stripe_customer_id($user_id) {
        $subscription = $this->get_subscription_record($user_id);

        if ($subscription && !empty($subscription->stripe_customer_id)) {
            return $subscription->stripe_customer_id;
        }

        return get_user_meta($user_id, 'tmw_stripe_customer_id', true) ?: null;
    }

    /**
     * Get Stripe subscription ID for user
     *
     * @param int $user_id
     * @return string|null
     */
    public function get_stripe_subscription_id($user_id) {
        $subscription = $this->get_subscription_record($user_id);

        if ($subscription && !empty($subscription->stripe_subscription_id)) {
            return $subscription->stripe_subscription_id;
        }

        return get_user_meta($user_id, 'tmw_stripe_subscription_id', true) ?: null;
    }

    /**
     * Check if user has used their trial
     *
     * @param int $user_id
     * @return bool
     */
    public function has_used_trial($user_id) {
        $subscription = $this->get_subscription_record($user_id);

        if ($subscription) {
            return (bool) $subscription->trial_used;
        }

        return (bool) get_user_meta($user_id, 'tmw_stripe_trial_used', true);
    }

    /**
     * Mark trial as used
     *
     * @param int $user_id
     * @return bool
     */
    public function mark_trial_used($user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';
        
        $result = $wpdb->update(
            $table,
            array('trial_used' => 1),
            array('user_id' => $user_id),
            array('%d'),
            array('%d')
        );

        update_user_meta($user_id, 'tmw_stripe_trial_used', 1);

        return $result !== false;
    }

    /**
     * Get subscription status
     *
     * @param int $user_id
     * @return string
     */
    public function get_subscription_status($user_id) {
        $subscription = $this->get_subscription_record($user_id);

        if ($subscription) {
            return $subscription->status;
        }

        return get_user_meta($user_id, 'tmw_subscription_status', true) ?: 'none';
    }
}

// =========================================================================
// GLOBAL HELPER FUNCTIONS
// =========================================================================

/**
 * Get Stripe adapter instance
 *
 * @return TMW_Stripe_Adapter
 */
function tmw_stripe_adapter() {
    static $adapter = null;

    if ($adapter === null) {
        $adapter = new TMW_Stripe_Adapter();
    }

    return $adapter;
}

/**
 * Check if user has used their trial
 *
 * @param int $user_id
 * @return bool
 */
function tmw_user_has_used_trial($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    return tmw_stripe_adapter()->has_used_trial($user_id);
}

/**
 * Get user's Stripe customer ID
 *
 * @param int $user_id
 * @return string|null
 */
function tmw_get_stripe_customer_id($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    return tmw_stripe_adapter()->get_stripe_customer_id($user_id);
}

/**
 * Get user's Stripe subscription ID
 *
 * @param int $user_id
 * @return string|null
 */
function tmw_get_stripe_subscription_id($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    return tmw_stripe_adapter()->get_stripe_subscription_id($user_id);
}
