<?php
/**
 * Public-Facing Functionality
 *
 * Handles frontend scripts, AJAX, and user registration hooks.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Public {

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Only on relevant pages
        if (!$this->should_load_scripts()) {
            return;
        }

        wp_enqueue_script(
            'tmw-stripe-public',
            TMW_STRIPE_PLUGIN_URL . 'public/js/tmw-stripe-public.js',
            array('jquery'),
            TMW_STRIPE_VERSION,
            true
        );

        wp_localize_script('tmw-stripe-public', 'tmwStripe', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'checkoutNonce' => wp_create_nonce('tmw_stripe_checkout'),
            'portalNonce'   => wp_create_nonce('tmw_stripe_portal'),
            'isLoggedIn'    => is_user_logged_in(),
            'loginUrl'      => function_exists('tmw_get_page_url') 
                ? tmw_get_page_url('login') 
                : wp_login_url(),
            'strings'       => array(
                'processing'  => __('Processing...', 'tmw-stripe-subscriptions'),
                'redirecting' => __('Redirecting to checkout...', 'tmw-stripe-subscriptions'),
                'error'       => __('An error occurred. Please try again.', 'tmw-stripe-subscriptions'),
                'loginFirst'  => __('Please log in first.', 'tmw-stripe-subscriptions'),
            ),
        ));
    }

    /**
     * Check if scripts should be loaded on this page
     *
     * @return bool
     */
    private function should_load_scripts() {
        // Check if Stripe is the active membership plugin
        $plugin = function_exists('tmw_get_setting') 
            ? tmw_get_setting('membership_plugin', 'simple-membership')
            : 'simple-membership';

        if ($plugin !== 'stripe') {
            return false;
        }

        // Load on pricing, profile, renewal pages
        if (is_page(array('pricing', 'subscription', 'my-profile', 'renewal'))) {
            return true;
        }

        // Load if page has pricing or subscribe content
        global $post;
        if ($post && (
            has_shortcode($post->post_content, 'tmw_pricing') ||
            strpos($post->post_content, 'tmw-subscribe-btn') !== false
        )) {
            return true;
        }

        return false;
    }

    /**
     * Handle user registration - assign free tier
     *
     * @param int $user_id
     */
    public function on_user_register($user_id) {
        // Check if Stripe is active
        $plugin = function_exists('tmw_get_setting') 
            ? tmw_get_setting('membership_plugin', 'simple-membership')
            : 'simple-membership';

        if ($plugin !== 'stripe') {
            return;
        }

        // Get default tier from settings
        $settings = TMW_Stripe_API::get_settings();
        $default_tier = $settings['default_tier'] ?? 'free';

        // Set user to free tier
        update_user_meta($user_id, 'tmw_subscription_tier', $default_tier);
        update_user_meta($user_id, 'tmw_subscription_status', 'active'); // Free tier is always "active"
        update_user_meta($user_id, 'tmw_stripe_trial_used', 0);

        // Create subscription record
        global $wpdb;
        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $wpdb->insert($table, array(
                'user_id'            => $user_id,
                'stripe_customer_id' => '', // Will be created on first checkout
                'tier_slug'          => $default_tier,
                'status'             => 'active',
                'trial_used'         => 0,
            ), array('%d', '%s', '%s', '%s', '%d'));
        }

        // Optionally create Stripe customer immediately
        if (!empty($settings['auto_create_customer'])) {
            $this->create_stripe_customer($user_id);
        }

        // Fire action
        do_action('tmw_user_registered_with_tier', $user_id, $default_tier);
    }

    /**
     * Create Stripe customer for user
     *
     * @param int $user_id
     * @return string|false Customer ID or false on failure
     */
    private function create_stripe_customer($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        $result = TMW_Stripe_API::create_customer(array(
            'email'    => $user->user_email,
            'name'     => $user->display_name,
            'metadata' => array(
                'user_id'    => $user_id,
                'user_login' => $user->user_login,
            ),
        ));

        if (is_wp_error($result)) {
            return false;
        }

        $customer_id = $result['id'];

        // Save customer ID
        update_user_meta($user_id, 'tmw_stripe_customer_id', $customer_id);

        // Update subscription record
        global $wpdb;
        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';

        $wpdb->update(
            $table,
            array('stripe_customer_id' => $customer_id),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );

        return $customer_id;
    }

    /**
     * Sync subscription status on user login
     *
     * @param string  $user_login
     * @param WP_User $user
     */
    public function sync_subscription_on_login($user_login, $user) {
        // Check if Stripe is active
        $plugin = function_exists('tmw_get_setting') 
            ? tmw_get_setting('membership_plugin', 'simple-membership')
            : 'simple-membership';

        if ($plugin !== 'stripe') {
            return;
        }

        $subscription_id = get_user_meta($user->ID, 'tmw_stripe_subscription_id', true);

        if (empty($subscription_id)) {
            return; // No subscription to sync
        }

        // Fetch current subscription from Stripe
        $subscription = TMW_Stripe_API::get_subscription($subscription_id);

        if (is_wp_error($subscription)) {
            return; // Can't fetch, keep existing data
        }

        // Update local data
        $status = $subscription['status'];
        $tier_slug = get_user_meta($user->ID, 'tmw_subscription_tier', true);

        // Check if subscription price changed (plan change)
        $price_id = $subscription['items']['data'][0]['price']['id'] ?? '';
        $detected_tier = TMW_Stripe_API::get_tier_by_price_id($price_id);

        if ($detected_tier && $detected_tier !== $tier_slug) {
            $old_tier = $tier_slug;
            $tier_slug = $detected_tier;
            update_user_meta($user->ID, 'tmw_subscription_tier', $tier_slug);
            do_action('tmw_subscription_changed', $user->ID, $old_tier, $tier_slug);
        }

        // Update status
        update_user_meta($user->ID, 'tmw_subscription_status', $status);

        // Update period end
        if (!empty($subscription['current_period_end'])) {
            update_user_meta(
                $user->ID, 
                'tmw_subscription_current_period_end', 
                date('Y-m-d H:i:s', $subscription['current_period_end'])
            );
        }

        // Handle canceled subscriptions
        if (!empty($subscription['cancel_at_period_end']) || $status === 'canceled') {
            update_user_meta($user->ID, 'tmw_subscription_status', 'canceled');
        }

        // Update database record
        global $wpdb;
        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $wpdb->update(
                $table,
                array(
                    'tier_slug'            => $tier_slug,
                    'status'               => $status,
                    'current_period_start' => date('Y-m-d H:i:s', $subscription['current_period_start']),
                    'current_period_end'   => date('Y-m-d H:i:s', $subscription['current_period_end']),
                ),
                array('user_id' => $user->ID),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );
        }
    }
}
