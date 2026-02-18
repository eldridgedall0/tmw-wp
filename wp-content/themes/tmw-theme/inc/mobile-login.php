<?php
/**
 * GarageMinder Mobile App Login Support
 *
 * Handles ?mobile=1 on the custom /login page (template-login.php).
 *
 * HOW THIS THEME'S LOGIN WORKS (important context):
 *   - The login page is a WordPress Page using template-login.php
 *   - The form submits via JavaScript fetch() to admin-ajax.php (action=tmw_login)
 *   - The AJAX handler tmw_ajax_login() in inc/ajax-handlers.php returns JSON: { redirect: url }
 *   - JavaScript then does window.location.href = url
 *
 * THEREFORE: The standard WP hooks login_form, login_redirect, login_enqueue_scripts
 * DO NOT APPLY HERE — those only fire on wp-login.php.
 * All changes must hook into the THEME's own mechanisms.
 *
 * MOBILE FLOW:
 *   1. Android WebView loads:  /login?mobile=1
 *   2. Template adds hidden input [mobile=1] to the form
 *   3. forms.js sends it as part of the AJAX POST
 *   4. tmw_ajax_login() (patched here) detects mobile=1 → returns redirect to /app/?login_success=1
 *   5. forms.js does window.location.href = '/app/?login_success=1'
 *   6. Android WebViewClient.shouldOverrideUrlLoading() intercepts "login_success=1"
 *   7. Android extracts WP cookies → POST /gm/api/v1/auth/token-exchange → gets JWT tokens
 *
 * @package flavor-starter-flavor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// 1. MOBILE DETECTION HELPER
// =============================================================================

/**
 * Returns true if this request came from the mobile app WebView.
 * Checks GET, POST, and the WordPress session (persisted across AJAX calls).
 */
function tmw_is_mobile_app_request(): bool {
    // Check current request parameters
    if ( isset( $_REQUEST['mobile'] ) && $_REQUEST['mobile'] === '1' ) {
        return true;
    }
    // Check session (set when the login page was first loaded)
    if ( session_status() === PHP_SESSION_ACTIVE && ! empty( $_SESSION['tmw_mobile_login'] ) ) {
        return true;
    }
    return false;
}

/**
 * The URL that the Android WebViewClient will intercept.
 */
function tmw_mobile_success_url(): string {
    return home_url( '/app/?login_success=1' );
}

// =============================================================================
// 2. PERSIST ?mobile=1 IN SESSION SO AJAX CAN SEE IT
//    The login page loads with ?mobile=1 in the URL (GET request).
//    When forms.js submits via fetch(), it's a separate POST to admin-ajax.php.
//    We use both a session flag AND a hidden form input to bridge this gap.
// =============================================================================

add_action( 'template_redirect', 'tmw_mobile_start_session_if_needed', 1 );

function tmw_mobile_start_session_if_needed(): void {
    if ( is_page_template( 'templates/template-login.php' ) ) {
        if ( session_status() === PHP_SESSION_NONE ) {
            session_start();
        }
        if ( isset( $_GET['mobile'] ) && $_GET['mobile'] === '1' ) {
            $_SESSION['tmw_mobile_login'] = true;
        }
    }
}

// Also start session for AJAX calls (admin-ajax.php doesn't start one automatically)
add_action( 'init', 'tmw_mobile_ajax_session', 1 );

function tmw_mobile_ajax_session(): void {
    if ( wp_doing_ajax() && session_status() === PHP_SESSION_NONE ) {
        session_start();
    }
}

// =============================================================================
// 3. BYPASS LOGGED-IN REDIRECT ON LOGIN PAGE FOR MOBILE
//    security.php redirects logged-in users away from template-login.php.
//    For mobile, if someone re-visits /login?mobile=1 while already logged in,
//    redirect them straight to the success URL so the app can grab the cookies.
// =============================================================================

add_action( 'template_redirect', 'tmw_mobile_logged_in_bypass', 5 );

