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

// Get current membership plugin setting
$membership_plugin = '';
if (function_exists('tmw_get_setting')) {
    $membership_plugin = tmw_get_setting('membership_plugin', 'simple-membership');
}

$show_stripe = ($membership_plugin === 'stripe');
?>

<!-- Pricing Fields (always shown) -->
<tr class="tmw-tier-pricing-fields">
    <th><label for="tier-price-monthly"><?php _e('Monthly Price ($)', 'tmw-stripe-subscriptions'); ?></label></th>
    <td>
        <input type="number" id="tier-price-monthly" class="small-text" min="0" step="0.01" value="0">
        <p class="description"><?php _e('Display price for monthly billing', 'tmw-stripe-subscriptions'); ?></p>
    </td>
</tr>
<tr class="tmw-tier-pricing-fields">
    <th><label for="tier-price-yearly"><?php _e('Yearly Price ($)', 'tmw-stripe-subscriptions'); ?></label></th>
    <td>
        <input type="number" id="tier-price-yearly" class="small-text" min="0" step="0.01" value="0">
        <p class="description"><?php _e('Display price for yearly billing', 'tmw-stripe-subscriptions'); ?></p>
    </td>
</tr>

<!-- Stripe Price ID Fields (shown when membership_plugin = 'stripe') -->
<tr class="tmw-stripe-tier-field" style="<?php echo $show_stripe ? '' : 'display:none;'; ?>">
    <th><label for="tier-stripe-price-monthly"><?php _e('Stripe Monthly Price ID', 'tmw-stripe-subscriptions'); ?></label></th>
    <td>
        <input type="text" id="tier-stripe-price-monthly" class="regular-text" placeholder="price_xxxxxxxxxxxxx">
        <p class="description"><?php _e('From Stripe Dashboard → Products → Price ID', 'tmw-stripe-subscriptions'); ?></p>
    </td>
</tr>
<tr class="tmw-stripe-tier-field" style="<?php echo $show_stripe ? '' : 'display:none;'; ?>">
    <th><label for="tier-stripe-price-yearly"><?php _e('Stripe Yearly Price ID', 'tmw-stripe-subscriptions'); ?></label></th>
    <td>
        <input type="text" id="tier-stripe-price-yearly" class="regular-text" placeholder="price_yyyyyyyyyyyyy">
        <p class="description"><?php _e('Optional - for yearly billing discount', 'tmw-stripe-subscriptions'); ?></p>
    </td>
</tr>
<tr class="tmw-stripe-tier-field" style="<?php echo $show_stripe ? '' : 'display:none;'; ?>">
    <th><label for="tier-stripe-product-id"><?php _e('Stripe Product ID', 'tmw-stripe-subscriptions'); ?></label></th>
    <td>
        <input type="text" id="tier-stripe-product-id" class="regular-text" placeholder="prod_zzzzzzzzzzzzz">
        <p class="description"><?php _e('Optional - for reference only', 'tmw-stripe-subscriptions'); ?></p>
    </td>
</tr>
