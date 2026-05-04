<?php
/**
 * Users module — user management abilities.
 *
 * @package WP_SiteAgent
 */

namespace WP_SiteAgent\Modules;

defined( 'ABSPATH' ) || exit;

/**
 * Module Users class.
 *
 * Provides abilities for listing, creating, and updating WordPress users
 * and roles. Never exposes password hashes.
 */
class Module_Users extends Module_Base {

	/**
	 * {@inheritdoc}
	 */
	public function get_module_name(): string {
		return 'users';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_ability_names(): array {
		return [
			'siteagent/list-users',
			'siteagent/get-user',
			'siteagent/create-user',
			'siteagent/update-user-role',
			'siteagent/list-roles',
			'siteagent/get-user-stats',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function register_abilities(): void {
		$this->register_list_users();
		$this->register_get_user();
		$this->register_create_user();
		$this->register_update_user_role();
		$this->register_list_roles();
		$this->register_get_user_stats();
	}

	// -------------------------------------------------------------------------
	// Ability: siteagent_list-users
	// -------------------------------------------------------------------------

	/**
	 * Register the siteagent_list-users ability.
	 *
	 * @return void
	 */
	private function register_list_users(): void {
		$this->register(
			'siteagent/list-users',
			[
				'label'               => __( 'List Users', 'siteagent' ),
				'description'         => __( 'List WordPress users with filtering and sorting.', 'siteagent' ),
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'role'          => [ 'type' => 'string', 'description' => 'Filter by role slug' ],
						'limit'         => [ 'type' => 'integer', 'default' => 20, 'maximum' => 100 ],
						'offset'        => [ 'type' => 'integer', 'default' => 0 ],
						'search'        => [ 'type' => 'string' ],
						'orderby'       => [ 'type' => 'string', 'enum' => [ 'registered', 'login', 'display_name', 'email' ], 'default' => 'registered' ],
						'inactive_days' => [ 'type' => 'integer', 'description' => 'Filter users with no login in N days' ],
					],
				],
				'permission_callback' => static function ( int $user_id ) {
					if ( $user_id > 0 ) {
						$user = get_user_by( 'id', $user_id );
						return $user && $user->has_cap( 'list_users' );
					}
					return current_user_can( 'list_users' );
				},
				'execute_callback'    => [ $this, 'execute_list_users' ],
				'annotations'         => [
					'readonly' => true,
					'meta'     => [ 'mcp' => [ 'public' => true ] ],
				],
			]
		);
	}

	/**
	 * Execute siteagent_list-users.
	 *
	 * @param array<string, mixed> $input Validated input.
	 * @return array<string, mixed>
	 */
	public function execute_list_users( array $input ): array {
		$args = [
			'number'  => min( absint( $input['limit'] ?? 20 ), 100 ),
			'offset'  => absint( $input['offset'] ?? 0 ),
			'orderby' => sanitize_text_field( $input['orderby'] ?? 'registered' ),
			'order'   => 'DESC',
		];

		if ( ! empty( $input['role'] ) ) {
			$args['role'] = sanitize_text_field( $input['role'] );
		}

		if ( ! empty( $input['search'] ) ) {
			$args['search']         = '*' . sanitize_text_field( $input['search'] ) . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
		}

		$users  = get_users( $args );
		$result = [];

		foreach ( $users as $user ) {
			// Filter by inactive days if requested.
			if ( ! empty( $input['inactive_days'] ) ) {
				$last_login = get_user_meta( $user->ID, 'siteagent_last_login', true );
				if ( $last_login ) {
					$days_since = ( time() - strtotime( $last_login ) ) / DAY_IN_SECONDS;
					if ( $days_since < (int) $input['inactive_days'] ) {
						continue;
					}
				}
			}

			$result[] = $this->format_user( $user );
		}

		return [
			'users' => $result,
			'count' => count( $result ),
		];
	}

	// -------------------------------------------------------------------------
	// Ability: siteagent_get-user
	// -------------------------------------------------------------------------

	/**
	 * Register the siteagent_get-user ability.
	 *
	 * @return void
	 */
	private function register_get_user(): void {
		$this->register(
			'siteagent/get-user',
			[
				'label'               => __( 'Get User', 'siteagent' ),
				'description'         => __( 'Get a WordPress user profile (password hash never included).', 'siteagent' ),
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'user_id' ],
					'properties' => [
						'user_id' => [ 'type' => 'integer' ],
					],
				],
				'permission_callback' => static function ( int $user_id ) {
					if ( $user_id > 0 ) {
						$user = get_user_by( 'id', $user_id );
						return $user && $user->has_cap( 'list_users' );
					}
					return current_user_can( 'list_users' );
				},
				'execute_callback'    => [ $this, 'execute_get_user' ],
				'annotations'         => [
					'readonly' => true,
					'meta'     => [ 'mcp' => [ 'public' => true ] ],
				],
			]
		);
	}

	/**
	 * Execute siteagent_get-user.
	 *
	 * @param array<string, mixed> $input Validated input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function execute_get_user( array $input ): array|\WP_Error {
		$user = get_user_by( 'id', absint( $input['user_id'] ) );

		if ( ! $user ) {
			return $this->error( __( 'User not found.', 'siteagent' ), 'not_found' );
		}

		$post_count = count_user_posts( $user->ID );
		$meta       = get_user_meta( $user->ID );

		// Safe meta — exclude password-related and session keys.
		$safe_meta = [];
		$meta_blocklist = [
			'session_tokens',
			'wp_user_level',
			'_woocommerce_persistent_cart',
		];

		foreach ( $meta as $key => $values ) {
			if ( in_array( $key, $meta_blocklist, true ) || str_starts_with( $key, 'wp_capabilities' ) ) {
				continue;
			}
			$safe_meta[ $key ] = count( $values ) === 1 ? $values[0] : $values;
		}

		return [
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'display_name' => $user->display_name,
			'email'        => $user->user_email,
			'url'          => $user->user_url,
			'registered'   => $user->user_registered,
			'roles'        => $user->roles,
			'post_count'   => (int) $post_count,
			'meta'         => $safe_meta,
		];
	}

	// -------------------------------------------------------------------------
	// Ability: siteagent_create-user
	// -------------------------------------------------------------------------

	/**
	 * Register the siteagent_create-user ability.
	 *
	 * @return void
	 */
	private function register_create_user(): void {
		$this->register(
			'siteagent/create-user',
			[
				'label'               => __( 'Create User', 'siteagent' ),
				'description'         => __( 'Create a new WordPress user with a secure auto-generated password.', 'siteagent' ),
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'username', 'email', 'role' ],
					'properties' => [
						'username'          => [ 'type' => 'string' ],
						'email'             => [ 'type' => 'string' ],
						'role'              => [ 'type' => 'string' ],
						'first_name'        => [ 'type' => 'string' ],
						'last_name'         => [ 'type' => 'string' ],
						'send_notification' => [ 'type' => 'boolean', 'default' => true ],
					],
				],
				'permission_callback' => static function ( int $user_id ) {
					if ( $user_id > 0 ) {
						$user = get_user_by( 'id', $user_id );
						return $user && $user->has_cap( 'create_users' );
					}
					return current_user_can( 'create_users' );
				},
				'execute_callback'    => [ $this, 'execute_create_user' ],
				'annotations'         => [
					'meta' => [ 'mcp' => [ 'public' => true ] ],
				],
			]
		);
	}

	/**
	 * Execute siteagent_create-user.
	 *
	 * @param array<string, mixed> $input Validated input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function execute_create_user( array $input ): array|\WP_Error {
		$username = sanitize_user( $input['username'] );
		$email    = sanitize_email( $input['email'] );
		$role     = sanitize_text_field( $input['role'] );

		// Validate role exists.
		$wp_roles = wp_roles();
		if ( ! isset( $wp_roles->roles[ $role ] ) ) {
			return $this->error(
				sprintf(
					/* translators: %s: role name */
					__( 'Role "%s" does not exist.', 'siteagent' ),
					$role
				),
				'invalid_role'
			);
		}

		// Check username/email not taken.
		if ( username_exists( $username ) ) {
			return $this->error( __( 'Username already exists.', 'siteagent' ), 'username_exists' );
		}
		if ( email_exists( $email ) ) {
			return $this->error( __( 'Email address already in use.', 'siteagent' ), 'email_exists' );
		}

		// Generate secure password.
		$password = wp_generate_password( 24, true, true );

		$user_data = [
			'user_login' => $username,
			'user_email' => $email,
			'user_pass'  => $password,
			'role'       => $role,
		];

		if ( ! empty( $input['first_name'] ) ) {
			$user_data['first_name'] = sanitize_text_field( $input['first_name'] );
		}
		if ( ! empty( $input['last_name'] ) ) {
			$user_data['last_name'] = sanitize_text_field( $input['last_name'] );
		}

		$user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Send notification if requested.
		if ( $input['send_notification'] ?? true ) {
			wp_new_user_notification( $user_id, null, 'both' );
		}

		return [
			'user_id'  => $user_id,
			'username' => $username,
			'email'    => $email,
			'role'     => $role,
		];
	}

	// -------------------------------------------------------------------------
	// Ability: siteagent_update-user-role
	// -------------------------------------------------------------------------

	/**
	 * Register the siteagent_update-user-role ability.
	 *
	 * @return void
	 */
	private function register_update_user_role(): void {
		$this->register(
			'siteagent/update-user-role',
			[
				'label'               => __( 'Update User Role', 'siteagent' ),
				'description'         => __( 'Change a WordPress user\'s role.', 'siteagent' ),
				'input_schema'        => [
					'type'       => 'object',
					'required'   => [ 'user_id', 'role' ],
					'properties' => [
						'user_id' => [ 'type' => 'integer' ],
						'role'    => [ 'type' => 'string' ],
					],
				],
				'permission_callback' => static function ( int $user_id ) {
					if ( $user_id > 0 ) {
						$user = get_user_by( 'id', $user_id );
						return $user && $user->has_cap( 'edit_users' );
					}
					return current_user_can( 'edit_users' );
				},
				'execute_callback'    => [ $this, 'execute_update_user_role' ],
				'annotations'         => [
					'meta' => [ 'mcp' => [ 'public' => true ] ],
				],
			]
		);
	}

	/**
	 * Execute siteagent_update-user-role.
	 *
	 * @param array<string, mixed> $input Validated input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function execute_update_user_role( array $input ): array|\WP_Error {
		$target_user_id = absint( $input['user_id'] );
		$new_role       = sanitize_text_field( $input['role'] );

		$target_user = get_user_by( 'id', $target_user_id );
		if ( ! $target_user ) {
			return $this->error( __( 'User not found.', 'siteagent' ), 'not_found' );
		}

		// Validate role exists.
		$wp_roles = wp_roles();
		if ( ! isset( $wp_roles->roles[ $new_role ] ) ) {
			return $this->error(
				sprintf(
					/* translators: %s: role name */
					__( 'Role "%s" does not exist.', 'siteagent' ),
					$new_role
				),
				'invalid_role'
			);
		}

		// Prevent demoting admins unless acting user is also admin.
		if ( in_array( 'administrator', $target_user->roles, true ) && 'administrator' !== $new_role ) {
			$current_user = wp_get_current_user();
			if ( ! in_array( 'administrator', $current_user->roles, true ) ) {
				return $this->error(
					__( 'Only administrators can demote other administrators.', 'siteagent' ),
					'insufficient_permissions'
				);
			}
		}

		$target_user->set_role( $new_role );

		return [
			'updated'      => true,
			'user_id'      => $target_user_id,
			'username'     => $target_user->user_login,
			'new_role'     => $new_role,
		];
	}

	// -------------------------------------------------------------------------
	// Ability: siteagent_list-roles
	// -------------------------------------------------------------------------

	/**
	 * Register the siteagent_list-roles ability.
	 *
	 * @return void
	 */
	private function register_list_roles(): void {
		$this->register(
			'siteagent/list-roles',
			[
				'label'            => __( 'List Roles', 'siteagent' ),
				'description'      => __( 'List all registered WordPress roles and their capabilities.', 'siteagent' ),
				'execute_callback' => [ $this, 'execute_list_roles' ],
				'annotations'      => [
					'readonly' => true,
					'meta'     => [ 'mcp' => [ 'public' => true ] ],
				],
			]
		);
	}

	/**
	 * Execute siteagent_list-roles.
	 *
	 * @param array<string, mixed> $input Validated input.
	 * @return array<int, array<string, mixed>>
	 */
	public function execute_list_roles( array $input ): array {
		$wp_roles = wp_roles();
		$roles    = [];

		foreach ( $wp_roles->roles as $role_slug => $role_data ) {
			$roles[] = [
				'slug'         => $role_slug,
				'name'         => $role_data['name'],
				'capabilities' => array_keys( array_filter( $role_data['capabilities'] ) ),
				'user_count'   => count( get_users( [ 'role' => $role_slug, 'fields' => 'ids' ] ) ),
			];
		}

		return $roles;
	}

	// -------------------------------------------------------------------------
	// Ability: siteagent_get-user-stats
	// -------------------------------------------------------------------------

	/**
	 * Register the siteagent_get-user-stats ability.
	 *
	 * @return void
	 */
	private function register_get_user_stats(): void {
		$this->register(
			'siteagent/get-user-stats',
			[
				'label'               => __( 'User Stats', 'siteagent' ),
				'description'         => __( 'Get aggregate user statistics including counts by role and registration trends.', 'siteagent' ),
				'permission_callback' => static function ( int $user_id ) {
					if ( $user_id > 0 ) {
						$user = get_user_by( 'id', $user_id );
						return $user && $user->has_cap( 'list_users' );
					}
					return current_user_can( 'list_users' );
				},
				'execute_callback'    => [ $this, 'execute_get_user_stats' ],
				'annotations'         => [
					'readonly' => true,
					'meta'     => [ 'mcp' => [ 'public' => true ] ],
				],
			]
		);
	}

	/**
	 * Execute siteagent_get-user-stats.
	 *
	 * @param array<string, mixed> $input Validated input.
	 * @return array<string, mixed>
	 */
	public function execute_get_user_stats( array $input ): array {
		$cache_key = 'user_stats';
		$cached    = get_transient( 'siteagent_' . $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$wp_roles   = wp_roles();
		$by_role    = [];
		$total_users = 0;

		foreach ( array_keys( $wp_roles->roles ) as $role ) {
			$count          = count( get_users( [ 'role' => $role, 'fields' => 'ids' ] ) );
			$by_role[ $role ] = $count;
			$total_users    += $count;
		}

		// Registrations this month.
		$month_start = gmdate( 'Y-m-01 00:00:00' );
		$week_start  = gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );

		$registered_month = count( get_users( [
			'date_query' => [ [ 'after' => $month_start ] ],
			'fields'     => 'ids',
		] ) );

		$registered_week = count( get_users( [
			'date_query' => [ [ 'after' => $week_start ] ],
			'fields'     => 'ids',
		] ) );

		$stats = [
			'total_users'         => $total_users,
			'by_role'             => $by_role,
			'registered_this_month' => $registered_month,
			'registered_this_week'  => $registered_week,
		];

		set_transient( 'siteagent_' . $cache_key, $stats, 15 * MINUTE_IN_SECONDS );

		return $stats;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Format a WP_User as a safe summary array (no password hash).
	 *
	 * @param \WP_User $user User object.
	 * @return array<string, mixed>
	 */
	private function format_user( \WP_User $user ): array {
		return [
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'display_name' => $user->display_name,
			'email'        => $user->user_email,
			'role'         => implode( ', ', $user->roles ),
			'registered'   => $user->user_registered,
			'post_count'   => (int) count_user_posts( $user->ID ),
		];
	}
}

