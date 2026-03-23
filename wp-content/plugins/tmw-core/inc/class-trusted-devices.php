<?php
/**
 * TMW Core — Trusted Devices
 *
 * "Stay logged in on this device" with no fixed expiry.
 * Tokens never expire automatically; revoked only by the user or an admin.
 *
 * Security model
 * ──────────────
 * • 128-char hex random token generated on opt-in (random_bytes(64)).
 * • Only the SHA-256 hash stored in the DB — raw token never touches the server again.
 * • Cookie is HttpOnly, SameSite=Strict, Secure on HTTPS.
 * • Token is rotated on every auto-login so a stolen cookie cannot be replayed.
 * • Explicit logout immediately revokes the current device token.
 * • Per-user cap of 20 devices; oldest is pruned when cap is hit.
 *
 * @package tmw-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TMW_Trusted_Devices {

    const COOKIE_NAME  = 'tmw_trusted_device';
    const COOKIE_DAYS  = 3650; // ~10 years — effectively never expires unless revoked
    const MAX_PER_USER = 20;

    // =========================================================================
    // BOOT
    // =========================================================================

    public static function init() {
        add_action( 'init',              array( __CLASS__, 'maybe_autologin' ), 1 );
        add_action( 'wp_logout',         array( __CLASS__, 'revoke_on_logout' ) );
        add_action( 'tmw_login_extras',  array( __CLASS__, 'render_login_checkbox' ) );
        add_action( 'tmw_profile_sections', array( __CLASS__, 'render_profile_section' ) );
    }

    // =========================================================================
    // AUTO-LOGIN
    // =========================================================================

    public static function maybe_autologin() {
        if ( is_user_logged_in() ) {
            return;
        }

        $raw_token = isset( $_COOKIE[ self::COOKIE_NAME ] )
            ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) )
            : '';

        if ( empty( $raw_token ) ) {
            return;
        }

        $device = self::find_device_by_token( $raw_token );

        if ( ! $device ) {
            self::clear_cookie();
            return;
        }

        // Rotate token for security before logging in
        $new_raw = self::rotate_token( $device->id );
        if ( $new_raw ) {
            self::set_cookie( $new_raw );
        }

        wp_set_current_user( $device->user_id );
        wp_set_auth_cookie( $device->user_id, false );

        $user = get_user_by( 'ID', $device->user_id );
        if ( $user ) {
            do_action( 'wp_login', $user->user_login, $user );
        }
    }

    // =========================================================================
    // TRUST A DEVICE
    // =========================================================================

    /**
     * Called after a successful login when the user checked "Stay logged in".
     *
     * @param int $user_id
     * @return bool
     */
    public static function trust_current_device( $user_id ) {
        global $wpdb;

        self::enforce_device_cap( $user_id );

        $raw_token   = self::generate_token();
        $token_hash  = hash( 'sha256', $raw_token );
        $device_info = self::detect_device_info();
        $now         = current_time( 'mysql' );

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'tmw_trusted_devices',
            array(
                'user_id'      => $user_id,
                'token_hash'   => $token_hash,
                'device_label' => $device_info['label'],
                'device_info'  => $device_info['full'],
                'ip_address'   => self::get_client_ip(),
                'created_at'   => $now,
                'last_used_at' => $now,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return false;
        }

        self::set_cookie( $raw_token );
        do_action( 'tmw_device_trusted', $user_id, $wpdb->insert_id );

        return true;
    }

    // =========================================================================
    // REVOKE
    // =========================================================================

    /**
     * Revoke a specific device. Validates ownership.
     *
     * @param int $device_id
     * @param int $user_id
     * @return bool
     */
    public static function revoke_device( $device_id, $user_id ) {
        global $wpdb;
        return (bool) $wpdb->delete(
            $wpdb->prefix . 'tmw_trusted_devices',
            array( 'id' => $device_id, 'user_id' => $user_id ),
            array( '%d', '%d' )
        );
    }

    /**
     * Revoke all trusted devices for a user (e.g. on password change).
     *
     * @param int $user_id
     */
    public static function revoke_all_devices( $user_id ) {
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'tmw_trusted_devices',
            array( 'user_id' => $user_id ),
            array( '%d' )
        );
        self::clear_cookie();
    }

    /**
     * Revoke the device matching the current browser cookie on logout.
     */
    public static function revoke_on_logout() {
        $raw_token = isset( $_COOKIE[ self::COOKIE_NAME ] )
            ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) )
            : '';

        if ( ! $raw_token ) {
            return;
        }

        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'tmw_trusted_devices',
            array( 'token_hash' => hash( 'sha256', $raw_token ) ),
            array( '%s' )
        );

        self::clear_cookie();
    }

    // =========================================================================
    // QUERIES
    // =========================================================================

    public static function get_user_devices( $user_id ) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, device_label, device_info, ip_address, created_at, last_used_at
                 FROM {$wpdb->prefix}tmw_trusted_devices
                 WHERE user_id = %d
                 ORDER BY last_used_at DESC",
                $user_id
            )
        );
    }

    public static function find_device_by_token( $raw_token ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tmw_trusted_devices WHERE token_hash = %s",
                hash( 'sha256', $raw_token )
            )
        );
    }

    /**
     * Returns the DB row ID of the current browser's trusted device, or null.
     *
     * @param int $user_id
     * @return int|null
     */
    public static function get_current_device_id( $user_id ) {
        $raw_token = isset( $_COOKIE[ self::COOKIE_NAME ] )
            ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) )
            : '';

        if ( ! $raw_token ) {
            return null;
        }

        $device = self::find_device_by_token( $raw_token );

        return ( $device && (int) $device->user_id === (int) $user_id )
            ? (int) $device->id
            : null;
    }

    // =========================================================================
    // TOKEN HELPERS
    // =========================================================================

    private static function generate_token() {
        return bin2hex( random_bytes( 64 ) );
    }

    private static function rotate_token( $device_id ) {
        global $wpdb;
        $new_raw  = self::generate_token();
        $new_hash = hash( 'sha256', $new_raw );

        $ok = $wpdb->update(
            $wpdb->prefix . 'tmw_trusted_devices',
            array( 'token_hash' => $new_hash, 'last_used_at' => current_time( 'mysql' ) ),
            array( 'id' => $device_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        return $ok !== false ? $new_raw : false;
    }

    // =========================================================================
    // COOKIE HELPERS
    // =========================================================================

    private static function set_cookie( $raw_token ) {
        $expiry = time() + ( self::COOKIE_DAYS * DAY_IN_SECONDS );
        $secure = is_ssl();

        if ( PHP_VERSION_ID >= 70300 ) {
            setcookie( self::COOKIE_NAME, $raw_token, array(
                'expires'  => $expiry,
                'path'     => COOKIEPATH ?: '/',
                'domain'   => COOKIE_DOMAIN ?: '',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Strict',
            ) );
        } else {
            setcookie(
                self::COOKIE_NAME,
                $raw_token,
                $expiry,
                ( COOKIEPATH ?: '/' ) . '; SameSite=Strict',
                COOKIE_DOMAIN ?: '',
                $secure,
                true
            );
        }
    }

    private static function clear_cookie() {
        setcookie( self::COOKIE_NAME, '', time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true );
        unset( $_COOKIE[ self::COOKIE_NAME ] );
    }

    // =========================================================================
    // DEVICE DETECTION
    // =========================================================================

    private static function detect_device_info() {
        $ua      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $browser = 'Unknown Browser';
        $os      = 'Unknown OS';

        if      ( strpos( $ua, 'Edg/'      ) !== false ) $browser = 'Edge';
        elseif  ( strpos( $ua, 'OPR/'      ) !== false ) $browser = 'Opera';
        elseif  ( strpos( $ua, 'Firefox/'  ) !== false ) $browser = 'Firefox';
        elseif  ( strpos( $ua, 'Chrome/'   ) !== false ) $browser = 'Chrome';
        elseif  ( strpos( $ua, 'Safari/'   ) !== false ) $browser = 'Safari';
        elseif  ( strpos( $ua, 'Trident/'  ) !== false ) $browser = 'IE';

        if      ( strpos( $ua, 'iPhone'    ) !== false ) $os = 'iPhone';
        elseif  ( strpos( $ua, 'iPad'      ) !== false ) $os = 'iPad';
        elseif  ( strpos( $ua, 'Android'   ) !== false ) $os = 'Android';
        elseif  ( strpos( $ua, 'Windows'   ) !== false ) $os = 'Windows';
        elseif  ( strpos( $ua, 'Macintosh' ) !== false ) $os = 'Mac';
        elseif  ( strpos( $ua, 'Linux'     ) !== false ) $os = 'Linux';

        return array(
            'label' => $browser . ' on ' . $os,
            'full'  => substr( $ua, 0, 500 ),
        );
    }

    private static function get_client_ip() {
        foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }
        return '';
    }

    private static function enforce_device_cap( $user_id ) {
        global $wpdb;
        $count = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}tmw_trusted_devices WHERE user_id = %d", $user_id )
        );
        if ( $count >= self::MAX_PER_USER ) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}tmw_trusted_devices WHERE user_id = %d ORDER BY last_used_at ASC LIMIT %d",
                    $user_id,
                    $count - self::MAX_PER_USER + 1
                )
            );
        }
    }

    // =========================================================================
    // UI — Login checkbox
    // =========================================================================

    public static function render_login_checkbox() {
        ?>
        <label class="tmw-auth-remember tmw-trusted-device-label">
            <input type="checkbox" name="trust_device" id="trust_device" value="1">
            <span class="tmw-trusted-device-text">
                <?php esc_html_e( 'Stay logged in on this device', 'tmw-core' ); ?>
                <span class="tmw-trusted-device-hint">
                    <?php esc_html_e( 'Your browser will be remembered. Manage trusted devices from your profile.', 'tmw-core' ); ?>
                </span>
            </span>
        </label>
        <?php
    }

    // =========================================================================
    // UI — Profile section
    // =========================================================================

    public static function render_profile_section( $user ) {
        $devices           = self::get_user_devices( $user->ID );
        $current_device_id = self::get_current_device_id( $user->ID );
        $is_trusted        = ! is_null( $current_device_id );
        ?>
        <div class="tmw-profile-section" id="tmw-trusted-devices-section">
            <h2 class="tmw-profile-section-title">
                <i class="fas fa-shield-alt"></i>
                <?php esc_html_e( 'Trusted Devices', 'tmw-core' ); ?>
            </h2>

            <div class="tmw-card">
                <div class="tmw-card-body">

                    <?php if ( empty( $devices ) ) : ?>
                        <p class="tmw-trusted-devices-empty">
                            <?php esc_html_e( 'No trusted devices yet. Check "Stay logged in on this device" next time you sign in.', 'tmw-core' ); ?>
                        </p>
                    <?php else : ?>
                        <div class="tmw-trusted-devices-list">
                            <?php foreach ( $devices as $device ) :
                                $is_current = ( (int) $device->id === $current_device_id );
                                $last_used  = human_time_diff( strtotime( $device->last_used_at ), current_time( 'timestamp' ) );
                                $created    = date_i18n( get_option( 'date_format' ), strtotime( $device->created_at ) );
                            ?>
                            <div class="tmw-device-row <?php echo $is_current ? 'tmw-device-current' : ''; ?>"
                                 data-device-id="<?php echo esc_attr( $device->id ); ?>">
                                <div class="tmw-device-icon">
                                    <i class="fas <?php echo esc_attr( self::device_icon( $device->device_label ) ); ?>"></i>
                                </div>
                                <div class="tmw-device-info">
                                    <div class="tmw-device-name">
                                        <?php echo esc_html( $device->device_label ?: __( 'Unknown Device', 'tmw-core' ) ); ?>
                                        <?php if ( $is_current ) : ?>
                                            <span class="tmw-device-current-badge">
                                                <i class="fas fa-check-circle"></i>
                                                <?php esc_html_e( 'This device', 'tmw-core' ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tmw-device-meta">
                                        <span><i class="fas fa-clock"></i>
                                            <?php printf( esc_html__( 'Last used %s ago', 'tmw-core' ), esc_html( $last_used ) ); ?>
                                        </span>
                                        <span><i class="fas fa-calendar-alt"></i>
                                            <?php printf( esc_html__( 'Added %s', 'tmw-core' ), esc_html( $created ) ); ?>
                                        </span>
                                        <?php if ( $device->ip_address ) : ?>
                                        <span><i class="fas fa-map-marker-alt"></i>
                                            <?php echo esc_html( $device->ip_address ); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="tmw-device-actions">
                                    <button type="button"
                                            class="tmw-btn tmw-btn-danger-outline tmw-revoke-device-btn"
                                            data-device-id="<?php echo esc_attr( $device->id ); ?>"
                                            data-label="<?php echo esc_attr( $device->device_label ); ?>">
                                        <i class="fas fa-trash-alt"></i>
                                        <?php esc_html_e( 'Remove', 'tmw-core' ); ?>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! $is_trusted ) : ?>
                        <div class="tmw-trust-this-device">
                            <p class="tmw-trust-hint">
                                <?php esc_html_e( 'This browser is not currently trusted.', 'tmw-core' ); ?>
                            </p>
                            <button type="button" class="tmw-btn tmw-btn-secondary" id="tmw-trust-this-device-btn">
                                <i class="fas fa-plus-circle"></i>
                                <?php esc_html_e( 'Trust This Device', 'tmw-core' ); ?>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $devices ) ) : ?>
                        <div class="tmw-revoke-all-wrap">
                            <button type="button"
                                    class="tmw-btn tmw-btn-danger-outline tmw-btn-sm"
                                    id="tmw-revoke-all-devices-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <?php esc_html_e( 'Remove All Trusted Devices', 'tmw-core' ); ?>
                            </button>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <?php
    }

    private static function device_icon( $label ) {
        $label = strtolower( $label );
        if ( strpos( $label, 'iphone' ) !== false || strpos( $label, 'android' ) !== false ) return 'fa-mobile-alt';
        if ( strpos( $label, 'ipad'   ) !== false ) return 'fa-tablet-alt';
        return 'fa-laptop';
    }
}
