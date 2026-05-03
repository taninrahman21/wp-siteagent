<?php
/**
 * Plugin Name: SiteAgent for WordPress
 * Plugin URI:  https://github.com/taninrahman21/wp-siteagent
 * Description: Turn your WordPress site into an AI agent-operable command layer using the Abilities API and Model Context Protocol (MCP). Let Claude Desktop, Cursor, VS Code, and other MCP-compatible AI clients discover, read, and safely operate your WordPress site through natural language.
 * Version:     1.0.0
 * Author:      BuiltByTanin
 * Author URI:  https://github.com/taninrahman21
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-siteagent
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 *
 * @package SiteAgent_For_WordPress
 */

defined('ABSPATH') || exit;

// Plugin constants.
define('SITEAGENT_VERSION', '1.0.0');
define('SITEAGENT_DB_VERSION', '1.0.0');
define('SITEAGENT_PATH', plugin_dir_path(__FILE__));
define('SITEAGENT_URL', plugin_dir_url(__FILE__));
define('SITEAGENT_BASENAME', plugin_basename(__FILE__));
define('SITEAGENT_MIN_WP', '6.0');
define('SITEAGENT_MIN_PHP', '8.1');

/**
 * Check minimum PHP version before doing anything else.
 */
if (version_compare(PHP_VERSION, SITEAGENT_MIN_PHP, '<')) {
	add_action(
		'admin_notices',
		function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: required PHP version 2: current PHP version */
						__('WP SiteAgent requires PHP %1$s or higher. You are running PHP %2$s. Please upgrade PHP.', 'wp-siteagent'),
						SITEAGENT_MIN_PHP,
						PHP_VERSION
					)
				)
			);
		}
	);
	return;
}

/**
 * Require autoloader and vendor setup.
 */
// Load composer's autoloader if present.
if (file_exists(SITEAGENT_PATH . 'vendor/autoload.php')) {
	require_once SITEAGENT_PATH . 'vendor/autoload.php';
}

// Load the manual autoloader class file.
require_once SITEAGENT_PATH . 'includes/class-autoloader.php';

// Register the autoloader — all subsequent classes are auto-loaded.
WP_SiteAgent\Autoloader::register();

/**
 * Check minimum WordPress version.
 * We do this after autoloader so we can use our classes.
 */
function siteagent_check_wp_version(): bool
{
	global $wp_version;
	return version_compare($wp_version, SITEAGENT_MIN_WP, '>=');
}

/**
 * Display admin notice for unsupported WordPress version.
 */
function siteagent_wp_version_notice(): void
{
	global $wp_version;
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: 1: required WP version 2: current WP version */
				__('WP SiteAgent requires WordPress %1$s or higher. You are running WordPress %2$s. Please upgrade WordPress.', 'wp-siteagent'),
				SITEAGENT_MIN_WP,
				$wp_version
			)
		)
	);
}

/**
 * Plugin activation hook.
 */
function siteagent_activate(): void
{
	if (!siteagent_check_wp_version()) {
		deactivate_plugins(SITEAGENT_BASENAME);
		wp_die(
			esc_html(
				sprintf(
					/* translators: %s: required WP version */
					__('WP SiteAgent requires WordPress %s or higher.', 'wp-siteagent'),
					SITEAGENT_MIN_WP
				)
			)
		);
	}
	WP_SiteAgent\Installer::activate();
}
register_activation_hook(__FILE__, 'siteagent_activate');

/**
 * Plugin deactivation hook.
 */
function siteagent_deactivate(): void
{
	WP_SiteAgent\Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'siteagent_deactivate');

/**
 * Boot the plugin on plugins_loaded.
 */
function siteagent_boot(): void
{
	if (!siteagent_check_wp_version()) {
		add_action('admin_notices', 'siteagent_wp_version_notice');
		return;
	}

	WP_SiteAgent\Plugin::get_instance()->boot();

	// One-time fix to ensure all modules are enabled.
	if (!get_option('siteagent_modules_restored_v1')) {
		update_option('siteagent_enabled_modules', ['content', 'seo', 'diagnostics', 'media', 'users', 'woocommerce']);
		update_option('siteagent_modules_restored_v1', true);
	}
}
add_action('plugins_loaded', 'siteagent_boot', 20);
