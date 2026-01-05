<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="tmw-site">

    <header id="masthead" class="tmw-header">
        <div class="tmw-container">
            <div class="tmw-header-inner">
                
                <!-- Logo -->
                <div class="tmw-header-logo">
                    <?php tmw_logo(); ?>
                </div>

                <!-- Primary Navigation (Desktop) -->
                <nav class="tmw-nav-primary" aria-label="<?php esc_attr_e('Primary Menu', 'flavor-starter-flavor'); ?>">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => '',
                        'fallback_cb'    => 'tmw_primary_menu_fallback',
                        'depth'          => 1,
                    ));
                    ?>
                </nav>

                <!-- Header Actions -->
                <div class="tmw-header-actions">
                    <!-- Theme Toggle -->
                    <?php tmw_theme_toggle(); ?>

                    <!-- Auth Navigation -->
                    <?php tmw_auth_nav(); ?>

                    <!-- Mobile Menu Toggle -->
                    <button type="button" class="tmw-mobile-toggle" 
                            aria-label="<?php esc_attr_e('Open menu', 'flavor-starter-flavor'); ?>"
                            aria-expanded="false"
                            aria-controls="mobile-menu">
                        <i class="fas fa-bars"></i>
                        <i class="fas fa-times"></i>
                    </button>
                </div>

            </div>
        </div>
    </header>

    <!-- Mobile Navigation -->
    <nav id="mobile-menu" class="tmw-nav-mobile" aria-label="<?php esc_attr_e('Mobile Menu', 'flavor-starter-flavor'); ?>">
        <div class="tmw-nav-mobile-inner">
            
            <?php if (is_user_logged_in()) : 
                $user = wp_get_current_user();
            ?>
            <div class="tmw-mobile-user">
                <img src="<?php echo esc_url(get_avatar_url($user->ID, array('size' => 48))); ?>" 
                     alt="" class="tmw-avatar">
                <div class="tmw-mobile-user-info">
                    <div class="tmw-mobile-user-name"><?php echo esc_html($user->display_name); ?></div>
                    <div class="tmw-mobile-user-email"><?php echo esc_html($user->user_email); ?></div>
                </div>
            </div>
            <?php endif; ?>

            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => '',
                'fallback_cb'    => 'tmw_primary_menu_fallback',
                'depth'          => 1,
            ));
            ?>

            <div class="tmw-nav-mobile-actions">
                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo esc_url(tmw_get_app_url()); ?>" class="tmw-btn tmw-btn-primary tmw-btn-full">
                        <i class="fas fa-rocket"></i>
                        <?php _e('Go to App', 'flavor-starter-flavor'); ?>
                    </a>
                    <a href="<?php echo esc_url(tmw_get_page_url('my-profile')); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-full">
                        <i class="fas fa-user"></i>
                        <?php _e('My Profile', 'flavor-starter-flavor'); ?>
                    </a>
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="tmw-btn tmw-btn-ghost tmw-btn-full">
                        <i class="fas fa-sign-out-alt"></i>
                        <?php _e('Sign Out', 'flavor-starter-flavor'); ?>
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url(tmw_get_page_url('login')); ?>" class="tmw-btn tmw-btn-secondary tmw-btn-full">
                        <?php _e('Sign In', 'flavor-starter-flavor'); ?>
                    </a>
                    <a href="<?php echo esc_url(tmw_get_page_url('register')); ?>" class="tmw-btn tmw-btn-primary tmw-btn-full">
                        <?php _e('Get Started Free', 'flavor-starter-flavor'); ?>
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </nav>

    <main id="main" class="tmw-main">

<?php
/**
 * Fallback menu if no menu assigned
 */
function tmw_primary_menu_fallback() {
    echo '<ul>';
    echo '<li><a href="' . esc_url(home_url('/')) . '">' . __('Home', 'flavor-starter-flavor') . '</a></li>';
    echo '<li><a href="' . esc_url(tmw_get_page_url('pricing')) . '">' . __('Pricing', 'flavor-starter-flavor') . '</a></li>';
    if (!is_user_logged_in()) {
        echo '<li><a href="' . esc_url(tmw_get_page_url('login')) . '">' . __('Login', 'flavor-starter-flavor') . '</a></li>';
    }
    echo '</ul>';
}
