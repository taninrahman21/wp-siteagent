<?php
/**
 * Admin dashboard template.
 *
 * @package WP_SiteAgent
 */

defined('ABSPATH') || exit;

$siteagent_plugin = \WP_SiteAgent\Plugin::get_instance();
$siteagent_registry = $siteagent_plugin->get_abilities_registry();
$siteagent_auth = $siteagent_plugin->get_auth_manager();
$siteagent_audit = $siteagent_plugin->get_audit_logger();

$siteagent_stats = $siteagent_audit->get_stats();
$siteagent_tokens = $siteagent_auth->list_tokens(0);
$siteagent_abilities = $siteagent_registry->get_all();
$siteagent_recent_logs = $siteagent_audit->get_logs(['per_page' => 5, 'page' => 1]);
$siteagent_mcp_endpoint = rest_url('siteagent/v1/mcp/streamable');
$siteagent_site_url = get_site_url();
$siteagent_is_enabled = (bool) get_option('siteagent_enabled', true);
$siteagent_modules = $siteagent_plugin->get_modules();
$siteagent_enabled_mods = $siteagent_plugin->get_enabled_modules();

// Dynamic Calculations for Stats Cards
$siteagent_calls_today = $siteagent_stats['calls_today'];
$siteagent_calls_yesterday = $siteagent_stats['calls_yesterday'];
$siteagent_diff = $siteagent_calls_today - $siteagent_calls_yesterday;
$siteagent_pct_change = $siteagent_calls_yesterday > 0 ? round(($siteagent_diff / $siteagent_calls_yesterday) * 100) : 0;
$siteagent_trend_up = $siteagent_diff >= 0;

