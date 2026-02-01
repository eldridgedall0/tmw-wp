<?php
/**
 * Subscribers Management Page
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin = new TMW_Stripe_Admin();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Subscribers', 'tmw-stripe-subscriptions'); ?></h1>
    
    <?php if (TMW_Stripe_API::is_configured()) : ?>
        <a href="<?php echo esc_url($admin->get_stripe_dashboard_url()); ?>" target="_blank" class="page-title-action">
            <?php _e('View in Stripe', 'tmw-stripe-subscriptions'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php
    // Show any admin notices
    settings_errors('tmw_stripe_subscribers');
    ?>

    <div class="tmw-subscribers-wrap">
        <?php $subscribers->views(); ?>

        <form method="get">
            <input type="hidden" name="page" value="tmw-stripe-subscribers">
            <?php
            $subscribers->search_box(__('Search', 'tmw-stripe-subscriptions'), 'subscriber');
            $subscribers->display();
            ?>
        </form>
    </div>

    <div class="tmw-subscribers-help" style="margin-top:20px;padding:15px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;">
        <h3 style="margin-top:0;"><?php _e('Status Definitions', 'tmw-stripe-subscriptions'); ?></h3>
        <ul style="margin-bottom:0;">
            <li><span style="color:#22c55e;">● Active</span> - <?php _e('Subscription is current and paid', 'tmw-stripe-subscriptions'); ?></li>
            <li><span style="color:#3b82f6;">● Trialing</span> - <?php _e('User is in free trial period', 'tmw-stripe-subscriptions'); ?></li>
            <li><span style="color:#f59e0b;">● Past Due</span> - <?php _e('Payment failed, retrying', 'tmw-stripe-subscriptions'); ?></li>
            <li><span style="color:#ef4444;">● Canceled</span> - <?php _e('User canceled, access until period end', 'tmw-stripe-subscriptions'); ?></li>
            <li><span style="color:#6b7280;">● Inactive</span> - <?php _e('No active subscription (free tier)', 'tmw-stripe-subscriptions'); ?></li>
        </ul>
    </div>
</div>
