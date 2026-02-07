<?php
/**
 * TMW Stripe Subscriptions
 *
 * Native Stripe subscription management for TrackMyWrench/GarageMinder.
 * Replaces Simple Membership with direct Stripe integration using
 * Checkout Sessions, Customer Portal, and Webhooks.
 *
 * @package     TMW_Stripe_Subscriptions
 * @author      TrackMyWrench
 * @copyright   2026 TrackMyWrench
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       TMW Stripe Subscriptions
 * Plugin URI:        https://trackmywrench.com
 * Description:       Native Stripe subscription management for TrackMyWrench. Handles checkout, billing, upgrades, downgrades, and cancellations via Stripe.
 * Version:           1.0.0
 * Author:            TrackMyWrench
 * Author URI:        https://trackmywrench.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       tmw-stripe-subscriptions
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('TMW_STRIPE_VERSION', '1.0.0');
define('TMW_STRIPE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TMW_STRIPE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TMW_STRIPE_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('TMW_STRIPE_DB_VERSION', '1.0.0');

/**
 * Activation hook
 */
register_activation_hook(__FILE__, function() {
    require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-activator.php';
    TMW_Stripe_Activator::activate();
});

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-deactivator.php';
    TMW_Stripe_Deactivator::deactivate();
});

/**
 * Load all required files
 */
function tmw_stripe_load_files() {
    static $loaded = false;
    
    if ($loaded) {
        return;
    }
    
    // Core classes
    require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-api.php';
    require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-adapter.php';
    require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-webhook.php';
    require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-checkout.php';
    require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-portal.php';

    // Admin classes
    require_once TMW_STRIPE_PLUGIN_DIR . 'admin/class-tmw-stripe-admin.php';
    require_once TMW_STRIPE_PLUGIN_DIR . 'admin/class-tmw-stripe-subscribers.php';

    // Public classes
    require_once TMW_STRIPE_PLUGIN_DIR . 'public/class-tmw-stripe-public.php';
    
    $loaded = true;
}

/**
 * Initialize plugin - runs on plugins_loaded
 */
function tmw_stripe_init() {
    // Load all files first
    tmw_stripe_load_files();
    
    // Initialize admin
    if (is_admin()) {
        tmw_stripe_admin_init();
    }
    
    // Initialize public/frontend
    tmw_stripe_public_init();
}
add_action('plugins_loaded', 'tmw_stripe_init');

/**
 * Initialize admin functionality
 */
function tmw_stripe_admin_init() {
    $admin = new TMW_Stripe_Admin();

    // Register admin menu
    add_action('admin_menu', array($admin, 'add_admin_menu'));
    
    // Enqueue admin scripts
    add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));

    // Plugin action links on plugins page
    add_filter('plugin_action_links_' . TMW_STRIPE_PLUGIN_BASENAME, array($admin, 'add_plugin_action_links'));

    // AJAX handlers for plugin settings page
    add_action('wp_ajax_tmw_save_stripe_settings', 'tmw_stripe_ajax_save_settings');
    add_action('wp_ajax_tmw_test_stripe_connection', 'tmw_stripe_ajax_test_connection');
}

/**
 * Initialize public/frontend functionality
 */
function tmw_stripe_public_init() {
    $public = new TMW_Stripe_Public();
    $checkout = new TMW_Stripe_Checkout();
    $portal = new TMW_Stripe_Portal();
    $webhook = new TMW_Stripe_Webhook();

    // Frontend scripts
    add_action('wp_enqueue_scripts', array($public, 'enqueue_scripts'));

    // AJAX handlers for checkout and portal
    add_action('wp_ajax_tmw_stripe_checkout', array($checkout, 'create_checkout_session'));
    add_action('wp_ajax_tmw_stripe_portal', array($portal, 'create_portal_session'));

    // User registration hook
    add_action('user_register', array($public, 'on_user_register'));

    // Login sync
    add_action('wp_login', array($public, 'sync_subscription_on_login'), 10, 2);

    // REST API webhook endpoint
    add_action('rest_api_init', array($webhook, 'register_endpoint'));
}

/**
 * AJAX handler for saving Stripe settings
 */
function tmw_stripe_ajax_save_settings() {
    check_ajax_referer('tmw_stripe_admin', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $settings = array(
        'mode'                 => sanitize_text_field($_POST['mode'] ?? 'test'),
        'test_publishable_key' => sanitize_text_field($_POST['test_publishable_key'] ?? ''),
        'test_secret_key'      => sanitize_text_field($_POST['test_secret_key'] ?? ''),
        'test_webhook_secret'  => sanitize_text_field($_POST['test_webhook_secret'] ?? ''),
        'live_publishable_key' => sanitize_text_field($_POST['live_publishable_key'] ?? ''),
        'live_secret_key'      => sanitize_text_field($_POST['live_secret_key'] ?? ''),
        'live_webhook_secret'  => sanitize_text_field($_POST['live_webhook_secret'] ?? ''),
        'success_url'          => esc_url_raw($_POST['success_url'] ?? ''),
        'cancel_url'           => esc_url_raw($_POST['cancel_url'] ?? ''),
    );

    update_option('tmw_stripe_settings', $settings);

    wp_send_json_success(array('message' => 'Settings saved successfully'));
}

/**
 * AJAX handler for testing Stripe connection
 */
function tmw_stripe_ajax_test_connection() {
    check_ajax_referer('tmw_stripe_admin', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $api = new TMW_Stripe_API();
    
    if (!$api->get_secret_key()) {
        wp_send_json_error('No API key configured for current mode');
    }

    $result = $api->test_connection();

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result['error']);
    }
}

/**
 * Register the Stripe adapter with the theme (if theme supports it)
 */
add_filter('tmw_membership_adapters', function($adapters) {
    if (!is_array($adapters)) {
        $adapters = array();
    }
    $adapters['stripe'] = 'TMW_Stripe_Adapter';
    return $adapters;
});

/**
 * Helper function to get Stripe settings
 * 
 * @return array
 */
function tmw_stripe_get_settings() {
    return get_option('tmw_stripe_settings', array());
}

/**
 * Helper function to check if Stripe is configured
 * 
 * @return bool
 */
function tmw_stripe_is_configured() {
    if (!class_exists('TMW_Stripe_API')) {
        tmw_stripe_load_files();
    }
    return TMW_Stripe_API::is_configured();
}