function tmw_mobile_logged_in_bypass(): void {
    if ( ! is_page_template( 'templates/template-login.php' ) ) {
        return;
    }
    if ( ! is_user_logged_in() ) {
        return;
    }
    // Mobile app re-visiting /login while already logged in → send to success URL
    if ( isset( $_GET['mobile'] ) && $_GET['mobile'] === '1' ) {
        wp_safe_redirect( tmw_mobile_success_url() );
        exit;
    }
}

// =============================================================================
// 4. INJECT MOBILE HIDDEN INPUT INTO THE LOGIN FORM TEMPLATE
//    template-login.php renders the form. We hook into wp_head to inject
//    the hidden input via JS on the client, since we can't directly modify
//    the form output from PHP without editing the template.
//    We also use wp_footer to inject it via DOM manipulation — zero template changes needed.
// =============================================================================

add_action( 'wp_footer', 'tmw_mobile_inject_form_input', 1 );

function tmw_mobile_inject_form_input(): void {
    if ( ! is_page_template( 'templates/template-login.php' ) ) {
        return;
    }
    if ( ! isset( $_GET['mobile'] ) || $_GET['mobile'] !== '1' ) {
        return;
    }
    ?>
    <script id="tmw-mobile-input-injector">
    (function() {
        var form = document.getElementById('tmw-login-form');
        if (!form) return;

        // Inject hidden mobile=1 field so forms.js sends it in the AJAX POST
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'mobile';
        input.value = '1';
        form.appendChild(input);
    })();
    </script>
    <?php
}

// =============================================================================
// 5. PATCH THE AJAX LOGIN HANDLER TO RETURN MOBILE SUCCESS URL
//    We need to intercept tmw_ajax_login() before it calls wp_send_json_success().
//    The cleanest approach: hook into wp_login (fires after wp_signon succeeds)
//    and store a flag. Then filter the AJAX response.
//    Since we can't easily filter wp_send_json_success, we use a different approach:
//    Remove the original handler and replace it with our extended version.
// =============================================================================

/**
 * Remove original login handler and replace with mobile-aware version.
 * We use priority 5 (after wp_ajax registration at default 10 doesn't work —
 * the action is registered in ajax-handlers.php which loads before us).
 * So we unhook and re-register.
 */
add_action( 'init', 'tmw_mobile_override_login_ajax', 20 );

function tmw_mobile_override_login_ajax(): void {
    // Remove the original handler
    remove_action( 'wp_ajax_nopriv_tmw_login', 'tmw_ajax_login' );

    // Register our replacement (works for both logged-out and logged-in,
    // though login while already logged-in is an edge case)
    add_action( 'wp_ajax_nopriv_tmw_login', 'tmw_mobile_ajax_login' );
    add_action( 'wp_ajax_tmw_login',        'tmw_mobile_ajax_login' );
}

/**
 * Mobile-aware login AJAX handler.
 * Drop-in replacement for tmw_ajax_login() in ajax-handlers.php.
 * Identical logic, but detects mobile=1 and overrides the redirect URL.
 */
