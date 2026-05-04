<?php
/**
 * Admin menu registration and page routing.
 *
 * @package WP_SiteAgent
 */

namespace WP_SiteAgent\Admin;

defined( 'ABSPATH' ) || exit;

use WP_SiteAgent\Abilities_Registry;
use WP_SiteAgent\Auth_Manager;
use WP_SiteAgent\Audit_Logger;

/**
 * Admin class.
 *
 * Registers the SiteAgent admin menu and sub-pages, enqueues assets,
 * and passes localized data to JavaScript.
 */
class Admin {

	/**
	 * Admin page hook suffixes.
	 *
	 * @var array<string>
	 */
	private array $page_hooks = [];

	/**
	 * Abilities registry.
	 *
	 * @var Abilities_Registry
	 */
	private Abilities_Registry $registry;

	/**
	 * Auth manager.
	 *
	 * @var Auth_Manager
	 */
	private Auth_Manager $auth;

	/**
	 * Audit logger.
	 *
	 * @var Audit_Logger
	 */
	private Audit_Logger $audit;

	/**
	 * Constructor.
	 *
	 * @param Abilities_Registry $registry Abilities registry.
	 * @param Auth_Manager       $auth     Auth manager.
	 * @param Audit_Logger       $audit    Audit logger.
	 */
	public function __construct(
		Abilities_Registry $registry,
		Auth_Manager $auth,
		Audit_Logger $audit
	) {
		$this->registry = $registry;
		$this->auth     = $auth;
		$this->audit    = $audit;
	}

	/**
	 * Initialize admin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register the admin menu and subpages.
	 *
	 * @return void
	 */
	public function register_menus(): void {
		// Robot/AI SVG icon as base64 encoded data URI.
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7H4a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7 14a2 2 0 0 1 2 2 2 2 0 0 1-2 2 2 2 0 0 1-2-2 2 2 0 0 1 2-2m10 0a2 2 0 0 1 2 2 2 2 0 0 1-2 2 2 2 0 0 1-2-2 2 2 0 0 1 2-2m-5 2a2 2 0 0 1 2 2 2 2 0 0 1-2 2 2 2 0 0 1-2-2 2 2 0 0 1 2-2z"/></svg>' );

		// Main menu page.
		$this->page_hooks[] = add_menu_page(
			__( 'WP SiteAgent', 'siteagent' ),
			__( 'SiteAgent', 'siteagent' ),
			'manage_options',
			'siteagent',
			[ $this, 'render_dashboard' ],
			$icon,
			80
		);

		// Dashboard submenu.
		add_submenu_page(
			'siteagent',
			__( 'Dashboard — WP SiteAgent', 'siteagent' ),
			__( 'Dashboard', 'siteagent' ),
			'manage_options',
			'siteagent',
			[ $this, 'render_dashboard' ]
		);

		// Abilities submenu.
		$this->page_hooks[] = add_submenu_page(
			'siteagent',
			__( 'Abilities — WP SiteAgent', 'siteagent' ),
			__( 'Abilities', 'siteagent' ),
			'manage_options',
			'siteagent-abilities',
			[ $this, 'render_abilities' ]
		);

		// API Tokens submenu.
		$this->page_hooks[] = add_submenu_page(
			'siteagent',
			__( 'API Tokens — WP SiteAgent', 'siteagent' ),
			__( 'API Tokens', 'siteagent' ),
			'manage_options',
			'siteagent-tokens',
			[ $this, 'render_tokens' ]
		);

		// Audit Log submenu.
		$this->page_hooks[] = add_submenu_page(
			'siteagent',
			__( 'Audit Log — WP SiteAgent', 'siteagent' ),
			__( 'Audit Log', 'siteagent' ),
			'manage_options',
			'siteagent-audit',
			[ $this, 'render_audit_log' ]
		);

		// Settings submenu.
		$this->page_hooks[] = add_submenu_page(
			'siteagent',
			__( 'Settings — WP SiteAgent', 'siteagent' ),
			__( 'Settings', 'siteagent' ),
			'manage_options',
			'siteagent-settings',
			[ $this, 'render_settings' ]
		);

		// Tools submenu.
		$this->page_hooks[] = add_submenu_page(
			'siteagent',
			__( 'Tools — WP SiteAgent', 'siteagent' ),
			__( 'Tools', 'siteagent' ),
			'manage_options',
			'siteagent-tools',
			[ $this, 'render_tools' ]
		);
	}

