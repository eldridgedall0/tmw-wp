<?php
/**
 * Template Name: Membership Renewal
 * Template Post Type: page
 *
 * @package flavor-starter-flavor
 */

// Require login
if (!is_user_logged_in()) {
    wp_redirect(tmw_get_page_url('login'));
    exit;
}

$user = wp_get_current_user();
$subscription = tmw_get_user_subscription_data($user->ID);
$current_tier = $subscription['tier'];

get_header();
?>

<div class="tmw-renewal-page">
    
    <header class="tmw-renewal-header">
        <h1><?php _e('Manage Your Subscription', 'flavor-starter-flavor'); ?></h1>
        <p class="text-muted">
            <?php _e('Review your current plan, update payment methods, or change your subscription.', 'flavor-starter-flavor'); ?>
        </p>
    </header>

    <?php tmw_display_flash_messages(); ?>

    <!-- Current Subscription -->
    <div class="tmw-renewal-current">
        <div class="tmw-renewal-current-header">
            <span class="tmw-renewal-current-tier">
                <?php echo esc_html(tmw_get_tier_name($current_tier)); ?> <?php _e('Plan', 'flavor-starter-flavor'); ?>
            </span>
            <?php echo tmw_get_tier_badge($current_tier); ?>
        </div>

        <?php if ($current_tier !== 'free') : ?>
            <div class="tmw-subscription-details">
                <div class="tmw-subscription-detail">
                    <div class="tmw-subscription-detail-label"><?php _e('Status', 'flavor-starter-flavor'); ?></div>
                    <div>
                        <span class="tmw-subscription-status <?php echo $subscription['is_active'] ? '' : 'inactive'; ?>">
                            <?php echo $subscription['is_active'] ? __('Active', 'flavor-starter-flavor') : __('Inactive', 'flavor-starter-flavor'); ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($subscription['expiry_date']) : ?>
                <div class="tmw-subscription-detail">
                    <div class="tmw-subscription-detail-label"><?php _e('Next Billing Date', 'flavor-starter-flavor'); ?></div>
                    <div><?php echo date_i18n(get_option('date_format'), strtotime($subscription['expiry_date'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($current_tier === 'free') : ?>
        
        <!-- Upgrade Options -->
        <h2 class="h4 mb-4"><?php _e('Upgrade Your Plan', 'flavor-starter-flavor'); ?></h2>
        
        <div class="tmw-renewal-options">
            
            <label class="tmw-renewal-option">
                <input type="radio" name="plan" value="paid_monthly">
                <div class="tmw-renewal-option-content">
                    <div class="tmw-renewal-option-name"><?php _e('Paid - Monthly', 'flavor-starter-flavor'); ?></div>
                    <div class="tmw-renewal-option-desc"><?php _e('10 vehicles, unlimited entries, recalls, exports', 'flavor-starter-flavor'); ?></div>
                </div>
                <div class="tmw-renewal-option-price">
                    <div class="tmw-renewal-option-amount">$9</div>
                    <div class="tmw-renewal-option-period"><?php _e('/month', 'flavor-starter-flavor'); ?></div>
                </div>
            </label>

            <label class="tmw-renewal-option">
                <input type="radio" name="plan" value="paid_yearly">
                <div class="tmw-renewal-option-content">
                    <div class="tmw-renewal-option-name"><?php _e('Paid - Yearly', 'flavor-starter-flavor'); ?></div>
                    <div class="tmw-renewal-option-desc"><?php _e('Same as monthly, save 20%', 'flavor-starter-flavor'); ?></div>
                </div>
                <div class="tmw-renewal-option-price">
                    <div class="tmw-renewal-option-amount">$86</div>
                    <div class="tmw-renewal-option-period"><?php _e('/year', 'flavor-starter-flavor'); ?></div>
                </div>
            </label>

            <label class="tmw-renewal-option">
                <input type="radio" name="plan" value="fleet_monthly">
                <div class="tmw-renewal-option-content">
                    <div class="tmw-renewal-option-name"><?php _e('Fleet - Monthly', 'flavor-starter-flavor'); ?></div>
                    <div class="tmw-renewal-option-desc"><?php _e('Unlimited everything, API access, team members', 'flavor-starter-flavor'); ?></div>
                </div>
                <div class="tmw-renewal-option-price">
                    <div class="tmw-renewal-option-amount">$29</div>
                    <div class="tmw-renewal-option-period"><?php _e('/month', 'flavor-starter-flavor'); ?></div>
                </div>
            </label>

            <label class="tmw-renewal-option">
                <input type="radio" name="plan" value="fleet_yearly">
                <div class="tmw-renewal-option-content">
                    <div class="tmw-renewal-option-name"><?php _e('Fleet - Yearly', 'flavor-starter-flavor'); ?></div>
                    <div class="tmw-renewal-option-desc"><?php _e('Same as monthly, save 20%', 'flavor-starter-flavor'); ?></div>
                </div>
                <div class="tmw-renewal-option-price">
                    <div class="tmw-renewal-option-amount">$278</div>
                    <div class="tmw-renewal-option-period"><?php _e('/year', 'flavor-starter-flavor'); ?></div>
                </div>
            </label>

        </div>

        <div class="mt-6">
            <a href="<?php echo esc_url(home_url('/membership-join/')); ?>" class="tmw-btn tmw-btn-primary tmw-btn-large">
                <?php _e('Continue to Checkout', 'flavor-starter-flavor'); ?>
            </a>
        </div>

    <?php else : ?>

        <!-- Manage Subscription -->
        <h2 class="h4 mb-4"><?php _e('Subscription Options', 'flavor-starter-flavor'); ?></h2>

        <div class="tmw-grid tmw-grid-2">
            
            <div class="tmw-card">
                <div class="tmw-card-body">
                    <h3 class="h5 mb-2"><i class="fas fa-credit-card text-accent"></i> <?php _e('Payment Method', 'flavor-starter-flavor'); ?></h3>
                    <p class="text-muted text-sm mb-4"><?php _e('Update your payment information', 'flavor-starter-flavor'); ?></p>
                    <a href="<?php echo esc_url(home_url('/membership-account/')); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-small">
                        <?php _e('Update Payment', 'flavor-starter-flavor'); ?>
                    </a>
                </div>
            </div>

            <div class="tmw-card">
                <div class="tmw-card-body">
                    <h3 class="h5 mb-2"><i class="fas fa-file-invoice text-accent"></i> <?php _e('Billing History', 'flavor-starter-flavor'); ?></h3>
                    <p class="text-muted text-sm mb-4"><?php _e('View past invoices and receipts', 'flavor-starter-flavor'); ?></p>
                    <a href="<?php echo esc_url(home_url('/membership-account/')); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-small">
                        <?php _e('View History', 'flavor-starter-flavor'); ?>
                    </a>
                </div>
            </div>

            <?php if ($current_tier === 'paid') : ?>
            <div class="tmw-card">
                <div class="tmw-card-body">
                    <h3 class="h5 mb-2"><i class="fas fa-arrow-up text-success"></i> <?php _e('Upgrade to Fleet', 'flavor-starter-flavor'); ?></h3>
                    <p class="text-muted text-sm mb-4"><?php _e('Get unlimited vehicles and API access', 'flavor-starter-flavor'); ?></p>
                    <a href="<?php echo esc_url(home_url('/membership-join/?level=3')); ?>" class="tmw-btn tmw-btn-primary tmw-btn-small">
                        <?php _e('Upgrade', 'flavor-starter-flavor'); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="tmw-card">
                <div class="tmw-card-body">
                    <h3 class="h5 mb-2"><i class="fas fa-times-circle text-error"></i> <?php _e('Cancel Subscription', 'flavor-starter-flavor'); ?></h3>
                    <p class="text-muted text-sm mb-4"><?php _e('Your data will be kept if you resubscribe', 'flavor-starter-flavor'); ?></p>
                    <a href="<?php echo esc_url(home_url('/membership-account/')); ?>" class="tmw-btn tmw-btn-danger tmw-btn-small">
                        <?php _e('Cancel', 'flavor-starter-flavor'); ?>
                    </a>
                </div>
            </div>

        </div>

    <?php endif; ?>

    <!-- Back to Profile -->
    <div class="mt-8 text-center">
        <a href="<?php echo esc_url(tmw_get_page_url('my-profile')); ?>" class="text-muted">
            <i class="fas fa-arrow-left"></i>
            <?php _e('Back to Profile', 'flavor-starter-flavor'); ?>
        </a>
    </div>

</div>

<?php
get_footer();
