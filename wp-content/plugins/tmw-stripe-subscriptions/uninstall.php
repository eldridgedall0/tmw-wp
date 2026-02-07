<?php
/**
 * Uninstall TMW Stripe Subscriptions
 *
 * Fired when the plugin is uninstalled/deleted.
 * Cleans up database tables and options.
 *
 * @package TMW_Stripe_Subscriptions
 */

// If uninstall not called from WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Option to keep data on uninstall (can be set in settings)
$keep_data = get_option('tmw_stripe_keep_data_on_uninstall', false);

if ($keep_data) {
    return; // Don't delete anything
}

global $wpdb;

// Delete plugin options
delete_option('tmw_stripe_settings');
delete_option('tmw_stripe_db_version');
delete_option('tmw_stripe_activated');
delete_option('tmw_stripe_keep_data_on_uninstall');

// Delete subscriptions table
$table_name = $wpdb->prefix . 'tmw_stripe_subscriptions';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Optionally clean up user meta (commented out by default to preserve data)
// Uncomment if you want complete cleanup
/*
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'tmw_stripe_%'");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'tmw_subscription_tier'");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'tmw_subscription_status'");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'tmw_subscription_current_period_end'");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'tmw_subscription_ends_at'");
$wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'tmw_tier_changed'");
*/

// Clear any scheduled events
$timestamp = wp_next_scheduled('tmw_stripe_daily_check');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'tmw_stripe_daily_check');
}

// Clear any transients
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_tmw_stripe_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_tmw_stripe_%'");
