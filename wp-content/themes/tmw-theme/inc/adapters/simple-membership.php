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

        $member = $this->get_member_data($user_id);
        
        if ($member) {
            $account_state = isset($member->account_state) ? $member->account_state : '';
            return $account_state === 'active';
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

        $member = $this->get_member_data($user_id);
        
        if (!$member) {
            return null;
        }

        // Check for subscription_starts field
        if (!empty($member->subscription_starts)) {
            // For free/lifetime memberships, this might be the start date
            // Check if there's an expiry set
        }

        // Check for expiry in user meta (set by Stripe addon or manual)
        $user = get_user_by('ID', $user_id);
        if ($user) {
            // Try various meta keys used by SWPM addons
            $expiry_keys = array(
                'swpm_subscription_expiry',
                'swpm_expiry_date',
                'swpm_account_expiry',
            );
            
            foreach ($expiry_keys as $key) {
                $expiry = get_user_meta($user_id, $key, true);
                if ($expiry) {
                    return date('Y-m-d', strtotime($expiry));
                }
            }
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

        $member = $this->get_member_data($user_id);
        
        if ($member && isset($member->membership_level)) {
            return (int) $member->membership_level;
        }

        return null;
    }

    /**
     * Get membership level name from Simple Membership
     *
     * @param int $user_id
     * @return string|null Level name or null
     */
    public function get_level_name($user_id) {
        if (!$this->is_plugin_active()) {
            return null;
        }

        $level_id = $this->get_level_id($user_id);
        
        if (!$level_id) {
            return null;
        }

        // Get level info from database
        global $wpdb;
        $table = $wpdb->prefix . 'swpm_membership_tbl';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return null;
        }

        $level = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $level_id
        ));

        if ($level && isset($level->alias)) {
            return $level->alias;
        }

        return null;
    }

    /**
     * Get membership level info
     *
     * @param int $level_id
     * @return object|null
     */
    public function get_level_info($level_id) {
        if (!$this->is_plugin_active()) {
            return null;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'swpm_membership_tbl';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $level_id
        ));
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

        // Try by username first
        $member = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_name = %s",
            $user->user_login
        ));

        // If not found, try by email
        if (!$member) {
            $member = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE email = %s",
                $user->user_email
            ));
        }

        return $member;
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

    /**
     * Get Simple Membership page URLs
     *
     * @return array
     */
    public function get_swpm_page_urls() {
        $settings = get_option('swpm-settings', array());
        
        return array(
            'login'        => isset($settings['login-page-url']) ? $settings['login-page-url'] : '',
            'registration' => isset($settings['registration-page-url']) ? $settings['registration-page-url'] : '',
            'profile'      => isset($settings['profile-page-url']) ? $settings['profile-page-url'] : '',
            'join'         => isset($settings['join-us-page-url']) ? $settings['join-us-page-url'] : '',
        );
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
        $user = get_user_by('email', $member->email);
    }
    
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
        $user = get_user_by('email', $member->email);
    }
    
    if (!$user) {
        return;
    }

    update_user_meta($user->ID, 'tmw_subscription_status', $new_status);
    
    do_action('tmw_subscription_status_changed', $user->ID, $new_status);
}

/**
 * Sync user on login
 */
add_action('wp_login', 'tmw_sync_swpm_on_login', 10, 2);

function tmw_sync_swpm_on_login($user_login, $user) {
    $adapter = tmw_get_membership_adapter();
    
    if (!$adapter || !$adapter->is_plugin_active()) {
        return;
    }

    $member = $adapter->get_member_data($user->ID);
    
    if ($member) {
        // Sync tier
        $tier = tmw_map_level_to_tier($member->membership_level);
        update_user_meta($user->ID, 'tmw_subscription_tier', $tier);
        update_user_meta($user->ID, 'tmw_smp_level_id', $member->membership_level);
        update_user_meta($user->ID, 'tmw_subscription_status', $member->account_state);
    }
}
