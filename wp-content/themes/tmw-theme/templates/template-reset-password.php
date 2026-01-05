<?php
/**
 * Template Name: Reset Password
 * Template Post Type: page
 *
 * @package flavor-starter-flavor
 */

// Redirect if already logged in
if (is_user_logged_in()) {
    wp_redirect(tmw_get_app_url());
    exit;
}

// Get reset key and login from URL
$reset_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$user_login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';

// Validate reset key
$valid_key = false;
$error_message = '';

if ($reset_key && $user_login) {
    $user = check_password_reset_key($reset_key, $user_login);
    if (!is_wp_error($user)) {
        $valid_key = true;
    } else {
        $error_message = __('This password reset link is invalid or has expired. Please request a new one.', 'flavor-starter-flavor');
    }
} else {
    $error_message = __('Invalid password reset link. Please request a new one.', 'flavor-starter-flavor');
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
                <h1 class="tmw-auth-title"><?php _e('Set New Password', 'flavor-starter-flavor'); ?></h1>
                <p class="tmw-auth-subtitle"><?php _e('Create a new password for your account', 'flavor-starter-flavor'); ?></p>
            </div>

            <?php tmw_display_flash_messages(); ?>

            <?php if ($valid_key) : ?>

                <form id="tmw-reset-form" class="tmw-auth-form" method="post">
                    
                    <?php tmw_field(array(
                        'name'        => 'password',
                        'label'       => __('New Password', 'flavor-starter-flavor'),
                        'type'        => 'password',
                        'icon'        => 'fas fa-lock',
                        'placeholder' => __('Enter new password', 'flavor-starter-flavor'),
                        'required'    => true,
                        'autocomplete' => 'new-password',
                    )); ?>

                    <div class="tmw-password-strength">
                        <div class="tmw-password-strength-bar">
                            <div class="tmw-password-strength-fill"></div>
                        </div>
                        <div class="tmw-password-strength-text">
                            <span class="tmw-password-strength-label"></span>
                        </div>
                    </div>

                    <?php tmw_field(array(
                        'name'        => 'password_confirm',
                        'label'       => __('Confirm New Password', 'flavor-starter-flavor'),
                        'type'        => 'password',
                        'icon'        => 'fas fa-lock',
                        'placeholder' => __('Confirm new password', 'flavor-starter-flavor'),
                        'required'    => true,
                        'autocomplete' => 'new-password',
                    )); ?>

                    <div class="tmw-password-match" style="display: none;"></div>

                    <input type="hidden" name="key" value="<?php echo esc_attr($reset_key); ?>">
                    <input type="hidden" name="login" value="<?php echo esc_attr($user_login); ?>">

                    <?php tmw_nonce_field('tmw_reset_password'); ?>

                    <?php tmw_button(array(
                        'text'  => __('Reset Password', 'flavor-starter-flavor'),
                        'type'  => 'submit',
                        'style' => 'primary',
                        'full'  => true,
                        'icon'  => 'fas fa-key',
                    )); ?>

                </form>

            <?php else : ?>

                <?php tmw_alert($error_message, 'error'); ?>

                <a href="<?php echo esc_url(tmw_get_page_url('forgot-password')); ?>" class="tmw-btn tmw-btn-primary tmw-btn-full">
                    <?php _e('Request New Reset Link', 'flavor-starter-flavor'); ?>
                </a>

            <?php endif; ?>

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
