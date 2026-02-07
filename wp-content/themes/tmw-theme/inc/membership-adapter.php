<?php
/**
 * Membership Adapter Pattern
 *
 * Abstracts membership plugin interactions for easy switching.
 * Supports: Simple Membership, WooCommerce Subscriptions, 
 * Paid Memberships Pro, MemberPress, and manual user meta.
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// ADAPTER INTERFACE
// =============================================================================

interface TMW_Membership_Adapter_Interface {
    /**
     * Get user's subscription tier
     * @param int $user_id
     * @return string 'free', 'paid', 'fleet', or 'none'
     */
    public function get_user_tier($user_id);

    /**
     * Check if subscription is active
     * @param int $user_id
     * @return bool
     */
    public function is_active($user_id);

    /**
     * Get subscription expiry date
     * @param int $user_id
     * @return string|null Date string or null
     */
    public function get_expiry_date($user_id);

    /**
     * Get user's membership level ID (plugin-specific)
     * @param int $user_id
     * @return int|string|null
     */
    public function get_level_id($user_id);

    /**
     * Check if the plugin is active
     * @return bool
     */
    public function is_plugin_active();
}

// =============================================================================
// ADAPTER FACTORY
// =============================================================================

/**
 * Get the appropriate membership adapter based on settings
 *
 * @return TMW_Membership_Adapter_Interface|null
 */
function tmw_get_membership_adapter() {
    static $adapter = null;

    if ($adapter !== null) {
        return $adapter;
    }

    $plugin = tmw_get_setting('membership_plugin', 'simple-membership');

    switch ($plugin) {
        case 'simple-membership':
            require_once TMW_THEME_DIR . '/inc/adapters/simple-membership.php';
            $adapter = new TMW_Simple_Membership_Adapter();
            break;

        case 'woocommerce':
            require_once TMW_THEME_DIR . '/inc/adapters/woocommerce.php';
            $adapter = new TMW_WooCommerce_Adapter();
            break;

        case 'pmpro':
            require_once TMW_THEME_DIR . '/inc/adapters/pmpro.php';
            $adapter = new TMW_PMPro_Adapter();
            break;

        case 'memberpress':
            require_once TMW_THEME_DIR . '/inc/adapters/memberpress.php';
            $adapter = new TMW_MemberPress_Adapter();
            break;

        case 'stripe':
            // Load Stripe adapter from plugin if active
            if (class_exists('TMW_Stripe_Adapter')) {
                $adapter = new TMW_Stripe_Adapter();
            } else {
                // Fallback to user-meta if plugin not active
                require_once TMW_THEME_DIR . '/inc/adapters/user-meta.php';
                $adapter = new TMW_User_Meta_Adapter();
            }
            break;

        case 'user-meta':
        default:
            require_once TMW_THEME_DIR . '/inc/adapters/user-meta.php';
            $adapter = new TMW_User_Meta_Adapter();
            break;
    }

    // Verify plugin is active
    if (!$adapter->is_plugin_active()) {
        // Fallback to user meta adapter
        require_once TMW_THEME_DIR . '/inc/adapters/user-meta.php';
        $adapter = new TMW_User_Meta_Adapter();
    }

    return $adapter;
}

// =============================================================================
// HELPER: MAP LEVEL ID TO TIER
// =============================================================================

/**
 * Map tier name to level ID (reverse lookup)
 *
 * @param string $tier Tier name
 * @return int Level ID
 */
function tmw_map_tier_to_level($tier) {
    $mapping = get_option('tmw_level_mapping', array());
    
    switch ($tier) {
        case 'free':
            return isset($mapping['free_level_id']) ? (int) $mapping['free_level_id'] : 1;
        case 'paid':
            return isset($mapping['paid_level_id']) ? (int) $mapping['paid_level_id'] : 2;
        case 'fleet':
            return isset($mapping['fleet_level_id']) ? (int) $mapping['fleet_level_id'] : 3;
        default:
            return isset($mapping['free_level_id']) ? (int) $mapping['free_level_id'] : 1;
    }
}

// =============================================================================
// DETECT ACTIVE MEMBERSHIP PLUGINS
// =============================================================================

/**
 * Check which membership plugins are installed and active
 *
 * @return array Array of active plugin slugs
 */
function tmw_detect_membership_plugins() {
    $active = array();

    // Simple Membership
    if (class_exists('SimpleWpMembership') || defined('SIMPLE_WP_MEMBERSHIP_VER')) {
        $active[] = 'simple-membership';
    }

    // WooCommerce Subscriptions
    if (class_exists('WC_Subscriptions') || function_exists('wcs_user_has_subscription')) {
        $active[] = 'woocommerce';
    }

    // Paid Memberships Pro
    if (defined('PMPRO_VERSION') || function_exists('pmpro_hasMembershipLevel')) {
        $active[] = 'pmpro';
    }

    // MemberPress
    if (defined('MEPR_VERSION') || class_exists('MeprUser')) {
        $active[] = 'memberpress';
    }
	
	// Stripe (TMW Stripe Subscriptions plugin)
	if (class_exists('TMW_Stripe_Subscriptions') || defined('TMW_STRIPE_VERSION')) {
    	$active[] = 'stripe';
	}

    // Always available
    $active[] = 'user-meta';

    return $active;
}