<?php
/**
 * Plugin installer — creates DB tables, default options, and schedules cron.
 *
 * @package WP_SiteAgent
 */

namespace WP_SiteAgent;

defined( 'ABSPATH' ) || exit;

/**
 * Installer class.
 *
 * Handles plugin activation: creates custom DB tables, sets default options,
 * and schedules WP-Cron events.
 */
class Installer {

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_default_options();
		self::schedule_cron();
		flush_rewrite_rules();
		update_option( 'siteagent_db_version', SITEAGENT_DB_VERSION );
	}

	/**
	 * Create custom database tables using dbDelta().
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Table: siteagent_tokens.
		$table_tokens = $wpdb->prefix . 'siteagent_tokens';
		$sql_tokens   = "CREATE TABLE {$table_tokens} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			token_hash VARCHAR(64) NOT NULL,
			label VARCHAR(255) NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			abilities LONGTEXT NOT NULL,
			expires_at DATETIME DEFAULT NULL,
			last_used DATETIME DEFAULT NULL,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY token_hash (token_hash),
			KEY user_id (user_id)
		) {$charset_collate};";

		dbDelta( $sql_tokens );

		// Table: siteagent_audit_log.
		$table_audit = $wpdb->prefix . 'siteagent_audit_log';
		$sql_audit   = "CREATE TABLE {$table_audit} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			token_id BIGINT(20) UNSIGNED DEFAULT NULL,
			user_id BIGINT(20) UNSIGNED DEFAULT NULL,
			ability_name VARCHAR(255) NOT NULL,
			input_json LONGTEXT NOT NULL,
			result_status VARCHAR(20) NOT NULL,
			result_summary TEXT DEFAULT NULL,
			ip_address VARCHAR(45) NOT NULL,
			user_agent VARCHAR(500) DEFAULT NULL,
			duration_ms INT(11) DEFAULT NULL,
			executed_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY ability_name (ability_name),
			KEY executed_at (executed_at),
			KEY token_id (token_id)
		) {$charset_collate};";

		dbDelta( $sql_audit );

		// Table: siteagent_rate_limits.
		$table_rate = $wpdb->prefix . 'siteagent_rate_limits';
		$sql_rate   = "CREATE TABLE {$table_rate} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			token_id BIGINT(20) UNSIGNED NOT NULL,
			window_key VARCHAR(50) NOT NULL,
			request_count INT(11) NOT NULL DEFAULT 0,
			window_start DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY token_window (token_id, window_key)
		) {$charset_collate};";

		dbDelta( $sql_rate );
	}

	/**
	 * Set default plugin options if not already set.
	 *
	 * @return void
	 */
	private static function set_default_options(): void {
		$defaults = [
			'siteagent_hourly_limit'           => 200,
			'siteagent_daily_limit'            => 2000,
			'siteagent_log_retention_days'     => 30,
			'siteagent_enabled_modules'        => [ 'content', 'seo', 'diagnostics', 'media', 'users' ],
			'siteagent_cache_ttl'              => 3600,
			'siteagent_enabled'                => true,
			'siteagent_display_name'           => get_bloginfo( 'name' ) . ' Agent',
			'siteagent_log_level'              => 'all',
			'siteagent_delete_data_on_uninstall' => false,
		];

		foreach ( $defaults as $option => $value ) {
			if ( false === get_option( $option ) ) {
				update_option( $option, $value );
			}
		}
	}

	/**
	 * Schedule WP-Cron cleanup event.
	 *
	 * @return void
	 */
	private static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'siteagent_cleanup_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'siteagent_cleanup_logs' );
		}

		if ( ! wp_next_scheduled( 'siteagent_cleanup_expired_tokens' ) ) {
			wp_schedule_event( time(), 'daily', 'siteagent_cleanup_expired_tokens' );
		}
	}
}
