<?php
/**
 * TMW Core — Security Controls
 *
 * 1. WP-Admin lock  — toggle: only administrators allowed into /wp-admin/
 * 2. Maintenance mode — toggle: GarageMinder app returns 503 for non-admins
 *
 * Both are enabled/disabled from WP Admin → TMW Core → General.
 *
 * @package tmw-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TMW_Security_Controls {

    public static function init() {
        // Priority 5 — after theme's own admin_init (priority 1) so we can override
        add_action( 'admin_init', array( __CLASS__, 'maybe_block_wp_admin' ), 5 );

        // Priority 20 — after WordPress and theme are fully loaded
        add_action( 'init', array( __CLASS__, 'maybe_maintenance_mode' ), 20 );
    }

    // =========================================================================
    // WP-ADMIN LOCK
    // =========================================================================

    public static function maybe_block_wp_admin() {
        if ( ! self::is_admin_lock_enabled() ) return;
        if ( wp_doing_ajax() )               return;
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return;
        if ( current_user_can( 'manage_options' ) )      return;

        $redirect = function_exists( 'tmw_get_page_url' ) ? tmw_get_page_url( 'my-profile' ) : home_url( '/' );
        wp_safe_redirect( $redirect ?: home_url( '/' ) );
        exit;
    }

    public static function is_admin_lock_enabled() {
        return (bool) tmw_core_option( 'restrict_wp_admin', false );
    }

    // =========================================================================
    // MAINTENANCE MODE
    // =========================================================================

    public static function maybe_maintenance_mode() {
        if ( ! self::is_maintenance_mode_enabled() )                      return;
        if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) return;

        // Only intercept requests to the garage app path
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $garage_path = function_exists( 'tmw_get_app_url' )
            ? wp_parse_url( tmw_get_app_url(), PHP_URL_PATH )
            : '/garage/';

        if ( strpos( $request_uri, rtrim( $garage_path, '/' ) ) !== 0 ) return;

        // JSON / API requests
        if ( isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( $_SERVER['HTTP_ACCEPT'], 'application/json' ) !== false ) {
            http_response_code( 503 );
            header( 'Content-Type: application/json' );
            echo wp_json_encode( array(
                'success'     => false,
                'error'       => 'maintenance_mode',
                'message'     => self::get_maintenance_message(),
                'retry_after' => 3600,
            ) );
            exit;
        }
    }

    public static function is_maintenance_mode_enabled() {
        return (bool) tmw_core_option( 'maintenance_mode', false );
    }

    public static function get_maintenance_message() {
        return tmw_core_option(
            'maintenance_message',
            __( 'The app is temporarily down for maintenance. Please check back shortly.', 'tmw-core' )
        );
    }
}
