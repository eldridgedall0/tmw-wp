<?php
/**
 * Stripe Webhook Handler
 *
 * Handles incoming Stripe webhook events via REST API endpoint.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Webhook {

    /**
     * REST API namespace
     */
    const NAMESPACE = 'tmw-stripe/v1';

    /**
     * Register REST API endpoint
     */
    public function register_endpoint() {
        register_rest_route(self::NAMESPACE, '/webhook', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true', // Verified via signature
        ));
    }

    /**
     * Handle incoming webhook
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_webhook($request) {
        // Get raw body for signature verification
        $payload = $request->get_body();
        $signature = $request->get_header('Stripe-Signature');

        if (empty($signature)) {
            return new WP_REST_Response(
                array('error' => 'Missing signature'),
                400
            );
        }

        // Verify signature
        $event = TMW_Stripe_API::verify_webhook_signature($payload, $signature);

        if (is_wp_error($event)) {
            $this->log('Webhook signature verification failed: ' . $event->get_error_message());
            return new WP_REST_Response(
                array('error' => $event->get_error_message()),
                400
            );
        }

        $this->log('Received webhook: ' . $event['type']);

        // Handle event
        $result = $this->process_event($event);

        if (is_wp_error($result)) {
            $this->log('Webhook processing failed: ' . $result->get_error_message());
            return new WP_REST_Response(
                array('error' => $result->get_error_message()),
                500
            );
        }

        return new WP_REST_Response(array('received' => true), 200);
    }

    /**
     * Process webhook event
     *
     * @param array $event
     * @return bool|WP_Error
     */
    private function process_event($event) {
        $type = $event['type'] ?? '';
        $data = $event['data']['object'] ?? array();

        switch ($type) {
            case 'checkout.session.completed':
                return $this->handle_checkout_completed($data);

            case 'customer.subscription.created':
                return $this->handle_subscription_created($data);

            case 'customer.subscription.updated':
                return $this->handle_subscription_updated($data);

            case 'customer.subscription.deleted':
                return $this->handle_subscription_deleted($data);

            case 'invoice.payment_succeeded':
                return $this->handle_payment_succeeded($data);

            case 'invoice.payment_failed':
                return $this->handle_payment_failed($data);

            case 'customer.subscription.trial_will_end':
                return $this->handle_trial_will_end($data);

            default:
                $this->log('Unhandled webhook event type: ' . $type);
                return true;
        }
    }

    /**
     * Handle checkout.session.completed
     *
     * @param array $session
     * @return bool|WP_Error
     */
    private function handle_checkout_completed($session) {
        $this->log('Processing checkout.session.completed');

        // Get user ID from metadata
        $user_id = $session['metadata']['user_id'] ?? null;
        $tier_slug = $session['metadata']['tier_slug'] ?? null;

        if (!$user_id) {
            // Try to find user by customer email
            $customer_email = $session['customer_details']['email'] ?? $session['customer_email'] ?? null;
            if ($customer_email) {
                $user = get_user_by('email', $customer_email);
                if ($user) {
                    $user_id = $user->ID;
                }
            }
        }

        if (!$user_id) {
            return new WP_Error('no_user', 'Could not identify user from checkout session');
        }

        $customer_id = $session['customer'] ?? '';
        $subscription_id = $session['subscription'] ?? '';

        // Get subscription details from Stripe
        $subscription = null;
        if ($subscription_id) {
            $subscription = TMW_Stripe_API::get_subscription($subscription_id);
            if (is_wp_error($subscription)) {
                $this->log('Failed to fetch subscription: ' . $subscription->get_error_message());
                $subscription = null;
            }
        }

        // Determine tier from subscription price if not in metadata
        if (!$tier_slug && $subscription) {
            $price_id = $subscription['items']['data'][0]['price']['id'] ?? '';
            $tier_slug = TMW_Stripe_API::get_tier_by_price_id($price_id);
        }

        if (!$tier_slug) {
            $tier_slug = 'paid'; // Default fallback
        }

        // Prepare subscription data
        $data = array(
            'stripe_customer_id'     => $customer_id,
            'stripe_subscription_id' => $subscription_id,
            'tier_slug'              => $tier_slug,
            'status'                 => 'active',
            'current_period_start'   => null,
            'current_period_end'     => null,
            'trial_used'             => 0,
        );

        // Add subscription period dates
        if ($subscription) {
            if (!empty($subscription['current_period_start'])) {
                $data['current_period_start'] = date('Y-m-d H:i:s', $subscription['current_period_start']);
            }
            if (!empty($subscription['current_period_end'])) {
                $data['current_period_end'] = date('Y-m-d H:i:s', $subscription['current_period_end']);
            }
            if (!empty($subscription['status'])) {
                $data['status'] = $subscription['status'];
            }
            // Check if trial was used
            if (!empty($subscription['trial_start'])) {
                $data['trial_used'] = 1;
            }
        }

        // Save subscription record
        $adapter = tmw_stripe_adapter();
        $old_tier = $adapter->get_user_tier($user_id);
        $adapter->save_subscription_record($user_id, $data);

        // Fire action
        do_action('tmw_subscription_changed', $user_id, $old_tier, $tier_slug);
        do_action('tmw_stripe_checkout_completed', $user_id, $data);

        $this->log("Checkout completed for user $user_id, tier: $tier_slug");

        return true;
    }

    /**
     * Handle customer.subscription.created
     *
     * @param array $subscription
     * @return bool|WP_Error
     */
    private function handle_subscription_created($subscription) {
        $this->log('Processing customer.subscription.created');

        $user_id = $this->get_user_from_subscription($subscription);

        if (!$user_id) {
            $this->log('Could not find user for subscription');
            return true; // Not an error, might be external subscription
        }

        $price_id = $subscription['items']['data'][0]['price']['id'] ?? '';
        $tier_slug = TMW_Stripe_API::get_tier_by_price_id($price_id);

        if (!$tier_slug) {
            $this->log('Could not determine tier from price: ' . $price_id);
            return true;
        }

        $data = array(
            'stripe_customer_id'     => $subscription['customer'],
            'stripe_subscription_id' => $subscription['id'],
            'tier_slug'              => $tier_slug,
            'status'                 => $subscription['status'],
            'current_period_start'   => date('Y-m-d H:i:s', $subscription['current_period_start']),
            'current_period_end'     => date('Y-m-d H:i:s', $subscription['current_period_end']),
            'trial_used'             => !empty($subscription['trial_start']) ? 1 : 0,
        );

        $adapter = tmw_stripe_adapter();
        $old_tier = $adapter->get_user_tier($user_id);
        $adapter->save_subscription_record($user_id, $data);

        do_action('tmw_subscription_changed', $user_id, $old_tier, $tier_slug);

        $this->log("Subscription created for user $user_id, tier: $tier_slug");

        return true;
    }

    /**
     * Handle customer.subscription.updated
     *
     * @param array $subscription
     * @return bool|WP_Error
     */
    private function handle_subscription_updated($subscription) {
        $this->log('Processing customer.subscription.updated');

        $user_id = $this->get_user_from_subscription($subscription);

        if (!$user_id) {
            $this->log('Could not find user for subscription');
            return true;
        }

        $adapter = tmw_stripe_adapter();
        $existing = $adapter->get_subscription_record($user_id);
        $old_tier = $adapter->get_user_tier($user_id);

        // Determine new tier from price
        $price_id = $subscription['items']['data'][0]['price']['id'] ?? '';
        $new_tier = TMW_Stripe_API::get_tier_by_price_id($price_id);

        if (!$new_tier && $existing) {
            $new_tier = $existing->tier_slug;
        }

        $data = array(
            'stripe_customer_id'     => $subscription['customer'],
            'stripe_subscription_id' => $subscription['id'],
            'tier_slug'              => $new_tier ?: 'free',
            'status'                 => $subscription['status'],
            'current_period_start'   => date('Y-m-d H:i:s', $subscription['current_period_start']),
            'current_period_end'     => date('Y-m-d H:i:s', $subscription['current_period_end']),
            'canceled_at'            => null,
        );

        // Handle cancellation
        if (!empty($subscription['cancel_at_period_end']) || $subscription['status'] === 'canceled') {
            $data['status'] = 'canceled';
            $data['canceled_at'] = date('Y-m-d H:i:s');
        }

        // Preserve trial_used flag
        if ($existing) {
            $data['trial_used'] = $existing->trial_used;
        }

        $adapter->save_subscription_record($user_id, $data);

        // Fire appropriate actions
        if ($old_tier !== $new_tier) {
            do_action('tmw_subscription_changed', $user_id, $old_tier, $new_tier);
        }

        do_action('tmw_subscription_status_changed', $user_id, $data['status']);

        $this->log("Subscription updated for user $user_id, status: {$data['status']}, tier: {$data['tier_slug']}");

        return true;
    }

    /**
     * Handle customer.subscription.deleted
     *
     * @param array $subscription
     * @return bool|WP_Error
     */
    private function handle_subscription_deleted($subscription) {
        $this->log('Processing customer.subscription.deleted');

        $user_id = $this->get_user_from_subscription($subscription);

        if (!$user_id) {
            $this->log('Could not find user for subscription');
            return true;
        }

        $adapter = tmw_stripe_adapter();
        $old_tier = $adapter->get_user_tier($user_id);
        $existing = $adapter->get_subscription_record($user_id);

        // Get free tier
        $free_tier = 'free';
        if (function_exists('tmw_get_tiers')) {
            $tiers = tmw_get_tiers();
            foreach ($tiers as $slug => $tier) {
                if (!empty($tier['is_free'])) {
                    $free_tier = $slug;
                    break;
                }
            }
        }

        // Update to free tier but keep customer ID
        $data = array(
            'stripe_customer_id'     => $subscription['customer'],
            'stripe_subscription_id' => null,
            'tier_slug'              => $free_tier,
            'status'                 => 'inactive',
            'current_period_start'   => null,
            'current_period_end'     => null,
            'canceled_at'            => date('Y-m-d H:i:s'),
            'trial_used'             => $existing ? $existing->trial_used : 1,
        );

        $adapter->save_subscription_record($user_id, $data);

        do_action('tmw_subscription_changed', $user_id, $old_tier, $free_tier);
        do_action('tmw_stripe_subscription_canceled', $user_id, $subscription['id']);

        $this->log("Subscription deleted for user $user_id, reverted to $free_tier tier");

        return true;
    }

    /**
     * Handle invoice.payment_succeeded
     *
     * @param array $invoice
     * @return bool|WP_Error
     */
    private function handle_payment_succeeded($invoice) {
        $this->log('Processing invoice.payment_succeeded');

        // Only handle subscription invoices
        if (empty($invoice['subscription'])) {
            return true;
        }

        $subscription_id = $invoice['subscription'];
        $user_id = $this->get_user_by_subscription_id($subscription_id);

        if (!$user_id) {
            return true;
        }

        // Get fresh subscription data
        $subscription = TMW_Stripe_API::get_subscription($subscription_id);

        if (is_wp_error($subscription)) {
            return true;
        }

        $adapter = tmw_stripe_adapter();
        $existing = $adapter->get_subscription_record($user_id);

        if (!$existing) {
            return true;
        }

        // Update period dates and status
        $data = array(
            'stripe_customer_id'     => $subscription['customer'],
            'stripe_subscription_id' => $subscription_id,
            'tier_slug'              => $existing->tier_slug,
            'status'                 => 'active',
            'current_period_start'   => date('Y-m-d H:i:s', $subscription['current_period_start']),
            'current_period_end'     => date('Y-m-d H:i:s', $subscription['current_period_end']),
            'trial_used'             => $existing->trial_used,
            'canceled_at'            => null,
        );

        $adapter->save_subscription_record($user_id, $data);

        $this->log("Payment succeeded for user $user_id, subscription renewed");

        return true;
    }

    /**
     * Handle invoice.payment_failed
     *
     * @param array $invoice
     * @return bool|WP_Error
     */
    private function handle_payment_failed($invoice) {
        $this->log('Processing invoice.payment_failed');

        if (empty($invoice['subscription'])) {
            return true;
        }

        $subscription_id = $invoice['subscription'];
        $user_id = $this->get_user_by_subscription_id($subscription_id);

        if (!$user_id) {
            return true;
        }

        $adapter = tmw_stripe_adapter();
        $existing = $adapter->get_subscription_record($user_id);

        if (!$existing) {
            return true;
        }

        // Update status to past_due
        global $wpdb;
        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';

        $wpdb->update(
            $table,
            array('status' => 'past_due'),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );

        update_user_meta($user_id, 'tmw_subscription_status', 'past_due');

        do_action('tmw_subscription_status_changed', $user_id, 'past_due');
        do_action('tmw_stripe_payment_failed', $user_id, $invoice);

        $this->log("Payment failed for user $user_id, status set to past_due");

        return true;
    }

    /**
     * Handle customer.subscription.trial_will_end
     *
     * @param array $subscription
     * @return bool|WP_Error
     */
    private function handle_trial_will_end($subscription) {
        $this->log('Processing customer.subscription.trial_will_end');

        $user_id = $this->get_user_from_subscription($subscription);

        if (!$user_id) {
            return true;
        }

        do_action('tmw_stripe_trial_will_end', $user_id, $subscription);

        $this->log("Trial ending soon for user $user_id");

        return true;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get WordPress user ID from subscription
     *
     * @param array $subscription
     * @return int|null
     */
    private function get_user_from_subscription($subscription) {
        // Check metadata first
        if (!empty($subscription['metadata']['user_id'])) {
            return (int) $subscription['metadata']['user_id'];
        }

        // Try by customer ID
        $customer_id = $subscription['customer'] ?? '';
        if ($customer_id) {
            return $this->get_user_by_customer_id($customer_id);
        }

        return null;
    }

    /**
     * Get user by Stripe customer ID
     *
     * @param string $customer_id
     * @return int|null
     */
    private function get_user_by_customer_id($customer_id) {
        global $wpdb;

        // Check subscriptions table
        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table WHERE stripe_customer_id = %s",
            $customer_id
        ));

        if ($user_id) {
            return (int) $user_id;
        }

        // Check user meta
        $users = get_users(array(
            'meta_key'   => 'tmw_stripe_customer_id',
            'meta_value' => $customer_id,
            'number'     => 1,
            'fields'     => 'ID',
        ));

        return !empty($users) ? (int) $users[0] : null;
    }

    /**
     * Get user by Stripe subscription ID
     *
     * @param string $subscription_id
     * @return int|null
     */
    private function get_user_by_subscription_id($subscription_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'tmw_stripe_subscriptions';
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table WHERE stripe_subscription_id = %s",
            $subscription_id
        ));

        if ($user_id) {
            return (int) $user_id;
        }

        // Check user meta
        $users = get_users(array(
            'meta_key'   => 'tmw_stripe_subscription_id',
            'meta_value' => $subscription_id,
            'number'     => 1,
            'fields'     => 'ID',
        ));

        return !empty($users) ? (int) $users[0] : null;
    }

    /**
     * Log webhook message
     *
     * @param string $message
     */
    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[TMW Stripe Webhook] ' . $message);
        }
    }
}
