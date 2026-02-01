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
function tmw_stripe_activate() {
    require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-activator.php';
    TMW_Stripe_Activator::activate();
}
register_activation_hook(__FILE__, 'tmw_stripe_activate');

/**
 * Deactivation hook
 */
function tmw_stripe_deactivate() {
    require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-deactivator.php';
    TMW_Stripe_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'tmw_stripe_deactivate');

/**
 * Main plugin class
 */
final class TMW_Stripe_Subscriptions {

    /**
     * Plugin instance
     * @var TMW_Stripe_Subscriptions
     */
    private static $instance = null;

    /**
     * Loader instance
     * @var TMW_Stripe_Loader
     */
    private $loader;

    /**
     * Get plugin instance
     * @return TMW_Stripe_Subscriptions
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_webhook_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core
        require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-loader.php';
        require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-api.php';
        require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-adapter.php';
        require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-webhook.php';
        require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-checkout.php';
        require_once TMW_STRIPE_PLUGIN_DIR . 'includes/class-tmw-stripe-portal.php';

        // Admin
        require_once TMW_STRIPE_PLUGIN_DIR . 'admin/class-tmw-stripe-admin.php';
        require_once TMW_STRIPE_PLUGIN_DIR . 'admin/class-tmw-stripe-settings.php';
        require_once TMW_STRIPE_PLUGIN_DIR . 'admin/class-tmw-stripe-subscribers.php';

        // Public
        require_once TMW_STRIPE_PLUGIN_DIR . 'public/class-tmw-stripe-public.php';

        $this->loader = new TMW_Stripe_Loader();
    }

    /**
     * Set plugin text domain
     */
    private function set_locale() {
        $this->loader->add_action('plugins_loaded', $this, 'load_textdomain');
    }

    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'tmw-stripe-subscriptions',
            false,
            dirname(TMW_STRIPE_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Register admin hooks
     */
    private function define_admin_hooks() {
        $admin = new TMW_Stripe_Admin();
        $settings = new TMW_Stripe_Settings();
        $subscribers = new TMW_Stripe_Subscribers();

        // Admin menu and scripts
        $this->loader->add_action('admin_menu', $admin, 'add_subscribers_menu');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');

        // Settings tab hooks
        $this->loader->add_filter('tmw_admin_tabs', $settings, 'add_stripe_tab');
        $this->loader->add_action('tmw_render_stripe_tab', $settings, 'render_tab');
        $this->loader->add_action('wp_ajax_tmw_save_stripe_settings', $settings, 'ajax_save_settings');
        $this->loader->add_action('wp_ajax_tmw_test_stripe_connection', $settings, 'ajax_test_connection');

        // Tier modal Stripe fields
        $this->loader->add_action('tmw_tier_modal_fields', $settings, 'render_tier_stripe_fields');
        $this->loader->add_action('admin_footer', $settings, 'render_tier_fields_script');

        // Subscribers page
        $this->loader->add_action('admin_init', $subscribers, 'process_actions');
    }

    /**
     * Register public hooks
     */
    private function define_public_hooks() {
        $public = new TMW_Stripe_Public();
        $checkout = new TMW_Stripe_Checkout();
        $portal = new TMW_Stripe_Portal();

        // Frontend scripts
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');

        // AJAX handlers
        $this->loader->add_action('wp_ajax_tmw_stripe_checkout', $checkout, 'create_checkout_session');
        $this->loader->add_action('wp_ajax_tmw_stripe_portal', $portal, 'create_portal_session');

        // User registration - assign free tier
        $this->loader->add_action('user_register', $public, 'on_user_register');

        // Login sync
        $this->loader->add_action('wp_login', $public, 'sync_subscription_on_login', 10, 2);
    }

    /**
     * Register webhook hooks
     */
    private function define_webhook_hooks() {
        $webhook = new TMW_Stripe_Webhook();

        // REST API endpoint
        $this->loader->add_action('rest_api_init', $webhook, 'register_endpoint');
    }

    /**
     * Run the plugin
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Get loader instance
     * @return TMW_Stripe_Loader
     */
    public function get_loader() {
        return $this->loader;
    }
}

/**
 * Initialize plugin
 */
function tmw_stripe_subscriptions() {
    return TMW_Stripe_Subscriptions::instance();
}

// Start the plugin
add_action('plugins_loaded', function() {
    // Check if theme functions exist (theme must be active)
    if (function_exists('tmw_get_tiers')) {
        tmw_stripe_subscriptions()->run();
    }
}, 20);

/**
 * Register the Stripe adapter with the theme
 */
add_filter('tmw_membership_adapters', function($adapters) {
    $adapters['stripe'] = 'TMW_Stripe_Adapter';
    return $adapters;
});
