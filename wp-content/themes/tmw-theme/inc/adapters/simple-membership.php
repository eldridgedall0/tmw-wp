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
// HELPER FUNCTIONS FOR SIMPLE MEMBERSHIP
// =============================================================================

/**
 * Get Simple Membership level name for a user
 *
 * @param int $user_id
 * @return string Level name or tier name as fallback
 */
function tmw_get_swpm_level_name($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $adapter = tmw_get_membership_adapter();
    
    if ($adapter && method_exists($adapter, 'get_level_name')) {
        $name = $adapter->get_level_name($user_id);
        if ($name) {
            return $name;
        }
    }

    // Fallback to tier name
    $tier = tmw_get_user_tier($user_id);
    return tmw_get_tier_name($tier);
}

/**
 * Get Simple Membership profile page URL
 *
 * @return string
 */
function tmw_get_swpm_profile_url() {
    $adapter = tmw_get_membership_adapter();
    
    if ($adapter && method_exists($adapter, 'get_swpm_page_urls')) {
        $urls = $adapter->get_swpm_page_urls();
        if (!empty($urls['profile'])) {
            return $urls['profile'];
        }
    }

    // Fallback - try common page slugs
    $page = get_page_by_path('membership-profile');
    if ($page) {
        return get_permalink($page);
    }

    return home_url('/membership-profile/');
}

/**
 * Get Simple Membership join/registration page URL
 *
 * @param int|null $level_id Optional level ID to pre-select
 * @return string
 */
function tmw_get_swpm_join_url($level_id = null) {
    $adapter = tmw_get_membership_adapter();
    $url = '';
    
    if ($adapter && method_exists($adapter, 'get_swpm_page_urls')) {
        $urls = $adapter->get_swpm_page_urls();
        if (!empty($urls['join'])) {
            $url = $urls['join'];
        } elseif (!empty($urls['registration'])) {
            $url = $urls['registration'];
        }
    }

    // Fallback - try common page slugs
    if (empty($url)) {
        $page = get_page_by_path('membership-join');
        if (!$page) {
            $page = get_page_by_path('join');
        }
        if ($page) {
            $url = get_permalink($page);
        } else {
            $url = home_url('/membership-join/');
        }
    }

    // Add level parameter if provided
    if ($level_id) {
        $url = add_query_arg('level', $level_id, $url);
    }

    return $url;
}

/**
 * Check if user is on a free/no-cost membership level
 *
 * @param int $user_id
 * @return bool
 */
function tmw_is_free_membership($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $tier = tmw_get_user_tier($user_id);
    
    // Free tier is always free
    if ($tier === 'free') {
        return true;
    }

    // Check the mapped level ID
    $adapter = tmw_get_membership_adapter();
    if ($adapter && method_exists($adapter, 'get_level_id')) {
        $level_id = $adapter->get_level_id($user_id);
        $free_level_id = tmw_get_level_mapping('free_level_id');
        
        if ($level_id && $free_level_id && $level_id == $free_level_id) {
            return true;
        }
    }

    return false;
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
