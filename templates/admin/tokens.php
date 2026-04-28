<?php
/**
 * Token management template.
 *
 * @package WP_SiteAgent
 */

defined( 'ABSPATH' ) || exit;

$plugin   = \WP_SiteAgent\Plugin::get_instance();
$auth     = $plugin->get_auth_manager();
$registry = $plugin->get_abilities_registry();
$tokens   = $auth->list_tokens( 0 );

$disabled_abs = (array) get_option( 'siteagent_disabled_abilities', [] );
$all_abilities = $registry->get_all();
$abilities = array_filter( $all_abilities, function( $ability ) use ( $disabled_abs ) {
	return ! in_array( $ability['name'], $disabled_abs, true );
} );
?>
<div class="sa-wrap">
	<?php require SITEAGENT_PATH . 'templates/partials/header.php'; ?>

	<div class="sa-main-content">
		<div class="sa-container">
			<div class="sa-page-header" style="display: flex; justify-content: space-between; align-items: center;">
				<div>
					<h2><?php esc_html_e( 'API Tokens', 'wp-siteagent' ); ?></h2>
					<p class="sa-page-desc"><?php esc_html_e( 'Manage access tokens for your MCP clients. Revoked tokens are immediately invalidated.', 'wp-siteagent' ); ?></p>
				</div>
				<button type="button" id="sa-generate-token-btn" class="sa-btn sa-btn--primary" onclick="siteagentTokens.openGenerateModal()">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
					<?php esc_html_e( 'Generate Token', 'wp-siteagent' ); ?>
				</button>
			</div>

			<!-- Tokens Table -->
			<div class="sa-card">
				<div class="sa-card-body sa-card--no-pad">
					<?php if ( empty( $tokens ) ) : ?>
					<div class="sa-empty-state" style="padding: 60px 24px; text-align: center;">
						<div style="margin-bottom: 20px; color: var(--sa-text-muted);">
							<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
						</div>
						<p style="font-size: 15px; color: var(--sa-text-secondary);"><?php esc_html_e( 'No tokens yet. Generate your first token to connect an MCP client.', 'wp-siteagent' ); ?></p>
					</div>
					<?php else : ?>
					<div class="sa-table-wrap">
						<table class="sa-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Label', 'wp-siteagent' ); ?></th>
									<th><?php esc_html_e( 'Created', 'wp-siteagent' ); ?></th>
									<th><?php esc_html_e( 'Expires', 'wp-siteagent' ); ?></th>
									<th><?php esc_html_e( 'Last Used', 'wp-siteagent' ); ?></th>
									<th><?php esc_html_e( 'Abilities', 'wp-siteagent' ); ?></th>
									<th><?php esc_html_e( 'Status', 'wp-siteagent' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'wp-siteagent' ); ?></th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ( $tokens as $token ) :
								$is_active  = (int) $token['is_active'] === 1;
								$is_expired = ! empty( $token['expires_at'] ) && strtotime( $token['expires_at'] ) < time();
								$status     = ! $is_active ? 'revoked' : ( $is_expired ? 'expired' : 'active' );
								
								$display_abilities = [];
								if ( empty( $token['abilities'] ) ) {
									$abilities_text = __( 'All abilities', 'wp-siteagent' );
								} else {
									foreach ( (array) $token['abilities'] as $ab_name ) {
										$display_abilities[] = $all_abilities[ $ab_name ]['label'] ?? $ab_name;
									}
									$abilities_text = implode( ', ', array_slice( $display_abilities, 0, 3 ) ) . ( count( $display_abilities ) > 3 ? ' +' . ( count( $display_abilities ) - 3 ) . ' more' : '' );
								}
							?>
							<tr>
								<td><strong><?php echo esc_html( $token['label'] ); ?></strong></td>
								<td class="sa-td--time"><?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $token['created_at'] ) ) ); ?></td>
								<td class="sa-td--time"><?php echo $token['expires_at'] ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $token['expires_at'] ) ) ) : '<em>' . esc_html__( 'Never', 'wp-siteagent' ) . '</em>'; ?></td>
								<td class="sa-td--time"><?php echo $token['last_used'] ? esc_html( human_time_diff( strtotime( $token['last_used'] ) ) . ' ' . __( 'ago', 'wp-siteagent' ) ) : '<em>' . esc_html__( 'Never', 'wp-siteagent' ) . '</em>'; ?></td>
								<td class="sa-td--abilities" title="<?php echo isset( $token['abilities'] ) ? esc_attr( implode( ', ', $token['abilities'] ) ) : ''; ?>" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; color: var(--sa-text-secondary);"><?php echo esc_html( $abilities_text ); ?></td>
								<td><span class="sa-badge sa-badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status ); ?></span></td>
								<td class="sa-td--actions">
									<?php if ( $is_active && ! $is_expired ) : ?>
									<button type="button"
										class="sa-btn sa-btn--ghost sa-btn--sm"
										style="color: var(--sa-danger); border-color: rgba(214,54,56,0.2);"
										onclick="siteagentTokens.revokeToken(<?php echo absint( $token['id'] ); ?>, '<?php echo esc_attr( $token['label'] ); ?>')">
										<?php esc_html_e( 'Revoke', 'wp-siteagent' ); ?>
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

		<?php require SITEAGENT_PATH . 'templates/partials/footer.php'; ?>
	</div>

	<!-- Generate Token Modal -->
	<div id="sa-generate-modal" class="sa-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="sa-modal-title">
		<div class="sa-modal">
			<div class="sa-modal-header">
				<h3 id="sa-modal-title"><?php esc_html_e( 'Generate API Token', 'wp-siteagent' ); ?></h3>
				<button type="button" class="sa-modal-close" onclick="siteagentTokens.closeModal()">&times;</button>
			</div>
			<div class="sa-modal-body">
				<form id="sa-generate-token-form">
					<div class="sa-form-group">
						<label for="sa-token-label"><?php esc_html_e( 'Label', 'wp-siteagent' ); ?> <span class="sa-required">*</span></label>
						<input type="text" id="sa-token-label" name="label" class="sa-input" placeholder="<?php esc_attr_e( 'e.g. Claude Desktop - Home', 'wp-siteagent' ); ?>" required />
						<p class="sa-hint"><?php esc_html_e( 'A descriptive name to help you identify this token later.', 'wp-siteagent' ); ?></p>
					</div>
					<div class="sa-form-group">
						<label for="sa-token-expires"><?php esc_html_e( 'Expires', 'wp-siteagent' ); ?></label>
						<input type="date" id="sa-token-expires" name="expires_at" class="sa-input" min="<?php echo esc_attr( gmdate( 'Y-m-d', strtotime( '+1 day' ) ) ); ?>" />
						<p class="sa-hint"><?php esc_html_e( 'Leave blank for no expiry.', 'wp-siteagent' ); ?></p>
					</div>
					<div class="sa-form-group">
						<label><?php esc_html_e( 'Ability Restrictions', 'wp-siteagent' ); ?></label>
						<p class="sa-hint"><?php esc_html_e( 'Leave all unchecked to allow all abilities.', 'wp-siteagent' ); ?></p>
						<div class="sa-abilities-check" style="margin-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; max-height: 200px; overflow-y: auto; padding: 12px; border: 1px solid var(--sa-border); border-radius: 4px; background: var(--sa-bg);">
							<?php foreach ( $abilities as $ability ) : ?>
							<label class="sa-checkbox-label" style="padding: 4px 0; display: flex; align-items: center; gap: 8px;">
								<input type="checkbox" name="abilities[]" value="<?php echo esc_attr( $ability['name'] ); ?>" />
								<span style="font-size: 13px; font-weight: 500;"><?php echo esc_html( $ability['label'] ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>
					<div id="sa-token-reveal" class="sa-token-reveal" style="display:none;">
						<div class="sa-token-reveal-warning">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
							<?php esc_html_e( 'Copy this token now — it will NEVER be shown again!', 'wp-siteagent' ); ?>
						</div>
						<div class="sa-token-value-wrap">
							<code id="sa-new-token-value" class="sa-token-value"></code>
							<button type="button" id="sa-copy-token-btn" class="sa-btn sa-btn--primary sa-btn--sm">
								<?php esc_html_e( 'Copy', 'wp-siteagent' ); ?>
							</button>
						</div>

						<div style="margin-top: 24px; padding: 20px; border: 1px solid rgba(13, 148, 136, 0.2); border-radius: 8px; background: rgba(13, 148, 136, 0.05);">
							<h4 style="margin: 0 0 12px; font-size: 14px; font-weight: 700; color: var(--sa-primary);"><?php esc_html_e( 'Connect Claude Desktop', 'wp-siteagent' ); ?></h4>
							
							<div class="sa-os-tabs">
								<button type="button" id="sa-os-tab-windows" class="sa-os-tab sa-os-tab--active" onclick="siteagentTokens.switchOsTab('windows')">
									Windows
								</button>
								<button type="button" id="sa-os-tab-mac" class="sa-os-tab" onclick="siteagentTokens.switchOsTab('mac')">
									macOS
								</button>
								<button type="button" id="sa-os-tab-linux" class="sa-os-tab" onclick="siteagentTokens.switchOsTab('linux')">
									Linux
								</button>
							</div>

							<div id="sa-os-desc" class="sa-os-description">
								<?php esc_html_e( 'Connect this site to Claude Desktop automatically by running a simple PowerShell command.', 'wp-siteagent' ); ?>
							</div>

							<button type="button" id="sa-copy-claude-btn" class="sa-btn sa-btn--secondary sa-btn--sm" style="width: 100%; justify-content: center;">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>
								<span id="sa-copy-claude-label"><?php esc_html_e( 'Copy PowerShell Command', 'wp-siteagent' ); ?></span>
							</button>

							<div class="sa-node-note">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
								<span><?php printf( esc_html__( 'No Node.js? Download LTS from %s first.', 'wp-siteagent' ), '<a href="https://nodejs.org/" target="_blank" rel="noopener">nodejs.org</a>' ); ?></span>
							</div>
						</div>

						<label class="sa-checkbox-label sa-confirm-copy" style="font-size: 12px; font-weight: 600; margin-top: 24px;">
							<input type="checkbox" id="sa-confirm-copied" />
							<?php esc_html_e( 'I have saved it securely.', 'wp-siteagent' ); ?>
						</label>
					</div>
					<!-- Hidden targets for siteagent.copyText() -->
					<textarea id="sa-dynamic-copy-target" style="position:fixed; left:-9999px; top:0; opacity:0;" readonly></textarea>
				</form>
			</div>
			<div class="sa-modal-footer">
				<button type="button" class="sa-btn sa-btn--ghost" onclick="siteagentTokens.closeModal()"><?php esc_html_e( 'Cancel', 'wp-siteagent' ); ?></button>
				<button type="button" id="sa-submit-token" class="sa-btn sa-btn--primary" onclick="siteagentTokens.generateToken()">
					<?php esc_html_e( 'Generate Token', 'wp-siteagent' ); ?>
				</button>
				<button type="button" id="sa-close-after-copy" class="sa-btn sa-btn--success" style="display:none;" onclick="siteagentTokens.closeModal()" disabled>
					<?php esc_html_e( 'Done — Close', 'wp-siteagent' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>
