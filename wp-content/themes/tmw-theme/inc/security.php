<?php
/**
 * Security & Access Control
 *
 * - Blocks non-admin access to wp-admin
 * - Redirects wp-login.php to frontend login
 * - Hides admin bar for non-admins
 * - Disables user enumeration
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// BLOCK WP-ADMIN ACCESS FOR NON-ADMINS
// =============================================================================
add_action('admin_init', 'tmw_block_wp_admin', 1);

function tmw_block_wp_admin() {
    // Allow AJAX requests
    if (wp_doing_ajax()) {
        return;
    }

    // Allow REST API requests
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }

    // Allow admins
    if (current_user_can('manage_options')) {
        return;
    }

    // Allow users with specific capabilities (editors, etc.)
    if (current_user_can('edit_posts')) {
        return;
    }

    // Redirect to profile page or home
    $redirect = tmw_get_page_url('my-profile');
    if (!$redirect || $redirect === home_url('/')) {
        $redirect = home_url('/');
    }

    wp_safe_redirect($redirect);
    exit;
}

// =============================================================================
// HIDE ADMIN BAR FOR NON-ADMINS
// =============================================================================
add_action('after_setup_theme', 'tmw_hide_admin_bar');

function tmw_hide_admin_bar() {
    // if (!current_user_can('manage_options')) {
        add_filter('show_admin_bar', '__return_false');
        
        // Also remove the admin bar CSS
        remove_action('wp_head', '_admin_bar_bump_cb');
    // }
}

// =============================================================================
// REDIRECT WP-LOGIN.PHP TO FRONTEND
// =============================================================================
// add_action('login_init', 'tmw_redirect_wp_login');

function tmw_redirect_wp_login() {
    // Get the action
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';

    // Allow certain actions that need wp-login.php
    $allowed_actions = array(
        'postpass',      // Password protected posts
        'logout',        // Let WordPress handle logout
        'confirmaction', // Privacy data export
    );

    if (in_array($action, $allowed_actions)) {
        return;
    }

    // Don't redirect for interim login (embedded login)
    if (isset($_REQUEST['interim-login'])) {
        return;
    }

    // Get redirect_to parameter
    $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '';

    switch ($action) {
        case 'login':
            $login_url = tmw_get_page_url('login');
            if ($login_url) {
                if ($redirect_to) {
                    $login_url = add_query_arg('redirect_to', urlencode($redirect_to), $login_url);
                }
                wp_safe_redirect($login_url);
                exit;
            }
            break;

        case 'register':
            $register_url = tmw_get_page_url('register');
            if ($register_url) {
                wp_safe_redirect($register_url);
                exit;
            }
            break;

        case 'lostpassword':
        case 'retrievepassword':
            $forgot_url = tmw_get_page_url('forgot-password');
            if ($forgot_url) {
                wp_safe_redirect($forgot_url);
                exit;
            }
            break;

        case 'rp':
        case 'resetpass':
            $reset_url = tmw_get_page_url('reset-password');
            if ($reset_url) {
                // Pass along the key and login
                if (isset($_GET['key']) && isset($_GET['login'])) {
                    $reset_url = add_query_arg(array(
                        'key'   => $_GET['key'],
                        'login' => rawurlencode($_GET['login']),
                    ), $reset_url);
                }
                wp_safe_redirect($reset_url);
                exit;
            }
            break;
    }
}

// =============================================================================
// CUSTOM LOGIN REDIRECT (After successful login)
// =============================================================================
add_filter('login_redirect', 'tmw_custom_login_redirect', 999, 3);

function tmw_custom_login_redirect($redirect_to, $requested_redirect_to, $user) {
    // Check for errors
    if (is_wp_error($user)) {
        return $redirect_to;
    }

    // Admin users go to admin
    if ($user instanceof WP_User && $user->has_cap('manage_options')) {
        // If they specifically requested a redirect, honor it
        if (!empty($requested_redirect_to) && strpos($requested_redirect_to, admin_url()) !== false) {
            return $redirect_to;
        }
    }

    // Get setting for where to redirect
    $setting = tmw_get_setting('login_redirect', 'app');

    switch ($setting) {
        case 'app':
            return tmw_get_app_url();
        case 'profile':
            return tmw_get_page_url('my-profile') ?: home_url('/');
        case 'home':
        default:
            return home_url('/');
    }
}

// =============================================================================
// CUSTOM LOGOUT REDIRECT
// =============================================================================
add_action('wp_logout', 'tmw_custom_logout_redirect');

function tmw_custom_logout_redirect() {
    $setting = tmw_get_setting('logout_redirect', 'home');

    switch ($setting) {
        case 'login':
            $redirect = tmw_get_page_url('login') ?: home_url('/');
            break;
        case 'home':
        default:
            $redirect = home_url('/');
            break;
    }

    wp_safe_redirect($redirect);
    exit;
}

// =============================================================================
// DISABLE XML-RPC
// =============================================================================
add_filter('xmlrpc_enabled', '__return_false');

// =============================================================================
// DISABLE USER ENUMERATION VIA REST API
// =============================================================================
add_filter('rest_endpoints', 'tmw_disable_user_endpoints');

function tmw_disable_user_endpoints($endpoints) {
    // Only disable for non-admins
    if (current_user_can('manage_options')) {
        return $endpoints;
    }

    // Remove user endpoints
    if (isset($endpoints['/wp/v2/users'])) {
        unset($endpoints['/wp/v2/users']);
    }
    if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    if (isset($endpoints['/wp/v2/users/me'])) {
        unset($endpoints['/wp/v2/users/me']);
    }

    return $endpoints;
}

// =============================================================================
// DISABLE AUTHOR ARCHIVES (User enumeration prevention)
// =============================================================================
add_action('template_redirect', 'tmw_disable_author_archives');

function tmw_disable_author_archives() {
    if (is_author()) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
}

// =============================================================================
// PREVENT USERNAME ENUMERATION VIA ?author=N
// =============================================================================
add_action('template_redirect', 'tmw_prevent_author_enum');

function tmw_prevent_author_enum() {
    if (!is_admin() && isset($_GET['author']) && is_numeric($_GET['author'])) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
}

// =============================================================================
// SECURITY HEADERS
// =============================================================================
add_action('send_headers', 'tmw_security_headers');

function tmw_security_headers() {
    // Don't add headers in admin
    if (is_admin()) {
        return;
    }

    // X-Frame-Options: Allow same-origin for most pages.
    // Exception: the login page with ?mobile=1 — it loads inside an Android WebView
    // which is NOT same-origin. We omit X-Frame-Options so WebView can render it.
    $is_mobile_login = is_page_template('templates/template-login.php')
                    && isset($_GET['mobile'])
                    && $_GET['mobile'] === '1';

    if (!$is_mobile_login) {
        header('X-Frame-Options: SAMEORIGIN');
    }

    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// =============================================================================
// PROTECT SENSITIVE PAGES FROM LOGGED-OUT USERS
// =============================================================================
add_action('template_redirect', 'tmw_protect_member_pages');

function tmw_protect_member_pages() {
    // Profile page requires login
    if (is_page_template('templates/template-profile.php') && !is_user_logged_in()) {
        $login_url = tmw_get_page_url('login');
        $login_url = add_query_arg('redirect_to', urlencode(get_permalink()), $login_url);
        wp_safe_redirect($login_url);
        exit;
    }

    // Renewal page requires login
    if (is_page_template('templates/template-renewal.php') && !is_user_logged_in()) {
        $login_url = tmw_get_page_url('login');
        $login_url = add_query_arg('redirect_to', urlencode(get_permalink()), $login_url);
        wp_safe_redirect($login_url);
        exit;
    }
}

// =============================================================================
// REDIRECT LOGGED-IN USERS AWAY FROM AUTH PAGES
// =============================================================================
add_action('template_redirect', 'tmw_redirect_logged_in_users', 10);

function tmw_redirect_logged_in_users() {
    if (!is_user_logged_in()) {
        return;
    }

    // If logged in and on login page, redirect to app
    if (is_page_template('templates/template-login.php')) {
        // Exception: mobile app WebView re-visiting /login?mobile=1 while already logged in.
        // In this case tmw_mobile_logged_in_bypass() (priority 5) already fired and
        // redirected to the success URL — so we never reach here for mobile.
        // For regular web users, proceed normally.
        if (isset($_GET['mobile']) && $_GET['mobile'] === '1') {
            return; // mobile-login.php handles this at priority 5
        }
        wp_safe_redirect(tmw_get_app_url());
        exit;
    }

    // If logged in and on register page, redirect to app
    if (is_page_template('templates/template-register.php')) {
        wp_safe_redirect(tmw_get_app_url());
        exit;
    }
}