function tmw_mobile_ajax_login(): void {
    check_ajax_referer( 'tmw_nonce', 'nonce' );

    $username = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : '';
    $password = isset( $_POST['password'] ) ? $_POST['password'] : '';
    $remember = isset( $_POST['remember'] ) && $_POST['remember'] === 'true';

    if ( empty( $username ) || empty( $password ) ) {
        wp_send_json_error( array(
            'message' => __( 'Please enter your username and password.', 'flavor-starter-flavor' ),
        ) );
    }

    $user = wp_authenticate( $username, $password );

    if ( is_wp_error( $user ) ) {
        wp_send_json_error( array(
            'message' => __( 'Invalid username or password.', 'flavor-starter-flavor' ),
        ) );
    }

    // Log the user in and set cookies
    wp_set_auth_cookie( $user->ID, $remember );
    wp_set_current_user( $user->ID );

    // -------------------------------------------------------------------
    // MOBILE APP DETECTION
    // Check both the POST field (from our injected hidden input) and session.
    // -------------------------------------------------------------------
    $is_mobile = tmw_is_mobile_app_request();

    if ( $is_mobile ) {
        // Clear the session flag so it doesn't linger
        if ( session_status() === PHP_SESSION_ACTIVE ) {
            unset( $_SESSION['tmw_mobile_login'] );
        }

        // Return the success URL — Android WebView will intercept this redirect
        wp_send_json_success( array(
            'redirect' => tmw_mobile_success_url(),
            'message'  => __( 'Login successful! Redirecting...', 'flavor-starter-flavor' ),
            'mobile'   => true, // Optional flag for debugging
        ) );
    }

    // -------------------------------------------------------------------
    // Standard web flow (unchanged from original tmw_ajax_login)
    // -------------------------------------------------------------------
    $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( $_POST['redirect_to'] ) : '';

    if ( empty( $redirect_to ) ) {
        $setting = tmw_get_setting( 'login_redirect', 'app' );
        switch ( $setting ) {
            case 'app':
                $redirect_to = tmw_get_app_url();
                break;
            case 'profile':
                $redirect_to = tmw_get_page_url( 'my-profile' );
                break;
            default:
                $redirect_to = home_url( '/' );
        }
    }

    wp_send_json_success( array(
        'redirect' => $redirect_to,
        'message'  => __( 'Login successful! Redirecting...', 'flavor-starter-flavor' ),
    ) );
}

// =============================================================================
// 6. STRIP HEADER/FOOTER CHROME FOR MOBILE WEBVIEW
//    When ?mobile=1 is on the login page URL, the WebView should see
//    only the login card — no nav, no header, no footer.
//    We add a body class and inject CSS to hide the surrounding chrome.
// =============================================================================

add_filter( 'body_class', 'tmw_mobile_body_class' );

function tmw_mobile_body_class( array $classes ): array {
    if ( is_page_template( 'templates/template-login.php' )
         && isset( $_GET['mobile'] ) && $_GET['mobile'] === '1' ) {
        $classes[] = 'tmw-mobile-webview';
    }
    return $classes;
}

add_action( 'wp_head', 'tmw_mobile_webview_styles', 99 );

