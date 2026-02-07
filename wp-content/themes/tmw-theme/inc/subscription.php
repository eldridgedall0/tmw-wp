<?php
/**
 * Subscription Tier Logic - Dynamic System
 *
 * Handles subscription tier detection and limit enforcement.
 * Works with the membership adapter pattern for plugin flexibility.
 * Includes GarageMinder API functions for the app.
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// GET USER SUBSCRIPTION TIER
// =============================================================================

/**
 * Get the subscription tier slug for a user
 *
 * @param int $user_id User ID (defaults to current user)
 * @return string Tier slug (e.g., 'free', 'paid', 'fleet') or 'none'
 */
function tmw_get_user_tier($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return 'none';
    }

    // Get the membership adapter
    $adapter = tmw_get_membership_adapter();
    
    if ($adapter) {
        return $adapter->get_user_tier($user_id);
    }

    // Fallback to user meta
    $tier = get_user_meta($user_id, 'tmw_subscription_tier', true);
    $tiers = tmw_get_tiers();
    
    // Validate tier exists
    if ($tier && isset($tiers[$tier])) {
        return $tier;
    }
    
    // Return first free tier as fallback
    foreach ($tiers as $slug => $t) {
        if (!empty($t['is_free'])) {
            return $slug;
        }
    }
    
    return array_key_first($tiers) ?: 'free';
}

/**
 * Get user's tier ID (SWPM level ID)
 *
 * @param int $user_id User ID
 * @return int Tier ID (SWPM level ID)
 */
function tmw_get_user_tier_id($user_id = 0) {
    $tier_slug = tmw_get_user_tier($user_id);
    $tier = tmw_get_tier($tier_slug);
    return $tier ? (int) ($tier['swpm_level_id'] ?? 0) : 0;
}

/**
 * Check if user has active subscription
 *
 * @param int $user_id User ID
 * @return bool
 */
function tmw_user_has_active_subscription($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    // Free tiers are always "active"
    if (tmw_is_free_membership($user_id)) {
        return true;
    }

    $adapter = tmw_get_membership_adapter();
    
    if ($adapter) {
        return $adapter->is_active($user_id);
    }

    // Fallback: check user meta
    $status = get_user_meta($user_id, 'tmw_subscription_status', true);
    return $status === 'active';
}

/**
 * Get subscription expiry date
 *
 * @param int $user_id User ID
 * @return string|null Date string or null if no expiry
 */
function tmw_get_subscription_expiry($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $adapter = tmw_get_membership_adapter();
    
    if ($adapter) {
        return $adapter->get_expiry_date($user_id);
    }

    return get_user_meta($user_id, 'tmw_subscription_expiry', true) ?: null;
}

/**
 * Check if user is on a free/no-cost membership
 *
 * @param int $user_id User ID
 * @return bool
 */
function tmw_is_free_membership($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $tier_slug = tmw_get_user_tier($user_id);
    $tier = tmw_get_tier($tier_slug);
    
    return $tier ? !empty($tier['is_free']) : true;
}

// =============================================================================
// GET TIER LIMITS (Dynamic)
// =============================================================================

/**
 * Get all limits for a specific tier
 *
 * @param string $tier Tier slug
 * @return array Limits array
 */
function tmw_get_tier_limits($tier = 'free') {
    $values = tmw_get_tier_values();
    
    if (isset($values[$tier])) {
        return $values[$tier];
    }
    
    // Return first tier's values as fallback
    return reset($values) ?: array();
}

/**
 * Get limits for the current user
 *
 * @param int $user_id Optional user ID
 * @return array Limits array
 */
function tmw_get_user_limits($user_id = 0) {
    $tier = tmw_get_user_tier($user_id);
    return tmw_get_tier_limits($tier);
}

/**
 * Check if a specific feature is allowed for user
 *
 * @param string $feature Feature/limit key
 * @param int $user_id Optional user ID
 * @return mixed Boolean for toggles, int for limits (-1 = unlimited)
 */
function tmw_user_can($feature, $user_id = 0) {
    $limits = tmw_get_user_limits($user_id);
    
    if (isset($limits[$feature])) {
        return $limits[$feature];
    }

    return false;
}

/**
 * Get a specific limit value for user
 *
 * @param int $user_id User ID
 * @param string $limit_key Limit key
 * @return mixed Limit value
 */
function tmw_get_user_limit($user_id, $limit_key) {
    $tier = tmw_get_user_tier($user_id);
    return tmw_get_tier_value($tier, $limit_key);
}

