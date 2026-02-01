<?php
/**
 * Stripe API Wrapper
 *
 * Handles all Stripe API interactions using cURL.
 * Does not require Stripe PHP SDK - uses REST API directly.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_API {

    /**
     * Stripe API base URL
     */
    const API_BASE = 'https://api.stripe.com/v1';

    /**
     * Get Stripe settings
     *
     * @return array
     */
    public static function get_settings() {
        return get_option('tmw_stripe_settings', array());
    }

    /**
     * Get API mode (test or live)
     *
     * @return string
     */
    public static function get_mode() {
        $settings = self::get_settings();
        return isset($settings['mode']) ? $settings['mode'] : 'test';
    }

    /**
     * Get secret key based on current mode
     *
     * @return string
     */
    public static function get_secret_key() {
        $settings = self::get_settings();
        $mode = self::get_mode();
        $key = $mode === 'live' ? 'live_secret_key' : 'test_secret_key';
        return isset($settings[$key]) ? $settings[$key] : '';
    }

    /**
     * Get publishable key based on current mode
     *
     * @return string
     */
    public static function get_publishable_key() {
        $settings = self::get_settings();
        $mode = self::get_mode();
        $key = $mode === 'live' ? 'live_publishable_key' : 'test_publishable_key';
        return isset($settings[$key]) ? $settings[$key] : '';
    }

    /**
     * Get webhook secret based on current mode
     *
     * @return string
     */
    public static function get_webhook_secret() {
        $settings = self::get_settings();
        $mode = self::get_mode();
        $key = $mode === 'live' ? 'live_webhook_secret' : 'test_webhook_secret';
        return isset($settings[$key]) ? $settings[$key] : '';
    }

    /**
     * Make API request to Stripe
     *
     * @param string $endpoint API endpoint (e.g., '/customers')
     * @param string $method   HTTP method
     * @param array  $data     Request data
     * @return array|WP_Error
     */
    public static function request($endpoint, $method = 'GET', $data = array()) {
        $secret_key = self::get_secret_key();

        if (empty($secret_key)) {
            return new WP_Error('no_api_key', __('Stripe API key not configured.', 'tmw-stripe-subscriptions'));
        }

        $url = self::API_BASE . $endpoint;

        $args = array(
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'timeout' => 30,
        );

        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = self::build_query($data);
        } elseif (!empty($data) && $method === 'GET') {
            $url = add_query_arg($data, $url);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 400) {
            $error_message = isset($decoded['error']['message']) 
                ? $decoded['error']['message'] 
                : __('Unknown Stripe API error', 'tmw-stripe-subscriptions');
            $error_code = isset($decoded['error']['code']) 
                ? $decoded['error']['code'] 
                : 'stripe_error';
            
            return new WP_Error($error_code, $error_message, $decoded);
        }

        return $decoded;
    }

    /**
     * Build query string for nested arrays (Stripe format)
     *
     * @param array  $data
     * @param string $prefix
     * @return string
     */
    private static function build_query($data, $prefix = '') {
        $result = array();

        foreach ($data as $key => $value) {
            $new_key = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $result[] = self::build_query($value, $new_key);
            } else {
                $result[] = urlencode($new_key) . '=' . urlencode($value);
            }
        }

        return implode('&', $result);
    }

    // =========================================================================
    // CUSTOMERS
    // =========================================================================

    /**
     * Create a Stripe customer
     *
     * @param array $data Customer data (email, name, metadata)
     * @return array|WP_Error
     */
    public static function create_customer($data) {
        return self::request('/customers', 'POST', $data);
    }

    /**
     * Get a Stripe customer
     *
     * @param string $customer_id
     * @return array|WP_Error
     */
    public static function get_customer($customer_id) {
        return self::request('/customers/' . $customer_id);
    }

    /**
     * Update a Stripe customer
     *
     * @param string $customer_id
     * @param array  $data
     * @return array|WP_Error
     */
    public static function update_customer($customer_id, $data) {
        return self::request('/customers/' . $customer_id, 'POST', $data);
    }

    // =========================================================================
    // SUBSCRIPTIONS
    // =========================================================================

    /**
     * Create a subscription
     *
     * @param array $data Subscription data
     * @return array|WP_Error
     */
    public static function create_subscription($data) {
        return self::request('/subscriptions', 'POST', $data);
    }

    /**
     * Get a subscription
     *
     * @param string $subscription_id
     * @return array|WP_Error
     */
    public static function get_subscription($subscription_id) {
        return self::request('/subscriptions/' . $subscription_id);
    }

    /**
     * Update a subscription
     *
     * @param string $subscription_id
     * @param array  $data
     * @return array|WP_Error
     */
    public static function update_subscription($subscription_id, $data) {
        return self::request('/subscriptions/' . $subscription_id, 'POST', $data);
    }

    /**
     * Cancel a subscription
     *
     * @param string $subscription_id
     * @param bool   $at_period_end
     * @return array|WP_Error
     */
    public static function cancel_subscription($subscription_id, $at_period_end = true) {
        if ($at_period_end) {
            return self::update_subscription($subscription_id, array(
                'cancel_at_period_end' => 'true',
            ));
        }

        return self::request('/subscriptions/' . $subscription_id, 'DELETE');
    }

    // =========================================================================
    // CHECKOUT SESSIONS
    // =========================================================================

    /**
     * Create a Checkout Session
     *
     * @param array $data Session data
     * @return array|WP_Error
     */
    public static function create_checkout_session($data) {
        return self::request('/checkout/sessions', 'POST', $data);
    }

    /**
     * Get a Checkout Session
     *
     * @param string $session_id
     * @return array|WP_Error
     */
    public static function get_checkout_session($session_id) {
        return self::request('/checkout/sessions/' . $session_id);
    }

    // =========================================================================
    // BILLING PORTAL
    // =========================================================================

    /**
     * Create a Billing Portal Session
     *
     * @param array $data Session data (customer, return_url)
     * @return array|WP_Error
     */
    public static function create_portal_session($data) {
        return self::request('/billing_portal/sessions', 'POST', $data);
    }

    // =========================================================================
    // PRODUCTS & PRICES
    // =========================================================================

    /**
     * Get a price
     *
     * @param string $price_id
     * @return array|WP_Error
     */
    public static function get_price($price_id) {
        return self::request('/prices/' . $price_id);
    }

    /**
     * List prices for a product
     *
     * @param string $product_id
     * @return array|WP_Error
     */
    public static function list_prices($product_id = null) {
        $data = array('limit' => 100);
        if ($product_id) {
            $data['product'] = $product_id;
        }
        return self::request('/prices', 'GET', $data);
    }

    /**
     * Get a product
     *
     * @param string $product_id
     * @return array|WP_Error
     */
    public static function get_product($product_id) {
        return self::request('/products/' . $product_id);
    }

    // =========================================================================
    // WEBHOOK VERIFICATION
    // =========================================================================

    /**
     * Verify webhook signature
     *
     * @param string $payload   Raw request body
     * @param string $signature Stripe-Signature header
     * @return array|WP_Error   Parsed event or error
     */
    public static function verify_webhook_signature($payload, $signature) {
        $webhook_secret = self::get_webhook_secret();

        if (empty($webhook_secret)) {
            return new WP_Error('no_webhook_secret', __('Webhook secret not configured.', 'tmw-stripe-subscriptions'));
        }

        // Parse the signature header
        $sig_parts = array();
        foreach (explode(',', $signature) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $sig_parts[$pair[0]] = $pair[1];
            }
        }

        if (!isset($sig_parts['t']) || !isset($sig_parts['v1'])) {
            return new WP_Error('invalid_signature', __('Invalid signature format.', 'tmw-stripe-subscriptions'));
        }

        $timestamp = $sig_parts['t'];
        $expected_signature = $sig_parts['v1'];

        // Check timestamp (allow 5 minute tolerance)
        if (abs(time() - $timestamp) > 300) {
            return new WP_Error('expired_signature', __('Webhook signature has expired.', 'tmw-stripe-subscriptions'));
        }

        // Compute expected signature
        $signed_payload = $timestamp . '.' . $payload;
        $computed_signature = hash_hmac('sha256', $signed_payload, $webhook_secret);

        // Compare signatures (timing-safe)
        if (!hash_equals($computed_signature, $expected_signature)) {
            return new WP_Error('signature_mismatch', __('Webhook signature verification failed.', 'tmw-stripe-subscriptions'));
        }

        // Parse and return event
        $event = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_payload', __('Invalid webhook payload.', 'tmw-stripe-subscriptions'));
        }

        return $event;
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Test API connection
     *
     * @return array|WP_Error
     */
    public static function test_connection() {
        return self::request('/balance');
    }

    /**
     * Check if API is configured
     *
     * @return bool
     */
    public static function is_configured() {
        $secret_key = self::get_secret_key();
        return !empty($secret_key);
    }

    /**
     * Get tier slug from price ID
     *
     * @param string $price_id
     * @return string|null
     */
    public static function get_tier_by_price_id($price_id) {
        if (!function_exists('tmw_get_tiers')) {
            return null;
        }

        $tiers = tmw_get_tiers();

        foreach ($tiers as $slug => $tier) {
            $monthly = $tier['stripe_price_id_monthly'] ?? '';
            $yearly = $tier['stripe_price_id_yearly'] ?? '';

            if ($price_id === $monthly || $price_id === $yearly) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Get price ID for a tier
     *
     * @param string $tier_slug
     * @param string $period 'monthly' or 'yearly'
     * @return string|null
     */
    public static function get_price_id_for_tier($tier_slug, $period = 'monthly') {
        if (!function_exists('tmw_get_tier')) {
            return null;
        }

        $tier = tmw_get_tier($tier_slug);

        if (!$tier) {
            return null;
        }

        $key = 'stripe_price_id_' . $period;
        return isset($tier[$key]) ? $tier[$key] : null;
    }
}
