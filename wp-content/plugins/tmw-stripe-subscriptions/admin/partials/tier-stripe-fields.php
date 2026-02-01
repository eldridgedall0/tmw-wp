<?php
/**
 * Stripe Fields for Tier Modal
 *
 * These fields are conditionally shown/hidden based on the
 * membership_plugin setting in General Settings.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Stripe Price Fields (shown when membership_plugin = 'stripe') -->
<div class="tmw-stripe-tier-fields" id="tmw-stripe-tier-fields" style="display:none;">
    <hr style="margin:15px 0;border-color:#e5e7eb;">
    <h4 style="margin:0 0 10px;color:#6b7280;font-size:12px;text-transform:uppercase;">
        <?php _e('Stripe Configuration', 'tmw-stripe-subscriptions'); ?>
    </h4>
    
    <div class="tmw-field-row">
        <label for="tier-stripe-price-monthly"><?php _e('Monthly Price ID', 'tmw-stripe-subscriptions'); ?></label>
        <input type="text" id="tier-stripe-price-monthly" name="stripe_price_id_monthly" 
               class="widefat" placeholder="price_xxxxxxxxxxxxx">
        <p class="description"><?php _e('Stripe Price ID for monthly billing', 'tmw-stripe-subscriptions'); ?></p>
    </div>

    <div class="tmw-field-row">
        <label for="tier-stripe-price-yearly"><?php _e('Yearly Price ID', 'tmw-stripe-subscriptions'); ?></label>
        <input type="text" id="tier-stripe-price-yearly" name="stripe_price_id_yearly" 
               class="widefat" placeholder="price_yyyyyyyyyyyyy">
        <p class="description"><?php _e('Stripe Price ID for yearly billing (optional)', 'tmw-stripe-subscriptions'); ?></p>
    </div>

    <div class="tmw-field-row">
        <label for="tier-stripe-product-id"><?php _e('Product ID (Optional)', 'tmw-stripe-subscriptions'); ?></label>
        <input type="text" id="tier-stripe-product-id" name="stripe_product_id" 
               class="widefat" placeholder="prod_zzzzzzzzzzzzz">
        <p class="description"><?php _e('Stripe Product ID for reference', 'tmw-stripe-subscriptions'); ?></p>
    </div>
</div>

<style>
.tmw-stripe-tier-fields {
    background: #f9fafb;
    padding: 15px;
    margin: 15px -15px -15px;
    border-top: 1px solid #e5e7eb;
}
.tmw-stripe-tier-fields .tmw-field-row {
    margin-bottom: 12px;
}
.tmw-stripe-tier-fields .tmw-field-row:last-child {
    margin-bottom: 0;
}
.tmw-stripe-tier-fields label {
    display: block;
    font-weight: 500;
    margin-bottom: 4px;
}
.tmw-stripe-tier-fields .description {
    font-size: 11px;
    color: #6b7280;
    margin-top: 4px;
}
</style>