// =============================================================================
// TIER DISPLAY HELPERS (Dynamic)
// =============================================================================

/**
 * Get tier display name
 *
 * @param string $tier_slug Tier slug
 * @return string Display name
 */
function tmw_get_tier_name($tier_slug) {
    if ($tier_slug === 'none') {
        return __('No Subscription', 'flavor-starter-flavor');
    }
    
    $tier = tmw_get_tier($tier_slug);
    
    if ($tier && !empty($tier['name'])) {
        return $tier['name'];
    }
    
    return ucfirst($tier_slug);
}

/**
 * Get tier badge HTML
 *
 * @param string $tier_slug Tier slug
 * @return string HTML badge
 */
function tmw_get_tier_badge($tier_slug) {
    $tier = tmw_get_tier($tier_slug);
    $name = tmw_get_tier_name($tier_slug);
    $color = $tier ? ($tier['color'] ?? '#6b7280') : '#6b7280';
    
    return sprintf(
        '<span class="tmw-badge tmw-badge-%s" style="background:%s;color:#fff;">%s</span>',
        esc_attr($tier_slug),
        esc_attr($color),
        esc_html($name)
    );
}

/**
 * Get upgrade URL based on current tier
 *
 * @param string $current_tier Current tier slug
 * @return string Upgrade URL
 */
function tmw_get_upgrade_url($current_tier = null) {
    if ($current_tier === null) {
        $current_tier = tmw_get_user_tier();
    }

    $pricing_page = tmw_get_page_url('pricing');
    return add_query_arg('upgrade', $current_tier, $pricing_page);
}

// =============================================================================
// GARAGEMINDER API FUNCTIONS
// These functions are designed for use in the GarageMinder app
// =============================================================================

/**
 * Check if user is on a specific tier by SWPM level ID
 *
 * @param int $user_id WordPress user ID
 * @param int $tier_id SWPM level ID to check
 * @return bool True if user is on this tier
 */
function gm_has_subscription($user_id, $tier_id) {
    $user_tier_id = tmw_get_user_tier_id($user_id);
    return $user_tier_id === (int) $tier_id;
}

/**
 * Check if user is on a specific tier by slug
 *
 * @param int $user_id WordPress user ID
 * @param string $tier_slug Tier slug (e.g., 'free', 'paid', 'fleet')
 * @return bool True if user is on this tier
 */
function gm_is_tier($user_id, $tier_slug) {
    return tmw_get_user_tier($user_id) === $tier_slug;
}

/**
 * Check if user is on a tier at or above the specified level
 *
 * @param int $user_id WordPress user ID
 * @param string $min_tier_slug Minimum tier slug required
 * @return bool True if user meets or exceeds this tier
 */
function gm_has_tier_or_higher($user_id, $min_tier_slug) {
    $tiers = tmw_get_tiers();
    $user_tier = tmw_get_user_tier($user_id);
    
    $min_order = isset($tiers[$min_tier_slug]) ? ($tiers[$min_tier_slug]['order'] ?? 99) : 99;
    $user_order = isset($tiers[$user_tier]) ? ($tiers[$user_tier]['order'] ?? 0) : 0;
    
    return $user_order >= $min_order;
}

/**
 * Get user's tier ID (SWPM level ID)
 *
 * @param int $user_id WordPress user ID
 * @return int SWPM level ID
 */
function gm_get_user_tier_id($user_id) {
    return tmw_get_user_tier_id($user_id);
}

/**
 * Get user's tier slug
 *
 * @param int $user_id WordPress user ID
 * @return string Tier slug
 */
function gm_get_user_tier($user_id) {
    return tmw_get_user_tier($user_id);
}

/**
 * Get a specific limit value for user
 *
 * @param int $user_id WordPress user ID
 * @param string $limit_key Limit key (e.g., 'max_vehicles', 'recalls_enabled')
 * @return mixed Limit value (int, bool, or string depending on limit type)
 */
function gm_get_limit($user_id, $limit_key) {
    return tmw_get_user_limit($user_id, $limit_key);
}

/**
 * Check if user can add more items (numeric limit check)
 *
 * @param int $user_id WordPress user ID
 * @param string $limit_key Limit key (e.g., 'max_vehicles', 'max_entries')
 * @param int $current_count Current count of items
 * @return bool True if user can add more
 */
function gm_can_add($user_id, $limit_key, $current_count) {
    $max = gm_get_limit($user_id, $limit_key);
    
    // -1 means unlimited
    if ($max === -1 || $max === '-1') {
        return true;
    }
    
    return (int) $current_count < (int) $max;
}

