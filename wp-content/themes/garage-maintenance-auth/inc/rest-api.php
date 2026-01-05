<?php
/**
 * REST API Endpoints
 *
 * Provides REST API endpoints for the GarageMinder app to fetch
 * user and subscription data when running on a subdomain.
 *
 * @package flavor-starter-flavor
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// REGISTER REST ROUTES
// =============================================================================
add_action('rest_api_init', 'tmw_register_rest_routes');

function tmw_register_rest_routes() {
    // Namespace for our endpoints
    $namespace = 'tmw/v1';

    // GET /tmw/v1/user/current - Get current user data
    register_rest_route($namespace, '/user/current', array(
        'methods'             => 'GET',
        'callback'            => 'tmw_rest_get_current_user',
        'permission_callback' => 'tmw_rest_check_auth',
    ));

    // GET /tmw/v1/subscription - Get subscription data for current user
    register_rest_route($namespace, '/subscription', array(
        'methods'             => 'GET',
        'callback'            => 'tmw_rest_get_subscription',
        'permission_callback' => 'tmw_rest_check_auth',
    ));

    // GET /tmw/v1/subscription/limits - Get subscription limits for current user
    register_rest_route($namespace, '/subscription/limits', array(
        'methods'             => 'GET',
        'callback'            => 'tmw_rest_get_limits',
        'permission_callback' => 'tmw_rest_check_auth',
    ));

    // GET /tmw/v1/subscription/tiers - Get all tier configurations (public)
    register_rest_route($namespace, '/subscription/tiers', array(
        'methods'             => 'GET',
        'callback'            => 'tmw_rest_get_tiers',
        'permission_callback' => '__return_true', // Public endpoint
    ));

    // POST /tmw/v1/user/theme - Update user's theme preference
    register_rest_route($namespace, '/user/theme', array(
        'methods'             => 'POST',
        'callback'            => 'tmw_rest_update_theme',
        'permission_callback' => 'tmw_rest_check_auth',
        'args'                => array(
            'theme' => array(
                'required'          => true,
                'validate_callback' => function($param) {
                    return in_array($param, array('dark', 'light'));
                },
            ),
        ),
    ));

    // GET /tmw/v1/auth/check - Check if user is authenticated
    register_rest_route($namespace, '/auth/check', array(
        'methods'             => 'GET',
        'callback'            => 'tmw_rest_auth_check',
        'permission_callback' => '__return_true',
    ));

    // GET /tmw/v1/config - Get app configuration
    register_rest_route($namespace, '/config', array(
        'methods'             => 'GET',
        'callback'            => 'tmw_rest_get_config',
        'permission_callback' => '__return_true',
    ));
}

// =============================================================================
// PERMISSION CALLBACK
// =============================================================================

/**
 * Check if user is authenticated for protected endpoints
 *
 * @param WP_REST_Request $request
 * @return bool|WP_Error
 */
function tmw_rest_check_auth($request) {
    if (!is_user_logged_in()) {
        return new WP_Error(
            'rest_not_authenticated',
            __('You must be logged in to access this endpoint.', 'flavor-starter-flavor'),
            array('status' => 401)
        );
    }
    return true;
}

// =============================================================================
// ENDPOINT CALLBACKS
// =============================================================================