function tmw_mobile_webview_styles(): void {
    if ( ! is_page_template( 'templates/template-login.php' ) ) {
        return;
    }
    if ( ! isset( $_GET['mobile'] ) || $_GET['mobile'] !== '1' ) {
        return;
    }
    ?>
    <style id="tmw-mobile-webview-css">
        /* ============================================================
           MOBILE WEBVIEW OVERRIDES
           Applied only when body has .tmw-mobile-webview class
           (i.e. when /login?mobile=1 is loaded in Android WebView)
           ============================================================ */

        body.tmw-mobile-webview {
            /* Prevent elastic scroll revealing gaps */
            overscroll-behavior: none;
        }

        /* Hide site header, footer, and mobile nav toggle */
        body.tmw-mobile-webview .tmw-header,
        body.tmw-mobile-webview .tmw-footer,
        body.tmw-mobile-webview .tmw-nav-mobile,
        body.tmw-mobile-webview .tmw-mobile-toggle {
            display: none !important;
        }

        /* Remove top padding that was reserved for the header */
        body.tmw-mobile-webview .tmw-main {
            padding-top: 0 !important;
            margin-top: 0 !important;
        }

        /* Center the auth card vertically in the viewport */
        body.tmw-mobile-webview .tmw-auth-page {
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Larger tap targets on mobile */
        body.tmw-mobile-webview .tmw-form-group input[type="text"],
        body.tmw-mobile-webview .tmw-form-group input[type="email"],
        body.tmw-mobile-webview .tmw-form-group input[type="password"] {
            font-size: 16px !important; /* Prevents iOS keyboard zoom-on-focus */
            min-height: 48px !important;
            padding: 12px 14px !important;
        }

        body.tmw-mobile-webview .tmw-btn {
            min-height: 48px !important;
            font-size: 15px !important;
        }

        /* Hide the "Don't have an account?" footer link — not relevant inside app */
        body.tmw-mobile-webview .tmw-auth-footer,
        body.tmw-mobile-webview .tmw-divider {
            display: none !important;
        }

        /* Subtle "Sign in to GarageMinder" label replaces subtitle */
        body.tmw-mobile-webview .tmw-mobile-app-badge {
            display: inline-flex !important;
        }
    </style>
    <?php
}

// =============================================================================
// 7. ADD "SIGN IN TO GARAGEMINDER APP" BADGE IN THE TEMPLATE
//    Instead of modifying template-login.php, we inject a small badge
//    via wp_footer that JavaScript moves to the right spot in the DOM.
// =============================================================================

add_action( 'wp_footer', 'tmw_mobile_inject_app_badge', 5 );

function tmw_mobile_inject_app_badge(): void {
    if ( ! is_page_template( 'templates/template-login.php' ) ) {
        return;
    }
    if ( ! isset( $_GET['mobile'] ) || $_GET['mobile'] !== '1' ) {
        return;
    }
    ?>
    <script id="tmw-mobile-badge-injector">
    (function() {
        // Insert an "App Login" badge below the subtitle
        var subtitle = document.querySelector('.tmw-auth-subtitle');
        if (!subtitle) return;

        var badge = document.createElement('span');
        badge.className = 'tmw-badge tmw-badge-accent tmw-mobile-app-badge';
        badge.style.cssText = 'display:none; margin-top: 10px;'; // shown by CSS when .tmw-mobile-webview
        badge.innerHTML = '<i class="fas fa-mobile-alt" style="margin-right:6px;"></i>GarageMinder App';
        subtitle.insertAdjacentElement('afterend', badge);
    })();
    </script>
    <?php
}

// =============================================================================
// 8. PASS tmwData.isMobile FLAG TO JavaScript
//    Extends the localized data object so forms.js and any other script
//    can detect mobile context without re-reading the URL parameter.
// =============================================================================

add_filter( 'tmw_localize_data', 'tmw_mobile_localize_data' );

function tmw_mobile_localize_data( array $data ): array {
    $data['isMobile'] = (
        is_page_template( 'templates/template-login.php' )
        && isset( $_GET['mobile'] )
        && $_GET['mobile'] === '1'
    );
    return $data;
}

// Also inject inline if the filter isn't applied (in case enqueue.php doesn't use it)
add_action( 'wp_footer', 'tmw_mobile_inline_flag', 1 );

function tmw_mobile_inline_flag(): void {
    if ( ! is_page_template( 'templates/template-login.php' ) ) {
        return;
    }
    ?>
    <script id="tmw-mobile-flag">
    // Extend tmwData with mobile flag for forms.js
    if (typeof tmwData !== 'undefined') {
        tmwData.isMobile = <?php echo ( isset( $_GET['mobile'] ) && $_GET['mobile'] === '1' ) ? 'true' : 'false'; ?>;
    }
    </script>
    <?php
}

// =============================================================================
// 9. JS BRIDGE + SUCCESS PAGE DETECTION
//    When the WebView navigates to /app/?login_success=1, fire the optional
//    JS bridge as belt-and-suspenders alongside shouldOverrideUrlLoading().
// =============================================================================

add_action( 'wp_footer', 'tmw_mobile_success_bridge', 99 );

function tmw_mobile_success_bridge(): void {
    if ( ! isset( $_GET['login_success'] ) || $_GET['login_success'] !== '1' ) {
        return;
    }
    ?>
    <script id="tmw-mobile-success-bridge">
    (function() {
        /**
         * Android injects window.GarageMinderBridge via:
         *   webView.addJavascriptInterface(new GarageMinderBridge(), "GarageMinderBridge")
         *
         * The PRIMARY interception is shouldOverrideUrlLoading() in Android detecting
         * "login_success=1" in the URL. This JS call is secondary/optional.
         */
        if (window.GarageMinderBridge
            && typeof window.GarageMinderBridge.onLoginSuccess === 'function') {
            window.GarageMinderBridge.onLoginSuccess();
        }
    })();
    </script>
    <?php
}
