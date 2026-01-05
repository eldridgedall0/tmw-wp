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
 * Get level mapping settings
 */
function tmw_get_level_mapping($key, $default = 0) {
    $mapping = get_option('tmw_level_mapping', array());
    return isset($mapping[$key]) ? (int) $mapping[$key] : $default;
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
