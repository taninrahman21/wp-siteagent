<?php
/**
 * Plugin Name: SiteAgent for WordPress
 * Plugin URI:  https://github.com/taninrahman21/siteagent
 * Description: Turn your WordPress site into an AI agent-operable command layer using the Abilities API and Model Context Protocol (MCP). Let Claude Desktop, Cursor, VS Code, and other MCP-compatible AI clients discover, read, and safely operate your WordPress site through natural language.
 * Version:     1.0.0
 * Author:      BuiltByTanin
 * Author URI:  https://github.com/taninrahman21
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: siteagent
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.9
 *
 * @package SiteAgent
 */

defined('ABSPATH') || exit;

/**
 * SiteAgent Plugin Constants.
 */
define('SITEAGENT_VERSION', '1.0.0');
define('SITEAGENT_DB_VERSION', '1.0.0');
define('SITEAGENT_MIN_PHP', '8.1');
define('SITEAGENT_PATH', plugin_dir_path(__FILE__));
define('SITEAGENT_BASENAME', plugin_basename(__FILE__));
define('SITEAGENT_URL', plugin_dir_url(__FILE__));

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
						__('SiteAgent requires PHP %1$s or higher. You are running PHP %2$s. Please upgrade PHP.', 'siteagent'),
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
if (file_exists(SITEAGENT_PATH . 'vendor/autoload.php')) {
	require_once SITEAGENT_PATH . 'vendor/autoload.php';
}

/**
 * Bootstrap the plugin.
 */
add_action(
	'plugins_loaded',
	function () {
		// Initialize the core plugin instance.
		$siteagent_plugin = WP_SiteAgent\Plugin::get_instance();
		$siteagent_plugin->boot();

		// One-time fix to ensure all modules are enabled on first run.
		if (!get_option('siteagent_modules_restored_v1')) {
			$siteagent_default_modules = ['content', 'seo', 'diagnostics', 'media', 'users'];
			if (class_exists('WooCommerce')) {
				$siteagent_default_modules[] = 'woocommerce';
			}
			update_option('siteagent_enabled_modules', $siteagent_default_modules);
			update_option('siteagent_modules_restored_v1', 1);
		}
	}
);

/**
 * Activation and Deactivation hooks.
 */
register_activation_hook(__FILE__, ['WP_SiteAgent\Installer', 'activate']);
register_deactivation_hook(__FILE__, ['WP_SiteAgent\Deactivator', 'deactivate']);

