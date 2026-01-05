<?php
/**
 * Template Name: Register
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
                <h1 class="tmw-auth-title"><?php _e('Create Account', 'flavor-starter-flavor'); ?></h1>
                <p class="tmw-auth-subtitle"><?php _e('Start tracking your vehicle maintenance today', 'flavor-starter-flavor'); ?></p>
            </div>

            <?php tmw_display_flash_messages(); ?>

            <form id="tmw-register-form" class="tmw-auth-form" method="post">
                
                <div class="tmw-form-row">
                    <?php tmw_field(array(
                        'name'        => 'first_name',
                        'label'       => __('First Name', 'flavor-starter-flavor'),
                        'type'        => 'text',
                        'icon'        => 'fas fa-user',
                        'placeholder' => __('First name', 'flavor-starter-flavor'),
                        'required'    => true,
                        'autocomplete' => 'given-name',
                    )); ?>

                    <?php tmw_field(array(
                        'name'        => 'last_name',
                        'label'       => __('Last Name', 'flavor-starter-flavor'),
                        'type'        => 'text',
                        'icon'        => 'fas fa-user',
                        'placeholder' => __('Last name', 'flavor-starter-flavor'),
                        'required'    => true,
                        'autocomplete' => 'family-name',
                    )); ?>
                </div>

                <?php tmw_field(array(
                    'name'        => 'email',
                    'label'       => __('Email Address', 'flavor-starter-flavor'),
                    'type'        => 'email',
                    'icon'        => 'fas fa-envelope',
                    'placeholder' => __('you@example.com', 'flavor-starter-flavor'),
                    'required'    => true,
                    'autocomplete' => 'email',
                )); ?>

                <?php tmw_field(array(
                    'name'        => 'password',
                    'label'       => __('Password', 'flavor-starter-flavor'),
                    'type'        => 'password',
                    'icon'        => 'fas fa-lock',
                    'placeholder' => __('Create a password', 'flavor-starter-flavor'),
                    'required'    => true,
                    'autocomplete' => 'new-password',
                    'help'        => __('At least 8 characters with a mix of letters, numbers, and symbols', 'flavor-starter-flavor'),
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
                    'label'       => __('Confirm Password', 'flavor-starter-flavor'),
                    'type'        => 'password',
                    'icon'        => 'fas fa-lock',
                    'placeholder' => __('Confirm your password', 'flavor-starter-flavor'),
                    'required'    => true,
                    'autocomplete' => 'new-password',
                )); ?>

                <div class="tmw-password-match" style="display: none;"></div>

                <div class="tmw-field">
                    <label class="tmw-checkbox">
                        <input type="checkbox" name="agree_terms" value="1" required>
                        <?php printf(
                            __('I agree to the %sTerms of Service%s and %sPrivacy Policy%s', 'flavor-starter-flavor'),
                            '<a href="' . esc_url(tmw_get_page_url('terms')) . '" target="_blank">',
                            '</a>',
                            '<a href="' . esc_url(tmw_get_page_url('privacy')) . '" target="_blank">',
                            '</a>'
                        ); ?>
                    </label>
                </div>

                <?php tmw_nonce_field('tmw_register'); ?>

                <?php tmw_button(array(
                    'text'  => __('Create Account', 'flavor-starter-flavor'),
                    'type'  => 'submit',
                    'style' => 'primary',
                    'full'  => true,
                    'icon'  => 'fas fa-user-plus',
                )); ?>

            </form>

            <div class="tmw-divider"><?php _e('or', 'flavor-starter-flavor'); ?></div>

            <div class="tmw-auth-footer">
                <?php _e('Already have an account?', 'flavor-starter-flavor'); ?>
                <a href="<?php echo esc_url(tmw_get_page_url('login')); ?>">
                    <?php _e('Sign in', 'flavor-starter-flavor'); ?>
                </a>
            </div>

        </div>
    </div>
</div>

<?php
get_footer();
