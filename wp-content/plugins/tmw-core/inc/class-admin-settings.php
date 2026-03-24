<?php
/**
 * TMW Core — Admin Settings Page
 *
 * WP Admin → TMW Core
 * Single-tab settings page covering security controls and maintenance mode.
 * Trusted Devices and Login History tabs have been removed — device
 * management is handled entirely from the user's profile page.
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
            'maintenance_message' => isset( $input['maintenance_message'] )
                ? sanitize_textarea_field( $input['maintenance_message'] )
                : '',
        );
    }

    // =========================================================================
    // ASSETS
    // =========================================================================

    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::PAGE_SLUG ) === false ) return;
        wp_enqueue_style( 'tmw-core-admin', TMW_CORE_URL . 'assets/css/tmw-core-admin.css', array(), TMW_CORE_VERSION );
    }

    // =========================================================================
    // PAGE
    // =========================================================================

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $opts = wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
        ?>
        <div class="wrap tmw-core-admin-wrap">
            <h1>
                <span class="dashicons dashicons-shield" style="font-size:26px;vertical-align:middle;margin-right:6px;"></span>
                <?php esc_html_e( 'TMW Core', 'tmw-core' ); ?>
                <span class="tmw-core-version">v<?php echo esc_html( TMW_CORE_VERSION ); ?></span>
            </h1>

            <form method="post" action="options.php" class="tmw-core-form">
                <?php settings_fields( 'tmw_core_settings_group' ); ?>

                <!-- Security Controls -->
                <div class="tmw-core-section">
                    <h2 class="tmw-core-section-title">
                        <span class="dashicons dashicons-lock"></span>
                        <?php esc_html_e( 'Security Controls', 'tmw-core' ); ?>
                    </h2>
                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="restrict_wp_admin">
                                    <?php esc_html_e( 'Restrict WP Admin Access', 'tmw-core' ); ?>
                                </label>
                            </th>
                            <td>
                                <label class="tmw-core-toggle">
                                    <input type="checkbox"
                                           id="restrict_wp_admin"
                                           name="<?php echo esc_attr( self::OPTION_KEY ); ?>[restrict_wp_admin]"
                                           value="1"
                                           <?php checked( $opts['restrict_wp_admin'] ); ?>>
                                    <span class="tmw-core-toggle-slider"></span>
                                </label>
                                <p class="description">
                                    <?php esc_html_e( 'Only administrators can access /wp-admin/. All other roles are redirected to the profile page.', 'tmw-core' ); ?>
                                </p>
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

                <!-- Maintenance Mode -->
                <div class="tmw-core-section">
                    <h2 class="tmw-core-section-title">
                        <span class="dashicons dashicons-hammer"></span>
                        <?php esc_html_e( 'Maintenance Mode', 'tmw-core' ); ?>
                    </h2>
                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="maintenance_mode">
                                    <?php esc_html_e( 'Enable Maintenance Mode', 'tmw-core' ); ?>
                                </label>
                            </th>
                            <td>
                                <label class="tmw-core-toggle">
                                    <input type="checkbox"
                                           id="maintenance_mode"
                                           name="<?php echo esc_attr( self::OPTION_KEY ); ?>[maintenance_mode]"
                                           value="1"
                                           <?php checked( $opts['maintenance_mode'] ); ?>>
                                    <span class="tmw-core-toggle-slider"></span>
                                </label>
                                <p class="description">
                                    <?php esc_html_e( 'The GarageMinder app returns a 503 maintenance response to all non-admin users. Admins are exempt.', 'tmw-core' ); ?>
                                </p>
                                <?php if ( $opts['maintenance_mode'] ) : ?>
                                    <p class="tmw-core-notice tmw-core-notice-danger">
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php esc_html_e( 'ACTIVE — the GarageMinder app is in maintenance mode. Users cannot access it.', 'tmw-core' ); ?>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="maintenance_message">
                                    <?php esc_html_e( 'Maintenance Message', 'tmw-core' ); ?>
                                </label>
                            </th>
                            <td>
                                <textarea id="maintenance_message"
                                          name="<?php echo esc_attr( self::OPTION_KEY ); ?>[maintenance_message]"
                                          class="large-text"
                                          rows="3"><?php echo esc_textarea( $opts['maintenance_message'] ); ?></textarea>
                                <p class="description">
                                    <?php esc_html_e( 'Shown to users when maintenance mode is active. Leave blank for the default message.', 'tmw-core' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( __( 'Save Settings', 'tmw-core' ) ); ?>
            </form>
        </div>
        <?php
    }
}
