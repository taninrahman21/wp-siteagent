<?php
/**
 * Settings page template.
 *
 * @package WP_SiteAgent
 */

defined( 'ABSPATH' ) || exit;

$siteagent_plugin        = \WP_SiteAgent\Plugin::get_instance();
$siteagent_enabled_mods  = $siteagent_plugin->get_enabled_modules();

$siteagent_all_modules = [
	'content'     => [ 'label' => __( 'Content', 'siteagent' ),     'desc' => __( 'Posts, pages, CPT management', 'siteagent' ),         'count' => 9 ],
	'seo'         => [ 'label' => __( 'SEO', 'siteagent' ),         'desc' => __( 'SEO analysis and meta management', 'siteagent' ),     'count' => 6 ],
	'diagnostics' => [ 'label' => __( 'Diagnostics', 'siteagent' ), 'desc' => __( 'Site health, error logs, cron', 'siteagent' ),        'count' => 7 ],
	'media'       => [ 'label' => __( 'Media', 'siteagent' ),       'desc' => __( 'Media library management', 'siteagent' ),             'count' => 6 ],
	'users'       => [ 'label' => __( 'Users', 'siteagent' ),       'desc' => __( 'User management', 'siteagent' ),                      'count' => 6 ],
	'woocommerce' => [ 'label' => __( 'WooCommerce', 'siteagent' ), 'desc' => __( 'Products, orders, coupons (requires WooCommerce)', 'siteagent' ), 'count' => 12 ],
];
?>
<div class="sa-wrap">
	<?php require SITEAGENT_PATH . 'templates/partials/header.php'; ?>

	<div class="sa-main-content">
		<div class="sa-container">
			<div class="sa-page-header" style="margin-bottom: 32px; border-bottom: 1px solid var(--sa-border); padding-bottom: 24px;">
				<h2 style="margin: 0; font-size: 24px; font-weight: 700; color: var(--sa-secondary);"><?php esc_html_e( 'Settings', 'siteagent' ); ?></h2>
				<p style="margin: 8px 0 0; font-size: 14px; color: var(--sa-text-muted);"><?php esc_html_e( 'Configure the core behavior of the MCP server and available tool modules.', 'siteagent' ); ?></p>
			</div>

			<form method="post" action="options.php">
			<?php settings_fields( 'siteagent_settings' ); ?>

			<div class="sa-dashboard-body">

				<div class="sa-dashboard-main">
					<!-- General Configuration -->
					<div class="sa-card">
						<div class="sa-card-header">
							<h3><?php esc_html_e( 'General Configuration', 'siteagent' ); ?></h3>
						</div>
						<div class="sa-card-body">
							<div class="sa-setting-item">
								<div class="sa-setting-info">
									<span class="sa-setting-title"><?php esc_html_e( 'Enable MCP Server', 'siteagent' ); ?></span>
									<span class="sa-setting-desc"><?php esc_html_e( 'Activate the Model Context Protocol server for this site. Disabling this will shut down the endpoint for all agents.', 'siteagent' ); ?></span>
								</div>
								<label class="sa-switch">
									<input type="checkbox" name="siteagent_enabled" value="1" <?php checked( get_option( 'siteagent_enabled', true ) ); ?> onchange="siteagent.saveOption('siteagent_enabled', this.checked)" />
									<span class="sa-slider"></span>
								</label>
							</div>

							<div class="sa-setting-item">
								<div class="sa-setting-info" style="padding-top: 8px;">
									<span class="sa-setting-title"><?php esc_html_e( 'Agent Display Name', 'siteagent' ); ?></span>
									<span class="sa-setting-desc"><?php esc_html_e( 'The localized name that AI clients (like Claude or Cursor) will see during initialization.', 'siteagent' ); ?></span>
									<input type="text" id="siteagent_display_name" name="siteagent_display_name"
										value="<?php echo esc_attr( get_option( 'siteagent_display_name', '' ) ); ?>"
										class="sa-input" style="margin-top: 12px; max-width: 400px;" placeholder="<?php esc_attr_e( 'e.g. My Website Agent', 'siteagent' ); ?>"
										onblur="siteagent.saveOption('siteagent_display_name', this.value)" />
								</div>
							</div>
						</div>
					</div>

					<!-- Active Modules -->
					<div class="sa-card" style="margin-top: 32px;">
						<div class="sa-card-header">
							<h3><?php esc_html_e( 'Active Modules', 'siteagent' ); ?></h3>
						</div>
						<div class="sa-card-body">
							<div class="sa-abilities-grid">
								<?php foreach ( $siteagent_all_modules as $siteagent_slug => $siteagent_mod ) :
									$siteagent_wc_available = $siteagent_slug !== 'woocommerce' || class_exists( 'WooCommerce' );
									$siteagent_is_active = in_array( $siteagent_slug, $siteagent_enabled_mods, true );
								?>
								<div class="sa-setting-item" style="padding: 16px; border: 1px solid var(--sa-border); border-radius: 8px; opacity: <?php echo esc_attr( $siteagent_wc_available ? '1' : '0.5' ); ?>;">
									<div class="sa-setting-info">
										<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
											<span class="sa-setting-title" style="margin-bottom: 0;"><?php echo esc_html( $siteagent_mod['label'] ); ?></span>
											<span class="sa-tag sa-tag--public" style="font-size: 10px;"><?php
												printf(
													/* translators: %d: number of tools */
													esc_html__( '%d tools', 'siteagent' ),
													(int) $siteagent_mod['count']
												); ?></span>
										</div>
										<span class="sa-setting-desc" style="font-size: 12px;"><?php echo esc_html( $siteagent_mod['desc'] ); ?></span>
										<?php if ( ! $siteagent_wc_available ) : ?>
											<small style="color: var(--sa-danger); display: block; margin-top: 4px; font-weight: 700; font-size: 10px; text-transform: uppercase;"><?php esc_html_e( 'WooCommerce Missing', 'siteagent' ); ?></small>
										<?php endif; ?>
									</div>
									<label class="sa-switch">
										<input type="checkbox" name="siteagent_enabled_modules[]" value="<?php echo esc_attr( $siteagent_slug ); ?>" <?php checked( $siteagent_is_active ); ?> <?php echo ! $siteagent_wc_available ? 'disabled' : ''; ?> 
											onchange="siteagent.toggleModule('<?php echo esc_js( $siteagent_slug ); ?>', this.checked)" />
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
							<h3><?php esc_html_e( 'Rate Limiting', 'siteagent' ); ?></h3>
						</div>
						<div class="sa-card-body">
							<div class="sa-form-group">
								<span class="sa-setting-title"><?php esc_html_e( 'Hourly Limit', 'siteagent' ); ?></span>
								<input type="number" name="siteagent_hourly_limit" value="<?php echo esc_attr( get_option( 'siteagent_hourly_limit', 200 ) ); ?>" class="sa-input" style="margin-top: 8px;" 
									onblur="siteagent.saveOption('siteagent_hourly_limit', this.value)" />
								<p class="sa-hint"><?php esc_html_e( 'Calls allowed per hour per token.', 'siteagent' ); ?></p>
							</div>
							<div class="sa-form-group" style="margin-top: 20px;">
								<span class="sa-setting-title"><?php esc_html_e( 'Daily Limit', 'siteagent' ); ?></span>
								<input type="number" name="siteagent_daily_limit" value="<?php echo esc_attr( get_option( 'siteagent_daily_limit', 2000 ) ); ?>" class="sa-input" style="margin-top: 8px;" 
									onblur="siteagent.saveOption('siteagent_daily_limit', this.value)" />
								<p class="sa-hint"><?php esc_html_e( 'Calls allowed per day per token.', 'siteagent' ); ?></p>
							</div>
						</div>
					</div>

					<!-- System Cache -->
					<div class="sa-card">
						<div class="sa-card-header">
							<h3><?php esc_html_e( 'System Cache', 'siteagent' ); ?></h3>
						</div>
						<div class="sa-card-body">
							<div class="sa-form-group">
								<span class="sa-setting-title"><?php esc_html_e( 'Cache TTL', 'siteagent' ); ?></span>
								<div style="display: flex; gap: 8px; align-items: center; margin-top: 8px;">
									<input type="number" name="siteagent_cache_ttl" value="<?php echo esc_attr( get_option( 'siteagent_cache_ttl', 3600 ) ); ?>" class="sa-input" 
										onblur="siteagent.saveOption('siteagent_cache_ttl', this.value)" />
									<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="siteagent.clearCache()"><?php esc_html_e( 'Flush', 'siteagent' ); ?></button>
								</div>
								<p class="sa-hint"><?php esc_html_e( 'Results TTL (seconds).', 'siteagent' ); ?></p>
							</div>
						</div>
					</div>

					<!-- Audit Logging -->
					<div class="sa-card">
						<div class="sa-card-header">
							<h3><?php esc_html_e( 'Audit Logging', 'siteagent' ); ?></h3>
						</div>
						<div class="sa-card-body">
							<div class="sa-form-group">
								<span class="sa-setting-title"><?php esc_html_e( 'Retention', 'siteagent' ); ?></span>
								<div style="display: flex; align-items: center; gap: 8px; margin-top: 8px;">
									<input type="number" name="siteagent_log_retention_days" value="<?php echo esc_attr( get_option( 'siteagent_log_retention_days', 30 ) ); ?>" class="sa-input" style="width: 80px;" 
										onblur="siteagent.saveOption('siteagent_log_retention_days', this.value)" />
									<span style="font-size: 13px; color: var(--sa-text-muted);"><?php esc_html_e( 'days', 'siteagent' ); ?></span>
								</div>
							</div>
							<div class="sa-form-group" style="margin-top: 20px;">
								<span class="sa-setting-title"><?php esc_html_e( 'Detail Level', 'siteagent' ); ?></span>
								<select name="siteagent_log_level" class="sa-select" style="margin-top: 8px;" onchange="siteagent.saveOption('siteagent_log_level', this.value)">
									<option value="all" <?php selected( get_option( 'siteagent_log_level', 'all' ), 'all' ); ?>><?php esc_html_e( 'Detailed (All calls)', 'siteagent' ); ?></option>
									<option value="errors-only" <?php selected( get_option( 'siteagent_log_level' ), 'errors-only' ); ?>><?php esc_html_e( 'Errors Only', 'siteagent' ); ?></option>
									<option value="none" <?php selected( get_option( 'siteagent_log_level' ), 'none' ); ?>><?php esc_html_e( 'Off', 'siteagent' ); ?></option>
								</select>
							</div>
						</div>
					</div>

					<!-- Danger Zone -->
					<div class="sa-card sa-danger-card">
						<div class="sa-card-header sa-danger-header">
							<h3 class="sa-danger-title"><?php esc_html_e( 'Maintenance', 'siteagent' ); ?></h3>
						</div>
						<div class="sa-card-body" style="padding: 20px;">
							<div style="margin-bottom: 20px;">
								<span class="sa-setting-title" style="font-size: 13px;"><?php esc_html_e( 'Full Reset', 'siteagent' ); ?></span>
								<button type="button" class="sa-btn sa-btn--sm sa-btn--ghost" style="width: 100%; margin-top: 8px; color: var(--sa-danger); border-color: rgba(214,54,56,0.1);" onclick="siteagent.dangerAction('reset_all', '<?php echo esc_js( wp_create_nonce( 'siteagent_admin' ) ); ?>')">
									<?php esc_html_e( 'Reset All Data', 'siteagent' ); ?>
								</button>
							</div>
							<div style="display: flex; justify-content: space-between; align-items: center;">
								<span class="sa-setting-title" style="margin-bottom: 0; font-size: 13px;"><?php esc_html_e( 'Auto-Cleanup', 'siteagent' ); ?></span>
								<label class="sa-switch">
									<input type="checkbox" name="siteagent_delete_data_on_uninstall" value="1" <?php checked( get_option( 'siteagent_delete_data_on_uninstall', false ) ); ?> 
										onchange="siteagent.saveOption('siteagent_delete_data_on_uninstall', this.checked)" />
									<span class="sa-slider"></span>
								</label>
							</div>
						</div>
					</div>
				</div>

			</div>

			<div class="sa-settings-footer" style="margin-top:48px; padding-top: 32px; border-top: 1px solid var(--sa-border); display:flex; justify-content:flex-end;">
				<?php submit_button( __( 'Save All Settings', 'siteagent' ), 'sa-btn sa-btn--primary', 'submit', false ); ?>
			</div>
			</form>
		</div>

		<?php require SITEAGENT_PATH . 'templates/partials/footer.php'; ?>
	</div>
</div>

