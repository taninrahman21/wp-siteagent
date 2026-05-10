<?php
/**
 * Token management template.
 *
 * @package MySiteHand
 */

defined('ABSPATH') || exit;

$msh_plugin = \MySiteHand\Plugin::get_instance();
$msh_auth = $msh_plugin->get_auth_manager();
$msh_registry = $msh_plugin->get_abilities_registry();
$msh_tokens = $msh_auth->list_tokens(0);

$msh_disabled_abs = (array) get_option('msh_disabled_abilities', []);
$msh_all_abilities = $msh_registry->get_all();
$msh_abilities = array_filter($msh_all_abilities, function ($msh_ability) use ($msh_disabled_abs) {
	return !in_array($msh_ability['name'], $msh_disabled_abs, true);
});
?>
<div class="sa-wrap">
	<?php require MSH_PATH . 'templates/partials/header.php'; ?>

	<div class="sa-main-content">
		<div class="sa-container">
			<div class="sa-page-header" style="display: flex; justify-content: space-between; align-items: center;">
				<div>
					<h2><?php echo esc_html__('API Tokens', 'my-site-hand'); ?></h2>
					<p class="sa-page-desc">
						<?php echo esc_html__('Manage access tokens for your MCP clients. Revoked tokens are immediately invalidated.', 'my-site-hand'); ?>
					</p>
				</div>
				<button type="button" id="sa-generate-token-btn" class="sa-btn sa-btn--primary"
					onclick="mshTokens.openGenerateModal()">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
						stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
						<line x1="12" y1="5" x2="12" y2="19"></line>
						<line x1="5" y1="12" x2="19" y2="12"></line>
					</svg>
					<?php echo esc_html__('Generate Token', 'my-site-hand'); ?>
				</button>
			</div>

			<!-- Tokens Table -->
			<div class="sa-card">
				<div class="sa-card-body sa-card--no-pad">
					<?php if (empty($msh_tokens)): ?>
						<div class="sa-empty-state" style="padding: 60px 24px; text-align: center;">
							<div style="margin-bottom: 20px; color: var(--sa-text-muted);">
								<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor"
									stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
									<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
									<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
								</svg>
							</div>
							<p style="font-size: 15px; color: var(--sa-text-secondary);">
								<?php echo esc_html__('No tokens yet. Generate your first token to connect an MCP client.', 'my-site-hand'); ?>
							</p>
						</div>
					<?php else: ?>
						<div class="sa-table-wrap">
							<table class="sa-table">
								<thead>
									<tr>
										<th><?php echo esc_html__('Label', 'my-site-hand'); ?></th>
										<th><?php echo esc_html__('Created', 'my-site-hand'); ?></th>
										<th><?php echo esc_html__('Expires', 'my-site-hand'); ?></th>
										<th><?php echo esc_html__('Last Used', 'my-site-hand'); ?></th>
										<th><?php echo esc_html__('Abilities', 'my-site-hand'); ?></th>
										<th><?php echo esc_html__('Status', 'my-site-hand'); ?></th>
										<th><?php echo esc_html__('Actions', 'my-site-hand'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($msh_tokens as $msh_token):
										$msh_is_active = (int) $msh_token['is_active'] === 1;
										$msh_is_expired = !empty($msh_token['expires_at']) && strtotime($msh_token['expires_at']) < time();
										$msh_status = !$msh_is_active ? 'revoked' : ($msh_is_expired ? 'expired' : 'active');

										$msh_display_abilities = [];
										if (empty($msh_token['abilities'])) {
											$msh_abilities_text = __('All abilities', 'my-site-hand');
										} else {
											foreach ((array) $msh_token['abilities'] as $msh_ab_name) {
												$msh_display_abilities[] = $msh_all_abilities[$msh_ab_name]['label'] ?? $msh_ab_name;
											}
											$msh_abilities_text = implode(', ', array_slice($msh_display_abilities, 0, 3)) . (count($msh_display_abilities) > 3 ? ' +' . (count($msh_display_abilities) - 3) . ' more' : '');
										}
										?>
										<tr>
											<td><strong><?php echo esc_html($msh_token['label']); ?></strong></td>
											<td class="sa-td--time">
												<?php echo esc_html(wp_date(get_option('date_format'), strtotime($msh_token['created_at']))); ?>
											</td>
											<td class="sa-td--time">
												<?php echo $msh_token['expires_at'] ? esc_html(wp_date(get_option('date_format'), strtotime($msh_token['expires_at']))) : '<em>' . esc_html__('Never', 'my-site-hand') . '</em>'; ?>
											</td>
											<td class="sa-td--time">
												<?php 
												echo $msh_token['last_used'] 
													? esc_html( 
														sprintf( 
															/* translators: %s: relative time */
															__( '%s ago', 'my-site-hand' ), 
															human_time_diff( strtotime( $msh_token['last_used'] ) ) 
														) 
													) 
													: '<em>' . esc_html__( 'Never', 'my-site-hand' ) . '</em>'; 
												?>
											</td>
											<td class="sa-td--abilities"
												title="<?php echo isset($msh_token['abilities']) ? esc_attr(implode(', ', $msh_token['abilities'])) : ''; ?>"
												style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; color: var(--sa-text-secondary);">
												<?php echo esc_html($msh_abilities_text); ?></td>
											<td><span
													class="sa-badge sa-badge--<?php echo esc_attr($msh_status); ?>">
													<?php 
													if ( 'active' === $msh_status ) {
														echo esc_html__( 'active', 'my-site-hand' );
													} elseif ( 'revoked' === $msh_status ) {
														echo esc_html__( 'revoked', 'my-site-hand' );
													} elseif ( 'expired' === $msh_status ) {
														echo esc_html__( 'expired', 'my-site-hand' );
													} else {
														echo esc_html( $msh_status );
													}
													?></span>
											</td>
											<td class="sa-td--actions">
												<?php if ($msh_is_active && !$msh_is_expired): ?>
													<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm"
														style="color: var(--sa-danger); border-color: rgba(214,54,56,0.2);"
														onclick="mshTokens.revokeToken(<?php echo absint($msh_token['id']); ?>, '<?php echo esc_attr($msh_token['label']); ?>')">
														<?php echo esc_html__('Revoke', 'my-site-hand'); ?>
													</button>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php require MSH_PATH . 'templates/partials/footer.php'; ?>
	</div>

	<!-- Generate Token Modal -->
	<div id="sa-generate-modal" class="sa-modal-overlay" style="display:none;" role="dialog" aria-modal="true"
		aria-labelledby="sa-modal-title">
		<div class="sa-modal">
			<div class="sa-modal-header">
				<h3 id="sa-modal-title"><?php echo esc_html__('Generate API Token', 'my-site-hand'); ?></h3>
				<button type="button" class="sa-modal-close" onclick="mshTokens.closeModal()">&times;</button>
			</div>
			<div class="sa-modal-body">
				<form id="sa-generate-token-form">
					<div class="sa-form-group">
						<label for="sa-token-label"><?php echo esc_html__('Label', 'my-site-hand'); ?> <span
								class="sa-required">*</span></label>
						<input type="text" id="sa-token-label" name="label" class="sa-input"
							placeholder="<?php esc_attr_e('e.g. Claude Desktop - Home', 'my-site-hand'); ?>"
							required />
						<p class="sa-hint">
							<?php echo esc_html__('A descriptive name to help you identify this token later.', 'my-site-hand'); ?>
						</p>
					</div>
					<div class="sa-form-group">
						<label for="sa-token-expires"><?php echo esc_html__('Expires', 'my-site-hand'); ?></label>
						<input type="date" id="sa-token-expires" name="expires_at" class="sa-input"
							min="<?php echo esc_attr(gmdate('Y-m-d', strtotime('+1 day'))); ?>" />
						<p class="sa-hint"><?php echo esc_html__('Leave blank for no expiry.', 'my-site-hand'); ?></p>
					</div>
					<div class="sa-form-group">
						<label><?php echo esc_html__('Ability Restrictions', 'my-site-hand'); ?></label>
						<p class="sa-hint">
							<?php echo esc_html__('Leave all unchecked to allow all abilities.', 'my-site-hand'); ?></p>
						<div class="sa-abilities-check"
							style="margin-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-height: 200px; overflow-y: auto; padding: 12px; border: 1px solid var(--sa-border); border-radius: 4px; background: var(--sa-bg);">
							<?php foreach ($msh_abilities as $msh_ability): ?>
								<label class="sa-checkbox-label"
									style="padding: 4px 0; display: flex; align-items: center; gap: 8px;">
									<input type="checkbox" name="abilities[]"
										value="<?php echo esc_attr($msh_ability['name']); ?>" />
									<span
										style="font-size: 13px; font-weight: 500;"><?php echo esc_html($msh_ability['label']); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
					<div id="sa-token-reveal" class="sa-token-reveal" style="display:none;">
						<div class="sa-token-reveal-warning">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
								stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
								style="margin-right: 6px;">
								<path
									d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
								</path>
								<line x1="12" y1="9" x2="12" y2="13"></line>
								<line x1="12" y1="17" x2="12.01" y2="17"></line>
							</svg>
							<?php echo esc_html__('Copy this token now — it will NEVER be shown again!', 'my-site-hand'); ?>
						</div>
						<div class="sa-token-value-wrap">
							<code id="sa-new-token-value" class="sa-token-value"></code>
							<button type="button" id="sa-copy-token-btn" class="sa-btn sa-btn--primary sa-btn--sm">
								<?php echo esc_html__('Copy', 'my-site-hand'); ?>
							</button>
						</div>

						<div
							style="margin-top: 24px; padding: 20px; border: 1px solid rgba(13, 148, 136, 0.2); border-radius: 8px; background: rgba(13, 148, 136, 0.05);">
							<div class="sa-os-tabs"
								style="margin-bottom: 20px; border-bottom: 1px solid rgba(13, 148, 136, 0.1); padding: 10px; display: flex; gap: 8px;">
								<button type="button" id="sa-client-tab-claude" class="sa-os-tab sa-os-tab--active"
									onclick="mshTokens.switchClientTab('claude')">
									<?php echo esc_html__( 'Claude Desktop', 'my-site-hand' ); ?>
								</button>
								<button type="button" id="sa-client-tab-cursor" class="sa-os-tab"
									onclick="mshTokens.switchClientTab('cursor')">
									<?php echo esc_html__( 'Cursor', 'my-site-hand' ); ?>
								</button>
							</div>

							<!-- Claude Desktop Panel -->
							<div id="sa-claude-panel">
								<h4
									style="margin: 0 0 12px; font-size: 14px; font-weight: 700; color: var(--sa-primary);">
									<?php echo esc_html__('Connect Claude Desktop', 'my-site-hand'); ?></h4>

								<div class="sa-os-tabs">
									<button type="button" id="sa-os-tab-windows" class="sa-os-tab sa-os-tab--active"
										onclick="mshTokens.switchOsTab('windows')">
										<?php echo esc_html__( 'Windows', 'my-site-hand' ); ?>
									</button>
									<button type="button" id="sa-os-tab-mac" class="sa-os-tab"
										onclick="mshTokens.switchOsTab('mac')">
										<?php echo esc_html__( 'macOS', 'my-site-hand' ); ?>
									</button>
									<button type="button" id="sa-os-tab-linux" class="sa-os-tab"
										onclick="mshTokens.switchOsTab('linux')">
										<?php echo esc_html__( 'Linux', 'my-site-hand' ); ?>
									</button>
								</div>

								<div class="sa-connection-steps"
									style="margin-top: 16px; display: flex; flex-direction: column; gap: 16px;">
									<div class="sa-step">
										<div
											style="font-size: 12px; font-weight: 700; color: var(--sa-text-secondary); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
											<span
												style="background: var(--sa-primary); color: #fff; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">1</span>
											<?php echo esc_html__('Install mcp-remote', 'my-site-hand'); ?>
										</div>
										<div class="sa-token-value-wrap" style="margin-bottom: 0;">
											<input type="text" id="sa-claude-step-1" class="sa-token-value"
												style="width: 100%; border-color: rgba(13, 148, 136, 0.2);" readonly
												value="npm install -g mcp-remote" />
											<button type="button" class="sa-btn sa-btn--primary sa-btn--sm"
												onclick="msh.copyText('sa-claude-step-1')">
												<?php echo esc_html__('Copy', 'my-site-hand'); ?>
											</button>
										</div>
									</div>

									<div class="sa-step">
										<div
											style="font-size: 12px; font-weight: 700; color: var(--sa-text-secondary); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
											<span
												style="background: var(--sa-primary); color: #fff; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">2</span>
											<?php echo esc_html__('Connect to Claude Desktop', 'my-site-hand'); ?>
										</div>
										<div class="sa-token-value-wrap" style="margin-bottom: 0;">
											<input type="text" id="sa-claude-step-2" class="sa-token-value"
												style="width: 100%; border-color: rgba(13, 148, 136, 0.2);" readonly
												placeholder="..." />
											<button type="button" class="sa-btn sa-btn--primary sa-btn--sm"
												onclick="msh.copyText('sa-claude-step-2')">
												<?php echo esc_html__('Copy', 'my-site-hand'); ?>
											</button>
										</div>
									</div>
								</div>

								<p
									style="margin: 16px 0 0; font-size: 11px; font-style: italic; color: var(--sa-text-muted); display: flex; align-items: center; gap: 6px;">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
										stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
										<circle cx="12" cy="12" r="10"></circle>
										<line x1="12" y1="16" x2="12" y2="12"></line>
										<line x1="12" y1="8" x2="12.01" y2="8"></line>
									</svg>
									<?php echo esc_html__('Run Step 1 first and wait for it to complete before running Step 2.', 'my-site-hand'); ?>
								</p>

								<div class="sa-node-note">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
										stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
										<circle cx="12" cy="12" r="10"></circle>
										<line x1="12" y1="16" x2="12" y2="12"></line>
										<line x1="12" y1="8" x2="12.01" y2="8"></line>
									</svg>
									<span><?php
										printf(
											/* translators: %s: link to nodejs.org */
											esc_html__('No Node.js? Download LTS from %s first.', 'my-site-hand'),
											'<a href="https://nodejs.org/" target="_blank" rel="noopener">nodejs.org</a>'
										); ?></span>
								</div>
							</div>

							<!-- Cursor Panel -->
							<div id="sa-cursor-panel" style="display:none;">
								<h4
									style="margin: 0 0 12px; font-size: 14px; font-weight: 700; color: var(--sa-primary);">
									<?php echo esc_html__('Connect Cursor', 'my-site-hand'); ?></h4>

								<div class="sa-form-group" style="margin-bottom: 12px;">
									<label
										style="font-size: 11px; font-weight: 700; margin-bottom: 4px; display: block; color: var(--sa-text-secondary);"><?php echo esc_html__('MCP Server URL', 'my-site-hand'); ?></label>
									<div class="sa-token-value-wrap">
										<input type="text" id="sa-cursor-url" class="sa-token-value"
											style="width: 100%; border-color: rgba(13, 148, 136, 0.2);" readonly />
										<button type="button" class="sa-btn sa-btn--primary sa-btn--sm"
											onclick="msh.copyText('sa-cursor-url')">
											<?php echo esc_html__('Copy', 'my-site-hand'); ?>
										</button>
									</div>
								</div>

								<div class="sa-form-group" style="margin-bottom: 12px;">
									<label
										style="font-size: 11px; font-weight: 700; margin-bottom: 4px; display: block; color: var(--sa-text-secondary);"><?php echo esc_html__('Type', 'my-site-hand'); ?></label>
									<div class="sa-token-value-wrap">
										<input type="text" id="sa-cursor-type" class="sa-token-value"
											style="width: 100%; border-color: rgba(13, 148, 136, 0.2);" readonly
											value="http" />
										<button type="button" class="sa-btn sa-btn--primary sa-btn--sm"
											onclick="msh.copyText('sa-cursor-type')">
											<?php echo esc_html__('Copy', 'my-site-hand'); ?>
										</button>
									</div>
								</div>

								<div class="sa-form-group" style="margin-bottom: 12px;">
									<label
										style="font-size: 11px; font-weight: 700; margin-bottom: 4px; display: block; color: var(--sa-text-secondary);"><?php echo esc_html__('Authorization Header', 'my-site-hand'); ?></label>
									<div class="sa-token-value-wrap">
										<input type="text" id="sa-cursor-auth" class="sa-token-value"
											style="width: 100%; border-color: rgba(13, 148, 136, 0.2);" readonly />
										<button type="button" class="sa-btn sa-btn--primary sa-btn--sm"
											onclick="msh.copyText('sa-cursor-auth')">
											<?php echo esc_html__('Copy', 'my-site-hand'); ?>
										</button>
									</div>
								</div>

								<p class="sa-hint"
									style="margin-top: 16px; font-size: 12px; line-height: 1.4; color: var(--sa-text-secondary);">
									<?php echo esc_html__('In Cursor: Settings → Features → MCP Servers → Add new MCP server → set Type to HTTP, paste the URL, and add the Authorization header.', 'my-site-hand'); ?>
								</p>
							</div>
						</div>

						<label class="sa-checkbox-label sa-confirm-copy"
							style="font-size: 12px; font-weight: 600; margin-top: 24px;">
							<input type="checkbox" id="sa-confirm-copied" />
							<?php echo esc_html__('I have saved it securely.', 'my-site-hand'); ?>
						</label>
					</div>
					<!-- Hidden targets for my-site-hand.copyText() -->
					<textarea id="sa-dynamic-copy-target" style="position:fixed; left:-9999px; top:0; opacity:0;"
						readonly></textarea>
				</form>
			</div>
			<div class="sa-modal-footer">
				<button type="button" class="sa-btn sa-btn--ghost"
					onclick="mshTokens.closeModal()"><?php echo esc_html__('Cancel', 'my-site-hand'); ?></button>
				<button type="button" id="sa-submit-token" class="sa-btn sa-btn--primary"
					onclick="mshTokens.generateToken()">
					<?php echo esc_html__('Generate Token', 'my-site-hand'); ?>
				</button>
				<button type="button" id="sa-close-after-copy" class="sa-btn sa-btn--success" style="display:none;"
					onclick="mshTokens.closeModal()" disabled>
					<?php echo esc_html__('Done — Close', 'my-site-hand'); ?>
				</button>
			</div>
		</div>
	</div>
</div>




