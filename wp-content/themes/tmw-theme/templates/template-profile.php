<?php
/**
 * Template Name: My Profile
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
$is_free = tmw_is_free_membership($user->ID);

// Get actual level name from Simple Membership
$level_name = tmw_get_swpm_level_name($user->ID);

get_header();
?>

<div class="tmw-profile-page">
    <div class="tmw-container tmw-container-narrow">
        
        <?php tmw_display_flash_messages(); ?>

        <!-- Profile Header -->
        <div class="tmw-profile-header">
            <div class="tmw-profile-avatar-wrap">
                <img src="<?php echo esc_url(get_avatar_url($user->ID, array('size' => 96))); ?>" 
                     alt="" class="tmw-profile-avatar">
            </div>
            <div class="tmw-profile-info">
                <h1><?php echo esc_html($user->display_name); ?></h1>
                <p class="tmw-profile-email"><?php echo esc_html($user->user_email); ?></p>
                <div class="tmw-profile-tier">
                    <?php echo tmw_get_tier_badge($subscription['tier']); ?>
                </div>
            </div>
        </div>

        <!-- Subscription Section -->
        <div class="tmw-profile-section">
            <h2 class="tmw-profile-section-title"><?php _e('Subscription', 'flavor-starter-flavor'); ?></h2>
            
            <div class="tmw-subscription-card">
                <div class="tmw-subscription-header">
                    <span class="tmw-subscription-tier">
                        <?php echo esc_html($level_name); ?>
                    </span>
                    <?php if (!$is_free) : ?>
                        <span class="tmw-subscription-status <?php echo $subscription['is_active'] ? 'active' : 'inactive'; ?>">
                            <i class="fas fa-<?php echo $subscription['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
                            <?php echo $subscription['is_active'] ? __('Active', 'flavor-starter-flavor') : __('Inactive', 'flavor-starter-flavor'); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="tmw-subscription-details">
                    <?php if (!$is_free && !empty($subscription['expiry_date'])) : ?>
                        <div class="tmw-subscription-detail">
                            <div class="tmw-subscription-detail-label"><?php _e('Renewal Date', 'flavor-starter-flavor'); ?></div>
                            <div><?php echo date_i18n(get_option('date_format'), strtotime($subscription['expiry_date'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="tmw-subscription-detail">
                        <div class="tmw-subscription-detail-label"><?php _e('Member Since', 'flavor-starter-flavor'); ?></div>
                        <div><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></div>
                    </div>
                </div>

                <div class="tmw-subscription-actions">
                    <?php if ($is_free) : ?>
                        <a href="<?php echo esc_url(tmw_get_page_url('subscription')); ?>" class="tmw-btn tmw-btn-primary">
                            <i class="fas fa-arrow-up"></i>
                            <?php _e('Upgrade Plan', 'flavor-starter-flavor'); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url(tmw_get_swpm_profile_url()); ?>" class="tmw-btn tmw-btn-secondary">
                            <?php _e('Manage Subscription', 'flavor-starter-flavor'); ?>
                        </a>
						<?php do_shortcode('[swpm_stripe_subscription_cancel_link]'); ?>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(tmw_get_app_url()); ?>" class="tmw-btn tmw-btn-ghost">
                        <i class="fas fa-rocket"></i>
                        <?php _e('Go to App', 'flavor-starter-flavor'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Profile Info Section -->
        <div class="tmw-profile-section">
            <h2 class="tmw-profile-section-title"><?php _e('Profile Information', 'flavor-starter-flavor'); ?></h2>
            
            <div class="tmw-card">
                <div class="tmw-card-body">
                    <form id="tmw-profile-form" method="post">
                        
                        <div class="tmw-form-row">
                            <?php tmw_field(array(
                                'name'        => 'first_name',
                                'label'       => __('First Name', 'flavor-starter-flavor'),
                                'type'        => 'text',
                                'value'       => $user->first_name,
                                'required'    => true,
                            )); ?>

                            <?php tmw_field(array(
                                'name'        => 'last_name',
                                'label'       => __('Last Name', 'flavor-starter-flavor'),
                                'type'        => 'text',
                                'value'       => $user->last_name,
                                'required'    => true,
                            )); ?>
                        </div>

                        <?php tmw_field(array(
                            'name'        => 'display_name',
                            'label'       => __('Display Name', 'flavor-starter-flavor'),
                            'type'        => 'text',
                            'value'       => $user->display_name,
                            'required'    => true,
                        )); ?>

                        <?php tmw_field(array(
                            'name'        => 'email',
                            'label'       => __('Email Address', 'flavor-starter-flavor'),
                            'type'        => 'email',
                            'value'       => $user->user_email,
                            'required'    => true,
                        )); ?>

                        <?php tmw_nonce_field('tmw_update_profile'); ?>

                        <?php tmw_button(array(
                            'text'  => __('Update Profile', 'flavor-starter-flavor'),
                            'type'  => 'submit',
                            'style' => 'primary',
                            'icon'  => 'fas fa-save',
                        )); ?>

                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password Section -->
        <div class="tmw-profile-section">
            <h2 class="tmw-profile-section-title"><?php _e('Change Password', 'flavor-starter-flavor'); ?></h2>
            
            <div class="tmw-card">
                <div class="tmw-card-body">
                    <form id="tmw-change-password-form" method="post">
                        
                        <?php tmw_field(array(
                            'name'        => 'current_password',
                            'label'       => __('Current Password', 'flavor-starter-flavor'),
                            'type'        => 'password',
                            'required'    => true,
                            'autocomplete' => 'current-password',
                        )); ?>

                        <?php tmw_field(array(
                            'name'        => 'new_password',
                            'label'       => __('New Password', 'flavor-starter-flavor'),
                            'type'        => 'password',
                            'required'    => true,
                            'autocomplete' => 'new-password',
                        )); ?>

                        <div class="tmw-password-strength">
                            <div class="tmw-password-strength-bar">
                                <div class="tmw-password-strength-fill"></div>
                            </div>
                        </div>

                        <?php tmw_field(array(
                            'name'        => 'confirm_password',
                            'label'       => __('Confirm New Password', 'flavor-starter-flavor'),
                            'type'        => 'password',
                            'required'    => true,
                            'autocomplete' => 'new-password',
                        )); ?>

                        <div class="tmw-password-match" style="display: none;"></div>

                        <?php tmw_nonce_field('tmw_change_password'); ?>

                        <?php tmw_button(array(
                            'text'  => __('Change Password', 'flavor-starter-flavor'),
                            'type'  => 'submit',
                            'style' => 'secondary',
                            'icon'  => 'fas fa-key',
                        )); ?>

                    </form>
                </div>
            </div>
        </div>

        <!-- Theme Preference Section -->
        <div class="tmw-profile-section">
            <h2 class="tmw-profile-section-title"><?php _e('Preferences', 'flavor-starter-flavor'); ?></h2>
            
            <div class="tmw-card">
                <div class="tmw-card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4><?php _e('Theme', 'flavor-starter-flavor'); ?></h4>
                            <p class="text-muted text-sm"><?php _e('Toggle between light and dark mode', 'flavor-starter-flavor'); ?></p>
                        </div>
                        <?php tmw_theme_toggle(); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logout -->
        <div class="tmw-profile-section">
            <a href="<?php echo esc_url(wp_logout_url(tmw_get_page_url('logout'))); ?>" 
               class="tmw-btn tmw-btn-danger tmw-btn-full">
                <i class="fas fa-sign-out-alt"></i>
                <?php _e('Sign Out', 'flavor-starter-flavor'); ?>
            </a>
        </div>

    </div>
</div>

<?php
get_footer();