/**
 * Get current user data
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function tmw_rest_get_current_user($request) {
    $user = wp_get_current_user();

    $data = array(
        'id'           => $user->ID,
        'username'     => $user->user_login,
        'email'        => $user->user_email,
        'display_name' => $user->display_name,
        'first_name'   => $user->first_name,
        'last_name'    => $user->last_name,
        'avatar_url'   => get_avatar_url($user->ID, array('size' => 128)),
        'registered'   => $user->user_registered,
        'subscription' => tmw_get_user_subscription_data($user->ID),
        'theme_mode'   => tmw_get_theme_mode(),
    );

    return rest_ensure_response($data);
}

/**
 * Get subscription data
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function tmw_rest_get_subscription($request) {
    $data = tmw_get_user_subscription_data();
    return rest_ensure_response($data);
}

/**
 * Get subscription limits
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function tmw_rest_get_limits($request) {
    $tier = tmw_get_user_tier();
    $limits = tmw_get_tier_limits($tier);
    
    $data = array(
        'tier'   => $tier,
        'limits' => $limits,
    );

    return rest_ensure_response($data);
}

/**
 * Get all tier configurations
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function tmw_rest_get_tiers($request) {
    $tiers = array('free', 'paid', 'fleet');
    $data = array();

    foreach ($tiers as $tier) {
        $data[$tier] = array(
            'name'   => tmw_get_tier_name($tier),
            'limits' => tmw_get_tier_limits($tier),
        );
    }

    return rest_ensure_response($data);
}

/**
 * Update user's theme preference
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function tmw_rest_update_theme($request) {
    $theme = $request->get_param('theme');
    $user_id = get_current_user_id();

    update_user_meta($user_id, 'tmw_theme_mode', $theme);

    return rest_ensure_response(array(
        'success' => true,
        'theme'   => $theme,
    ));
}

/**
 * Check authentication status
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function tmw_rest_auth_check($request) {
    $is_logged_in = is_user_logged_in();

    $data = array(
        'authenticated' => $is_logged_in,
    );

    if ($is_logged_in) {
        $user = wp_get_current_user();
        $data['user'] = array(
            'id'           => $user->ID,
            'display_name' => $user->display_name,
            'email'        => $user->user_email,
        );
        $data['tier'] = tmw_get_user_tier();
    } else {
        $data['login_url'] = tmw_get_page_url('login');
    }

    return rest_ensure_response($data);
}

/**
 * Get app configuration
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function tmw_rest_get_config($request) {
    $data = array(
        'app_name'      => get_bloginfo('name'),
        'app_url'       => tmw_get_app_url(),
        'home_url'      => home_url('/'),
        'api_url'       => rest_url('tmw/v1/'),
        'default_theme' => tmw_get_setting('default_theme', 'dark'),
        'version'       => TMW_THEME_VERSION,
        'urls'          => array(
            'login'          => tmw_get_page_url('login'),
            'register'       => tmw_get_page_url('register'),
            'profile'        => tmw_get_page_url('my-profile'),
            'pricing'        => tmw_get_page_url('pricing'),
            'forgot_password' => tmw_get_page_url('forgot-password'),
            'terms'          => tmw_get_page_url('terms'),
            'privacy'        => tmw_get_page_url('privacy'),
        ),
        'subscription_enabled' => true,
        'tiers' => array('free', 'paid', 'fleet'),
    );

    return rest_ensure_response($data);
}

// =============================================================================
// CORS SUPPORT FOR SUBDOMAIN
// =============================================================================
add_action('rest_api_init', 'tmw_add_cors_headers', 15);

function tmw_add_cors_headers() {
    // Only add CORS for our namespace
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    
    add_filter('rest_pre_serve_request', function($served, $result, $request, $server) {
        $route = $request->get_route();
        
        // Only apply to our endpoints
        if (strpos($route, '/tmw/v1/') === 0) {
            $app_url = tmw_get_app_url();
            $origin = parse_url($app_url, PHP_URL_SCHEME) . '://' . parse_url($app_url, PHP_URL_HOST);
            
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        }
        
        return $served;
    }, 10, 4);
}

// =============================================================================
// HANDLE OPTIONS PREFLIGHT REQUESTS
// =============================================================================
add_action('init', 'tmw_handle_preflight');

function tmw_handle_preflight() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        $app_url = tmw_get_app_url();
        $origin = parse_url($app_url, PHP_URL_SCHEME) . '://' . parse_url($app_url, PHP_URL_HOST);
        
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        header('Access-Control-Max-Age: 86400');
        exit;
    }
}
