<?php
/**
 * TMW Core — Login History
 *
 * Records login successes and failures to wp_tmw_login_history.
 * Data is stored silently in the background only — no admin UI,
 * no profile display. Available for future use or external queries.
 *
 * @package tmw-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TMW_Login_History {

    const MAX_PER_USER = 100;

    public static function init() {
        add_action( 'wp_login',        array( __CLASS__, 'record_success' ), 10, 2 );
        add_action( 'wp_login_failed', array( __CLASS__, 'record_failure' ), 10, 1 );
    }

    // =========================================================================
    // RECORDING
    // =========================================================================

    public static function record_success( $user_login, $user ) {
        self::insert( array(
            'user_id'  => $user->ID,
            'username' => $user_login,
            'event'    => 'login_success',
        ) );
    }

    public static function record_failure( $username ) {
        self::insert( array(
            'user_id'  => 0,
            'username' => $username,
            'event'    => 'login_failed',
        ) );
    }

    // =========================================================================
    // QUERY — available for future use
    // =========================================================================

    public static function get_for_user( $user_id, $limit = 10 ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tmw_login_history
                 WHERE user_id = %d
                 ORDER BY created_at DESC
                 LIMIT %d",
                $user_id,
                $limit
            )
        );
    }

    // =========================================================================
    // INTERNAL
    // =========================================================================

    private static function insert( $data ) {
        global $wpdb;

        $ua          = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $device_info = self::parse_ua_short( $ua );

        $wpdb->insert(
            $wpdb->prefix . 'tmw_login_history',
            array(
                'user_id'     => $data['user_id'],
                'username'    => substr( $data['username'], 0, 200 ),
                'event'       => $data['event'],
                'ip_address'  => self::get_client_ip(),
                'user_agent'  => substr( $ua, 0, 500 ),
                'device_info' => $device_info,
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $data['user_id'] > 0 ) {
            self::prune( $data['user_id'] );
        }
    }

    private static function prune( $user_id ) {
        global $wpdb;
        $count = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}tmw_login_history WHERE user_id = %d", $user_id )
        );
        if ( $count > self::MAX_PER_USER ) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}tmw_login_history WHERE user_id = %d ORDER BY created_at ASC LIMIT %d",
                    $user_id, $count - self::MAX_PER_USER
                )
            );
        }
    }

    private static function parse_ua_short( $ua ) {
        $b = 'Unknown'; $o = 'Unknown';
        if      ( strpos( $ua, 'Edg/'      ) !== false ) $b = 'Edge';
        elseif  ( strpos( $ua, 'Firefox/'  ) !== false ) $b = 'Firefox';
        elseif  ( strpos( $ua, 'Chrome/'   ) !== false ) $b = 'Chrome';
        elseif  ( strpos( $ua, 'Safari/'   ) !== false ) $b = 'Safari';
        if      ( strpos( $ua, 'iPhone'    ) !== false ) $o = 'iPhone';
        elseif  ( strpos( $ua, 'Android'   ) !== false ) $o = 'Android';
        elseif  ( strpos( $ua, 'Windows'   ) !== false ) $o = 'Windows';
        elseif  ( strpos( $ua, 'Macintosh' ) !== false ) $o = 'Mac';
        elseif  ( strpos( $ua, 'Linux'     ) !== false ) $o = 'Linux';
        return $b . ' on ' . $o;
    }

    private static function get_client_ip() {
        foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
            }
        }
        return '';
    }
}
