<?php
/**
 * Uninstall script — runs only when the plugin is deleted via WP Admin.
 *
 * @package MySiteHand
 */

// Security: WordPress must initiate this uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only delete data if the user opted in.
$msh_delete = get_option( 'msh_delete_data_on_uninstall', false );

if ( ! $msh_delete ) {
	return;
}

global $wpdb;

// Drop custom tables.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}msh_tokens`" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}msh_audit_log`" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}msh_rate_limits`" );

// Delete all plugin options.
$msh_options = [
	'msh_version',
	'msh_enabled',
	'msh_display_name',
	'msh_hourly_limit',
	'msh_daily_limit',
	'msh_enabled_modules',
	'msh_cache_ttl',
	'msh_log_retention_days',
	'msh_log_level',
	'msh_delete_data_on_uninstall',
];

foreach ( $msh_options as $msh_option ) {
	delete_option( $msh_option );
}

// Delete all my-site-hand transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM `{$wpdb->options}`
	WHERE `option_name` LIKE '_transient_MSH_%'
	OR `option_name` LIKE '_transient_timeout_MSH_%'"
);

// Remove cron schedule.
wp_clear_scheduled_hook( 'msh_cleanup_logs' );
wp_clear_scheduled_hook( 'msh_cleanup_rate_limits' );




