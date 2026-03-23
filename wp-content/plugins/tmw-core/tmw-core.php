<?php
/**
 * Plugin Name: TMW Core
 * Plugin URI:  https://trackmywrench.com
 * Description: Core functionality for TrackMyWrench — trusted devices, login history, security controls, and maintenance mode.
 * Version:     1.0.0
 * Author:      TrackMyWrench
 * Text Domain: tmw-core
 * Domain Path: /languages
 *
 * @package tmw-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// CONSTANTS
// =============================================================================
define( 'TMW_CORE_VERSION', '1.0.0' );
define( 'TMW_CORE_DIR',     plugin_dir_path( __FILE__ ) );
define( 'TMW_CORE_URL',     plugin_dir_url( __FILE__ ) );
define( 'TMW_CORE_SLUG',    'tmw-core' );

// =============================================================================
// AUTOLOAD MODULES
// =============================================================================
require_once TMW_CORE_DIR . 'inc/class-tmw-core-db.php';
require_once TMW_CORE_DIR . 'inc/class-trusted-devices.php';
require_once TMW_CORE_DIR . 'inc/class-login-history.php';
require_once TMW_CORE_DIR . 'inc/class-security-controls.php';
require_once TMW_CORE_DIR . 'inc/class-admin-settings.php';
require_once TMW_CORE_DIR . 'inc/class-ajax-handlers.php';

// =============================================================================
// ACTIVATION / DEACTIVATION
// =============================================================================
register_activation_hook( __FILE__, array( 'TMW_Core_DB', 'install' ) );
register_deactivation_hook( __FILE__, array( 'TMW_Core_DB', 'deactivate' ) );

// =============================================================================
// BOOT
// =============================================================================
add_action( 'plugins_loaded', 'tmw_core_boot', 5 );

function tmw_core_boot() {
    TMW_Trusted_Devices::init();
    TMW_Login_History::init();
    TMW_Security_Controls::init();
    TMW_Core_Admin_Settings::init();
    TMW_Core_Ajax::init();
}

// =============================================================================
// GLOBAL HELPER — get a plugin option with fallback
// =============================================================================
function tmw_core_option( $key, $default = '' ) {
    $options = get_option( 'tmw_core_settings', array() );
    return isset( $options[ $key ] ) && $options[ $key ] !== '' ? $options[ $key ] : $default;
}
