<?php
/**
 * User Meta Adapter (Fallback)
 *
 * Manual subscription management via WordPress user meta.
 * Use this when no membership plugin is installed, or for manual control.
 *
 * User meta keys used:
 * - tmw_subscription_tier: 'free', 'paid', 'fleet'
 * - tmw_subscription_status: 'active', 'inactive', 'expired'
 * - tmw_subscription_expiry: Date string (Y-m-d)
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_User_Meta_Adapter implements TMW_Membership_Adapter_Interface {

    /**
     * Always active (this is the fallback)
     *
     * @return bool
     */
    public function is_plugin_active() {
        return true;
    }

    /**
     * Get user's subscription tier from user meta
     *
     * @param int $user_id
     * @return string 'free', 'paid', 'fleet', or 'none'
     */
    public function get_user_tier($user_id) {
        $tier = get_user_meta($user_id, 'tmw_subscription_tier', true);
        
        if (empty($tier)) {
            // Check if user has a WordPress role that maps to a tier
            $user = get_user_by('ID', $user_id);
            if ($user) {
                if (in_array('administrator', $user->roles)) {
                    return 'fleet'; // Admins get full access
                }
            }
            
            return tmw_get_level_mapping('fallback_tier', 'free');
        }

        $valid_tiers = array('free', 'paid', 'fleet', 'none');
        return in_array($tier, $valid_tiers) ? $tier : 'free';
    }

    /**
     * Check if subscription is active
     *
     * @param int $user_id
     * @return bool
     */
    public function is_active($user_id) {
        $status = get_user_meta($user_id, 'tmw_subscription_status', true);
        
        // If no status set, check expiry
        if (empty($status)) {
            $expiry = $this->get_expiry_date($user_id);
            if ($expiry) {
                return strtotime($expiry) > time();
            }
            // No expiry means lifetime/active
            return true;
        }

        return $status === 'active';
    }

    /**
     * Get subscription expiry date
     *
     * @param int $user_id
     * @return string|null Date string (Y-m-d) or null
     */
    public function get_expiry_date($user_id) {
        $expiry = get_user_meta($user_id, 'tmw_subscription_expiry', true);
        
        if (empty($expiry)) {
            return null;
        }

        // Validate date format
        $timestamp = strtotime($expiry);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * Get user's level ID (not applicable for user meta)
     *
     * @param int $user_id
     * @return int|null
     */
    public function get_level_id($user_id) {
        // Map tier to a pseudo level ID
        $tier = $this->get_user_tier($user_id);
        return tmw_map_tier_to_level($tier);
    }

    /**
     * Set user's subscription tier
     *
     * @param int $user_id
     * @param string $tier
     * @return bool
     */
    public function set_user_tier($user_id, $tier) {
        $valid_tiers = array('free', 'paid', 'fleet', 'none');
        if (!in_array($tier, $valid_tiers)) {
            return false;
        }

        $old_tier = $this->get_user_tier($user_id);
        update_user_meta($user_id, 'tmw_subscription_tier', $tier);
        
        do_action('tmw_subscription_changed', $user_id, $old_tier, $tier);
        
        return true;
    }

    /**
     * Set subscription status
     *
     * @param int $user_id
     * @param string $status 'active', 'inactive', 'expired'
     * @return bool
     */
    public function set_status($user_id, $status) {
        $valid = array('active', 'inactive', 'expired', 'pending');
        if (!in_array($status, $valid)) {
            return false;
        }

        update_user_meta($user_id, 'tmw_subscription_status', $status);
        do_action('tmw_subscription_status_changed', $user_id, $status);
        
        return true;
    }

    /**
     * Set subscription expiry
     *
     * @param int $user_id
     * @param string $date Date string
     * @return bool
     */
    public function set_expiry($user_id, $date) {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return false;
        }

        update_user_meta($user_id, 'tmw_subscription_expiry', date('Y-m-d', $timestamp));
        return true;
    }

    /**
     * Grant subscription to user
     *
     * @param int $user_id
     * @param string $tier
     * @param string|null $expiry Optional expiry date
     * @return bool
     */
    public function grant_subscription($user_id, $tier, $expiry = null) {
        $this->set_user_tier($user_id, $tier);
        $this->set_status($user_id, 'active');
        
        if ($expiry) {
            $this->set_expiry($user_id, $expiry);
        } else {
            delete_user_meta($user_id, 'tmw_subscription_expiry');
        }

        return true;
    }

    /**
     * Revoke subscription from user
     *
     * @param int $user_id
     * @param bool $downgrade_to_free Whether to downgrade to free tier
     * @return bool
     */
    public function revoke_subscription($user_id, $downgrade_to_free = true) {
        if ($downgrade_to_free) {
            $this->set_user_tier($user_id, 'free');
        } else {
            $this->set_user_tier($user_id, 'none');
        }
        
        $this->set_status($user_id, 'inactive');
        
        return true;
    }
}

