<?php
/**
 * Admin Functionality
 *
 * Handles admin menu, scripts, and general admin functionality.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Admin {

    /**
     * Menu slug
     */
    const MENU_SLUG = 'tmw-stripe';

    /**
     * Add Stripe menu and submenus
     */
    public function add_admin_menu() {
        // Main menu - Stripe Subscriptions
        add_menu_page(
            __('Stripe Subscriptions', 'tmw-stripe-subscriptions'),
            __('Stripe Subs', 'tmw-stripe-subscriptions'),
            'manage_options',
            self::MENU_SLUG,
            array($this, 'render_settings_page'),
            'dashicons-money-alt',
            30 // Position after Comments
        );

        // Submenu - Settings (same as parent)
        add_submenu_page(
            self::MENU_SLUG,
            __('Stripe Settings', 'tmw-stripe-subscriptions'),
            __('Settings', 'tmw-stripe-subscriptions'),
            'manage_options',
            self::MENU_SLUG, // Same slug as parent to replace default
            array($this, 'render_settings_page')
        );

        // Submenu - Subscribers
        add_submenu_page(
            self::MENU_SLUG,
            __('Subscribers', 'tmw-stripe-subscriptions'),
            __('Subscribers', 'tmw-stripe-subscriptions'),
            'manage_options',
            'tmw-stripe-subscribers',
            array($this, 'render_subscribers_page')
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        $settings = TMW_Stripe_API::get_settings();
        $mode = $settings['mode'] ?? 'test';
        $is_configured = TMW_Stripe_API::is_configured();

        include TMW_STRIPE_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }

    /**
     * Render subscribers page
     */
    public function render_subscribers_page() {
        $subscribers = new TMW_Stripe_Subscribers();
        $subscribers->prepare_items();

        include TMW_STRIPE_PLUGIN_DIR . 'admin/partials/subscribers-page.php';
    }

    /**
     * Get Stripe dashboard URL
     *
     * @return string
     */
    public function get_stripe_dashboard_url() {
        $mode = TMW_Stripe_API::get_mode();
        return $mode === 'live' 
            ? 'https://dashboard.stripe.com/subscriptions' 
            : 'https://dashboard.stripe.com/test/subscriptions';
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook
     */
    public function enqueue_scripts($hook) {
        // Only load on our pages
        $allowed_pages = array(
            'toplevel_page_tmw-stripe',
            'stripe-subs_page_tmw-stripe-subscribers',
            // Also load on TMW settings for tier field integration
            'toplevel_page_tmw-settings',
        );

        if (!in_array($hook, $allowed_pages, true)) {
            return;
        }

        wp_enqueue_style(
            'tmw-stripe-admin',
            TMW_STRIPE_PLUGIN_URL . 'admin/css/tmw-stripe-admin.css',
            array(),
            TMW_STRIPE_VERSION
        );

        wp_enqueue_script(
            'tmw-stripe-admin',
            TMW_STRIPE_PLUGIN_URL . 'admin/js/tmw-stripe-admin.js',
            array('jquery'),
            TMW_STRIPE_VERSION,
            true
        );

        wp_localize_script('tmw-stripe-admin', 'tmwStripeAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('tmw_stripe_admin'),
            'strings' => array(
                'saving'         => __('Saving...', 'tmw-stripe-subscriptions'),
                'saved'          => __('Settings saved!', 'tmw-stripe-subscriptions'),
                'error'          => __('Error saving settings.', 'tmw-stripe-subscriptions'),
                'testing'        => __('Testing connection...', 'tmw-stripe-subscriptions'),
                'connected'      => __('Connected!', 'tmw-stripe-subscriptions'),
                'connectionFail' => __('Connection failed.', 'tmw-stripe-subscriptions'),
                'confirmDelete'  => __('Are you sure?', 'tmw-stripe-subscriptions'),
            ),
        ));
    }

    /**
     * Add settings link on plugins page
     *
     * @param array $links
     * @return array
     */
    public function add_plugin_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=' . self::MENU_SLUG),
            __('Settings', 'tmw-stripe-subscriptions')
        );

        array_unshift($links, $settings_link);

        return $links;
    }
}
