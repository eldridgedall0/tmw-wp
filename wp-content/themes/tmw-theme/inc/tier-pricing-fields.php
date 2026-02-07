<?php
/**
 * TMW Tier Pricing & Stripe Fields Extension
 * 
 * This file adds pricing and Stripe configuration fields to the tier modal
 * WITHOUT modifying the core admin-settings.php save function.
 * 
 * Data is stored in a separate option: tmw_tier_pricing
 * 
 * Add this to your theme's functions.php:
 * require_once get_template_directory() . '/inc/tier-pricing-fields.php';
 * 
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get pricing data for all tiers
 */
function tmw_get_tier_pricing() {
    return get_option('tmw_tier_pricing', array());
}

/**
 * Get pricing data for a specific tier
 */
function tmw_get_tier_price($tier_slug, $field = null) {
    $pricing = tmw_get_tier_pricing();
    $tier_data = $pricing[$tier_slug] ?? array();
    
    if ($field) {
        return $tier_data[$field] ?? '';
    }
    
    return $tier_data;
}

/**
 * Render additional fields in the tier modal
 * Hooks into: tmw_tier_modal_fields (if it exists) or admin_footer
 */
function tmw_render_tier_pricing_fields() {
    
    // Get all pricing data for JavaScript
    $pricing_data = tmw_get_tier_pricing();
    ?>
    
    <!-- Pricing Fields - Added via tier-pricing-fields.php -->
    <tr class="tmw-pricing-field">
        <th colspan="2" style="padding-bottom:0;">
            <h4 style="margin:0;border-top:1px solid #ddd;padding-top:15px;">Pricing</h4>
        </th>
    </tr>
    <tr class="tmw-pricing-field">
        <th><label for="tier-price-monthly">Monthly Price ($)</label></th>
        <td>
            <input type="number" id="tier-price-monthly" class="small-text" min="0" step="0.01" value="0">
            <p class="description">Display price for pricing page</p>
        </td>
    </tr>
    <tr class="tmw-pricing-field">
        <th><label for="tier-price-yearly">Yearly Price ($)</label></th>
        <td>
            <input type="number" id="tier-price-yearly" class="small-text" min="0" step="0.01" value="0">
            <p class="description">Display price for yearly billing</p>
        </td>
    </tr>
    
    <!-- Stripe Fields -->
    <?php
                // Stripe fields - only show if Stripe is selected as membership plugin
                $membership_plugin = tmw_get_setting('membership_plugin', 'simple-membership');
                $show_stripe = ($membership_plugin === 'stripe');
                ?>
                <tr class="tmw-stripe-field" style="<?php echo $show_stripe ? '' : 'display:none;'; ?>">
                    <th colspan="2" style="padding-bottom:0;"><h4 style="margin:0;border-top:1px solid #ddd;padding-top:15px;"><?php _e('Stripe Configuration', 'flavor-starter-flavor'); ?></h4></th>
                </tr>
                <tr class="tmw-stripe-field" style="<?php echo $show_stripe ? '' : 'display:none;'; ?>">
                    <th><label for="tier-stripe-price-monthly"><?php _e('Stripe Monthly Price ID', 'flavor-starter-flavor'); ?></label></th>
                    <td><input type="text" id="tier-stripe-price-monthly" class="regular-text" placeholder="price_xxxxxxxxxxxxx"><p class="description"><?php _e('From Stripe Dashboard → Products → Price ID', 'flavor-starter-flavor'); ?></p></td>
                </tr>
                <tr class="tmw-stripe-field" style="<?php echo $show_stripe ? '' : 'display:none;'; ?>">
                    <th><label for="tier-stripe-price-yearly"><?php _e('Stripe Yearly Price ID', 'flavor-starter-flavor'); ?></label></th>
                    <td><input type="text" id="tier-stripe-price-yearly" class="regular-text" placeholder="price_yyyyyyyyyyyyy"><p class="description"><?php _e('Optional - for yearly billing', 'flavor-starter-flavor'); ?></p></td>
                </tr>
                <tr class="tmw-stripe-field" style="<?php echo $show_stripe ? '' : 'display:none;'; ?>">
                    <th><label for="tier-stripe-product-id"><?php _e('Stripe Product ID', 'flavor-starter-flavor'); ?></label></th>
                    <td><input type="text" id="tier-stripe-product-id" class="regular-text" placeholder="prod_zzzzzzzzzzzzz"><p class="description"><?php _e('Optional - for reference', 'flavor-starter-flavor'); ?></p></td>
                </tr>
    
    <script>
    // Store pricing data for populating fields
    var tmwTierPricing = <?php echo json_encode($pricing_data); ?>;
    </script>
    <?php
}
add_action('tmw_tier_modal_fields', 'tmw_render_tier_pricing_fields');

/**
 * Add JavaScript to handle the pricing fields
 */
