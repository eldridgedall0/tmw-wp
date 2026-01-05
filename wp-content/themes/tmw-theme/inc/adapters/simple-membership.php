<?php
/**
 * Simple Membership Plugin Adapter
 *
 * Integrates with Simple Membership plugin for subscription management.
 * https://wordpress.org/plugins/simple-membership/
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Simple_Membership_Adapter implements TMW_Membership_Adapter_Interface {

    /**
     * Check if Simple Membership plugin is active
     *
     * @return bool
     */
    public function is_plugin_active() {
        return class_exists('SimpleWpMembership') || 
               defined('SIMPLE_WP_MEMBERSHIP_VER') ||
               class_exists('SwpmMemberUtils');
    }

    /**
     * Get user's subscription tier
     *
     * @param int $user_id
     * @return string 'free', 'paid', 'fleet', or 'none'
     */
    public function get_user_tier($user_id) {
        if (!$this->is_plugin_active()) {
            return tmw_get_level_mapping('fallback_tier', 'free');
        }

        $level_id = $this->get_level_id($user_id);
        
        if (!$level_id) {
            return tmw_get_level_mapping('fallback_tier', 'free');
        }

        return tmw_map_level_to_tier($level_id);
    }

    /**
     * Check if subscription is active
     *
     * @param int $user_id
     * @return bool
     */
    public function is_active($user_id) {
        if (!$this->is_plugin_active()) {
            return false;
        }

        // Check if SwpmMemberUtils class exists
        if (class_exists('SwpmMemberUtils')) {
            $member = SwpmMemberUtils::get_user_by_user_name(get_user_by('ID', $user_id)->user_login);
            
            if ($member) {
                $account_state = isset($member->account_state) ? $member->account_state : '';
                return $account_state === 'active';
            }
        }

        // Alternative: Check via database directly
        global $wpdb;
        $table = $wpdb->prefix . 'swpm_members_tbl';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $status = $wpdb->get_var($wpdb->prepare(
                "SELECT account_state FROM $table WHERE user_name = %s",
                get_user_by('ID', $user_id)->user_login
            ));
            
            return $status === 'active';
        }

        return false;
    }

    /**
     * Get subscription expiry date
     *
     * @param int $user_id
     * @return string|null Date string (Y-m-d) or null
     */
    public function get_expiry_date($user_id) {
        if (!$this->is_plugin_active()) {
            return null;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'swpm_members_tbl';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $user = get_user_by('ID', $user_id);
            if (!$user) {
                return null;
            }

            $expiry = $wpdb->get_var($wpdb->prepare(
                "SELECT subscription_starts FROM $table WHERE user_name = %s",
                $user->user_login
            ));

            // Simple Membership stores subscription_starts, not expiry
            // For recurring, we need to check the Stripe addon data
            // For now, return subscription_starts as reference
            
            // Check for expiry in user meta (set by Stripe addon)
            $stripe_expiry = get_user_meta($user_id, 'swpm_subscription_expiry', true);
            if ($stripe_expiry) {
                return date('Y-m-d', strtotime($stripe_expiry));
            }

            return $expiry ? date('Y-m-d', strtotime($expiry)) : null;
        }

        return null;
    }

    /**
     * Get user's membership level ID
     *
     * @param int $user_id
     * @return int|null
     */
    public function get_level_id($user_id) {
        if (!$this->is_plugin_active()) {
            return null;
        }

        // Try SwpmMemberUtils first
        if (class_exists('SwpmMemberUtils')) {
            $user = get_user_by('ID', $user_id);
            if ($user) {
                $member = SwpmMemberUtils::get_user_by_user_name($user->user_login);
                if ($member && isset($member->membership_level)) {
                    return (int) $member->membership_level;
                }
            }
        }

        // Fallback: Query database directly
        global $wpdb;
        $table = $wpdb->prefix . 'swpm_members_tbl';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $user = get_user_by('ID', $user_id);
            if (!$user) {
                return null;
            }

            $level = $wpdb->get_var($wpdb->prepare(
                "SELECT membership_level FROM $table WHERE user_name = %s",
                $user->user_login
            ));

            return $level ? (int) $level : null;
        }

        return null;
    }

    /**
     * Get member data from Simple Membership
     *
     * @param int $user_id
     * @return object|null
     */
    public function get_member_data($user_id) {
        if (!$this->is_plugin_active()) {
            return null;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'swpm_members_tbl';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return null;
        }

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_name = %s",
            $user->user_login
        ));
    }

    /**
     * Get all membership levels
     *
     * @return array
     */
    public function get_all_levels() {
        if (!$this->is_plugin_active()) {
            return array();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'swpm_membership_tbl';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return array();
        }

        $levels = $wpdb->get_results("SELECT * FROM $table ORDER BY id ASC");
        
        return $levels ? $levels : array();
    }
}

// =============================================================================
// HOOKS FOR SIMPLE MEMBERSHIP EVENTS
// =============================================================================

/**
 * Hook into Simple Membership level change
 */
add_action('swpm_membership_level_changed', 'tmw_smp_level_changed', 10, 2);

function tmw_smp_level_changed($member_id, $new_level) {
    // Get WordPress user ID from SMP member
    global $wpdb;
    $table = $wpdb->prefix . 'swpm_members_tbl';
    
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE member_id = %d",
        $member_id
    ));

    if (!$member) {
        return;
    }

    $user = get_user_by('login', $member->user_name);
    if (!$user) {
        return;
    }

    $old_tier = get_user_meta($user->ID, 'tmw_subscription_tier', true);
    $new_tier = tmw_map_level_to_tier($new_level);

    // Update user meta
    update_user_meta($user->ID, 'tmw_subscription_tier', $new_tier);
    update_user_meta($user->ID, 'tmw_smp_level_id', $new_level);

    // Fire our custom action
    do_action('tmw_subscription_changed', $user->ID, $old_tier, $new_tier);
}

/**
 * Hook into Simple Membership account status change
 */
add_action('swpm_account_status_changed', 'tmw_smp_status_changed', 10, 2);

function tmw_smp_status_changed($member_id, $new_status) {
    global $wpdb;
    $table = $wpdb->prefix . 'swpm_members_tbl';
    
    $member = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE member_id = %d",
        $member_id
    ));

    if (!$member) {
        return;
    }

    $user = get_user_by('login', $member->user_name);
    if (!$user) {
        return;
    }

    update_user_meta($user->ID, 'tmw_subscription_status', $new_status);
    
    do_action('tmw_subscription_status_changed', $user->ID, $new_status);
}
