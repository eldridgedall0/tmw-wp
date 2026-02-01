<?php
/**
 * Stripe Checkout Handler
 *
 * Creates Stripe Checkout Sessions for subscription purchases.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Checkout {

    /**
     * Create checkout session via AJAX
     */
    public function create_checkout_session() {
        // Verify nonce
        if (!check_ajax_referer('tmw_stripe_checkout', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'tmw-stripe-subscriptions')));
        }

        // Check user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'tmw-stripe-subscriptions')));
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        // Get tier and period from request
        $tier_slug = isset($_POST['tier']) ? sanitize_key($_POST['tier']) : '';
        $period = isset($_POST['period']) ? sanitize_key($_POST['period']) : 'monthly';

        if (empty($tier_slug)) {
            wp_send_json_error(array('message' => __('Invalid tier selected.', 'tmw-stripe-subscriptions')));
        }

        // Validate tier exists and has Stripe price
        if (!function_exists('tmw_get_tier')) {
            wp_send_json_error(array('message' => __('Theme not properly configured.', 'tmw-stripe-subscriptions')));
        }

        $tier = tmw_get_tier($tier_slug);

        if (!$tier) {
            wp_send_json_error(array('message' => __('Invalid tier.', 'tmw-stripe-subscriptions')));
        }

        // Don't allow checkout for free tiers
        if (!empty($tier['is_free'])) {
            wp_send_json_error(array('message' => __('Cannot checkout for free tier.', 'tmw-stripe-subscriptions')));
        }

        // Get price ID
        $price_key = 'stripe_price_id_' . $period;
        $price_id = $tier[$price_key] ?? '';

        if (empty($price_id)) {
            wp_send_json_error(array('message' => __('No price configured for this tier.', 'tmw-stripe-subscriptions')));
        }

        // Get settings
        $settings = TMW_Stripe_API::get_settings();

        // Build success and cancel URLs
        $success_url = home_url($settings['success_url'] ?? '/my-profile/');
        $cancel_url = home_url($settings['cancel_url'] ?? '/pricing/');

        // Add session ID to success URL for verification
        $success_url = add_query_arg(array(
            'checkout'   => 'success',
            'session_id' => '{CHECKOUT_SESSION_ID}',
        ), $success_url);

        $cancel_url = add_query_arg('checkout', 'canceled', $cancel_url);

        // Get or create Stripe customer
        $adapter = tmw_stripe_adapter();
        $customer_id = $adapter->get_stripe_customer_id($user_id);

        if (!$customer_id) {
            // Create new customer
            $customer_result = TMW_Stripe_API::create_customer(array(
                'email'    => $user->user_email,
                'name'     => $user->display_name,
                'metadata' => array(
                    'user_id'    => $user_id,
                    'user_login' => $user->user_login,
                ),
            ));

            if (is_wp_error($customer_result)) {
                wp_send_json_error(array('message' => $customer_result->get_error_message()));
            }

            $customer_id = $customer_result['id'];

            // Save customer ID
            update_user_meta($user_id, 'tmw_stripe_customer_id', $customer_id);

            // Also save to subscriptions table if exists
            global $wpdb;
            $table = $wpdb->prefix . 'tmw_stripe_subscriptions';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $table WHERE user_id = %d",
                    $user_id
                ));

                if ($existing) {
                    $wpdb->update(
                        $table,
                        array('stripe_customer_id' => $customer_id),
                        array('user_id' => $user_id),
                        array('%s'),
                        array('%d')
                    );
                } else {
                    $wpdb->insert($table, array(
                        'user_id'            => $user_id,
                        'stripe_customer_id' => $customer_id,
                        'tier_slug'          => 'free',
                        'status'             => 'none',
                    ), array('%d', '%s', '%s', '%s'));
                }
            }
        }

        // Build checkout session params
        $params = array(
            'customer'             => $customer_id,
            'mode'                 => 'subscription',
            'success_url'          => $success_url,
            'cancel_url'           => $cancel_url,
            'allow_promotion_codes' => 'true',
            'billing_address_collection' => 'auto',
            'line_items' => array(
                array(
                    'price'    => $price_id,
                    'quantity' => 1,
                ),
            ),
            'metadata' => array(
                'user_id'   => $user_id,
                'tier_slug' => $tier_slug,
                'period'    => $period,
            ),
            'subscription_data' => array(
                'metadata' => array(
                    'user_id'   => $user_id,
                    'tier_slug' => $tier_slug,
                ),
            ),
        );

        // Add trial period if enabled and not used
        $trial_enabled = !empty($settings['trial_enabled']);
        $trial_days = isset($settings['trial_days']) ? (int) $settings['trial_days'] : 7;

        if ($trial_enabled && $trial_days > 0 && !$adapter->has_used_trial($user_id)) {
            $params['subscription_data']['trial_period_days'] = $trial_days;
        }

        // Allow filtering
        $params = apply_filters('tmw_stripe_checkout_params', $params, $user_id, $tier_slug, $period);

        // Create checkout session
        $session = TMW_Stripe_API::create_checkout_session($params);

        if (is_wp_error($session)) {
            wp_send_json_error(array('message' => $session->get_error_message()));
        }

        // Return session URL for redirect
        wp_send_json_success(array(
            'url'        => $session['url'],
            'session_id' => $session['id'],
        ));
    }

    /**
     * Get checkout URL for a tier (for template use)
     *
     * @param string $tier_slug
     * @param string $period
     * @return string
     */
    public static function get_checkout_url($tier_slug, $period = 'monthly') {
        if (!is_user_logged_in()) {
            // Redirect to login first
            $login_url = function_exists('tmw_get_page_url') 
                ? tmw_get_page_url('login') 
                : wp_login_url();
            
            return add_query_arg(array(
                'redirect_to' => urlencode(add_query_arg(array(
                    'subscribe' => $tier_slug,
                    'period'    => $period,
                ), home_url('/pricing/'))),
            ), $login_url);
        }

        return add_query_arg(array(
            'action' => 'tmw_stripe_checkout',
            'tier'   => $tier_slug,
            'period' => $period,
            'nonce'  => wp_create_nonce('tmw_stripe_checkout'),
        ), admin_url('admin-ajax.php'));
    }
}

// =========================================================================
// GLOBAL HELPER FUNCTIONS
// =========================================================================

/**
 * Get subscribe/checkout URL for a tier
 * Works with any membership plugin based on settings
 *
 * @param string $tier_slug
 * @param string $period 'monthly' or 'yearly'
 * @return string
 */
function tmw_get_subscribe_url($tier_slug, $period = 'monthly') {
    // Check which membership plugin is active
    $plugin = function_exists('tmw_get_setting') 
        ? tmw_get_setting('membership_plugin', 'simple-membership')
        : get_option('tmw_settings')['membership_plugin'] ?? 'simple-membership';

    switch ($plugin) {
        case 'stripe':
            return TMW_Stripe_Checkout::get_checkout_url($tier_slug, $period);

        case 'simple-membership':
        default:
            // Fall back to SWPM join URL
            if (function_exists('tmw_get_swpm_join_url') && function_exists('tmw_get_tier')) {
                $tier = tmw_get_tier($tier_slug);
                $level_id = $tier['swpm_level_id'] ?? 0;
                return tmw_get_swpm_join_url($level_id);
            }
            return '#';
    }
}
