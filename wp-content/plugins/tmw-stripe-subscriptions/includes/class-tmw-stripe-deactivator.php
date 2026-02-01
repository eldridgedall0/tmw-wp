<?php
/**
 * Plugin Deactivator
 *
 * Handles plugin deactivation tasks.
 *
 * @package TMW_Stripe_Subscriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class TMW_Stripe_Deactivator {

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        self::clear_scheduled_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear scheduled cron events
     */
    private static function clear_scheduled_events() {
        $timestamp = wp_next_scheduled('tmw_stripe_daily_check');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'tmw_stripe_daily_check');
        }
    }
}
