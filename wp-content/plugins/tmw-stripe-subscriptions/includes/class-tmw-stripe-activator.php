<?php
/**
 * Plugin Activator
 *
 * Handles plugin activation tasks including database table creation.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::schedule_events();

        // Store activation time
        update_option('tmw_stripe_activated', time());

        // Flush rewrite rules for REST endpoint
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'tmw_stripe_subscriptions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            stripe_customer_id varchar(255) NOT NULL,
            stripe_subscription_id varchar(255) DEFAULT NULL,
            tier_slug varchar(50) NOT NULL DEFAULT 'free',
            status varchar(20) NOT NULL DEFAULT 'active',
            current_period_start datetime DEFAULT NULL,
            current_period_end datetime DEFAULT NULL,
            trial_used tinyint(1) NOT NULL DEFAULT 0,
            canceled_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY stripe_customer_id (stripe_customer_id),
            KEY stripe_subscription_id (stripe_subscription_id),
            KEY status (status),
            KEY tier_slug (tier_slug)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Store DB version
        update_option('tmw_stripe_db_version', TMW_STRIPE_DB_VERSION);
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $existing = get_option('tmw_stripe_settings', false);

        if ($existing === false) {
            $defaults = array(
                'mode'                  => 'test',
                'test_publishable_key'  => '',
                'test_secret_key'       => '',
                'test_webhook_secret'   => '',
                'live_publishable_key'  => '',
                'live_secret_key'       => '',
                'live_webhook_secret'   => '',
                'success_url'           => '/my-profile/',
                'cancel_url'            => '/pricing/',
                'trial_enabled'         => true,
                'trial_days'            => 7,
                'proration_behavior'    => 'always_invoice',
                'cancel_at_period_end'  => true,
                'default_tier'          => 'free',
                'auto_create_customer'  => false,
            );

            update_option('tmw_stripe_settings', $defaults);
        }
    }

    /**
     * Schedule cron events
     */
    private static function schedule_events() {
        // Schedule daily check for expired subscriptions
        if (!wp_next_scheduled('tmw_stripe_daily_check')) {
            wp_schedule_event(time(), 'daily', 'tmw_stripe_daily_check');
        }
    }
}
