<?php
/**
 * Stripe Settings
 *
 * Handles Stripe settings tab and tier Stripe fields.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Settings {

    /**
     * Add Stripe tab to TMW settings
     *
     * @param array $tabs
     * @return array
     */
    public function add_stripe_tab($tabs) {
        $tabs['stripe'] = __('Stripe', 'tmw-stripe-subscriptions');
        return $tabs;
    }

    /**
     * Render Stripe settings tab
     */
    public function render_tab() {
        $settings = TMW_Stripe_API::get_settings();
        $mode = $settings['mode'] ?? 'test';
        $is_configured = TMW_Stripe_API::is_configured();

        include TMW_STRIPE_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }

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
     * Render Stripe fields in tier modal
     */
    public function render_tier_stripe_fields() {
        include TMW_STRIPE_PLUGIN_DIR . 'admin/partials/tier-fields.php';
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

            // Extend tier save to include Stripe fields
            var originalSaveTier = window.tmwSaveTier;
            if (typeof originalSaveTier === 'function') {
                window.tmwSaveTier = function() {
                    // Add Stripe fields to data
                    var data = originalSaveTier.call(this);
                    if (data && data.data) {
                        data.data.stripe_price_id_monthly = $('#tier-stripe-price-monthly').val();
                        data.data.stripe_price_id_yearly = $('#tier-stripe-price-yearly').val();
                        data.data.stripe_product_id = $('#tier-stripe-product-id').val();
                    }
                    return data;
                };
            }

            // Populate Stripe fields when editing tier
            $(document).on('click', '.tmw-edit-tier', function() {
                var $row = $(this).closest('tr');
                var slug = $row.data('slug');
                
                // Fetch tier data via AJAX to get Stripe fields
                $.post(ajaxurl, {
                    action: 'tmw_get_tier_stripe_data',
                    nonce: '<?php echo wp_create_nonce('tmw_admin_nonce'); ?>',
                    slug: slug
                }, function(response) {
                    if (response.success) {
                        $('#tier-stripe-price-monthly').val(response.data.stripe_price_id_monthly || '');
                        $('#tier-stripe-price-yearly').val(response.data.stripe_price_id_yearly || '');
                        $('#tier-stripe-product-id').val(response.data.stripe_product_id || '');
                    }
                });
            });

            // Clear Stripe fields when adding new tier
            $(document).on('click', '#tmw-add-tier', function() {
                $('#tier-stripe-price-monthly').val('');
                $('#tier-stripe-price-yearly').val('');
                $('#tier-stripe-product-id').val('');
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
