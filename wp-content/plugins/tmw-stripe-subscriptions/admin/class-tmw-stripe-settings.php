<?php
/**
 * Stripe Settings
 *
 * Handles Stripe settings page and tier Stripe fields.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Settings {

    /**
     * AJAX: Save Stripe settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('tmw_stripe_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'tmw-stripe-subscriptions')));
        }

        $settings = array(
            'mode'                  => sanitize_key($_POST['mode'] ?? 'test'),
            'test_publishable_key'  => sanitize_text_field($_POST['test_publishable_key'] ?? ''),
            'test_secret_key'       => sanitize_text_field($_POST['test_secret_key'] ?? ''),
            'test_webhook_secret'   => sanitize_text_field($_POST['test_webhook_secret'] ?? ''),
            'live_publishable_key'  => sanitize_text_field($_POST['live_publishable_key'] ?? ''),
            'live_secret_key'       => sanitize_text_field($_POST['live_secret_key'] ?? ''),
            'live_webhook_secret'   => sanitize_text_field($_POST['live_webhook_secret'] ?? ''),
            'success_url'           => sanitize_text_field($_POST['success_url'] ?? '/my-profile/'),
            'cancel_url'            => sanitize_text_field($_POST['cancel_url'] ?? '/pricing/'),
            'trial_enabled'         => !empty($_POST['trial_enabled']),
            'trial_days'            => absint($_POST['trial_days'] ?? 7),
            'proration_behavior'    => sanitize_key($_POST['proration_behavior'] ?? 'always_invoice'),
            'cancel_at_period_end'  => !empty($_POST['cancel_at_period_end']),
            'default_tier'          => sanitize_key($_POST['default_tier'] ?? 'free'),
            'auto_create_customer'  => !empty($_POST['auto_create_customer']),
        );

        update_option('tmw_stripe_settings', $settings);

        wp_send_json_success(array('message' => __('Settings saved!', 'tmw-stripe-subscriptions')));
    }

    /**
     * AJAX: Test Stripe connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('tmw_stripe_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'tmw-stripe-subscriptions')));
        }

        $result = TMW_Stripe_API::test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Connection successful!', 'tmw-stripe-subscriptions'),
            'mode'    => TMW_Stripe_API::get_mode(),
        ));
    }

    /**
     * Render Stripe fields in tier modal (for TMW Settings integration)
     */
    public function render_tier_stripe_fields() {
        include TMW_STRIPE_PLUGIN_DIR . 'admin/partials/tier-stripe-fields.php';
    }

    /**
     * Render JavaScript for tier fields show/hide
     */
    public function render_tier_fields_script() {
        // Only on TMW settings page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'toplevel_page_tmw-settings') {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Show/hide Stripe fields based on membership plugin selection
            function toggleStripeFields() {
                var plugin = $('#membership_plugin').val();
                if (plugin === 'stripe') {
                    $('.tmw-stripe-tier-field').show();
                    $('.tmw-swpm-tier-field').hide();
                } else {
                    $('.tmw-stripe-tier-field').hide();
                    $('.tmw-swpm-tier-field').show();
                }
            }

            // Initial toggle
            toggleStripeFields();

            // Toggle on change
            $('#membership_plugin').on('change', toggleStripeFields);

            // When tier modal opens, apply current plugin setting
            $(document).on('click', '.tmw-edit-tier, #tmw-add-tier', function() {
                setTimeout(toggleStripeFields, 100);
            });
        });
        </script>
        <?php
    }
}

/**
 * AJAX handler to get tier Stripe data
 */
add_action('wp_ajax_tmw_get_tier_stripe_data', function() {
    check_ajax_referer('tmw_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $slug = sanitize_key($_POST['slug'] ?? '');

    if (empty($slug) || !function_exists('tmw_get_tier')) {
        wp_send_json_error('Invalid tier');
    }

    $tier = tmw_get_tier($slug);

    wp_send_json_success(array(
        'stripe_price_id_monthly' => $tier['stripe_price_id_monthly'] ?? '',
        'stripe_price_id_yearly'  => $tier['stripe_price_id_yearly'] ?? '',
        'stripe_product_id'       => $tier['stripe_product_id'] ?? '',
    ));
});

/**
 * Extend tier sanitization to include Stripe fields
 */
add_filter('tmw_sanitize_tier_data', function($data) {
    $data['stripe_price_id_monthly'] = isset($_POST['data']['stripe_price_id_monthly']) 
        ? sanitize_text_field($_POST['data']['stripe_price_id_monthly']) 
        : '';
    $data['stripe_price_id_yearly'] = isset($_POST['data']['stripe_price_id_yearly']) 
        ? sanitize_text_field($_POST['data']['stripe_price_id_yearly']) 
        : '';
    $data['stripe_product_id'] = isset($_POST['data']['stripe_product_id']) 
        ? sanitize_text_field($_POST['data']['stripe_product_id']) 
        : '';
    return $data;
}, 10, 1);
