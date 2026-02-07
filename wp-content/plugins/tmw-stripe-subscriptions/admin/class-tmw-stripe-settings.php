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
     * Render JavaScript for tier fields show/hide and data handling
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
                } else {
                    $('.tmw-stripe-tier-field').hide();
                }
            }

            // Initial toggle
            toggleStripeFields();

            // Toggle on change
            $('#membership_plugin').on('change', toggleStripeFields);

            // Store original save tier function and extend it
            var originalSaveTier = null;
            
            // Intercept tier save to include Stripe/pricing fields
            $(document).on('click', '#tmw-save-tier', function(e) {
                // The theme's JS already handles the AJAX call
                // We need to extend the data object with our fields
                // This is done via the tmw_sanitize_tier_data filter on the server side
            });

            // When opening tier modal for editing, fetch and populate Stripe data
            $(document).on('click', '.tmw-edit-tier', function() {
                var slug = $(this).closest('tr').data('slug');
                
                // Apply toggle after modal opens
                setTimeout(function() {
                    toggleStripeFields();
                    
                    // Fetch tier data including Stripe fields
                    $.post(ajaxurl, {
                        action: 'tmw_get_tier_full_data',
                        nonce: typeof tmw_admin !== 'undefined' ? tmw_admin.nonce : $('input[name="tmw_nonce"]').val(),
                        slug: slug
                    }, function(response) {
                        if (response.success && response.data) {
                            var tier = response.data;
                            // Populate pricing fields
                            $('#tier-price-monthly').val(tier.price_monthly || 0);
                            $('#tier-price-yearly').val(tier.price_yearly || 0);
                            // Populate Stripe fields
                            $('#tier-stripe-price-monthly').val(tier.stripe_price_id_monthly || '');
                            $('#tier-stripe-price-yearly').val(tier.stripe_price_id_yearly || '');
                            $('#tier-stripe-product-id').val(tier.stripe_product_id || '');
                        }
                    });
                }, 100);
            });

            // When opening tier modal for adding, clear Stripe fields
            $(document).on('click', '#tmw-add-tier', function() {
                setTimeout(function() {
                    toggleStripeFields();
                    // Clear pricing fields
                    $('#tier-price-monthly').val(0);
                    $('#tier-price-yearly').val(0);
                    // Clear Stripe fields
                    $('#tier-stripe-price-monthly').val('');
                    $('#tier-stripe-price-yearly').val('');
                    $('#tier-stripe-product-id').val('');
                }, 100);
            });

            // Extend the save tier data to include our fields
            // We do this by hooking into the form submission
            $(document).ajaxSend(function(event, jqxhr, settings) {
                if (settings.data && settings.data.indexOf('action=tmw_save_tier') !== -1) {
                    // Add our fields to the data
                    var extraData = '&data[price_monthly]=' + encodeURIComponent($('#tier-price-monthly').val() || 0);
                    extraData += '&data[price_yearly]=' + encodeURIComponent($('#tier-price-yearly').val() || 0);
                    extraData += '&data[stripe_price_id_monthly]=' + encodeURIComponent($('#tier-stripe-price-monthly').val() || '');
                    extraData += '&data[stripe_price_id_yearly]=' + encodeURIComponent($('#tier-stripe-price-yearly').val() || '');
                    extraData += '&data[stripe_product_id]=' + encodeURIComponent($('#tier-stripe-product-id').val() || '');
                    settings.data += extraData;
                }
            });
        });
        </script>
        <?php
    }
}

/**
 * AJAX handler to get full tier data including Stripe fields
 */
add_action('wp_ajax_tmw_get_tier_full_data', function() {
    check_ajax_referer('tmw_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $slug = sanitize_key($_POST['slug'] ?? '');

    if (empty($slug) || !function_exists('tmw_get_tier')) {
        wp_send_json_error('Invalid tier');
    }

    $tier = tmw_get_tier($slug);

    if (!$tier) {
        wp_send_json_error('Tier not found');
    }

    wp_send_json_success(array(
        'name'                    => $tier['name'] ?? '',
        'description'             => $tier['description'] ?? '',
        'swpm_level_id'           => $tier['swpm_level_id'] ?? 0,
        'is_free'                 => !empty($tier['is_free']),
        'order'                   => $tier['order'] ?? 1,
        'color'                   => $tier['color'] ?? '#6b7280',
        'price_monthly'           => $tier['price_monthly'] ?? 0,
        'price_yearly'            => $tier['price_yearly'] ?? 0,
        'stripe_price_id_monthly' => $tier['stripe_price_id_monthly'] ?? '',
        'stripe_price_id_yearly'  => $tier['stripe_price_id_yearly'] ?? '',
        'stripe_product_id'       => $tier['stripe_product_id'] ?? '',
    ));
});

/**
 * AJAX handler to get tier Stripe data (legacy)
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
 * Extend tier sanitization to include pricing and Stripe fields
 */
add_filter('tmw_sanitize_tier_data', function($data, $raw_data = null, $slug = '') {
    // Get data from POST if not passed
    $post_data = $_POST['data'] ?? array();
    
    // Pricing fields
    $data['price_monthly'] = isset($post_data['price_monthly']) 
        ? floatval($post_data['price_monthly']) 
        : 0;
    $data['price_yearly'] = isset($post_data['price_yearly']) 
        ? floatval($post_data['price_yearly']) 
        : 0;
    
    // Stripe fields
    $data['stripe_price_id_monthly'] = isset($post_data['stripe_price_id_monthly']) 
        ? sanitize_text_field($post_data['stripe_price_id_monthly']) 
        : '';
    $data['stripe_price_id_yearly'] = isset($post_data['stripe_price_id_yearly']) 
        ? sanitize_text_field($post_data['stripe_price_id_yearly']) 
        : '';
    $data['stripe_product_id'] = isset($post_data['stripe_product_id']) 
        ? sanitize_text_field($post_data['stripe_product_id']) 
        : '';
    
    return $data;
}, 10, 3);