	/**
	 * Enqueue admin CSS and JS only on SiteAgent pages.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		// Check if we're on a SiteAgent page.
		$is_siteagent_page = str_contains( $hook, 'siteagent' );

		if ( ! $is_siteagent_page ) {
			return;
		}

		// Admin CSS.
		wp_enqueue_style(
			'siteagent-admin',
			SITEAGENT_URL . 'assets/css/admin.css',
			[],
			SITEAGENT_VERSION
		);

		// Admin JS.
		wp_enqueue_script(
			'siteagent-admin',
			SITEAGENT_URL . 'assets/js/admin.js',
			[],
			SITEAGENT_VERSION,
			true
		);

		// Token Manager JS.
		wp_enqueue_script(
			'siteagent-token-manager',
			SITEAGENT_URL . 'assets/js/token-manager.js',
			[ 'siteagent-admin' ],
			SITEAGENT_VERSION,
			true
		);

		// Dashboard Connect JS.
		if ( 'toplevel_page_siteagent' === $hook ) {
			wp_enqueue_script(
				'siteagent-dashboard-connect',
				SITEAGENT_URL . 'assets/js/dashboard-connect.js',
				[ 'siteagent-admin' ],
				SITEAGENT_VERSION,
				true
			);
		}

		// Localized data for JavaScript.
		wp_localize_script(
			'siteagent-admin',
			'siteagentAdmin',
			[
				'nonce'       => wp_create_nonce( 'siteagent_admin' ),
				'restNonce'   => wp_create_nonce( 'wp_rest' ),
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'restUrl'     => rest_url( 'siteagent/v1/' ),
				'mcpEndpoint' => rest_url( 'siteagent/v1/mcp/streamable' ),
				'pluginUrl'   => SITEAGENT_URL,
				'i18n'        => [
					'copied'        => __( 'Copied!', 'siteagent' ),
					'confirmRevoke' => __( 'Are you sure you want to revoke this token?', 'siteagent' ),
					'saving'        => __( 'Saving...', 'siteagent' ),
					'saved'         => __( 'Saved!', 'siteagent' ),
					'error'         => __( 'An error occurred. Please try again.', 'siteagent' ),
					'cacheCleared'  => __( 'Cache cleared!', 'siteagent' ),
				],
			]
		);
	}

	/**
	 * Register plugin settings using the Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'siteagent_settings',
			'siteagent_enabled',
			[ 'sanitize_callback' => 'rest_sanitize_boolean' ]
		);

		register_setting(
			'siteagent_settings',
			'siteagent_display_name',
			[ 'sanitize_callback' => 'sanitize_text_field' ]
		);

		register_setting(
			'siteagent_settings',
			'siteagent_hourly_limit',
			[ 'sanitize_callback' => 'absint' ]
		);

		register_setting(
			'siteagent_settings',
			'siteagent_daily_limit',
			[ 'sanitize_callback' => 'absint' ]
		);

		register_setting(
			'siteagent_settings',
			'siteagent_enabled_modules',
			[
				'sanitize_callback' => static function ( $value ) {
					return is_array( $value ) ? array_map( 'sanitize_key', $value ) : [];
				},
			]
		);

		register_setting(
			'siteagent_settings',
			'siteagent_cache_ttl',
			[ 'sanitize_callback' => 'absint' ]
		);

		register_setting(
			'siteagent_settings',
			'siteagent_log_retention_days',
			[ 'sanitize_callback' => 'absint' ]
		);

		register_setting(
			'siteagent_settings',
			'siteagent_log_level',
			[
				'sanitize_callback' => static function ( $value ) {
					return in_array( $value, [ 'all', 'errors-only', 'none' ], true ) ? $value : 'all';
				},
			]
		);

		register_setting(
			'siteagent_settings',
			'siteagent_delete_data_on_uninstall',
			[ 'sanitize_callback' => 'rest_sanitize_boolean' ]
		);
	}

	// -------------------------------------------------------------------------
	// Page render callbacks
	// -------------------------------------------------------------------------

	/**
	 * Render the dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'siteagent' ) );
		}
		require SITEAGENT_PATH . 'templates/admin/dashboard.php';
	}

	/**
	 * Render the abilities page.
	 *
	 * @return void
	 */
	public function render_abilities(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'siteagent' ) );
		}
		require SITEAGENT_PATH . 'templates/admin/abilities.php';
	}

	/**
	 * Render the token management page.
	 *
	 * @return void
	 */
	public function render_tokens(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'siteagent' ) );
		}
		require SITEAGENT_PATH . 'templates/admin/tokens.php';
	}

	/**
	 * Render the audit log page.
	 *
	 * @return void
	 */
	public function render_audit_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'siteagent' ) );
		}
		require SITEAGENT_PATH . 'templates/admin/audit-log.php';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'siteagent' ) );
		}
		require SITEAGENT_PATH . 'templates/admin/settings.php';
	}

	/**
	 * Render the tools page.
	 *
	 * @return void
	 */
	public function render_tools(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'siteagent' ) );
		}
		require SITEAGENT_PATH . 'templates/admin/tools.php';
	}

	/**
	 * Get the abilities registry (for templates).
	 *
	 * @return Abilities_Registry
	 */
	public function get_registry(): Abilities_Registry {
		return $this->registry;
	}

	/**
	 * Get the auth manager (for templates).
	 *
	 * @return Auth_Manager
	 */
	public function get_auth(): Auth_Manager {
		return $this->auth;
	}

	/**
	 * Get the audit logger (for templates).
	 *
	 * @return Audit_Logger
	 */
	public function get_audit(): Audit_Logger {
		return $this->audit;
	}
}

