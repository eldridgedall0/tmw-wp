<?php
/**
 * TrackMyWrench Theme Functions
 *
 * @package flavor-starter-flavor
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// THEME CONSTANTS
// =============================================================================
define('TMW_THEME_VERSION', '2.0.0');
define('TMW_THEME_DIR', get_template_directory());
define('TMW_THEME_URI', get_template_directory_uri());

// =============================================================================
// INCLUDE MODULES
// =============================================================================
require_once TMW_THEME_DIR . '/inc/setup.php';           // Theme setup
require_once TMW_THEME_DIR . '/inc/enqueue.php';         // Scripts & styles
require_once TMW_THEME_DIR . '/inc/admin-settings.php';  // Admin settings page
require_once TMW_THEME_DIR . '/inc/subscription.php';    // Subscription tier logic
require_once TMW_THEME_DIR . '/inc/membership-adapter.php'; // Membership plugin abstraction
require_once TMW_THEME_DIR . '/inc/security.php';        // Security & redirects
require_once TMW_THEME_DIR . '/inc/rest-api.php';        // REST API endpoints
require_once TMW_THEME_DIR . '/inc/ajax-handlers.php';   // AJAX handlers
require_once TMW_THEME_DIR . '/inc/template-functions.php'; // Template helpers
require_once TMW_THEME_DIR . '/inc/enqueue-pages.php';
require_once get_template_directory() . '/inc/tier-pricing-fields.php';

// =============================================================================
// THEME SETUP
// =============================================================================
add_action('after_setup_theme', 'tmw_theme_setup');

function tmw_theme_setup() {
    // Text domain for translations
    load_theme_textdomain('flavor-starter-flavor', TMW_THEME_DIR . '/languages');

    // Theme supports
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));
    add_theme_support('custom-logo', array(
        'height'      => 256,
        'width'       => 256,
        'flex-width'  => true,
        'flex-height' => true,
    ));

    // Register navigation menus
    register_nav_menus(array(
        'primary'   => __('Primary Menu', 'flavor-starter-flavor'),
        'footer'    => __('Footer Menu', 'flavor-starter-flavor'),
    ));

    // Image sizes
    add_image_size('tmw-hero', 1920, 800, true);
    add_image_size('tmw-card', 600, 400, true);
}

// =============================================================================
// REGISTER PAGE TEMPLATES
// =============================================================================
add_filter('theme_page_templates', 'tmw_register_templates');

function tmw_register_templates($templates) {
    $templates['templates/template-login.php']           = __('TMW Login', 'flavor-starter-flavor');
    $templates['templates/template-register.php']        = __('TMW Register', 'flavor-starter-flavor');
    $templates['templates/template-forgot-password.php'] = __('TMW Forgot Password', 'flavor-starter-flavor');
    $templates['templates/template-reset-password.php']  = __('TMW Reset Password', 'flavor-starter-flavor');
    $templates['templates/template-profile.php']         = __('TMW My Profile', 'flavor-starter-flavor');
    $templates['templates/template-pricing.php']         = __('TMW Pricing', 'flavor-starter-flavor');
    $templates['templates/template-renewal.php']         = __('TMW Membership Renewal', 'flavor-starter-flavor');
    $templates['templates/template-logout.php']          = __('TMW Logout', 'flavor-starter-flavor');
    $templates['templates/template-terms.php']           = __('TMW Terms & Conditions', 'flavor-starter-flavor');
    $templates['templates/template-privacy.php']         = __('TMW Privacy Policy', 'flavor-starter-flavor');
    return $templates;
}

// =============================================================================
// GLOBAL HELPER FUNCTIONS
// =============================================================================

/**
 * Get a theme setting with fallback
 */