/**
 * Check if user has access to a feature (boolean or non-zero check)
 *
 * @param int $user_id WordPress user ID
 * @param string $limit_key Limit key
 * @return bool True if feature is enabled/available
 */
function gm_has_feature($user_id, $limit_key) {
    $value = gm_get_limit($user_id, $limit_key);
    
    // Boolean check
    if (is_bool($value)) {
        return $value;
    }
    
    // Numeric check (> 0 or -1 for unlimited)
    if (is_numeric($value)) {
        return (int) $value > 0 || (int) $value === -1;
    }
    
    // String check (not empty, not 'none', not '0')
    return !empty($value) && $value !== 'none' && $value !== '0';
}

/**
 * Get all limits for user's tier
 *
 * @param int $user_id WordPress user ID
 * @return array All limits as key => value pairs
 */
function gm_get_user_limits($user_id) {
    return tmw_get_user_limits($user_id);
}

/**
 * Format limit value for display
 *
 * @param mixed $value Limit value
 * @param string $type Limit type ('number', 'boolean', 'select')
 * @return string Formatted display string
 */
function gm_format_limit($value, $type = 'number') {
    switch ($type) {
        case 'boolean':
            return $value ? __('Yes', 'flavor-starter-flavor') : __('No', 'flavor-starter-flavor');
        
        case 'number':
            if ($value === -1 || $value === '-1') {
                return __('Unlimited', 'flavor-starter-flavor');
            }
            return (string) $value;
        
        case 'select':
            return ucfirst($value);
        
        default:
            return (string) $value;
    }
}

// =============================================================================
// SUBSCRIPTION DATA FOR REST API
// =============================================================================

/**
 * Get complete subscription data for a user
 *
 * @param int $user_id User ID
 * @return array Subscription data
 */
function tmw_get_user_subscription_data($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return array(
            'tier'        => 'none',
            'tier_id'     => 0,
            'tier_name'   => tmw_get_tier_name('none'),
            'is_active'   => false,
            'is_free'     => true,
            'limits'      => array(),
            'expiry_date' => null,
        );
    }

    $tier_slug = tmw_get_user_tier($user_id);
    $tier = tmw_get_tier($tier_slug);
    $is_free = $tier ? !empty($tier['is_free']) : true;
    $is_active = $is_free ? true : tmw_user_has_active_subscription($user_id);
    
    return array(
        'tier'        => $tier_slug,
        'tier_id'     => $tier ? (int) ($tier['swpm_level_id'] ?? 0) : 0,
        'tier_name'   => tmw_get_tier_name($tier_slug),
        'is_active'   => $is_active,
        'is_free'     => $is_free,
        'limits'      => tmw_get_tier_limits($tier_slug),
        'expiry_date' => $is_free ? null : tmw_get_subscription_expiry($user_id),
        'upgrade_url' => tmw_get_upgrade_url($tier_slug),
    );
}

// =============================================================================
// FEATURE COMPARISON DATA (for pricing page)
// =============================================================================

/**
 * Get feature comparison data for pricing page
 *
 * @return array Feature comparison
 */
function tmw_get_feature_comparison() {
    $tiers = tmw_get_tiers();
    $limits = tmw_get_limit_definitions();
    $values = tmw_get_tier_values();
    
    $comparison = array();
    
    foreach ($limits as $key => $limit) {
        $row = array(
            'name' => $limit['label'],
            'description' => $limit['description'] ?? '',
            'type' => $limit['type'],
        );
        
        foreach ($tiers as $tier_slug => $tier) {
            $value = $values[$tier_slug][$key] ?? null;
            $row[$tier_slug] = gm_format_limit($value, $limit['type']);
        }
        
        $comparison[] = $row;
    }
    
    return $comparison;
}

// =============================================================================
// SWPM HELPER FUNCTIONS
// =============================================================================

/**
 * Get SWPM level name for a user
 *
 * @param int $user_id User ID
 * @return string Level name
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
    return tmw_get_tier_name(tmw_get_user_tier($user_id));
}

/**
 * Get SWPM profile page URL
 *
 * @return string URL
 */
function tmw_get_swpm_profile_url() {
    $adapter = tmw_get_membership_adapter();
    
    if ($adapter && method_exists($adapter, 'get_swpm_page_urls')) {
        $urls = $adapter->get_swpm_page_urls();
        if (!empty($urls['profile'])) {
            return $urls['profile'];
        }
    }

    $page = get_page_by_path('membership-profile');
    return $page ? get_permalink($page) : home_url('/membership-profile/');
}

