<?php
/**
 * Template Name: Login
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
                <h1 class="tmw-auth-title"><?php _e('Welcome Back', 'flavor-starter-flavor'); ?></h1>
                <p class="tmw-auth-subtitle"><?php _e('Sign in to your account to continue', 'flavor-starter-flavor'); ?></p>
            </div>

            <?php tmw_display_flash_messages(); ?>

            <form id="tmw-login-form" class="tmw-auth-form" method="post">
                
                <?php tmw_field(array(
                    'name'        => 'username',
                    'label'       => __('Email or Username', 'flavor-starter-flavor'),
                    'type'        => 'text',
                    'icon'        => 'fas fa-user',
                    'placeholder' => __('Enter your email or username', 'flavor-starter-flavor'),
                    'required'    => true,
                    'autocomplete' => 'username',
                )); ?>

                <?php tmw_field(array(
                    'name'        => 'password',
                    'label'       => __('Password', 'flavor-starter-flavor'),
                    'type'        => 'password',
                    'icon'        => 'fas fa-lock',
                    'placeholder' => __('Enter your password', 'flavor-starter-flavor'),
                    'required'    => true,
                    'autocomplete' => 'current-password',
                )); ?>

                <div class="tmw-auth-extras">
                    <label class="tmw-auth-remember">
                        <input type="checkbox" name="remember" value="1">
                        <?php _e('Remember me', 'flavor-starter-flavor'); ?>
                    </label>
                    <a href="<?php echo esc_url(tmw_get_page_url('forgot-password')); ?>" class="tmw-auth-forgot">
                        <?php _e('Forgot password?', 'flavor-starter-flavor'); ?>
                    </a>
                </div>

                <?php tmw_nonce_field('tmw_login'); ?>

                <?php tmw_button(array(
                    'text'  => __('Sign In', 'flavor-starter-flavor'),
                    'type'  => 'submit',
                    'style' => 'primary',
                    'full'  => true,
                    'icon'  => 'fas fa-sign-in-alt',
                )); ?>

            </form>

            <div class="tmw-divider"><?php _e('or', 'flavor-starter-flavor'); ?></div>

            <div class="tmw-auth-footer">
                <?php _e("Don't have an account?", 'flavor-starter-flavor'); ?>
                <a href="<?php echo esc_url(tmw_get_page_url('register')); ?>">
                    <?php _e('Sign up free', 'flavor-starter-flavor'); ?>
                </a>
            </div>

        </div>
    </div>
</div>

<?php
get_footer();
