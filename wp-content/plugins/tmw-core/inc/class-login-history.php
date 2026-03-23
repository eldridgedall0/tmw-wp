<?php
/**
 * TMW Core — Login History
 *
 * Records every login success and failure. Profile page shows the last 5 entries.
 * Full log is available in WP Admin → TMW Core → Login History.
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
        add_action( 'tmw_profile_sections', array( __CLASS__, 'render_profile_section' ) );
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
    // QUERIES
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
    // PROFILE SECTION
    // =========================================================================

    public static function render_profile_section( $user ) {
        $recent = self::get_for_user( $user->ID, 5 );
        if ( empty( $recent ) ) {
            return;
        }
        ?>
        <div class="tmw-profile-section" id="tmw-login-history-section">
            <h2 class="tmw-profile-section-title">
                <i class="fas fa-history"></i>
                <?php esc_html_e( 'Recent Login Activity', 'tmw-core' ); ?>
            </h2>
            <div class="tmw-card">
                <div class="tmw-card-body">
                    <table class="tmw-login-history-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Date', 'tmw-core' ); ?></th>
                                <th><?php esc_html_e( 'Device', 'tmw-core' ); ?></th>
                                <th><?php esc_html_e( 'IP Address', 'tmw-core' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'tmw-core' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $recent as $row ) : ?>
                            <tr>
                                <td><?php echo esc_html( date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $row->created_at ) ) ); ?></td>
                                <td><?php echo esc_html( $row->device_info ?: __( 'Unknown', 'tmw-core' ) ); ?></td>
                                <td><?php echo esc_html( $row->ip_address ); ?></td>
                                <td>
                                    <?php if ( $row->event === 'login_success' ) : ?>
                                        <span class="tmw-status-badge tmw-status-success">
                                            <i class="fas fa-check"></i> <?php esc_html_e( 'Success', 'tmw-core' ); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="tmw-status-badge tmw-status-failed">
                                            <i class="fas fa-times"></i> <?php esc_html_e( 'Failed', 'tmw-core' ); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
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
