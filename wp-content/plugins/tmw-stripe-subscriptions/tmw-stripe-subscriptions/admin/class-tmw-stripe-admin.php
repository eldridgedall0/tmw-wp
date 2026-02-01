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
     * Add subscribers submenu under TrackMyWrench
     */
    public function add_subscribers_menu() {
        add_submenu_page(
            'tmw-settings', // Parent slug (TMW Settings page)
            __('Subscribers', 'tmw-stripe-subscriptions'),
            __('Subscribers', 'tmw-stripe-subscriptions'),
            'manage_options',
            'tmw-subscribers',
            array($this, 'render_subscribers_page')
        );
    }

    /**
     * Render subscribers page
     */
    public function render_subscribers_page() {
        // Include the subscribers list table class
        $subscribers = new TMW_Stripe_Subscribers();
        $subscribers->prepare_items();

        include TMW_STRIPE_PLUGIN_DIR . 'admin/partials/subscribers-page.php';
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook
     */
    public function enqueue_scripts($hook) {
        // Only load on our pages or TMW settings
        $allowed_pages = array(
            'trackmywrench_page_tmw-subscribers',
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
}
