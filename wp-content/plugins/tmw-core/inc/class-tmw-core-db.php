<?php
/**
 * TMW Core — Database Installer
 *
 * @package tmw-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TMW_Core_DB {

    const SCHEMA_VERSION = '1.0.0';
    const OPTION_KEY     = 'tmw_core_db_version';

    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── Trusted devices ───────────────────────────────────────────────────
        // Raw token lives in the browser cookie only.
        // Only the SHA-256 hash is stored here so a DB leak is harmless.
        $sql_devices = "CREATE TABLE {$wpdb->prefix}tmw_trusted_devices (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id       BIGINT UNSIGNED NOT NULL,
            token_hash    VARCHAR(64)     NOT NULL,
            device_label  VARCHAR(200)    NOT NULL DEFAULT '',
            device_info   VARCHAR(500)    NOT NULL DEFAULT '',
            ip_address    VARCHAR(45)     NOT NULL DEFAULT '',
            created_at    DATETIME        NOT NULL,
            last_used_at  DATETIME        NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY   token_hash (token_hash),
            KEY          user_id    (user_id)
        ) $charset;";

        // ── Login history ─────────────────────────────────────────────────────
        $sql_history = "CREATE TABLE {$wpdb->prefix}tmw_login_history (
            id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id      BIGINT UNSIGNED NOT NULL DEFAULT 0,
            username     VARCHAR(200)    NOT NULL DEFAULT '',
            event        VARCHAR(50)     NOT NULL DEFAULT 'login_success',
            ip_address   VARCHAR(45)     NOT NULL DEFAULT '',
            user_agent   VARCHAR(500)    NOT NULL DEFAULT '',
            device_info  VARCHAR(200)    NOT NULL DEFAULT '',
            created_at   DATETIME        NOT NULL,
            PRIMARY KEY (id),
            KEY user_id    (user_id),
            KEY event      (event),
            KEY created_at (created_at)
        ) $charset;";

        dbDelta( $sql_devices );
        dbDelta( $sql_history );

        update_option( self::OPTION_KEY, self::SCHEMA_VERSION );
    }

    public static function maybe_upgrade() {
        if ( version_compare( get_option( self::OPTION_KEY, '0.0.0' ), self::SCHEMA_VERSION, '<' ) ) {
            self::install();
        }
    }

    // Intentionally does NOT drop tables on deactivate — data is preserved.
    public static function deactivate() {}
}
