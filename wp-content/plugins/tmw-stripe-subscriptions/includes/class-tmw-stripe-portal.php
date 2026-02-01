<?php
/**
 * Stripe Customer Portal Handler
 *
 * Creates Stripe Billing Portal sessions for subscription management.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Portal {

    /**
     * Create portal session via AJAX
     */
    public function create_portal_session() {
        // Verify nonce
        if (!check_ajax_referer('tmw_stripe_portal', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'tmw-stripe-subscriptions')));
        }

        // Check user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'tmw-stripe-subscriptions')));
        }

        $user_id = get_current_user_id();

        // Get customer ID
        $adapter = tmw_stripe_adapter();
        $customer_id = $adapter->get_stripe_customer_id($user_id);

        if (!$customer_id) {
            wp_send_json_error(array('message' => __('No subscription found.', 'tmw-stripe-subscriptions')));
        }

        // Get return URL
        $settings = TMW_Stripe_API::get_settings();
        $return_url = home_url($settings['success_url'] ?? '/my-profile/');

        // Allow filtering return URL
        $return_url = apply_filters('tmw_stripe_portal_return_url', $return_url, $user_id);

        // Create portal session
        $session = TMW_Stripe_API::create_portal_session(array(
            'customer'   => $customer_id,
            'return_url' => $return_url,
        ));

        if (is_wp_error($session)) {
            wp_send_json_error(array('message' => $session->get_error_message()));
        }

        // Return portal URL for redirect
        wp_send_json_success(array(
            'url' => $session['url'],
        ));
    }

    /**
     * Get portal URL for template use
     *
     * @return string
     */
    public static function get_portal_url() {
        if (!is_user_logged_in()) {
            return '#';
        }

        return add_query_arg(array(
            'action' => 'tmw_stripe_portal',
            'nonce'  => wp_create_nonce('tmw_stripe_portal'),
        ), admin_url('admin-ajax.php'));
    }
}

// =========================================================================
// GLOBAL HELPER FUNCTIONS
// =========================================================================

/**
 * Get subscription management URL
 * Works with any membership plugin based on settings
 *
 * @return string
 */
function tmw_get_manage_subscription_url() {
    $plugin = function_exists('tmw_get_setting') 
        ? tmw_get_setting('membership_plugin', 'simple-membership')
        : get_option('tmw_settings')['membership_plugin'] ?? 'simple-membership';

    switch ($plugin) {
        case 'stripe':
            return TMW_Stripe_Portal::get_portal_url();

        case 'simple-membership':
        default:
            if (function_exists('tmw_get_swpm_profile_url')) {
                return tmw_get_swpm_profile_url();
            }
            return home_url('/my-profile/');
    }
}

/**
 * Get cancel subscription URL
 * For Stripe, this redirects to portal (cancel is handled there)
 *
 * @return string
 */
function tmw_get_cancel_subscription_url() {
    return tmw_get_manage_subscription_url();
}

/**
 * Render subscription management button/link
 *
 * @param array $args
 */
function tmw_subscription_manage_button($args = array()) {
    $defaults = array(
        'class' => 'tmw-btn tmw-btn-secondary',
        'text'  => __('Manage Subscription', 'tmw-stripe-subscriptions'),
        'icon'  => 'fas fa-cog',
    );

    $args = wp_parse_args($args, $defaults);

    $plugin = function_exists('tmw_get_setting') 
        ? tmw_get_setting('membership_plugin', 'simple-membership')
        : get_option('tmw_settings')['membership_plugin'] ?? 'simple-membership';

    if ($plugin === 'stripe') {
        // Use JavaScript handler for Stripe portal
        printf(
            '<button type="button" class="%s tmw-stripe-portal-btn" data-nonce="%s">
                <i class="%s"></i>
                <span>%s</span>
            </button>',
            esc_attr($args['class']),
            esc_attr(wp_create_nonce('tmw_stripe_portal')),
            esc_attr($args['icon']),
            esc_html($args['text'])
        );
    } else {
        // Regular link for other plugins
        printf(
            '<a href="%s" class="%s">
                <i class="%s"></i>
                <span>%s</span>
            </a>',
            esc_url(tmw_get_manage_subscription_url()),
            esc_attr($args['class']),
            esc_attr($args['icon']),
            esc_html($args['text'])
        );
    }
}

/**
 * Render subscription cancel button/link
 *
 * @param array $args
 */
function tmw_subscription_cancel_button($args = array()) {
    $defaults = array(
        'class' => 'tmw-btn tmw-btn-danger tmw-btn-small',
        'text'  => __('Cancel Subscription', 'tmw-stripe-subscriptions'),
        'icon'  => 'fas fa-times-circle',
    );

    $args = wp_parse_args($args, $defaults);

    // For Stripe, cancel is done through portal
    tmw_subscription_manage_button(array_merge($args, array(
        'text' => $args['text'],
    )));
}
