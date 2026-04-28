<?php
/**
 * Admin dashboard template.
 *
 * @package WP_SiteAgent
 */

defined('ABSPATH') || exit;

$plugin = \WP_SiteAgent\Plugin::get_instance();
$registry = $plugin->get_abilities_registry();
$auth = $plugin->get_auth_manager();
$audit = $plugin->get_audit_logger();

$stats = $audit->get_stats();
$tokens = $auth->list_tokens(0);
$abilities = $registry->get_all();
$recent_logs = $audit->get_logs(['per_page' => 5, 'page' => 1]);
$mcp_endpoint = rest_url('siteagent/v1/mcp/streamable');
$site_url = get_site_url();
$is_enabled = (bool) get_option('siteagent_enabled', true);
$modules = $plugin->get_modules();
$enabled_mods = $plugin->get_enabled_modules();
?>
<div class="sa-wrap">

	<?php require SITEAGENT_PATH . 'templates/partials/header.php'; ?>

	<div class="sa-main-content">
		<div class="sa-container">

			<!-- Top Status Bar -->
			<div class="sa-top-status">
				<div class="sa-status-indicator">
					<span class="sa-status-dot"></span>
					<?php esc_html_e('MCP Server Active', 'wp-siteagent'); ?>
				</div>
				<div class="sa-status-center">
					<div class="sa-endpoint-code" id="sa-mcp-url-top">
						<?php echo esc_html(str_replace(['https://', 'http://'], '', $mcp_endpoint)); ?>
					</div>
					<button type="button" class="sa-copy-btn sa-copy-btn--inline"
						onclick="siteagent.copyText('sa-mcp-url-top')">
						<?php esc_html_e('Copy', 'wp-siteagent'); ?>
					</button>
				</div>
				<div class="sa-status-protocol">
					<?php esc_html_e('Protocol: 2024-11-05', 'wp-siteagent'); ?>
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
						<?php echo esc_html(number_format($stats['calls_today'])); ?>
					</div>
					<div class="sa-stat-label"><?php esc_html_e('API calls today', 'wp-siteagent'); ?></div>
					<div class="sa-stat-meta sa-meta--up">
						<span class="sa-meta-icon">↑</span>
						<span>18% vs yesterday</span>
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
					<div class="sa-stat-value" id="stat-tokens"><?php echo esc_html(count($tokens)); ?></div>
					<div class="sa-stat-label"><?php esc_html_e('Active tokens', 'wp-siteagent'); ?></div>
					<div class="sa-stat-meta" style="color: var(--sa-text-muted);">
						<span>2 expire in 30d</span>
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
					<div class="sa-stat-value" id="stat-abilities"><?php echo esc_html(count($abilities)); ?></div>
					<div class="sa-stat-label"><?php esc_html_e('Abilities registered', 'wp-siteagent'); ?></div>
					<?php
					$public_count = count($registry->get_mcp_public());
					?>
					<div class="sa-stat-meta" style="color: var(--sa-text-muted);">
						<span><?php echo esc_html($public_count); ?> MCP-public</span>
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
						<?php echo esc_html(number_format($stats['errors_24h'])); ?>
					</div>
					<div class="sa-stat-label"><?php esc_html_e('Errors (24h)', 'wp-siteagent'); ?></div>
					<div class="sa-stat-meta sa-meta--down">
						<span>Rate: <?php echo esc_html($stats['error_rate']); ?>%</span>
					</div>
				</div>
			</div>

			<div class="sa-dashboard-body">

				<div class="sa-dashboard-main">
					<!-- Recent Audit Log -->
					<div class="sa-card" style="margin-bottom: 0; height: 100%;">
						<div class="sa-card-header" style="border-bottom: none; padding-bottom: 0;">
							<h2 style="font-size: 14px; font-weight: 600;">
								<?php esc_html_e('Recent audit log', 'wp-siteagent'); ?>
							</h2>
							<a href="<?php echo esc_url(admin_url('admin.php?page=wp-siteagent-audit')); ?>"
								class="sa-link"
								style="font-size: 12px; color: var(--sa-primary); text-decoration: none; font-weight: 500;"><?php esc_html_e('View all →', 'wp-siteagent'); ?></a>
						</div>
						<div class="sa-card-body sa-card--no-pad">
							<?php if (empty($recent_logs['logs'])): ?>
								<div class="sa-empty-state">
									<p><?php esc_html_e('No API calls yet.', 'wp-siteagent'); ?></p>
								</div>
							<?php else: ?>
								<div class="sa-table-wrap">
									<table class="sa-table" style="border-top: none;">
										<thead>
											<tr style="background: transparent;">
												<th
													style="background: transparent; border-bottom: 1px solid var(--sa-border); padding: 16px 24px; color: var(--sa-text-muted); font-size: 11px;">
													<?php esc_html_e('ABILITY', 'wp-siteagent'); ?>
												</th>
												<th
													style="background: transparent; border-bottom: 1px solid var(--sa-border); padding: 16px 24px; color: var(--sa-text-muted); font-size: 11px;">
													<?php esc_html_e('TOKEN', 'wp-siteagent'); ?>
												</th>
												<th
													style="background: transparent; border-bottom: 1px solid var(--sa-border); padding: 16px 24px; color: var(--sa-text-muted); font-size: 11px;">
													<?php esc_html_e('STATUS', 'wp-siteagent'); ?>
												</th>
												<th
													style="background: transparent; border-bottom: 1px solid var(--sa-border); padding: 16px 24px; color: var(--sa-text-muted); font-size: 11px;">
													<?php esc_html_e('TIME', 'wp-siteagent'); ?>
												</th>
											</tr>
										</thead>
										<tbody>
											<?php
											foreach ($recent_logs['logs'] as $log):
												$token_data = $auth->get_token($log['token_id']);
												$token_name = $token_data ? $token_data['name'] : '—';
												?>
												<tr>
													<td style="font-size: 13px;">
														<div style="font-weight: 500;">
															<?php echo esc_html($abilities[$log['ability_name']]['label'] ?? $log['ability_name']); ?>
														</div>
													</td>
													<td style="color: var(--sa-text-secondary); font-size: 13px;">
														<?php echo esc_html($token_name); ?>
													</td>
													<td>
														<span
															class="sa-badge sa-badge--<?php echo esc_attr($log['result_status']); ?>">
															<?php echo esc_html($log['result_status']); ?>
														</span>
													</td>
													<td style="color: var(--sa-text-muted); font-size: 13px;">
														<?php echo esc_html(human_time_diff(strtotime($log['executed_at']))); ?>
														<?php esc_html_e('ago', 'wp-siteagent'); ?>
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
								<?php esc_html_e('Module status', 'wp-siteagent'); ?>
							</h2>
						</div>
						<div class="sa-card-body" style="padding-top: 16px;">
							<div class="sa-modules-grid">
								<?php
								$all_module_defs = [
									'content' => ['label' => __('Content', 'wp-siteagent')],
									'seo' => ['label' => __('SEO', 'wp-siteagent')],
									'woocommerce' => ['label' => __('WooCommerce', 'wp-siteagent')],
									'diagnostics' => ['label' => __('Diagnostics', 'wp-siteagent')],
									'media' => ['label' => __('Media', 'wp-siteagent')],
									'users' => ['label' => __('Users', 'wp-siteagent')],
								];
								foreach ($all_module_defs as $slug => $def):
									$module_obj = $modules[$slug] ?? null;
									$ability_count = $module_obj ? count($module_obj->get_ability_names()) : 0;
									$is_mod_enabled = in_array($slug, $enabled_mods, true);
									$status_class = $is_mod_enabled ? 'sa-module-status-dot--enabled' : '';
									?>
									<div class="sa-module-card">
										<div class="sa-module-header">
											<span class="sa-module-title">
												<?php echo esc_html($def['label']); ?>
											</span>
											<span
												class="sa-module-status-dot <?php echo esc_attr($status_class); ?>"></span>
										</div>
										<span class="sa-module-count">
											<?php printf(esc_html__('%d abilities', 'wp-siteagent'), $ability_count); ?>
										</span>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>

				<div class="sa-dashboard-sidebar">
					<!-- AI Client Setup -->
					<div class="sa-card" style="margin-bottom: 24px;">
						<div class="sa-card-header" style="border-bottom: none; padding-bottom: 8px;">
							<h2 style="font-size: 14px; font-weight: 600;">
								<?php esc_html_e('Connect AI Clients', 'wp-siteagent'); ?>
							</h2>
						</div>
						<div class="sa-card-body" style="padding-top: 8px;">

							<div class="sa-tab-nav">
								<button type="button" class="sa-tab-btn sa-tab-btn--active" data-client="claude"
									onclick="window.siteagent.switchClientTab('claude')">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
										stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M12 8V4H8" />
										<rect width="16" height="12" x="4" y="8" rx="2" />
										<path d="M2 14h2" />
										<path d="M20 14h2" />
										<path d="M15 13v2" />
										<path d="M9 13v2" />
									</svg>
									Claude
								</button>
								<button type="button" class="sa-tab-btn" data-client="chatgpt"
									onclick="window.siteagent.switchClientTab('chatgpt')">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
										stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
										<path d="M12 7v10" />
										<path d="M8 12h8" />
									</svg>
									ChatGPT
								</button>
								<button type="button" class="sa-tab-btn" data-client="ide"
									onclick="window.siteagent.switchClientTab('ide')">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
										stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<polyline points="16 18 22 12 16 6" />
										<polyline points="8 6 2 12 8 18" />
									</svg>
									Cursor/IDEs
								</button>
							</div>

							<!-- Claude Setup -->
							<div class="sa-tab-content sa-tab-content--active" data-client="claude">
								<div class="sa-setup-guide">
									<ol class="sa-setup-steps">
										<li><?php esc_html_e('Open Claude Desktop settings.', 'wp-siteagent'); ?></li>
										<li><?php esc_html_e('Paste the bridge command below into your config file.', 'wp-siteagent'); ?>
										</li>
										<li><?php esc_html_e('Once saved, restart Claude Desktop to see the new tools.', 'wp-siteagent'); ?>
										</li>
									</ol>
								</div>
								<?php
								$claude_config = json_encode([
									'mcpServers' => [
										'wp-siteagent' => [
											'command' => 'node',
											'args' => ['/usr/local/lib/node_modules/mcp-remote/dist/proxy.js', $mcp_endpoint, '--header', 'Authorization: Bearer YOUR_TOKEN'],
										],
									],
								], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
								?>
								<div class="sa-mcp-block">
									<button type="button" class="sa-copy-btn"
										onclick="window.siteagent.copyText('sa-mcp-config-claude')">
										<span
											id="sa-copy-config-text-claude"><?php esc_html_e('Copy Configuration', 'wp-siteagent'); ?></span>
									</button>
									<pre><code id="sa-mcp-config-claude"><?php echo esc_html($claude_config); ?></code></pre>
								</div>
							</div>

							<!-- ChatGPT Setup -->
							<div class="sa-tab-content" data-client="chatgpt">
								<div class="sa-setup-guide">
									<ol class="sa-setup-steps">
										<li><?php esc_html_e('Open ChatGPT Desktop > Settings > Connected Apps.', 'wp-siteagent'); ?>
										</li>
										<li><?php esc_html_e('Click "Add Server" and select MCP.', 'wp-siteagent'); ?>
										</li>
										<li><?php esc_html_e('Paste the command below and add your API Token.', 'wp-siteagent'); ?>
										</li>
									</ol>
								</div>
								<div class="sa-mcp-block">
									<button type="button" class="sa-copy-btn"
										onclick="window.siteagent.copyText('sa-mcp-config-chatgpt')">
										<span
											id="sa-copy-config-text-chatgpt"><?php esc_html_e('Copy Bridge Command', 'wp-siteagent'); ?></span>
									</button>
									<pre><code id="sa-mcp-config-chatgpt">npx -y mcp-remote \<br>  <?php echo esc_url($mcp_endpoint); ?> \<br>  --header "Authorization: Bearer YOUR_TOKEN"</code></pre>
								</div>
							</div>

							<!-- IDE Setup (Cursor/Windsurf) -->
							<div class="sa-tab-content" data-client="ide">
								<div class="sa-setup-guide">
									<ol class="sa-setup-steps">
										<li><?php esc_html_e('Navigate to Editor Settings > MCP Servers.', 'wp-siteagent'); ?>
										</li>
										<li><?php esc_html_e('Create a new "command" server.', 'wp-siteagent'); ?>
										</li>
										<li><?php esc_html_e('Paste the following execution command:', 'wp-siteagent'); ?>
										</li>
									</ol>
								</div>
								<div class="sa-mcp-block">
									<button type="button" class="sa-copy-btn"
										onclick="window.siteagent.copyText('sa-mcp-config-ide')">
										<span
											id="sa-copy-config-text-ide"><?php esc_html_e('Copy Entry Command', 'wp-siteagent'); ?></span>
									</button>
									<pre><code id="sa-mcp-config-ide">npx -y mcp-remote \<br>  <?php echo esc_url($mcp_endpoint); ?> \<br>  --header "Authorization: Bearer YOUR_TOKEN"</code></pre>
								</div>
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