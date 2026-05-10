<?php
/**
 * Settings page template.
 *
 * @package MySiteHand
 */

defined( 'ABSPATH' ) || exit;

$msh_plugin        = \MySiteHand\Plugin::get_instance();
$msh_enabled_mods  = $msh_plugin->get_enabled_modules();

$msh_all_modules = [
	'content'     => [ 'label' => __( 'Content', 'my-site-hand' ),     'desc' => __( 'Posts, pages, CPT management', 'my-site-hand' ),         'count' => 9 ],
	'seo'         => [ 'label' => __( 'SEO', 'my-site-hand' ),         'desc' => __( 'SEO analysis and meta management', 'my-site-hand' ),     'count' => 6 ],
	'diagnostics' => [ 'label' => __( 'Diagnostics', 'my-site-hand' ), 'desc' => __( 'Site health, error logs, cron', 'my-site-hand' ),        'count' => 7 ],
	'media'       => [ 'label' => __( 'Media', 'my-site-hand' ),       'desc' => __( 'Media library management', 'my-site-hand' ),             'count' => 6 ],
	'users'       => [ 'label' => __( 'Users', 'my-site-hand' ),       'desc' => __( 'User management', 'my-site-hand' ),                      'count' => 6 ],
	'woocommerce' => [ 'label' => __( 'WooCommerce', 'my-site-hand' ), 'desc' => __( 'Products, orders, coupons (requires WooCommerce)', 'my-site-hand' ), 'count' => 12 ],
];
?>
<div class="sa-wrap">
	<?php require MSH_PATH . 'templates/partials/header.php'; ?>

	<div class="sa-main-content">
		<div class="sa-container">
			<div class="sa-page-header" style="margin-bottom: 32px; border-bottom: 1px solid var(--sa-border); padding-bottom: 24px;">
				<h2 style="margin: 0; font-size: 24px; font-weight: 700; color: var(--sa-secondary);"><?php esc_html_e( 'Settings', 'my-site-hand' ); ?></h2>
				<p style="margin: 8px 0 0; font-size: 14px; color: var(--sa-text-muted);"><?php esc_html_e( 'Configure the core behavior of the MCP server and available tool modules.', 'my-site-hand' ); ?></p>
			</div>

			<form method="post" action="options.php">
			<?php settings_fields( 'msh_settings' ); ?>

			<div class="sa-dashboard-body">

				<div class="sa-dashboard-main">
					<!-- General Configuration -->
					<div class="sa-card">
						<div class="sa-card-header">
							<h3><?php esc_html_e( 'General Configuration', 'my-site-hand' ); ?></h3>
						</div>
						<div class="sa-card-body">
							<div class="sa-setting-item">
								<div class="sa-setting-info">
									<span class="sa-setting-title"><?php esc_html_e( 'Enable MCP Server', 'my-site-hand' ); ?></span>
									<span class="sa-setting-desc"><?php esc_html_e( 'Activate the Model Context Protocol server for this site. Disabling this will shut down the endpoint for all agents.', 'my-site-hand' ); ?></span>
								</div>
								<label class="sa-switch">
									<input type="checkbox" name="msh_enabled" value="1" <?php checked( get_option( 'msh_enabled', true ) ); ?> onchange="msh.saveOption('msh_enabled', this.checked)" />
									<span class="sa-slider"></span>
								</label>
							</div>

							<div class="sa-setting-item">
								<div class="sa-setting-info" style="padding-top: 8px;">
									<span class="sa-setting-title"><?php esc_html_e( 'Agent Display Name', 'my-site-hand' ); ?></span>
									<span class="sa-setting-desc"><?php esc_html_e( 'The localized name that AI clients (like Claude or Cursor) will see during initialization.', 'my-site-hand' ); ?></span>
									<input type="text" id="msh_display_name" name="msh_display_name"
										value="<?php echo esc_attr( get_option( 'msh_display_name', '' ) ); ?>"
										class="sa-input" style="margin-top: 12px; max-width: 400px;" placeholder="<?php esc_attr_e( 'e.g. My Website Agent', 'my-site-hand' ); ?>"
										onblur="msh.saveOption('msh_display_name', this.value)" />
								</div>
							</div>
						</div>
					</div>

					<!-- Active Modules -->
					<div class="sa-card" style="margin-top: 32px;">
						<div class="sa-card-header">
							<h3><?php esc_html_e( 'Active Modules', 'my-site-hand' ); ?></h3>
						</div>
						<div class="sa-card-body">
							<div class="sa-abilities-grid">
								<?php foreach ( $msh_all_modules as $msh_slug => $msh_mod ) :
									$msh_wc_available = $msh_slug !== 'woocommerce' || class_exists( 'WooCommerce' );
									$msh_is_active = in_array( $msh_slug, $msh_enabled_mods, true );
								?>
								<div class="sa-setting-item" style="padding: 16px; border: 1px solid var(--sa-border); border-radius: 8px; opacity: <?php echo esc_attr( $msh_wc_available ? '1' : '0.5' ); ?>;">
									<div class="sa-setting-info">
										<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
											<span class="sa-setting-title" style="margin-bottom: 0;"><?php echo esc_html( $msh_mod['label'] ); ?></span>
											<span class="sa-tag sa-tag--public" style="font-size: 10px;"><?php
												printf(
													/* translators: %d: number of tools */
													esc_html__( '%d tools', 'my-site-hand' ),
													(int) $msh_mod['count']
												); ?></span>
										</div>
										<span class="sa-setting-desc" style="font-size: 12px;"><?php echo esc_html( $msh_mod['desc'] ); ?></span>
										<?php if ( ! $msh_wc_available ) : ?>
											<small style="color: var(--sa-danger); display: block; margin-top: 4px; font-weight: 700; font-size: 10px; text-transform: uppercase;"><?php esc_html_e( 'WooCommerce Missing', 'my-site-hand' ); ?></small>
										<?php endif; ?>
									</div>
									<label class="sa-switch">
										<input type="checkbox" name="msh_enabled_modules[]" value="<?php echo esc_attr( $msh_slug ); ?>" <?php checked( $msh_is_active ); ?> <?php echo ! $msh_wc_available ? 'disabled' : ''; ?> 
											onchange="msh.toggleModule('<?php echo esc_js( $msh_slug ); ?>', this.checked)" />
										<span class="sa-slider"></span>
									</label>
								</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>

				<div class="sa-dashboard-sidebar">
					<!-- Scaling & Rate Limiting -->
					<div class="sa-card">
						<div class="sa-card-header">
							<h3><?php esc_html_e( 'Rate Limiting', 'my-site-hand' ); ?></h3>
						</div>
						<div class="sa-card-body">
							<div class="sa-form-group">
								<span class="sa-setting-title"><?php esc_html_e( 'Hourly Limit', 'my-site-hand' ); ?></span>
								<input type="number" name="msh_hourly_limit" value="<?php echo esc_attr( get_option( 'msh_hourly_limit', 200 ) ); ?>" class="sa-input" style="margin-top: 8px;" 
									onblur="msh.saveOption('msh_hourly_limit', this.value)" />
								<p class="sa-hint"><?php esc_html_e( 'Calls allowed per hour per token.', 'my-site-hand' ); ?></p>
							</div>
							<div class="sa-form-group" style="margin-top: 20px;">
								<span class="sa-setting-title"><?php esc_html_e( 'Daily Limit', 'my-site-hand' ); ?></span>
								<input type="number" name="msh_daily_limit" value="<?php echo esc_attr( get_option( 'msh_daily_limit', 2000 ) ); ?>" class="sa-input" style="margin-top: 8px;" 
									onblur="msh.saveOption('msh_daily_limit', this.value)" />
								<p class="sa-hint"><?php esc_html_e( 'Calls allowed per day per token.', 'my-site-hand' ); ?></p>
							</div>
						</div>
					</div>

					<!-- System Cache -->
					<div class="sa-card">
						<div class="sa-card-header">
							<h3><?php esc_html_e( 'System Cache', 'my-site-hand' ); ?></h3>
						</div>
						<div class="sa-card-body">
							<div class="sa-form-group">
								<span class="sa-setting-title"><?php esc_html_e( 'Cache TTL', 'my-site-hand' ); ?></span>
								<div style="display: flex; gap: 8px; align-items: center; margin-top: 8px;">
									<input type="number" name="msh_cache_ttl" value="<?php echo esc_attr( get_option( 'msh_cache_ttl', 3600 ) ); ?>" class="sa-input" 
										onblur="msh.saveOption('msh_cache_ttl', this.value)" />
									<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="msh.clearCache()"><?php esc_html_e( 'Flush', 'my-site-hand' ); ?></button>
								</div>
								<p class="sa-hint"><?php esc_html_e( 'Results TTL (seconds).', 'my-site-hand' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Audit Logging -->
					<div class="sa-card">
						<div class="sa-card-header">
							<h3><?php esc_html_e( 'Audit Logging', 'my-site-hand' ); ?></h3>
						</div>
						<div class="sa-card-body">
							<div class="sa-form-group">
								<span class="sa-setting-title"><?php esc_html_e( 'Retention', 'my-site-hand' ); ?></span>
								<div style="display: flex; align-items: center; gap: 8px; margin-top: 8px;">
									<input type="number" name="msh_log_retention_days" value="<?php echo esc_attr( get_option( 'msh_log_retention_days', 30 ) ); ?>" class="sa-input" style="width: 80px;" 
										onblur="msh.saveOption('msh_log_retention_days', this.value)" />
									<span style="font-size: 13px; color: var(--sa-text-muted);"><?php esc_html_e( 'days', 'my-site-hand' ); ?></span>
								</div>
							</div>
							<div class="sa-form-group" style="margin-top: 20px;">
								<span class="sa-setting-title"><?php esc_html_e( 'Detail Level', 'my-site-hand' ); ?></span>
								<select name="msh_log_level" class="sa-select" style="margin-top: 8px;" onchange="msh.saveOption('msh_log_level', this.value)">
									<option value="all" <?php selected( get_option( 'msh_log_level', 'all' ), 'all' ); ?>><?php esc_html_e( 'Detailed (All calls)', 'my-site-hand' ); ?></option>
									<option value="errors-only" <?php selected( get_option( 'msh_log_level' ), 'errors-only' ); ?>><?php esc_html_e( 'Errors Only', 'my-site-hand' ); ?></option>
									<option value="none" <?php selected( get_option( 'msh_log_level' ), 'none' ); ?>><?php esc_html_e( 'Off', 'my-site-hand' ); ?></option>
								</select>
							</div>
						</div>
					</div>

					<!-- Danger Zone -->
					<div class="sa-card sa-danger-card">
						<div class="sa-card-header sa-danger-header">
							<h3 class="sa-danger-title"><?php esc_html_e( 'Maintenance', 'my-site-hand' ); ?></h3>
						</div>
						<div class="sa-card-body" style="padding: 20px;">
							<div style="margin-bottom: 20px;">
								<span class="sa-setting-title" style="font-size: 13px;"><?php esc_html_e( 'Full Reset', 'my-site-hand' ); ?></span>
								<button type="button" class="sa-btn sa-btn--sm sa-btn--ghost" style="width: 100%; margin-top: 8px; color: var(--sa-danger); border-color: rgba(214,54,56,0.1);" onclick="msh.dangerAction('reset_all', '<?php echo esc_js( wp_create_nonce( 'msh_admin' ) ); ?>')">
									<?php esc_html_e( 'Reset All Data', 'my-site-hand' ); ?>
								</button>
							</div>
							<div style="display: flex; justify-content: space-between; align-items: center;">
								<span class="sa-setting-title" style="margin-bottom: 0; font-size: 13px;"><?php esc_html_e( 'Auto-Cleanup', 'my-site-hand' ); ?></span>
								<label class="sa-switch">
									<input type="checkbox" name="msh_delete_data_on_uninstall" value="1" <?php checked( get_option( 'msh_delete_data_on_uninstall', false ) ); ?> 
										onchange="msh.saveOption('msh_delete_data_on_uninstall', this.checked)" />
									<span class="sa-slider"></span>
								</label>
							</div>
						</div>
					</div>
				</div>

			</div>

			<div class="sa-settings-footer" style="margin-top:48px; padding-top: 32px; border-top: 1px solid var(--sa-border); display:flex; justify-content:flex-end;">
				<?php submit_button( __( 'Save All Settings', 'my-site-hand' ), 'sa-btn sa-btn--primary', 'submit', false ); ?>
			</div>
			</form>
		</div>

		<?php require MSH_PATH . 'templates/partials/footer.php'; ?>
	</div>
</div>