$siteagent_expiring_30d = $siteagent_auth->get_expiring_count(30);
$siteagent_public_count = count($siteagent_registry->get_mcp_public());
?>
<div class="sa-wrap">

	<?php require SITEAGENT_PATH . 'templates/partials/header.php'; ?>

	<div class="sa-main-content">
		<div class="sa-container">

			<!-- Top Status Bar -->
			<div class="sa-top-status">
				<div class="sa-status-indicator">
					<span class="sa-status-dot"></span>
					<?php esc_html_e('MCP Server Active', 'siteagent'); ?>
				</div>
				<div class="sa-status-center">
					<div class="sa-endpoint-code" id="sa-mcp-url-top">
						<?php echo esc_html(str_replace(['https://', 'http://'], '', $siteagent_mcp_endpoint)); ?>
					</div>
					<button type="button" class="sa-copy-btn sa-copy-btn--inline"
						onclick="siteagent.copyText('sa-mcp-url-top')">
						<?php esc_html_e('Copy', 'siteagent'); ?>
					</button>
				</div>
				<div class="sa-status-protocol">
					<?php esc_html_e('Protocol: 2024-11-05', 'siteagent'); ?>
				</div>
			</div>

			<!-- Stats Cards -->
			<div class="sa-stats-grid">
				<div class="sa-stat-card sa-stat--calls">
					<div class="sa-stat-icon">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
							stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="12" cy="12" r="10"></circle>
							<line x1="12" y1="16" x2="12" y2="12"></line>
							<line x1="12" y1="8" x2="12.01" y2="8"></line>
						</svg>
					</div>
					<div class="sa-stat-value" id="stat-calls-today">
						<?php echo esc_html(number_format($siteagent_stats['calls_today'])); ?>
					</div>
					<div class="sa-stat-label"><?php esc_html_e('API calls today', 'siteagent'); ?></div>
					<div class="sa-stat-meta sa-meta--<?php echo esc_attr( $siteagent_trend_up ? 'up' : 'down' ); ?>">
						<span class="sa-meta-icon"><?php echo esc_html( $siteagent_trend_up ? '↑' : '↓' ); ?></span>
						<span><?php
							printf(
								/* translators: %d: percentage change in API calls */
								esc_html__('%d%% vs yesterday', 'siteagent'),
								(int) abs($siteagent_pct_change)
							); ?></span>
					</div>
				</div>
				<div class="sa-stat-card sa-stat--tokens">
					<div class="sa-stat-icon">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
							stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<polygon
								points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
							</polygon>
						</svg>
					</div>
					<div class="sa-stat-value" id="stat-tokens"><?php echo esc_html(count($siteagent_tokens)); ?></div>
					<div class="sa-stat-label"><?php esc_html_e('Active tokens', 'siteagent'); ?></div>
					<div class="sa-stat-meta" style="color: var(--sa-text-muted);">
						<span><?php
							printf(
								/* translators: %d: number of tokens expiring in 30 days */
								esc_html__('%d expire in 30d', 'siteagent'),
								(int) $siteagent_expiring_30d
							); ?></span>
					</div>
				</div>
				<div class="sa-stat-card sa-stat--abilities">
					<div class="sa-stat-icon">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
							stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<line x1="3" y1="12" x2="21" y2="12"></line>
							<line x1="3" y1="6" x2="21" y2="6"></line>
							<line x1="3" y1="18" x2="21" y2="18"></line>
						</svg>
					</div>
					<div class="sa-stat-value" id="stat-abilities"><?php echo esc_html(count($siteagent_abilities)); ?></div>
					<div class="sa-stat-label"><?php esc_html_e('Abilities registered', 'siteagent'); ?></div>
					<div class="sa-stat-meta" style="color: var(--sa-text-muted);">
						<span><?php
							printf(
								/* translators: %d: number of public abilities */
								esc_html__('%d MCP-public', 'siteagent'),
								(int) $siteagent_public_count
							); ?></span>
					</div>
				</div>
				<div class="sa-stat-card sa-stat--errors">
					<div class="sa-stat-icon">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
							stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="12" cy="12" r="10"></circle>
							<line x1="12" y1="8" x2="12" y2="12"></line>
							<line x1="12" y1="16" x2="12.01" y2="16"></line>
						</svg>
					</div>
					<div class="sa-stat-value" id="stat-errors">
						<?php echo esc_html(number_format($siteagent_stats['errors_24h'])); ?>
					</div>
					<div class="sa-stat-label"><?php esc_html_e('Errors (24h)', 'siteagent'); ?></div>
					<div class="sa-stat-meta sa-meta--<?php echo $siteagent_stats['error_rate'] > 5 ? 'down' : 'up'; ?>">
						<span><?php
							printf(
								/* translators: %s: error rate percentage */
								esc_html__('Rate: %s%%', 'siteagent'),
								esc_html($siteagent_stats['error_rate'])
							); ?></span>
					</div>
				</div>
			</div>

			<div class="sa-dashboard-body">

				<div class="sa-dashboard-main">
					<!-- Recent Audit Log -->
					<div class="sa-card" style="margin-bottom: 0; height: 100%;">
						<div class="sa-card-header" style="border-bottom: none; padding-bottom: 0;">
							<h2 style="font-size: 14px; font-weight: 600;">
								<?php esc_html_e('Recent audit log', 'siteagent'); ?>
							</h2>
							<a href="<?php echo esc_url(admin_url('admin.php?page=siteagent-audit')); ?>"
								class="sa-link"
								style="font-size: 12px; color: var(--sa-primary); text-decoration: none; font-weight: 500;"><?php esc_html_e('View all →', 'siteagent'); ?></a>
						</div>
						<div class="sa-card-body sa-card--no-pad">
							<?php if (empty($siteagent_recent_logs['logs'])): ?>
								<div class="sa-empty-state">
									<p><?php esc_html_e('No API calls yet.', 'siteagent'); ?></p>
								</div>
							<?php else: ?>
								<div class="sa-table-wrap">
									<table class="sa-table" style="border-top: none;">
										<thead>
											<tr style="background: transparent;">
												<th
													style="background: transparent; border-bottom: 1px solid var(--sa-border); padding: 16px 24px; color: var(--sa-text-muted); font-size: 11px;">
													<?php esc_html_e('ABILITY', 'siteagent'); ?>
												</th>
												<th
													style="background: transparent; border-bottom: 1px solid var(--sa-border); padding: 16px 24px; color: var(--sa-text-muted); font-size: 11px;">
													<?php esc_html_e('TOKEN', 'siteagent'); ?>
												</th>
												<th
													style="background: transparent; border-bottom: 1px solid var(--sa-border); padding: 16px 24px; color: var(--sa-text-muted); font-size: 11px;">
													<?php esc_html_e('STATUS', 'siteagent'); ?>
												</th>
												<th
													style="background: transparent; border-bottom: 1px solid var(--sa-border); padding: 16px 24px; color: var(--sa-text-muted); font-size: 11px;">
													<?php esc_html_e('TIME', 'siteagent'); ?>
												</th>
											</tr>
										</thead>
										<tbody>
											<?php
											foreach ($siteagent_recent_logs['logs'] as $siteagent_log):
												$siteagent_token_data = $siteagent_auth->get_token($siteagent_log['token_id']);
												$siteagent_token_name = $siteagent_token_data ? ($siteagent_token_data['label'] ?? '—') : '—';
												?>
												<tr>
													<td style="font-size: 13px;">
														<div style="font-weight: 500;">
															<?php echo esc_html($siteagent_abilities[$siteagent_log['ability_name']]['label'] ?? $siteagent_log['ability_name']); ?>
														</div>
													</td>
													<td style="color: var(--sa-text-secondary); font-size: 13px;">
														<?php echo esc_html($siteagent_token_name); ?>
													</td>
													<td>
														<span
															class="sa-badge sa-badge--<?php echo esc_attr($siteagent_log['result_status']); ?>">
															<?php 
															$siteagent_status = $siteagent_log['result_status'];
															if ( 'success' === $siteagent_status ) {
																esc_html_e( 'Success', 'siteagent' );
															} elseif ( 'error' === $siteagent_status ) {
																esc_html_e( 'Error', 'siteagent' );
															} elseif ( 'rate_limited' === $siteagent_status ) {
																esc_html_e( 'Rate Limited', 'siteagent' );
															} else {
																echo esc_html( $siteagent_status );
															}
															?>
														</span>
													</td>
													<td style="color: var(--sa-text-muted); font-size: 13px;">
														<?php 
														echo esc_html( 
															sprintf( 
																/* translators: %s: relative time */
																__( '%s ago', 'siteagent' ), 
																human_time_diff( strtotime( $siteagent_log['executed_at'] ) ) 
															) 
														); 
														?>
													</td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Module Status -->
					<div class="sa-card" style="margin-bottom: 0;">
						<div class="sa-card-header"
							style="border-bottom: 1px solid var(--sa-border); padding-bottom: 16px;">
							<h2 style="font-size: 14px; font-weight: 600;">
								<?php esc_html_e('Module status', 'siteagent'); ?>
							</h2>
						</div>
						<div class="sa-card-body" style="padding-top: 16px;">
							<div class="sa-modules-grid">
								<?php
								$siteagent_all_module_defs = [
									'content' => ['label' => __('Content', 'siteagent')],
									'seo' => ['label' => __('SEO', 'siteagent')],
									'woocommerce' => ['label' => __('WooCommerce', 'siteagent')],
									'diagnostics' => ['label' => __('Diagnostics', 'siteagent')],
									'media' => ['label' => __('Media', 'siteagent')],
									'users' => ['label' => __('Users', 'siteagent')],
								];
								foreach ($siteagent_all_module_defs as $siteagent_slug => $siteagent_def):
									$siteagent_module_obj = $siteagent_modules[$siteagent_slug] ?? null;
									$siteagent_ability_count = $siteagent_module_obj ? count($siteagent_module_obj->get_ability_names()) : 0;
									$siteagent_is_mod_enabled = in_array($siteagent_slug, $siteagent_enabled_mods, true);
									$siteagent_status_class = $siteagent_is_mod_enabled ? 'sa-module-status-dot--enabled' : '';
									?>
									<div class="sa-module-card">
										<div class="sa-module-header">
											<span class="sa-module-title">
												<?php echo esc_html($siteagent_def['label']); ?>
											</span>
											<span
												class="sa-module-status-dot <?php echo esc_attr($siteagent_status_class); ?>"></span>
										</div>
										<span class="sa-module-count">
											<?php
											printf(
												/* translators: %d: number of abilities */
												esc_html__('%d abilities', 'siteagent'),
												(int) $siteagent_ability_count
											); ?>
										</span>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>

				<div class="sa-dashboard-sidebar">
					<!-- Connect AI Client -->
					<div class="sa-card" style="margin-bottom: 24px;">
						<div class="sa-card-header" style="border-bottom: none; padding-bottom: 8px;">
							<h2 style="font-size: 14px; font-weight: 600;">
								<?php esc_html_e('Connect AI Client', 'siteagent'); ?>
							</h2>
						</div>
						<div class="sa-card-body" style="padding-top: 8px;">
							
							<!-- Token Input -->
							<div class="sa-form-group" style="margin-bottom: 20px;">
								<label for="sa-dash-token" style="font-size: 11px; font-weight: 700; margin-bottom: 8px; display: block; color: var(--sa-text-secondary); text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('Your API Token', 'siteagent'); ?></label>
								<input type="password" id="sa-dash-token" class="sa-input" placeholder="<?php esc_attr_e('Paste your token here...', 'siteagent'); ?>" />
								<p class="sa-hint" style="margin-top: 8px; font-size: 12px;">
									<?php
									printf(
										/* translators: %s: link to generate a token */
										esc_html__('Don\'t have a token yet? %s', 'siteagent'),
										'<a href="' . esc_url(admin_url('admin.php?page=siteagent-tokens')) . '" class="sa-link" style="color: var(--sa-primary); text-decoration: none; font-weight: 600;">' . esc_html__('Generate one →', 'siteagent') . '</a>'
									); ?>
								</p>
							</div>

							<!-- Client Tabs -->
							<div class="sa-tab-nav" style="margin-bottom: 20px;">
								<button type="button" id="sa-dash-client-tab-claude" class="sa-tab-btn sa-tab-btn--active" onclick="siteagentDash.switchClient('claude')">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
									<?php esc_html_e( 'Claude Desktop', 'siteagent' ); ?>
								</button>
								<button type="button" id="sa-dash-client-tab-cursor" class="sa-tab-btn" onclick="siteagentDash.switchClient('cursor')">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
									<?php esc_html_e( 'Cursor / IDEs', 'siteagent' ); ?>
								</button>
							</div>

							<!-- Claude Desktop Panel -->
							<div id="sa-dash-claude-panel">
								<div class="sa-os-tabs" style="margin-bottom: 16px;">
									<button type="button" id="sa-dash-os-tab-windows" class="sa-os-tab" onclick="siteagentDash.switchOs('windows')"><?php esc_html_e( 'Windows', 'siteagent' ); ?></button>
									<button type="button" id="sa-dash-os-tab-mac" class="sa-os-tab" onclick="siteagentDash.switchOs('mac')"><?php esc_html_e( 'macOS', 'siteagent' ); ?></button>
									<button type="button" id="sa-dash-os-tab-linux" class="sa-os-tab" onclick="siteagentDash.switchOs('linux')"><?php esc_html_e( 'Linux', 'siteagent' ); ?></button>
								</div>

								<div class="sa-tab-content sa-tab-content--active">
									<div class="sa-connection-steps" style="margin-top: 0; margin-bottom: 16px; display: flex; flex-direction: column; gap: 12px;">
										<div class="sa-step">
											<div style="font-size: 11px; font-weight: 700; color: var(--sa-text-secondary); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: 0.05em;">
												<span style="background: var(--sa-primary); color: #fff; width: 16px; height: 16px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 9px;">1</span>
												<?php esc_html_e( 'Install mcp-remote', 'siteagent' ); ?>
											</div>
											<div class="sa-token-value-wrap" style="margin-bottom: 0;">
												<input type="text" id="sa-dash-claude-step-1" class="sa-token-value" style="width: 100%; padding: 8px; font-size: 11px; border-color: var(--sa-border);" readonly value="npm install -g mcp-remote" />
												<button type="button" class="sa-btn sa-btn--primary sa-btn--sm" onclick="window.siteagent.copyText('sa-dash-claude-step-1')">
													<?php esc_html_e( 'Copy', 'siteagent' ); ?>
												</button>
											</div>
										</div>

										<div class="sa-step">
											<div style="font-size: 11px; font-weight: 700; color: var(--sa-text-secondary); margin-bottom: 6px; display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: 0.05em;">
												<span style="background: var(--sa-primary); color: #fff; width: 16px; height: 16px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 9px;">2</span>
												<?php esc_html_e( 'Connect to Claude Desktop', 'siteagent' ); ?>
											</div>
											<div class="sa-token-value-wrap" style="margin-bottom: 0;">
												<input type="text" id="sa-dash-claude-step-2" class="sa-token-value" style="width: 100%; padding: 8px; font-size: 11px; border-color: var(--sa-border);" readonly placeholder="<?php esc_attr_e( 'Paste token first...', 'siteagent' ); ?>" />
												<button type="button" class="sa-btn sa-btn--primary sa-btn--sm" onclick="window.siteagent.copyText('sa-dash-claude-step-2')">
													<?php esc_html_e( 'Copy', 'siteagent' ); ?>
												</button>
											</div>
										</div>
									</div>

									<p style="margin: 0 0 16px; font-size: 11px; font-style: italic; color: var(--sa-text-muted); line-height: 1.4;">
										<?php esc_html_e( 'Run Step 1 first, wait for it to finish, then run Step 2.', 'siteagent' ); ?>
									</p>
								</div>

								<div class="sa-node-note">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
									<span><?php
										printf(
											/* translators: %s: link to nodejs.org */
											esc_html__( 'No Node.js? Download LTS from %s first.', 'siteagent' ),
											'<a href="https://nodejs.org/" target="_blank" rel="noopener">nodejs.org</a>'
										); ?></span>
								</div>
							</div>

							<!-- Cursor Panel -->
							<div id="sa-dash-cursor-panel" style="display:none;">
								<div class="sa-form-group" style="margin-bottom: 12px;">
									<label style="font-size: 11px; font-weight: 700; margin-bottom: 4px; display: block; color: var(--sa-text-secondary);"><?php esc_html_e('MCP Server URL', 'siteagent'); ?></label>
									<div class="sa-token-value-wrap" style="margin-bottom: 0;">
										<input type="text" id="sa-dash-cursor-url" class="sa-token-value" style="width: 100%; padding: 8px; font-size: 12px; border-color: var(--sa-border);" readonly value="<?php echo esc_url($siteagent_mcp_endpoint); ?>" />
										<button type="button" class="sa-btn sa-btn--primary sa-btn--sm" onclick="window.siteagent.copyText('sa-dash-cursor-url')">
											<?php esc_html_e('Copy', 'siteagent'); ?>
										</button>
									</div>
								</div>

								<div class="sa-form-group" style="margin-bottom: 12px;">
									<label style="font-size: 11px; font-weight: 700; margin-bottom: 4px; display: block; color: var(--sa-text-secondary);"><?php esc_html_e('Type', 'siteagent'); ?></label>
									<div class="sa-token-value-wrap" style="margin-bottom: 0;">
										<input type="text" id="sa-dash-cursor-type" class="sa-token-value" style="width: 100%; padding: 8px; font-size: 12px; border-color: var(--sa-border);" readonly value="http" />
										<button type="button" class="sa-btn sa-btn--primary sa-btn--sm" onclick="window.siteagent.copyText('sa-dash-cursor-type')">
											<?php esc_html_e('Copy', 'siteagent'); ?>
										</button>
									</div>
								</div>

								<div class="sa-form-group" style="margin-bottom: 12px;">
									<label style="font-size: 11px; font-weight: 700; margin-bottom: 4px; display: block; color: var(--sa-text-secondary);"><?php esc_html_e('Authorization Header', 'siteagent'); ?></label>
									<div class="sa-token-value-wrap" style="margin-bottom: 0;">
										<input type="text" id="sa-dash-cursor-auth" class="sa-token-value" style="width: 100%; padding: 8px; font-size: 12px; border-color: var(--sa-border);" readonly placeholder="Bearer YOUR_TOKEN" />
										<button type="button" class="sa-btn sa-btn--primary sa-btn--sm" onclick="window.siteagent.copyText('sa-dash-cursor-auth')">
											<?php esc_html_e('Copy', 'siteagent'); ?>
										</button>
									</div>
								</div>

								<p class="sa-hint" style="margin-top: 16px; font-size: 12px; line-height: 1.4; color: var(--sa-text-secondary);">
									<?php esc_html_e('In Cursor: Settings → Features → MCP Servers → Add new MCP server → set Type to HTTP, paste the URL and Authorization header.', 'siteagent'); ?>
								</p>
							</div>

						</div>
					</div>


				</div>

			</div>

		</div>

	</div>

	<?php require SITEAGENT_PATH . 'templates/partials/footer.php'; ?>
</div>

</div>

