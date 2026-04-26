<?php
/**
 * Uninstall script — runs only when the plugin is deleted via WP Admin.
 *
 * @package WP_SiteAgent
 */

// Security: WordPress must initiate this uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only delete data if the user opted in.
$delete = get_option( 'siteagent_delete_data_on_uninstall', false );

if ( ! $delete ) {
	return;
}

global $wpdb;

// Drop custom tables.
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}siteagent_tokens`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}siteagent_audit_log`" );
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}siteagent_rate_limits`" );

// Delete all plugin options.
$options = [
	'siteagent_version',
	'siteagent_enabled',
	'siteagent_display_name',
	'siteagent_hourly_limit',
	'siteagent_daily_limit',
	'siteagent_enabled_modules',
	'siteagent_cache_ttl',
	'siteagent_log_retention_days',
	'siteagent_log_level',
	'siteagent_delete_data_on_uninstall',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete all SiteAgent transients.
$wpdb->query(
	"DELETE FROM `{$wpdb->options}`
	WHERE `option_name` LIKE '_transient_siteagent_%'
	OR `option_name` LIKE '_transient_timeout_siteagent_%'"
);

// Remove cron schedule.
wp_clear_scheduled_hook( 'siteagent_cleanup_logs' );
wp_clear_scheduled_hook( 'siteagent_cleanup_rate_limits' );
