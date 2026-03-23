<?php
/**
 * TMW Core — AJAX Handlers
 *
 * Frontend endpoints (logged-in users):
 *   tmw_core_trust_device         — trust current device from profile page
 *   tmw_core_revoke_device         — revoke a specific device
 *   tmw_core_revoke_all_devices    — revoke all of the user's devices
 *
 * Admin endpoints:
 *   tmw_core_admin_revoke_device   — admin: revoke any device by ID
 *
 * Login integration:
 *   Hooks into wp_login action (which fires inside the theme's tmw_ajax_login
 *   handler) to process the trust_device flag from the login form POST.
 *
 * @package tmw-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TMW_Core_Ajax {

    public static function init() {
        // Frontend (logged-in)
        add_action( 'wp_ajax_tmw_core_trust_device',       array( __CLASS__, 'trust_device' ) );
        add_action( 'wp_ajax_tmw_core_revoke_device',      array( __CLASS__, 'revoke_device' ) );
        add_action( 'wp_ajax_tmw_core_revoke_all_devices', array( __CLASS__, 'revoke_all_devices' ) );

        // Admin
        add_action( 'wp_ajax_tmw_core_admin_revoke_device', array( __CLASS__, 'admin_revoke_device' ) );

        // Intercept theme login to process trust_device checkbox
        add_action( 'wp_login', array( __CLASS__, 'handle_trust_on_login' ), 10, 2 );

        // Enqueue frontend assets on login + profile pages
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
    }

    // =========================================================================
    // TRUST CURRENT DEVICE (profile page)
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
    // REVOKE A DEVICE (user's own)
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
    // REVOKE ALL DEVICES (user)
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
    // ADMIN: REVOKE ANY DEVICE
    // =========================================================================

    public static function admin_revoke_device() {
        check_ajax_referer( 'tmw_core_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'tmw-core' ) ) );
        }

        $device_id = (int) ( $_POST['device_id'] ?? 0 );
        $user_id   = (int) ( $_POST['user_id']   ?? 0 );

        if ( ! $device_id || ! $user_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'tmw-core' ) ) );
        }

        if ( TMW_Trusted_Devices::revoke_device( $device_id, $user_id ) ) {
            wp_send_json_success( array( 'message' => __( 'Device revoked.', 'tmw-core' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Device not found.', 'tmw-core' ) ) );
        }
    }

    // =========================================================================
    // INTERCEPT LOGIN — process trust_device flag
    // =========================================================================

    /**
     * Fires via wp_login action inside the theme's tmw_ajax_login handler.
     * If the user checked "Stay logged in on this device", we trust the device now.
     *
     * @param string  $user_login
     * @param WP_User $user
     */
    public static function handle_trust_on_login( $user_login, $user ) {
        if ( ! wp_doing_ajax() ) {
            return;
        }

        // The theme's JS sends trust_device=true (string)
        $trust = ! empty( $_POST['trust_device'] ) && $_POST['trust_device'] === 'true';

        if ( $trust ) {
            TMW_Trusted_Devices::trust_current_device( $user->ID );
        }
    }

    // =========================================================================
    // FRONTEND ASSETS
    // =========================================================================

    public static function enqueue_frontend_assets() {
        $on_profile = is_page_template( 'templates/template-profile.php' );
        $on_login   = is_page_template( 'templates/template-login.php' );

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
}