function tmw_tier_pricing_admin_scripts() {
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'tmw-settings') === false) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Pricing data from PHP
        var pricingData = typeof tmwTierPricing !== 'undefined' ? tmwTierPricing : {};
        
        // Toggle Stripe fields based on membership plugin
        function toggleStripeFields() {
            var plugin = $('#membership_plugin').val();
            if (plugin === 'stripe') {
                $('.tmw-stripe-field').show();
            } else {
                $('.tmw-stripe-field').hide();
            }
        }
        
        // Initial toggle
        toggleStripeFields();
        $('#membership_plugin').on('change', toggleStripeFields);
        
        // When "Add New Tier" is clicked - clear pricing fields
        $('#tmw-add-tier').on('click', function() {
            setTimeout(function() {
                $('#tier-price-monthly').val(0);
                $('#tier-price-yearly').val(0);
                $('#tier-stripe-price-monthly').val('');
                $('#tier-stripe-price-yearly').val('');
                $('#tier-stripe-product-id').val('');
            }, 50);
        });
        
        // When "Edit" is clicked - populate pricing fields from our data
        $(document).on('click', '.tmw-edit-tier', function() {
            var slug = $(this).closest('tr').data('slug');
            var tierPricing = pricingData[slug] || {};
            
            setTimeout(function() {
                $('#tier-price-monthly').val(tierPricing.price_monthly || 0);
                $('#tier-price-yearly').val(tierPricing.price_yearly || 0);
                $('#tier-stripe-price-monthly').val(tierPricing.stripe_price_id_monthly || '');
                $('#tier-stripe-price-yearly').val(tierPricing.stripe_price_id_yearly || '');
                $('#tier-stripe-product-id').val(tierPricing.stripe_product_id || '');
            }, 50);
        });
        
        // When tier is saved - also save our pricing data via separate AJAX
        $('#tmw-save-tier').on('click', function() {
            var slug = $('#tier-slug').val().toLowerCase().replace(/[^a-z0-9_-]/g, '');
            if (!slug) return;
            
            // Save pricing data separately
            $.post(ajaxurl, {
                action: 'tmw_save_tier_pricing',
                nonce: '<?php echo wp_create_nonce('tmw_tier_pricing_nonce'); ?>',
                slug: slug,
                price_monthly: $('#tier-price-monthly').val(),
                price_yearly: $('#tier-price-yearly').val(),
                stripe_price_id_monthly: $('#tier-stripe-price-monthly').val(),
                stripe_price_id_yearly: $('#tier-stripe-price-yearly').val(),
                stripe_product_id: $('#tier-stripe-product-id').val()
            });
            // Note: The main tier save will trigger page reload
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'tmw_tier_pricing_admin_scripts');

/**
 * AJAX handler to save tier pricing data
 */
function tmw_ajax_save_tier_pricing() {
    check_ajax_referer('tmw_tier_pricing_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    $slug = sanitize_key($_POST['slug'] ?? '');
    if (empty($slug)) {
        wp_send_json_error('No tier slug');
    }
    
    $pricing = tmw_get_tier_pricing();
    
    $pricing[$slug] = array(
        'price_monthly'           => floatval($_POST['price_monthly'] ?? 0),
        'price_yearly'            => floatval($_POST['price_yearly'] ?? 0),
        'stripe_price_id_monthly' => sanitize_text_field($_POST['stripe_price_id_monthly'] ?? ''),
        'stripe_price_id_yearly'  => sanitize_text_field($_POST['stripe_price_id_yearly'] ?? ''),
        'stripe_product_id'       => sanitize_text_field($_POST['stripe_product_id'] ?? ''),
    );
    
    update_option('tmw_tier_pricing', $pricing);
    
    wp_send_json_success(array('slug' => $slug, 'pricing' => $pricing[$slug]));
}
add_action('wp_ajax_tmw_save_tier_pricing', 'tmw_ajax_save_tier_pricing');

/**
 * Helper function to get tier with pricing merged
 * Use this instead of tmw_get_tier() when you need pricing data
 */
function tmw_get_tier_with_pricing($tier_slug) {
    if (!function_exists('tmw_get_tier')) {
        return null;
    }
    
    $tier = tmw_get_tier($tier_slug);
    if (!$tier) {
        return null;
    }
    
    $pricing = tmw_get_tier_price($tier_slug);
    
    return array_merge($tier, array(
        'price_monthly'           => $pricing['price_monthly'] ?? 0,
        'price_yearly'            => $pricing['price_yearly'] ?? 0,
        'stripe_price_id_monthly' => $pricing['stripe_price_id_monthly'] ?? '',
        'stripe_price_id_yearly'  => $pricing['stripe_price_id_yearly'] ?? '',
        'stripe_product_id'       => $pricing['stripe_product_id'] ?? '',
    ));
}

/**
 * Helper to get all tiers with pricing merged
 */
function tmw_get_tiers_with_pricing() {
    if (!function_exists('tmw_get_tiers')) {
        return array();
    }
    
    $tiers = tmw_get_tiers();
    $pricing = tmw_get_tier_pricing();
    
    foreach ($tiers as $slug => &$tier) {
        $tier_pricing = $pricing[$slug] ?? array();
        $tier['price_monthly'] = $tier_pricing['price_monthly'] ?? 0;
        $tier['price_yearly'] = $tier_pricing['price_yearly'] ?? 0;
        $tier['stripe_price_id_monthly'] = $tier_pricing['stripe_price_id_monthly'] ?? '';
        $tier['stripe_price_id_yearly'] = $tier_pricing['stripe_price_id_yearly'] ?? '';
        $tier['stripe_product_id'] = $tier_pricing['stripe_product_id'] ?? '';
    }
    
    return $tiers;
}
