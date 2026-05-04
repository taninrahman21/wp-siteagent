<?php
/**
 * Core plugin singleton.
 *
 * @package WP_SiteAgent
 */

namespace WP_SiteAgent;

defined( 'ABSPATH' ) || exit;

use WP_SiteAgent\Admin\Admin;
use WP_SiteAgent\Modules\Module_Content;
use WP_SiteAgent\Modules\Module_Seo;
use WP_SiteAgent\Modules\Module_Woocommerce;
use WP_SiteAgent\Modules\Module_Diagnostics;
use WP_SiteAgent\Modules\Module_Media;
use WP_SiteAgent\Modules\Module_Users;

/**
 * Plugin class.
 *
 * Core singleton that bootstraps the entire plugin: initializes registries,
 * modules, MCP server, auth, rate limiter, audit logger, and admin UI.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered module instances.
	 *
	 * @var array<string, \WP_SiteAgent\Modules\Module_Base>
	 */
	private array $modules = [];

	/**
	 * Abilities registry instance.
	 *
	 * @var Abilities_Registry|null
	 */
	private ?Abilities_Registry $abilities_registry = null;

	/**
	 * Auth manager instance.
	 *
	 * @var Auth_Manager|null
	 */
	private ?Auth_Manager $auth_manager = null;

	/**
	 * Rate limiter instance.
	 *
	 * @var Rate_Limiter|null
	 */
	private ?Rate_Limiter $rate_limiter = null;

	/**
	 * Audit logger instance.
	 *
	 * @var Audit_Logger|null
	 */
	private ?Audit_Logger $audit_logger = null;

	/**
	 * Cache manager instance.
	 *
	 * @var Cache_Manager|null
	 */
	private ?Cache_Manager $cache_manager = null;

	/**
	 * MCP server instance.
	 *
	 * @var MCP_Server|null
	 */
	private ?MCP_Server $mcp_server = null;

	/**
	 * Private constructor — use get_instance().
	 */
	private function __construct() {}

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Boot the plugin.
	 *
	 * Called on plugins_loaded hook.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->init_services();
		$this->init_modules();
		$this->register_abilities();
		$this->boot_mcp_server();
		$this->register_cron_callbacks();

		if ( is_admin() ) {
			$this->boot_admin();
		}

		$this->boot_ajax_handlers();

		// Allow third-party plugins to hook in after everything is loaded.
		do_action( 'siteagent_loaded' );
	}

	/**
	 * Initialize core services.
	 */
	private function init_services(): void {
		$this->abilities_registry = new Abilities_Registry();
		$this->auth_manager       = new Auth_Manager();
		$this->rate_limiter       = new Rate_Limiter();
		$this->audit_logger       = new Audit_Logger();
		$this->cache_manager      = new Cache_Manager();

		// Make services accessible globally via plugin instance.
		Error_Handler::init();
	}

	/**
	 * Initialize and boot all enabled modules.
	 *
	 * @return void
	 */
	private function init_modules(): void {
		$all_modules = [
			'content'     => Module_Content::class,
			'seo'         => Module_Seo::class,
			'diagnostics' => Module_Diagnostics::class,
			'media'       => Module_Media::class,
			'users'       => Module_Users::class,
		];

		// Only add WooCommerce module if WooCommerce is active.
		if ( class_exists( 'WooCommerce' ) ) {
			$all_modules['woocommerce'] = Module_Woocommerce::class;
		}

		// Allow third-party filtering of the module list.
		$all_modules = apply_filters( 'siteagent_modules', $all_modules );

		foreach ( $all_modules as $name => $class ) {
			if ( class_exists( $class ) ) {
				$module                  = new $class( $this->abilities_registry );
				$this->modules[ $name ]  = $module;
			}
		}
	}

	/**
	 * Boot all modules (registers their abilities).
	 *
	 * @return void
	 */
	private function register_abilities(): void {
		foreach ( $this->modules as $module ) {
			$module->boot();
		}

		do_action( 'siteagent_abilities_registered' );
	}

	/**
	 * Boot the MCP server and REST API.
	 *
	 * @return void
	 */
	private function boot_mcp_server(): void {
		$this->mcp_server = new MCP_Server(
			$this->abilities_registry,
			$this->auth_manager,
			$this->rate_limiter,
			$this->audit_logger
		);

		// Register REST routes.
		add_action( 'rest_api_init', [ $this->mcp_server, 'register_routes' ] );

		// Register our custom REST controller.
		$rest_controller = new \WP_SiteAgent\Api\Rest_Controller(
			$this->abilities_registry,
			$this->auth_manager,
			$this->rate_limiter,
			$this->audit_logger,
			$this->cache_manager
		);
		add_action( 'rest_api_init', [ $rest_controller, 'register_routes' ] );
	}

	/**
	 * Boot the admin interface.
	 *
	 * @return void
	 */
	private function boot_admin(): void {
		$admin = new Admin(
			$this->abilities_registry,
			$this->auth_manager,
			$this->audit_logger
		);
		$admin->init();
	}

	/**
	 * Register AJAX action handlers for admin UI operations.
	 *
	 * Handles danger zone actions and single-option saves from the settings page.
	 *
	 * @return void
	 */
	private function boot_ajax_handlers(): void {
		add_action( 'wp_ajax_siteagent_danger_action', [ $this, 'ajax_danger_action' ] );
		add_action( 'wp_ajax_siteagent_save_option', [ $this, 'ajax_save_option' ] );
		add_action( 'wp_ajax_siteagent_toggle_ability', [ $this, 'ajax_toggle_ability' ] );
		add_action( 'wp_ajax_siteagent_toggle_module', [ $this, 'ajax_toggle_module' ] );
	}

	/**
	 * AJAX: Toggle a module in the enabled_modules list.
	 *
	 * @return void
	 */
	public function ajax_toggle_module(): void {
		check_ajax_referer( 'siteagent_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'siteagent' ) ], 403 );
		}

		$module_slug = sanitize_key( wp_unslash( $_POST['module_slug'] ?? '' ) );
		$is_enabled  = rest_sanitize_boolean( wp_unslash( $_POST['is_enabled'] ?? '0' ) );

		if ( empty( $module_slug ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing module slug.', 'siteagent' ) ], 400 );
		}

		$enabled = (array) get_option( 'siteagent_enabled_modules', [] );

		if ( $is_enabled ) {
			if ( ! in_array( $module_slug, $enabled, true ) ) {
				$enabled[] = $module_slug;
			}
		} else {
			$enabled = array_diff( $enabled, [ $module_slug ] );
		}

		update_option( 'siteagent_enabled_modules', array_values( array_unique( $enabled ) ) );

		wp_send_json_success( [ 'saved' => true, 'is_enabled' => $is_enabled ] );
	}

	/**
	 * AJAX: Toggle an individual ability (enable/disable).
	 *
	 * @return void
	 */
	public function ajax_toggle_ability(): void {
		check_ajax_referer( 'siteagent_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'siteagent' ) ], 403 );
		}

		$ability_name = sanitize_text_field( wp_unslash( $_POST['ability_name'] ?? '' ) );
		$is_enabled   = rest_sanitize_boolean( wp_unslash( $_POST['is_enabled'] ?? '0' ) );

		if ( empty( $ability_name ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing ability name.', 'siteagent' ) ], 400 );
		}

		$disabled = (array) get_option( 'siteagent_disabled_abilities', [] );

		if ( $is_enabled ) {
			// Remove from disabled list.
			$disabled = array_diff( $disabled, [ $ability_name ] );
		} else {
			// Add to disabled list.
			if ( ! in_array( $ability_name, $disabled, true ) ) {
				$disabled[] = $ability_name;
			}
		}

		update_option( 'siteagent_disabled_abilities', array_values( array_unique( $disabled ) ) );

		wp_send_json_success( [ 'saved' => true, 'is_enabled' => $is_enabled ] );
	}

	/**
	 * AJAX: Execute a danger-zone action.
	 *
	 * @return void
	 */
	public function ajax_danger_action(): void {
		check_ajax_referer( 'siteagent_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'siteagent' ) ], 403 );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['danger_action'] ?? '' ) );

		switch ( $action ) {
			case 'delete_tokens':
				$count = $this->auth_manager->delete_all_tokens();
				wp_send_json_success( [
					'message' => sprintf(
						/* translators: %d: number of tokens deleted */
						__( 'Deleted %d tokens.', 'siteagent' ),
						$count
					),
				] );
				break;

			case 'delete_logs':
				$count = $this->audit_logger->delete_all_logs();
				wp_send_json_success( [
					'message' => sprintf(
						/* translators: %d: number of log entries deleted */
						__( 'Deleted %d log entries.', 'siteagent' ),
						$count
					),
				] );
				break;

			case 'reset_all':
				// Delete all plugin options.
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'siteagent_%'" );

				// Clear logs and tokens.
				$this->audit_logger->delete_all_logs();
				$this->auth_manager->delete_all_tokens();

				wp_send_json_success( [
					'message' => __( 'All plugin data has been reset to defaults.', 'siteagent' ),
				] );
				break;

			default:
				wp_send_json_error( [ 'message' => __( 'Unknown action.', 'siteagent' ) ], 400 );
		}
	}

	public function ajax_save_option(): void {
		check_ajax_referer( 'siteagent_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'siteagent' ) ], 403 );
		}

		$allowed = [
			'siteagent_enabled',
			'siteagent_delete_data_on_uninstall',
			'siteagent_display_name',
			'siteagent_hourly_limit',
			'siteagent_daily_limit',
			'siteagent_cache_ttl',
			'siteagent_log_retention_days',
			'siteagent_log_level',
		];

		$option_name = sanitize_key( wp_unslash( $_POST['option_name'] ?? '' ) );

		if ( ! in_array( $option_name, $allowed, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Option not allowed via AJAX.', 'siteagent' ) ], 400 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below based on option type.
		$option_value = wp_unslash( $_POST['option_value'] ?? '' );

		// Sanitize based on option type.
		if ( in_array( $option_name, [ 'siteagent_enabled', 'siteagent_delete_data_on_uninstall' ], true ) ) {
			$option_value = rest_sanitize_boolean( $option_value );
		} elseif ( in_array( $option_name, [ 'siteagent_hourly_limit', 'siteagent_daily_limit', 'siteagent_cache_ttl', 'siteagent_log_retention_days' ], true ) ) {
			$option_value = absint( $option_value );
		} elseif ( 'siteagent_display_name' === $option_name ) {
			$option_value = sanitize_text_field( $option_value );
		} elseif ( 'siteagent_log_level' === $option_name ) {
			$option_value = in_array( $option_value, [ 'all', 'errors-only', 'none' ], true ) ? $option_value : 'all';
		} else {
			$option_value = sanitize_text_field( $option_value );
		}

		update_option( $option_name, $option_value );

		wp_send_json_success( [ 'saved' => true ] );
	}

	/**
	 * Register WP-Cron callbacks.
	 *
	 * @return void
	 */
	private function register_cron_callbacks(): void {
		add_action( 'siteagent_cleanup_logs', [ $this->audit_logger, 'cleanup_old_logs' ] );
		add_action( 'siteagent_cleanup_expired_tokens', [ $this->auth_manager, 'delete_expired_tokens' ] );
	}

	/**
	 * Get a registered module instance by name.
	 *
	 * @param string $name Module name (e.g. 'content', 'seo').
	 * @return \WP_SiteAgent\Modules\Module_Base|null
	 */
	public function get_module( string $name ): ?\WP_SiteAgent\Modules\Module_Base {
		return $this->modules[ $name ] ?? null;
	}

	/**
	 * Get list of enabled module names from options.
	 *
	 * @return array<string>
	 */
	public function get_enabled_modules(): array {
		return (array) get_option( 'siteagent_enabled_modules', [ 'content', 'seo', 'diagnostics', 'media', 'users' ] );
	}

	/**
	 * Get the abilities registry.
	 *
	 * @return Abilities_Registry
	 */
	public function get_abilities_registry(): Abilities_Registry {
		return $this->abilities_registry;
	}

	/**
	 * Get the auth manager.
	 *
	 * @return Auth_Manager
	 */
	public function get_auth_manager(): Auth_Manager {
		return $this->auth_manager;
	}

	/**
	 * Get the audit logger.
	 *
	 * @return Audit_Logger
	 */
	public function get_audit_logger(): Audit_Logger {
		return $this->audit_logger;
	}

	/**
	 * Get the cache manager.
	 *
	 * @return Cache_Manager
	 */
	public function get_cache_manager(): Cache_Manager {
		return $this->cache_manager;
	}

	/**
	 * Get the rate limiter.
	 *
	 * @return Rate_Limiter
	 */
	public function get_rate_limiter(): Rate_Limiter {
		return $this->rate_limiter;
	}

	/**
	 * Get all registered module instances.
	 *
	 * @return array<string, \WP_SiteAgent\Modules\Module_Base>
	 */
	public function get_modules(): array {
		return $this->modules;
	}
}

