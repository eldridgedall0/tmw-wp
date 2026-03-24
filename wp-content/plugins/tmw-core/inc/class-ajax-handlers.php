<?php
/**
 * TMW Core — AJAX Handlers
 *
 * Frontend endpoints (logged-in users):
 *   tmw_core_trust_device         — trust current device from profile page button
 *   tmw_core_revoke_device        — revoke a specific device
 *   tmw_core_revoke_all_devices   — revoke all of the user's devices
 *
 * Login integration — WHY wp_set_current_user, not wp_login:
 *   The theme's tmw_ajax_login handler calls wp_set_auth_cookie() and
 *   wp_set_current_user() but never fires do_action('wp_login').
 *   It then immediately calls wp_send_json_success() which exits.
 *   Hooking wp_login therefore never works for this login path.
 *
 *   wp_set_current_user() fires the 'set_current_user' action before it
 *   returns.  At that point $_POST is still fully intact, so we read
 *   trust_device there.  We guard against running more than once per
 *   request using a static flag.
 *
 * @package tmw-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TMW_Core_Ajax {

    public static function init() {
        // Frontend (logged-in users)
        add_action( 'wp_ajax_tmw_core_trust_device',       array( __CLASS__, 'trust_device' ) );
        add_action( 'wp_ajax_tmw_core_revoke_device',      array( __CLASS__, 'revoke_device' ) );
        add_action( 'wp_ajax_tmw_core_revoke_all_devices', array( __CLASS__, 'revoke_all_devices' ) );

        // Intercept the login — fires inside wp_set_current_user()
        // which the theme calls from tmw_ajax_login before exiting
        add_action( 'set_current_user', array( __CLASS__, 'handle_trust_on_login' ) );

        // Enqueue frontend assets
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
    }

    // =========================================================================
    // TRUST CURRENT DEVICE (profile page "Trust This Device" button)
    // =========================================================================

    public static function trust_device() {
        check_ajax_referer( 'tmw_core_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'tmw-core' ) ) );
        }

        if ( TMW_Trusted_Devices::trust_current_device( get_current_user_id() ) ) {
            wp_send_json_success( array( 'message' => __( 'This device is now trusted.', 'tmw-core' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not trust this device. Please try again.', 'tmw-core' ) ) );
        }
    }

    // =========================================================================
    // REVOKE A DEVICE
    // =========================================================================

    public static function revoke_device() {
        check_ajax_referer( 'tmw_core_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'tmw-core' ) ) );
        }

        $device_id = (int) ( $_POST['device_id'] ?? 0 );
        if ( ! $device_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid device ID.', 'tmw-core' ) ) );
        }

        if ( TMW_Trusted_Devices::revoke_device( $device_id, get_current_user_id() ) ) {
            wp_send_json_success( array( 'message' => __( 'Device removed.', 'tmw-core' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Device not found or permission denied.', 'tmw-core' ) ) );
        }
    }

    // =========================================================================
    // REVOKE ALL DEVICES
    // =========================================================================

    public static function revoke_all_devices() {
        check_ajax_referer( 'tmw_core_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'tmw-core' ) ) );
        }

        TMW_Trusted_Devices::revoke_all_devices( get_current_user_id() );
        wp_send_json_success( array( 'message' => __( 'All trusted devices removed.', 'tmw-core' ) ) );
    }

    // =========================================================================
    // INTERCEPT LOGIN — hook: set_current_user
    // =========================================================================

    /**
     * Fires every time wp_set_current_user() is called, including from inside
     * the theme's tmw_ajax_login AJAX handler.
     *
     * Guards:
     *  - Only runs during AJAX requests
     *  - Only runs when trust_device is set in POST
     *  - Only runs once per request (static $done flag)
     *  - Only runs when the user being set is actually authenticated
     *    (user_id > 0), not when WordPress resets to guest (user_id = 0)
     *  - Checks that we are handling the tmw_login action specifically
     */
    public static function handle_trust_on_login() {
        static $done = false;
        if ( $done ) {
            return;
        }

        // Must be an AJAX request for the tmw_login action
        if ( ! wp_doing_ajax() ) {
            return;
        }

        $action = isset( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : '';
        if ( $action !== 'tmw_login' ) {
            return;
        }

        // trust_device checkbox: value="1", only present in POST when checked
        if ( empty( $_POST['trust_device'] ) ) {
            return;
        }

        // get_current_user_id() reflects the user just set by wp_set_current_user()
        $user_id = get_current_user_id();
        if ( $user_id < 1 ) {
            return;
        }

        $done = true;
        TMW_Trusted_Devices::trust_current_device( $user_id );
    }

    // =========================================================================
    // FRONTEND ASSETS
    // =========================================================================

    /**
     * Enqueue CSS + JS on login and profile page templates.
     *
     * Uses a multi-condition check to cover both the case where
     * is_page_template() resolves with a relative path (the normal case when
     * the template lives in the theme) and an absolute-path match as fallback.
     */
    public static function enqueue_frontend_assets() {
        $on_profile = self::is_template( 'template-profile.php' );
        $on_login   = self::is_template( 'template-login.php' );

        if ( ! $on_profile && ! $on_login ) {
            return;
        }

        wp_enqueue_style(
            'tmw-core-frontend',
            TMW_CORE_URL . 'assets/css/tmw-core-frontend.css',
            array(),
            TMW_CORE_VERSION
        );

        wp_enqueue_script(
            'tmw-core-frontend',
            TMW_CORE_URL . 'assets/js/tmw-core-frontend.js',
            array( 'jquery' ),
            TMW_CORE_VERSION,
            true
        );

        // Use wp_localize_script — the safest cross-version way to pass data
        wp_localize_script( 'tmw-core-frontend', 'tmwCore', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'tmw_core_nonce' ),
            'i18n'    => array(
                'confirmRevoke'    => __( 'Remove this trusted device?', 'tmw-core' ),
                'confirmRevokeAll' => __( 'Remove all trusted devices? You will need to sign in again on all your devices.', 'tmw-core' ),
                'removing'         => __( 'Removing…', 'tmw-core' ),
                'trusting'         => __( 'Trusting device…', 'tmw-core' ),
                'reloading'        => __( 'Done! Reloading…', 'tmw-core' ),
                'error'            => __( 'Something went wrong. Please try again.', 'tmw-core' ),
            ),
        ) );
    }

    /**
     * Robust template check that works regardless of whether the template
     * is stored in the theme root, a subdirectory, or resolved as an
     * absolute path by WordPress.
     *
     * @param string $template_filename  e.g. 'template-profile.php'
     * @return bool
     */
    private static function is_template( $template_filename ) {
        // Standard check with relative path used by this theme
        if ( is_page_template( 'templates/' . $template_filename ) ) {
            return true;
        }

        // Fallback: compare basename of whatever template WP resolved
        $current = get_page_template_slug();
        if ( $current && basename( $current ) === $template_filename ) {
            return true;
        }

        return false;
    }
}