// =============================================================================
// ADMIN: MANUAL SUBSCRIPTION MANAGEMENT
// =============================================================================

/**
 * Add subscription fields to user profile (admin)
 */
add_action('show_user_profile', 'tmw_user_meta_profile_fields');
add_action('edit_user_profile', 'tmw_user_meta_profile_fields');

function tmw_user_meta_profile_fields($user) {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Only show if using user-meta adapter
    if (tmw_get_setting('membership_plugin', 'simple-membership') !== 'user-meta') {
        return;
    }

    $tier = get_user_meta($user->ID, 'tmw_subscription_tier', true);
    $status = get_user_meta($user->ID, 'tmw_subscription_status', true);
    $expiry = get_user_meta($user->ID, 'tmw_subscription_expiry', true);
    ?>
    <h3><?php _e('TrackMyWrench Subscription', 'flavor-starter-flavor'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="tmw_tier"><?php _e('Subscription Tier', 'flavor-starter-flavor'); ?></label></th>
            <td>
                <select name="tmw_subscription_tier" id="tmw_tier">
                    <option value="free" <?php selected($tier, 'free'); ?>><?php _e('Free', 'flavor-starter-flavor'); ?></option>
                    <option value="paid" <?php selected($tier, 'paid'); ?>><?php _e('Paid', 'flavor-starter-flavor'); ?></option>
                    <option value="fleet" <?php selected($tier, 'fleet'); ?>><?php _e('Fleet', 'flavor-starter-flavor'); ?></option>
                    <option value="none" <?php selected($tier, 'none'); ?>><?php _e('No Access', 'flavor-starter-flavor'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="tmw_status"><?php _e('Status', 'flavor-starter-flavor'); ?></label></th>
            <td>
                <select name="tmw_subscription_status" id="tmw_status">
                    <option value="active" <?php selected($status, 'active'); ?>><?php _e('Active', 'flavor-starter-flavor'); ?></option>
                    <option value="inactive" <?php selected($status, 'inactive'); ?>><?php _e('Inactive', 'flavor-starter-flavor'); ?></option>
                    <option value="expired" <?php selected($status, 'expired'); ?>><?php _e('Expired', 'flavor-starter-flavor'); ?></option>
                    <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending', 'flavor-starter-flavor'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="tmw_expiry"><?php _e('Expiry Date', 'flavor-starter-flavor'); ?></label></th>
            <td>
                <input type="date" name="tmw_subscription_expiry" id="tmw_expiry" value="<?php echo esc_attr($expiry); ?>">
                <p class="description"><?php _e('Leave empty for no expiry (lifetime).', 'flavor-starter-flavor'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save subscription fields from user profile
 */
add_action('personal_options_update', 'tmw_save_user_meta_fields');
add_action('edit_user_profile_update', 'tmw_save_user_meta_fields');

function tmw_save_user_meta_fields($user_id) {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (tmw_get_setting('membership_plugin', 'simple-membership') !== 'user-meta') {
        return;
    }

    if (isset($_POST['tmw_subscription_tier'])) {
        update_user_meta($user_id, 'tmw_subscription_tier', sanitize_key($_POST['tmw_subscription_tier']));
    }

    if (isset($_POST['tmw_subscription_status'])) {
        update_user_meta($user_id, 'tmw_subscription_status', sanitize_key($_POST['tmw_subscription_status']));
    }

    if (isset($_POST['tmw_subscription_expiry'])) {
        $expiry = sanitize_text_field($_POST['tmw_subscription_expiry']);
        if (empty($expiry)) {
            delete_user_meta($user_id, 'tmw_subscription_expiry');
        } else {
            update_user_meta($user_id, 'tmw_subscription_expiry', $expiry);
        }
    }
}
