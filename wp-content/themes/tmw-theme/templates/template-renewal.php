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
$is_free = tmw_is_free_membership($user->ID);
$level_name = tmw_get_swpm_level_name($user->ID);
$swpm_profile_url = tmw_get_swpm_profile_url();

// Get level IDs from settings
$paid_level_id = tmw_get_level_mapping('paid_level_id', 2);
$fleet_level_id = tmw_get_level_mapping('fleet_level_id', 3);

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
                <?php echo esc_html($level_name); ?>
            </span>
            <?php echo tmw_get_tier_badge($current_tier); ?>
        </div>

        <?php if (!$is_free) : ?>
            <div class="tmw-subscription-details">
                <div class="tmw-subscription-detail">
                    <div class="tmw-subscription-detail-label"><?php _e('Status', 'flavor-starter-flavor'); ?></div>
                    <div>
                        <span class="tmw-subscription-status <?php echo $subscription['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $subscription['is_active'] ? __('Active', 'flavor-starter-flavor') : __('Inactive', 'flavor-starter-flavor'); ?>
                        </span>
                    </div>
                </div>
                
                <?php if (!empty($subscription['expiry_date'])) : ?>
                <div class="tmw-subscription-detail">
                    <div class="tmw-subscription-detail-label"><?php _e('Next Billing Date', 'flavor-starter-flavor'); ?></div>
                    <div><?php echo date_i18n(get_option('date_format'), strtotime($subscription['expiry_date'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($is_free) : ?>
        
        <!-- Upgrade Options for Free Users -->
        <h2 class="h4 mb-4"><?php _e('Upgrade Your Plan', 'flavor-starter-flavor'); ?></h2>
        <p class="text-muted mb-6"><?php _e('Choose a plan below to unlock more features.', 'flavor-starter-flavor'); ?></p>
        
        <div class="tmw-grid tmw-grid-2">
            
            <div class="tmw-card">
                <div class="tmw-card-body">
                    <h3 class="h5 mb-2"><?php _e('Paid Plan', 'flavor-starter-flavor'); ?></h3>
                    <div class="tmw-renewal-option-price mb-3">
                        <span class="tmw-renewal-option-amount">$9</span>
                        <span class="tmw-renewal-option-period"><?php _e('/month', 'flavor-starter-flavor'); ?></span>
                    </div>
                    <p class="text-muted text-sm mb-4"><?php _e('10 vehicles, unlimited entries, recalls, exports', 'flavor-starter-flavor'); ?></p>
                    <?php if (tmw_is_stripe_active()) : ?>
                        <button type="button" 
                                class="tmw-btn tmw-btn-primary tmw-btn-full tmw-subscribe-btn" 
                                data-tier="paid"
                                data-period="monthly">
                            <?php _e('Subscribe Now', 'flavor-starter-flavor'); ?>
                        </button>
                    <?php else : ?>
                        <a href="<?php echo esc_url(tmw_get_swpm_join_url($paid_level_id)); ?>" class="tmw-btn tmw-btn-primary tmw-btn-full">
                            <?php _e('Subscribe Now', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tmw-card">
                <div class="tmw-card-body">
                    <h3 class="h5 mb-2"><?php _e('Fleet Plan', 'flavor-starter-flavor'); ?></h3>
                    <div class="tmw-renewal-option-price mb-3">
                        <span class="tmw-renewal-option-amount">$29</span>
                        <span class="tmw-renewal-option-period"><?php _e('/month', 'flavor-starter-flavor'); ?></span>
                    </div>
                    <p class="text-muted text-sm mb-4"><?php _e('Unlimited everything, API access, team members', 'flavor-starter-flavor'); ?></p>
                    <?php if (tmw_is_stripe_active()) : ?>
                        <button type="button" 
                                class="tmw-btn tmw-btn-secondary tmw-btn-full tmw-subscribe-btn" 
                                data-tier="fleet"
                                data-period="monthly">
                            <?php _e('Go Fleet', 'flavor-starter-flavor'); ?>
                        </button>
                    <?php else : ?>
                        <a href="<?php echo esc_url(tmw_get_swpm_join_url($fleet_level_id)); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-full">
                            <?php _e('Go Fleet', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    <?php else : ?>

        <!-- Manage Subscription for Paid Users -->
        <h2 class="h4 mb-4"><?php _e('Subscription Options', 'flavor-starter-flavor'); ?></h2>

        <div class="tmw-grid tmw-grid-2">
            
            <div class="tmw-card">
                <div class="tmw-card-body">
                    <h3 class="h5 mb-2"><i class="fas fa-user-cog text-accent"></i> <?php _e('Account Settings', 'flavor-starter-flavor'); ?></h3>
                    <p class="text-muted text-sm mb-4"><?php _e('Update your profile and membership details', 'flavor-starter-flavor'); ?></p>
                    <?php if (tmw_is_stripe_active()) : ?>
                        <button type="button" class="tmw-btn tmw-btn-secondary tmw-btn-small tmw-stripe-portal-btn">
                            <?php _e('Manage Account', 'flavor-starter-flavor'); ?>
                        </button>
                    <?php else : ?>
                        <a href="<?php echo esc_url($swpm_profile_url); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-small">
                            <?php _e('Manage Account', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($current_tier === 'paid') : ?>
            <div class="tmw-card">
                <div class="tmw-card-body">
                    <h3 class="h5 mb-2"><i class="fas fa-arrow-up text-success"></i> <?php _e('Upgrade to Fleet', 'flavor-starter-flavor'); ?></h3>
                    <p class="text-muted text-sm mb-4"><?php _e('Get unlimited vehicles and API access', 'flavor-starter-flavor'); ?></p>
                    <?php if (tmw_is_stripe_active()) : ?>
                        <button type="button" class="tmw-btn tmw-btn-primary tmw-btn-small tmw-stripe-portal-btn">
                            <?php _e('Upgrade', 'flavor-starter-flavor'); ?>
                        </button>
                    <?php else : ?>
                        <a href="<?php echo esc_url(tmw_get_swpm_join_url($fleet_level_id)); ?>" class="tmw-btn tmw-btn-primary tmw-btn-small">
                            <?php _e('Upgrade', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="tmw-card">
                <div class="tmw-card-body">
                    <h3 class="h5 mb-2"><i class="fas fa-credit-card text-accent"></i> <?php _e('Billing & Payments', 'flavor-starter-flavor'); ?></h3>
                    <p class="text-muted text-sm mb-4"><?php _e('View invoices and update payment method', 'flavor-starter-flavor'); ?></p>
                    <?php if (tmw_is_stripe_active()) : ?>
                        <button type="button" class="tmw-btn tmw-btn-secondary tmw-btn-small tmw-stripe-portal-btn">
                            <?php _e('View Billing', 'flavor-starter-flavor'); ?>
                        </button>
                    <?php else : ?>
                        <a href="<?php echo esc_url($swpm_profile_url); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-small">
                            <?php _e('View Billing', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tmw-card">
                <div class="tmw-card-body">
                    <h3 class="h5 mb-2"><i class="fas fa-times-circle text-error"></i> <?php _e('Cancel Subscription', 'flavor-starter-flavor'); ?></h3>
                    <p class="text-muted text-sm mb-4"><?php _e('Your data will be kept if you resubscribe', 'flavor-starter-flavor'); ?></p>
                    <?php if (tmw_is_stripe_active()) : ?>
                        <button type="button" class="tmw-btn tmw-btn-danger tmw-btn-small tmw-stripe-portal-btn">
                            <?php _e('Cancel Plan', 'flavor-starter-flavor'); ?>
                        </button>
                    <?php else : ?>
                        <a href="<?php echo esc_url($swpm_profile_url); ?>" class="tmw-btn tmw-btn-danger tmw-btn-small">
                            <?php _e('Cancel Plan', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php endif; ?>
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