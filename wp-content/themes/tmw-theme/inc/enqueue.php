<?php
/**
 * Enqueue Scripts and Styles
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// FRONTEND STYLES & SCRIPTS
// =============================================================================
add_action('wp_enqueue_scripts', 'tmw_enqueue_assets');

function tmw_enqueue_assets() {
    $version = TMW_THEME_VERSION;
    $uri = TMW_THEME_URI;

    // Google Fonts
    wp_enqueue_style(
        'tmw-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
        array(),
        null
    );

    // Font Awesome (from CDN)
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        array(),
        '6.5.1'
    );

    // Theme CSS Variables (Light/Dark themes)
    wp_enqueue_style(
        'tmw-variables',
        $uri . '/assets/css/variables.css',
        array(),
        $version
    );

    // Base styles
    wp_enqueue_style(
        'tmw-base',
        $uri . '/assets/css/base.css',
        array('tmw-variables'),
        $version
    );

    // Components
    wp_enqueue_style(
        'tmw-components',
        $uri . '/assets/css/components.css',
        array('tmw-base'),
        $version
    );

    // Layout
    wp_enqueue_style(
        'tmw-layout',
        $uri . '/assets/css/layout.css',
        array('tmw-base'),
        $version
    );

    // Header styles
    wp_enqueue_style(
        'tmw-header',
        $uri . '/assets/css/header.css',
        array('tmw-layout'),
        $version
    );

    // Footer styles
    wp_enqueue_style(
        'tmw-footer',
        $uri . '/assets/css/footer.css',
        array('tmw-layout'),
        $version
    );

    // Page-specific styles
    if (is_front_page()) {
        wp_enqueue_style(
            'tmw-front-page',
            $uri . '/assets/css/pages/front-page.css',
            array('tmw-components'),
            $version
        );
    }

    if (tmw_is_auth_page() || is_page_template('templates/template-profile.php')) {
        wp_enqueue_style(
            'tmw-auth',
            $uri . '/assets/css/pages/auth.css',
            array('tmw-components'),
            $version
        );
    }

    if (is_page_template('templates/template-pricing.php') || 
        is_page_template('templates/template-renewal.php')) {
        wp_enqueue_style(
            'tmw-pricing',
            $uri . '/assets/css/pages/pricing.css',
            array('tmw-components'),
            $version
        );
    }

    // Responsive
    wp_enqueue_style(
        'tmw-responsive',
        $uri . '/assets/css/responsive.css',
        array('tmw-layout'),
        $version
    );

    // Main JavaScript
    wp_enqueue_script(
        'tmw-main',
        $uri . '/assets/js/main.js',
        array(),
        $version,
        true
    );

    // Page templates CSS
wp_enqueue_style(
    'tmw-page-templates',
    TMW_THEME_URI . '/assets/css/page-templates.css',
    array(),
    TMW_THEME_VERSION
);

    // Theme toggle
    wp_enqueue_script(
        'tmw-theme-toggle',
        $uri . '/assets/js/theme-toggle.js',
        array(),
        $version,
        true
    );

    // Mobile navigation
    wp_enqueue_script(
        'tmw-mobile-nav',
        $uri . '/assets/js/mobile-nav.js',
        array(),
        $version,
        true
    );

    // Form validation on auth pages
    if (tmw_is_auth_page() || is_page_template('templates/template-profile.php')) {
        wp_enqueue_script(
            'tmw-forms',
            $uri . '/assets/js/forms.js',
            array(),
            $version,
            true
        );
    }

    // Localize script with data
    wp_localize_script('tmw-main', 'tmwData', array(
        'ajaxUrl'       => admin_url('admin-ajax.php'),
        'restUrl'       => rest_url('tmw/v1/'),
        'nonce'         => wp_create_nonce('tmw_nonce'),
        'homeUrl'       => home_url('/'),
        'appUrl'        => tmw_get_app_url(),
        'isLoggedIn'    => is_user_logged_in(),
        'defaultTheme'  => tmw_get_setting('default_theme', 'dark'),
        'currentTheme'  => tmw_get_theme_mode(),
        'i18n'          => array(
            'passwordWeak'    => __('Weak', 'flavor-starter-flavor'),
            'passwordMedium'  => __('Medium', 'flavor-starter-flavor'),
            'passwordStrong'  => __('Strong', 'flavor-starter-flavor'),
            'passwordMatch'   => __('Passwords match', 'flavor-starter-flavor'),
            'passwordNoMatch' => __('Passwords do not match', 'flavor-starter-flavor'),
        ),
    ));
}

// =============================================================================
// ADMIN STYLES & SCRIPTS
// =============================================================================
add_action('admin_enqueue_scripts', 'tmw_admin_enqueue');

function tmw_admin_enqueue($hook) {
    // Only load on our settings page
    if ($hook !== 'toplevel_page_tmw-settings' && strpos($hook, 'tmw-settings') === false) {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    wp_enqueue_style(
        'tmw-admin',
        TMW_THEME_URI . '/assets/css/admin.css',
        array(),
        TMW_THEME_VERSION
    );

    wp_enqueue_script(
        'tmw-admin',
        TMW_THEME_URI . '/assets/js/admin.js',
        array('jquery', 'wp-color-picker'),
        TMW_THEME_VERSION,
        true
    );
}

// =============================================================================
// PRELOAD CRITICAL FONTS
// =============================================================================
add_action('wp_head', 'tmw_preload_fonts', 1);

function tmw_preload_fonts() {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
}

// =============================================================================
// ADD THEME MODE TO HTML TAG
// =============================================================================
add_filter('language_attributes', 'tmw_add_theme_mode_attribute');

function tmw_add_theme_mode_attribute($output) {
    $mode = tmw_get_theme_mode();
    return $output . ' data-theme="' . esc_attr($mode) . '"';
}
