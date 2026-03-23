<?php
/**
 * TMW Core — Admin Settings Page
 *
 * WP Admin menu: TMW Core (position 58, just above the theme's TrackMyWrench menu)
 *
 * Tabs:
 *   General       — security controls, maintenance mode
 *   Trusted Devices — browse and revoke devices across all users
 *   Login History  — site-wide login audit log
 *
 * @package tmw-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TMW_Core_Admin_Settings {

    const PAGE_SLUG    = 'tmw-core';
    const OPTION_KEY   = 'tmw_core_settings';
    const NONCE_ACTION = 'tmw_core_admin_nonce';

    public static function init() {
        add_action( 'admin_menu',            array( __CLASS__, 'register_menu' ) );
        add_action( 'admin_init',            array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    // =========================================================================
    // MENU
    // =========================================================================

    public static function register_menu() {
        add_menu_page(
            __( 'TMW Core', 'tmw-core' ),
            __( 'TMW Core', 'tmw-core' ),
            'manage_options',
            self::PAGE_SLUG,
            array( __CLASS__, 'render_page' ),
            'dashicons-shield',
            58
        );
    }

    // =========================================================================
    // SETTINGS
    // =========================================================================

    public static function register_settings() {
        register_setting( 'tmw_core_settings_group', self::OPTION_KEY, array(
            'type'              => 'array',
            'sanitize_callback' => array( __CLASS__, 'sanitize' ),
            'default'           => self::defaults(),
        ) );
    }

    public static function defaults() {
        return array(
            'restrict_wp_admin'   => false,
            'maintenance_mode'    => false,
            'maintenance_message' => '',
        );
    }

    public static function sanitize( $input ) {
        return array(
            'restrict_wp_admin'   => ! empty( $input['restrict_wp_admin'] ),
            'maintenance_mode'    => ! empty( $input['maintenance_mode'] ),
            'maintenance_message' => isset( $input['maintenance_message'] ) ? sanitize_textarea_field( $input['maintenance_message'] ) : '',
        );
    }

    // =========================================================================
    // ASSETS
    // =========================================================================

    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::PAGE_SLUG ) === false ) return;

        wp_enqueue_style(  'tmw-core-admin', TMW_CORE_URL . 'assets/css/tmw-core-admin.css', array(), TMW_CORE_VERSION );
        wp_enqueue_script( 'tmw-core-admin', TMW_CORE_URL . 'assets/js/tmw-core-admin.js',  array( 'jquery' ), TMW_CORE_VERSION, true );

        wp_localize_script( 'tmw-core-admin', 'tmwCoreAdmin', array(
            'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'i18n'    => array(
                'confirmRevokeDevice' => __( 'Remove this trusted device?', 'tmw-core' ),
                'confirmRevokeAll'    => __( 'Remove ALL trusted devices for this user?', 'tmw-core' ),
                'error'               => __( 'An error occurred. Please try again.', 'tmw-core' ),
            ),
        ) );
    }

    // =========================================================================
    // PAGE
    // =========================================================================

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
        $opts = wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
        ?>
        <div class="wrap tmw-core-admin-wrap">
            <h1>
                <span class="dashicons dashicons-shield" style="font-size:26px;vertical-align:middle;margin-right:6px;"></span>
                <?php esc_html_e( 'TMW Core', 'tmw-core' ); ?>
                <span class="tmw-core-version">v<?php echo esc_html( TMW_CORE_VERSION ); ?></span>
            </h1>

            <nav class="nav-tab-wrapper tmw-core-tabs">
                <?php
                $tabs = array(
                    'general' => array( 'icon' => 'dashicons-admin-settings', 'label' => __( 'General',         'tmw-core' ) ),
                    'devices' => array( 'icon' => 'dashicons-laptop',          'label' => __( 'Trusted Devices', 'tmw-core' ) ),
                    'history' => array( 'icon' => 'dashicons-list-view',       'label' => __( 'Login History',   'tmw-core' ) ),
                );
                foreach ( $tabs as $slug => $t ) : ?>
                    <a href="?page=<?php echo esc_attr( self::PAGE_SLUG ); ?>&tab=<?php echo esc_attr( $slug ); ?>"
                       class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr( $t['icon'] ); ?>"></span>
                        <?php echo esc_html( $t['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="tmw-core-tab-content">
                <?php
                switch ( $tab ) {
                    case 'devices': self::render_tab_devices(); break;
                    case 'history': self::render_tab_history(); break;
                    default:        self::render_tab_general( $opts ); break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    // =========================================================================
    // TAB: GENERAL
    // =========================================================================

    private static function render_tab_general( $opts ) {
        ?>
        <form method="post" action="options.php" class="tmw-core-form">
            <?php settings_fields( 'tmw_core_settings_group' ); ?>

            <div class="tmw-core-section">
                <h2 class="tmw-core-section-title">
                    <span class="dashicons dashicons-lock"></span>
                    <?php esc_html_e( 'Security Controls', 'tmw-core' ); ?>
                </h2>
                <table class="form-table">
                    <tr>
                        <th><label for="restrict_wp_admin"><?php esc_html_e( 'Restrict WP Admin Access', 'tmw-core' ); ?></label></th>
                        <td>
                            <label class="tmw-core-toggle">
                                <input type="checkbox" id="restrict_wp_admin"
                                       name="<?php echo esc_attr( self::OPTION_KEY ); ?>[restrict_wp_admin]"
                                       value="1" <?php checked( $opts['restrict_wp_admin'] ); ?>>
                                <span class="tmw-core-toggle-slider"></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'Only administrators can access /wp-admin/. All other roles are redirected to the profile page.', 'tmw-core' ); ?></p>
                            <?php if ( $opts['restrict_wp_admin'] ) : ?>
                                <p class="tmw-core-notice tmw-core-notice-warning">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php esc_html_e( 'Active — non-administrator users cannot access WP Admin.', 'tmw-core' ); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="tmw-core-section">
                <h2 class="tmw-core-section-title">
                    <span class="dashicons dashicons-hammer"></span>
                    <?php esc_html_e( 'Maintenance Mode', 'tmw-core' ); ?>
                </h2>
                <table class="form-table">
                    <tr>
                        <th><label for="maintenance_mode"><?php esc_html_e( 'Enable Maintenance Mode', 'tmw-core' ); ?></label></th>
                        <td>
                            <label class="tmw-core-toggle">
                                <input type="checkbox" id="maintenance_mode"
                                       name="<?php echo esc_attr( self::OPTION_KEY ); ?>[maintenance_mode]"
                                       value="1" <?php checked( $opts['maintenance_mode'] ); ?>>
                                <span class="tmw-core-toggle-slider"></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'The GarageMinder app returns a 503 maintenance response to all non-admin users. Admins are exempt.', 'tmw-core' ); ?></p>
                            <?php if ( $opts['maintenance_mode'] ) : ?>
                                <p class="tmw-core-notice tmw-core-notice-danger">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php esc_html_e( 'ACTIVE — the GarageMinder app is in maintenance mode. Users cannot access it.', 'tmw-core' ); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="maintenance_message"><?php esc_html_e( 'Maintenance Message', 'tmw-core' ); ?></label></th>
                        <td>
                            <textarea id="maintenance_message"
                                      name="<?php echo esc_attr( self::OPTION_KEY ); ?>[maintenance_message]"
                                      class="large-text" rows="3"><?php echo esc_textarea( $opts['maintenance_message'] ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Shown to users when maintenance mode is active. Leave blank for the default message.', 'tmw-core' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button( __( 'Save Settings', 'tmw-core' ) ); ?>
        </form>
        <?php
    }

    // =========================================================================
    // TAB: TRUSTED DEVICES
    // =========================================================================

    private static function render_tab_devices() {
        global $wpdb;

        $paged    = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $per_page = 25;
        $offset   = ( $paged - 1 ) * $per_page;
        $total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}tmw_trusted_devices" );

        $devices = $wpdb->get_results( $wpdb->prepare(
            "SELECT d.*, u.user_login, u.display_name
             FROM {$wpdb->prefix}tmw_trusted_devices d
             LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID
             ORDER BY d.last_used_at DESC
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ) );
        ?>
        <div class="tmw-core-section">
            <h2 class="tmw-core-section-title">
                <span class="dashicons dashicons-laptop"></span>
                <?php printf( esc_html__( 'All Trusted Devices (%d total)', 'tmw-core' ), $total ); ?>
            </h2>
            <?php if ( empty( $devices ) ) : ?>
                <p><?php esc_html_e( 'No trusted devices found.', 'tmw-core' ); ?></p>
            <?php else : ?>
            <table class="wp-list-table widefat fixed striped tmw-core-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'User',        'tmw-core' ); ?></th>
                        <th><?php esc_html_e( 'Device',      'tmw-core' ); ?></th>
                        <th><?php esc_html_e( 'IP Address',  'tmw-core' ); ?></th>
                        <th><?php esc_html_e( 'Added',       'tmw-core' ); ?></th>
                        <th><?php esc_html_e( 'Last Used',   'tmw-core' ); ?></th>
                        <th><?php esc_html_e( 'Actions',     'tmw-core' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $devices as $d ) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $d->display_name ?: $d->user_login ); ?></strong><br>
                            <small><?php echo esc_html( $d->user_login ); ?></small>
                        </td>
                        <td><?php echo esc_html( $d->device_label ?: __( 'Unknown', 'tmw-core' ) ); ?></td>
                        <td><?php echo esc_html( $d->ip_address ); ?></td>
                        <td><?php echo esc_html( date_i18n( get_option('date_format'), strtotime( $d->created_at ) ) ); ?></td>
                        <td><?php echo esc_html( human_time_diff( strtotime( $d->last_used_at ), current_time('timestamp') ) . ' ago' ); ?></td>
                        <td>
                            <button type="button"
                                    class="button button-small button-link-delete tmw-admin-revoke-device"
                                    data-device-id="<?php echo esc_attr( $d->id ); ?>"
                                    data-user-id="<?php echo esc_attr( $d->user_id ); ?>">
                                <?php esc_html_e( 'Revoke', 'tmw-core' ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $total_pages = ceil( $total / $per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'current'   => $paged,
                    'total'     => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ) );
                echo '</div></div>';
            }
            ?>
            <?php endif; ?>
        </div>
        <?php
    }

    // =========================================================================
    // TAB: LOGIN HISTORY
    // =========================================================================

    private static function render_tab_history() {
        global $wpdb;

        $paged    = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $per_page = 50;
        $offset   = ( $paged - 1 ) * $per_page;
        $total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}tmw_login_history" );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT h.*, u.user_login, u.display_name
             FROM {$wpdb->prefix}tmw_login_history h
             LEFT JOIN {$wpdb->users} u ON h.user_id = u.ID
             ORDER BY h.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ) );
        ?>
        <div class="tmw-core-section">
            <h2 class="tmw-core-section-title">
                <span class="dashicons dashicons-list-view"></span>
                <?php printf( esc_html__( 'Login History (%d entries)', 'tmw-core' ), $total ); ?>
            </h2>
            <?php if ( empty( $rows ) ) : ?>
                <p><?php esc_html_e( 'No login history recorded yet.', 'tmw-core' ); ?></p>
            <?php else : ?>
            <table class="wp-list-table widefat fixed striped tmw-core-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date / Time', 'tmw-core' ); ?></th>
                        <th><?php esc_html_e( 'User',        'tmw-core' ); ?></th>
                        <th><?php esc_html_e( 'Status',      'tmw-core' ); ?></th>
                        <th><?php esc_html_e( 'Device',      'tmw-core' ); ?></th>
                        <th><?php esc_html_e( 'IP Address',  'tmw-core' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $rows as $r ) : ?>
                    <tr class="<?php echo $r->event === 'login_failed' ? 'tmw-row-failed' : ''; ?>">
                        <td><?php echo esc_html( date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime( $r->created_at ) ) ); ?></td>
                        <td>
                            <?php echo $r->user_id
                                ? esc_html( $r->display_name ?: $r->user_login )
                                : '<em>' . esc_html( $r->username ) . '</em>'; ?>
                        </td>
                        <td>
                            <?php if ( $r->event === 'login_success' ) : ?>
                                <span class="tmw-core-badge tmw-core-badge-success">✓ <?php esc_html_e( 'Success', 'tmw-core' ); ?></span>
                            <?php else : ?>
                                <span class="tmw-core-badge tmw-core-badge-danger">✗ <?php esc_html_e( 'Failed', 'tmw-core' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $r->device_info ); ?></td>
                        <td><?php echo esc_html( $r->ip_address ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php
            $total_pages = ceil( $total / $per_page );
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'current'   => $paged,
                    'total'     => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ) );
                echo '</div></div>';
            }
            ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