/**
 * Get SWPM join page URL
 *
 * @param int|null $level_id Optional level ID
 * @return string URL
 */
function tmw_get_swpm_join_url($level_id = null) {
    $adapter = tmw_get_membership_adapter();
    $url = '';
    
    if ($adapter && method_exists($adapter, 'get_swpm_page_urls')) {
        $urls = $adapter->get_swpm_page_urls();
        $url = $urls['join'] ?? $urls['registration'] ?? '';
    }

    if (empty($url)) {
        $page = get_page_by_path('membership-join');
        $url = $page ? get_permalink($page) : home_url('/membership-join/');
    }

    if ($level_id) {
        $url = add_query_arg('level', $level_id, $url);
    }

    return $url;
}

// =============================================================================
// MAP SWPM LEVEL TO TIER
// =============================================================================

/**
 * Map a Simple Membership level ID to a tier slug
 *
 * @param int $level_id SWPM level ID
 * @return string Tier slug
 */
function tmw_map_level_to_tier($level_id) {
    $tiers = tmw_get_tiers();
    
    foreach ($tiers as $slug => $tier) {
        if ((int) ($tier['swpm_level_id'] ?? 0) === (int) $level_id) {
            return $slug;
        }
    }
    
    // Return first free tier as fallback
    foreach ($tiers as $slug => $tier) {
        if (!empty($tier['is_free'])) {
            return $slug;
        }
    }
    
    return array_key_first($tiers) ?: 'free';
}

// =============================================================================
// HOOKS FOR SUBSCRIPTION CHANGES
// =============================================================================

/**
 * Action hook when subscription tier changes
 */
function tmw_subscription_changed($user_id, $old_tier, $new_tier) {
    do_action('tmw_subscription_changed', $user_id, $old_tier, $new_tier);
    
    update_user_meta($user_id, 'tmw_subscription_tier', $new_tier);
    update_user_meta($user_id, 'tmw_tier_changed', current_time('mysql'));
}

// =============================================================================
// GENERIC SUBSCRIPTION URL HELPERS
// These functions work with any membership plugin based on settings
// Note: Some of these may also be defined by the Stripe plugin - use function_exists()
// =============================================================================

/**
 * Check if current membership plugin is Stripe
 *
 * @return bool
 */
if (!function_exists('tmw_is_stripe_active')) {
    function tmw_is_stripe_active() {
        $plugin = tmw_get_setting('membership_plugin', 'simple-membership');
        return $plugin === 'stripe' && defined('TMW_STRIPE_VERSION');
    }
}

/**
 * Get subscribe/checkout URL for a tier
 * Works with any membership plugin based on settings
 * 
 * Note: This function may be overridden by the TMW Stripe Subscriptions plugin
 *
 * @param string $tier_slug Tier slug
 * @param string $period 'monthly' or 'yearly'
 * @return string URL
 */
if (!function_exists('tmw_get_subscribe_url')) {
    function tmw_get_subscribe_url($tier_slug, $period = 'monthly') {
        $plugin = tmw_get_setting('membership_plugin', 'simple-membership');

        switch ($plugin) {
            case 'stripe':
                // For Stripe, return # - JavaScript handles checkout via AJAX
                return '#';

            case 'simple-membership':
            default:
                $tier = tmw_get_tier($tier_slug);
                $level_id = $tier['swpm_level_id'] ?? 0;
                return tmw_get_swpm_join_url($level_id);
        }
    }
}

/**
 * Get subscription management URL
 * Works with any membership plugin based on settings
 * 
 * Note: This function may be overridden by the TMW Stripe Subscriptions plugin
 *
 * @return string URL
 */
if (!function_exists('tmw_get_manage_subscription_url')) {
    function tmw_get_manage_subscription_url() {
        $plugin = tmw_get_setting('membership_plugin', 'simple-membership');

        switch ($plugin) {
            case 'stripe':
                // For Stripe, return # - JavaScript handles portal redirect via AJAX
                return '#';

            case 'simple-membership':
            default:
                return tmw_get_swpm_profile_url();
        }
    }
}

/**
 * Get cancel subscription URL
 * For Stripe, this redirects to portal where cancel is handled
 * 
 * Note: This function may be overridden by the TMW Stripe Subscriptions plugin
 *
 * @return string URL
 */
if (!function_exists('tmw_get_cancel_subscription_url')) {
    function tmw_get_cancel_subscription_url() {
        return tmw_get_manage_subscription_url();
    }
}