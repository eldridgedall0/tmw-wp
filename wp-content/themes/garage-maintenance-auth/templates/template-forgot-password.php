<?php
/**
 * Template Name: Forgot Password
 * Template Post Type: page
 *
 * @package flavor-starter-flavor
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(tmw_get_app_url());
    exit;
}

get_header();
?>

<div class="tmw-auth-page">
    <div class="tmw-auth-container">
        <div class="tmw-auth-card">
            
            <div class="tmw-auth-header">
                <div class="tmw-auth-logo">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo.png'); ?>" alt="<?php bloginfo('name'); ?>">
                </div>
                <h1 class="tmw-auth-title"><?php _e('Forgot Password?', 'flavor-starter-flavor'); ?></h1>
                <p class="tmw-auth-subtitle"><?php _e("No worries, we'll send you reset instructions", 'flavor-starter-flavor'); ?></p>
            </div>

            <?php tmw_display_flash_messages(); ?>

            <form id="tmw-forgot-form" class="tmw-auth-form" method="post">
                
                <?php tmw_field(array(
                    'name'        => 'email',
                    'label'       => __('Email Address', 'flavor-starter-flavor'),
                    'type'        => 'email',
                    'icon'        => 'fas fa-envelope',
                    'placeholder' => __('Enter your email address', 'flavor-starter-flavor'),
                    'required'    => true,
                    'autocomplete' => 'email',
                    'help'        => __('Enter the email address associated with your account', 'flavor-starter-flavor'),
                )); ?>

                <?php tmw_nonce_field('tmw_forgot_password'); ?>

                <?php tmw_button(array(
                    'text'  => __('Send Reset Link', 'flavor-starter-flavor'),
                    'type'  => 'submit',
                    'style' => 'primary',
                    'full'  => true,
                    'icon'  => 'fas fa-paper-plane',
                )); ?>

            </form>

            <div class="tmw-auth-footer" style="margin-top: var(--tmw-space-6);">
                <a href="<?php echo esc_url(tmw_get_page_url('login')); ?>">
                    <i class="fas fa-arrow-left"></i>
                    <?php _e('Back to Sign In', 'flavor-starter-flavor'); ?>
                </a>
            </div>

        </div>
    </div>
</div>

<?php
get_footer();