function tmw_get_setting($key, $default = '') {
    $settings = get_option('tmw_settings', array());
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Get subscription settings
 */
function tmw_get_subscription_setting($key, $default = '') {
    $settings = get_option('tmw_subscription_settings', array());
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Get current theme mode (dark/light)
 */
function tmw_get_theme_mode() {
    if (is_user_logged_in()) {
        $user_mode = get_user_meta(get_current_user_id(), 'tmw_theme_mode', true);
        if ($user_mode) {
            return $user_mode;
        }
    }
    return tmw_get_setting('default_theme', 'dark');
}

/**
 * Get the GarageMinder app URL
 */
function tmw_get_app_url() {
    $app_url = tmw_get_setting('app_url', '');
    if (empty($app_url)) {
        // Fallback to same domain /garage/
        $app_url = home_url('/garage/');
    }
    return trailingslashit($app_url);
}

/**
 * Check if current page is an auth page
 */
function tmw_is_auth_page() {
    if (is_page_template('templates/template-login.php') ||
        is_page_template('templates/template-register.php') ||
        is_page_template('templates/template-forgot-password.php') ||
        is_page_template('templates/template-reset-password.php') ||
        is_page_template('templates/template-logout.php')) {
        return true;
    }
    return false;
}

/**
 * Get page URL by slug
 */
function tmw_get_page_url($slug) {
    $page = get_page_by_path($slug);
    if ($page) {
        return get_permalink($page->ID);
    }
    return home_url('/');
}

/**
 * Output theme mode class for body
 */
function tmw_body_theme_class($classes) {
    $mode = tmw_get_theme_mode();
    $classes[] = 'tmw-theme-' . $mode;
    return $classes;
}
add_filter('body_class', 'tmw_body_theme_class');

/**
 * GarageMinder Mobile App Login Support
 * 
 * Add this code to your theme's functions.php (or include this file from functions.php).
 * Handles the ?mobile=1 parameter to support WebView-based login from the Android app.
 * 
 * Flow:
 *   1. Android WebView loads: /login?mobile=1
 *   2. User submits login form normally
 *   3. On success → redirect to /app/?login_success=1  (Android intercepts this URL)
 *   4. Android extracts WordPress cookies and calls POST /gm/api/v1/auth/token-exchange
 *   5. API returns JWT access_token + refresh_token for all subsequent API calls
 */

// -------------------------------------------------------------------------
// 1. Persist ?mobile=1 across form submissions via a hidden input
// -------------------------------------------------------------------------
add_action( 'login_form', 'gm_mobile_hidden_input' );
function gm_mobile_hidden_input() {
    if ( isset( $_GET['mobile'] ) && $_GET['mobile'] === '1' ) {
        echo '<input type="hidden" name="mobile" value="1" />';
    }
}

// Also keep it when WordPress builds the login form action URL
add_filter( 'login_form_defaults', 'gm_mobile_login_form_defaults' );
function gm_mobile_login_form_defaults( $defaults ) {
    if ( isset( $_REQUEST['mobile'] ) && $_REQUEST['mobile'] === '1' ) {
        // Make the redirect_to point to our mobile success URL
        $defaults['redirect'] = home_url( '/app/?login_success=1&mobile=1' );
    }
    return $defaults;
}

// -------------------------------------------------------------------------
// 2. Override redirect_to for mobile logins BEFORE WordPress processes it
// -------------------------------------------------------------------------
add_filter( 'login_redirect', 'gm_mobile_login_redirect', 20, 3 );
function gm_mobile_login_redirect( $redirect_to, $requested_redirect_to, $user ) {

    // Only act if it's a successful login (user object, not WP_Error)
    if ( is_wp_error( $user ) ) {
        return $redirect_to;
    }

    // Check for ?mobile=1 in the POST data (carried via hidden input)
    $is_mobile = ( isset( $_POST['mobile'] ) && $_POST['mobile'] === '1' )
              || ( isset( $_GET['mobile'] )  && $_GET['mobile']  === '1'  );

    if ( $is_mobile ) {
        return home_url( '/app/?login_success=1' );
    }

    return $redirect_to;
}

// -------------------------------------------------------------------------
// 3. Inject mobile-specific UI tweaks into the login page <head>
//    - Hides the nav/header/footer so the page looks clean inside WebView
//    - Adds a meta viewport tag for proper mobile scaling
// -------------------------------------------------------------------------
add_action( 'login_enqueue_scripts', 'gm_mobile_login_styles' );
function gm_mobile_login_styles() {
    $is_mobile = isset( $_GET['mobile'] ) && $_GET['mobile'] === '1';
    if ( ! $is_mobile ) {
        return;
    }
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        /* Strip chrome for WebView — only show the login card */
        body.login {
            background: #0f1923 !important;
        }
        #login {
            padding: 40px 20px !important;
            width: 100% !important;
            max-width: 400px !important;
        }
        #login h1 a {
            background-size: contain !important;
        }
        /* Hide "Back to site" link — not useful inside WebView */
        #backtoblog,
        .privacy-policy-page-link,
        .language-switcher {
            display: none !important;
        }
        /* Larger tap targets for mobile */
        #loginform input[type=text],
        #loginform input[type=password] {
            font-size: 16px !important; /* prevents iOS zoom-on-focus */
            height: 44px !important;
            padding: 8px 12px !important;
        }
        #wp-submit {
            height: 44px !important;
            font-size: 15px !important;
        }
    </style>
    <?php
}

// -------------------------------------------------------------------------
// 4. Inject JavaScript that posts a message to the Android WebView JS bridge
//    when the login_success page loads (belt-and-suspenders alongside URL intercept)
// -------------------------------------------------------------------------
add_action( 'wp_footer', 'gm_mobile_login_success_bridge' );
function gm_mobile_login_success_bridge() {
    // Only fire on the /app/ page when login_success=1 is present
    if ( ! isset( $_GET['login_success'] ) || $_GET['login_success'] !== '1' ) {
        return;
    }
    ?>
    <script>
    (function() {
        // Android WebView JavaScript Interface (if injected by the app)
        if (window.GarageMinderBridge && typeof window.GarageMinderBridge.onLoginSuccess === 'function') {
            window.GarageMinderBridge.onLoginSuccess();
        }

        // The Android WebViewClient.shouldOverrideUrlLoading() will intercept this
        // URL automatically — the JS bridge call above is just extra safety.
    })();
    </script>
    <?php
}

// -------------------------------------------------------------------------
// 5. (Optional) Custom login page template for ?mobile=1
//    Adds a subtle "Sign in to GarageMinder" header inside WebView
// -------------------------------------------------------------------------
add_action( 'login_message', 'gm_mobile_login_message' );
function gm_mobile_login_message( $message ) {
    $is_mobile = isset( $_GET['mobile'] ) && $_GET['mobile'] === '1';
    if ( $is_mobile && empty( $message ) ) {
        return '<p class="message" style="text-align:center;">Sign in to <strong>GarageMinder</strong></p>';
    }
    return $message;
}
