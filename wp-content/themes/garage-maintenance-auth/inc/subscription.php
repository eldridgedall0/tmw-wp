<?php
/**
 * Subscription Tier Logic
 *
 * Handles subscription tier detection and limit enforcement.
 * Works with the membership adapter pattern for plugin flexibility.
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
 * Get the subscription tier for a user
 *
 * @param int $user_id User ID (defaults to current user)
 * @return string Tier name: 'free', 'paid', 'fleet', or 'none'
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
    return $tier ? $tier : tmw_get_level_mapping('fallback_tier', 'free');
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

    if (!$user_id) {
        return null;
    }

    $adapter = tmw_get_membership_adapter();
    
    if ($adapter) {
        return $adapter->get_expiry_date($user_id);
    }

    return get_user_meta($user_id, 'tmw_subscription_expiry', true) ?: null;
}

// =============================================================================
// GET TIER LIMITS
// =============================================================================

/**
 * Get all limits for a specific tier
 *
 * @param string $tier Tier name (free, paid, fleet)
 * @return array Limits array
 */
function tmw_get_tier_limits($tier = 'free') {
    $settings = get_option('tmw_subscription_settings', array());
    $defaults = tmw_get_default_subscription_settings();
    $settings = wp_parse_args($settings, $defaults);

    $valid_tiers = array('free', 'paid', 'fleet');
    if (!in_array($tier, $valid_tiers)) {
        $tier = 'free';
    }

    return array(
        'max_vehicles'          => (int) $settings[$tier . '_max_vehicles'],
        'max_entries'           => (int) $settings[$tier . '_max_entries'],
        'attachments_per_entry' => (int) $settings[$tier . '_attachments_per_entry'],
        'recalls_enabled'       => (bool) $settings[$tier . '_recalls_enabled'],
        'export_level'          => $settings[$tier . '_export_level'],
        'max_templates'         => (int) $settings[$tier . '_max_templates'],
        'vehicle_photos'        => (bool) $settings[$tier . '_vehicle_photos'],
        'api_access'            => (bool) $settings[$tier . '_api_access'],
        'team_members'          => (int) $settings[$tier . '_team_members'],
    );
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
 * @param string $feature Feature key
 * @param int $user_id Optional user ID
 * @return bool|int Boolean for toggles, int for limits (-1 = unlimited)
 */
function tmw_user_can($feature, $user_id = 0) {
    $limits = tmw_get_user_limits($user_id);
    
    if (isset($limits[$feature])) {
        return $limits[$feature];
    }

    return false;
}

// =============================================================================
// TIER DISPLAY HELPERS
// =============================================================================

/**
 * Get tier display name
 *
 * @param string $tier Tier slug
 * @return string Display name
 */
function tmw_get_tier_name($tier) {
    $names = array(
        'free'  => __('Free', 'flavor-starter-flavor'),
        'paid'  => __('Paid', 'flavor-starter-flavor'),
        'fleet' => __('Fleet', 'flavor-starter-flavor'),
        'none'  => __('No Subscription', 'flavor-starter-flavor'),
    );

    return isset($names[$tier]) ? $names[$tier] : ucfirst($tier);
}

/**
 * Get tier badge HTML
 *
 * @param string $tier Tier slug
 * @return string HTML badge
 */
function tmw_get_tier_badge($tier) {
    $name = tmw_get_tier_name($tier);
    $class = 'tmw-badge tmw-badge-' . esc_attr($tier);
    
    return '<span class="' . $class . '">' . esc_html($name) . '</span>';
}

/**
 * Get upgrade URL based on current tier
 *
 * @param string $current_tier Current tier
 * @return string Upgrade URL
 */
function tmw_get_upgrade_url($current_tier = null) {
    if ($current_tier === null) {
        $current_tier = tmw_get_user_tier();
    }

    $pricing_page = tmw_get_page_url('pricing');
    
    if ($current_tier === 'free') {
        return add_query_arg('upgrade', 'paid', $pricing_page);
    } elseif ($current_tier === 'paid') {
        return add_query_arg('upgrade', 'fleet', $pricing_page);
    }

    return $pricing_page;
}

// =============================================================================
// SUBSCRIPTION DATA FOR REST API / APP
// =============================================================================

/**
 * Get complete subscription data for a user (used by REST API)
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
            'tier'      => 'none',
            'active'    => false,
            'limits'    => tmw_get_tier_limits('free'),
            'expires'   => null,
        );
    }

    $tier = tmw_get_user_tier($user_id);
    
    return array(
        'tier'      => $tier,
        'tier_name' => tmw_get_tier_name($tier),
        'active'    => tmw_user_has_active_subscription($user_id),
        'limits'    => tmw_get_tier_limits($tier),
        'expires'   => tmw_get_subscription_expiry($user_id),
        'upgrade_url' => tmw_get_upgrade_url($tier),
    );
}

// =============================================================================
// FEATURE COMPARISON DATA
// =============================================================================

/**
 * Get feature comparison data for pricing page
 *
 * @return array Feature comparison
 */
function tmw_get_feature_comparison() {
    $tiers = array('free', 'paid', 'fleet');
    $comparison = array();

    $features = array(
        'max_vehicles' => array(
            'label' => __('Vehicles', 'flavor-starter-flavor'),
            'type'  => 'number',
        ),
        'max_entries' => array(
            'label' => __('Service Entries', 'flavor-starter-flavor'),
            'type'  => 'number',
        ),
        'attachments_per_entry' => array(
            'label' => __('Attachments per Entry', 'flavor-starter-flavor'),
            'type'  => 'number',
        ),
        'recalls_enabled' => array(
            'label' => __('Recall Alerts', 'flavor-starter-flavor'),
            'type'  => 'boolean',
        ),
        'export_level' => array(
            'label' => __('Export Reports', 'flavor-starter-flavor'),
            'type'  => 'export',
        ),
        'max_templates' => array(
            'label' => __('Service Templates', 'flavor-starter-flavor'),
            'type'  => 'number',
        ),
        'vehicle_photos' => array(
            'label' => __('Vehicle Photos', 'flavor-starter-flavor'),
            'type'  => 'boolean',
        ),
        'api_access' => array(
            'label' => __('API Access', 'flavor-starter-flavor'),
            'type'  => 'boolean',
        ),
        'team_members' => array(
            'label' => __('Team Members', 'flavor-starter-flavor'),
            'type'  => 'team',
        ),
    );

    foreach ($features as $key => $feature) {
        $row = array(
            'key'   => $key,
            'label' => $feature['label'],
            'type'  => $feature['type'],
            'values' => array(),
        );

        foreach ($tiers as $tier) {
            $limits = tmw_get_tier_limits($tier);
            $value = isset($limits[$key]) ? $limits[$key] : null;
            
            // Format the value for display
            if ($feature['type'] === 'number') {
                $row['values'][$tier] = $value === -1 ? __('Unlimited', 'flavor-starter-flavor') : $value;
            } elseif ($feature['type'] === 'boolean') {
                $row['values'][$tier] = $value;
            } elseif ($feature['type'] === 'export') {
                $labels = array(
                    'none'     => __('—', 'flavor-starter-flavor'),
                    'basic'    => __('CSV / PDF', 'flavor-starter-flavor'),
                    'advanced' => __('CSV / PDF + Bulk', 'flavor-starter-flavor'),
                );
                $row['values'][$tier] = isset($labels[$value]) ? $labels[$value] : $value;
            } elseif ($feature['type'] === 'team') {
                if ($tier === 'fleet' && $value > 0) {
                    $row['values'][$tier] = sprintf(__('Up to %d (Coming Soon)', 'flavor-starter-flavor'), $value);
                } else {
                    $row['values'][$tier] = $value > 0 ? $value : __('—', 'flavor-starter-flavor');
                }
            }
        }

        $comparison[] = $row;
    }

    return $comparison;
}

// =============================================================================
// HOOKS FOR SUBSCRIPTION CHANGES
// =============================================================================

/**
 * Action hook when subscription tier changes
 */
function tmw_subscription_changed($user_id, $old_tier, $new_tier) {
    do_action('tmw_subscription_changed', $user_id, $old_tier, $new_tier);
    
    // Update user meta with tier
    update_user_meta($user_id, 'tmw_subscription_tier', $new_tier);
    update_user_meta($user_id, 'tmw_tier_changed', current_time('mysql'));
}
