<?php
/**
 * Audit log template.
 *
 * @package WP_SiteAgent
 */

defined( 'ABSPATH' ) || exit;

$siteagent_plugin   = \WP_SiteAgent\Plugin::get_instance();
$siteagent_audit    = $siteagent_plugin->get_audit_logger();
$siteagent_auth     = $siteagent_plugin->get_auth_manager();
$siteagent_registry = $siteagent_plugin->get_abilities_registry();

// Current filters.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$siteagent_filters = [
	'per_page'     => 25,
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'page'         => max( 1, absint( $_GET['paged'] ?? 1 ) ),
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'token_id'     => ! empty( $_GET['token_id'] ) ? absint( $_GET['token_id'] ) : null,
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'ability_name' => ! empty( $_GET['ability'] ) ? sanitize_text_field( wp_unslash( $_GET['ability'] ) ) : null,
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'status'       => ! empty( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : null,
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'date_from'    => ! empty( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : null,
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	'date_to'      => ! empty( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : null,
];

$siteagent_filters = array_filter( $siteagent_filters );
$siteagent_filters['per_page'] = 25;

$siteagent_result      = $siteagent_audit->get_logs( $siteagent_filters );
$siteagent_logs        = $siteagent_result['logs'];
$siteagent_total       = $siteagent_result['total'];
$siteagent_pages       = $siteagent_result['pages'];
$siteagent_stats       = $siteagent_audit->get_stats();
$siteagent_tokens      = $siteagent_auth->list_tokens( 0 );
$siteagent_ability_names = array_keys( $siteagent_registry->get_all() );
$siteagent_nonce       = wp_create_nonce( 'siteagent_admin' );
?>
<div class="sa-wrap">
	<?php require SITEAGENT_PATH . 'templates/partials/header.php'; ?>

	<div class="sa-main-content">
		<div class="sa-container">
			<div class="sa-page-header" style="display: flex; justify-content: space-between; align-items: center;">
				<div>
					<h2><?php esc_html_e( 'Audit Log', 'wp-siteagent' ); ?></h2>
					<p class="sa-page-desc"><?php esc_html_e( 'Monitor all MCP requests and their execution results.', 'wp-siteagent' ); ?></p>
				</div>
				<a href="<?php echo esc_url( rest_url( 'siteagent/v1/audit-log/export?nonce=' . $siteagent_nonce ) ); ?>" class="sa-btn sa-btn--ghost">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
					<?php esc_html_e( 'Export CSV', 'wp-siteagent' ); ?>
				</a>
			</div>

			<!-- Summary Stats -->
			<div class="sa-stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 32px;">
				<div class="sa-stat-card">
					<div class="sa-stat-value"><?php echo esc_html( number_format( $siteagent_stats['calls_today'] ) ); ?></div>
					<div class="sa-stat-label"><?php esc_html_e( 'Calls Today', 'wp-siteagent' ); ?></div>
				</div>
				<div class="sa-stat-card">
					<div class="sa-stat-value"><?php echo esc_html( $siteagent_stats['error_rate'] ); ?>%</div>
					<div class="sa-stat-label"><?php esc_html_e( 'Error Rate', 'wp-siteagent' ); ?></div>
				</div>
				<div class="sa-stat-card">
					<div class="sa-stat-value"><?php echo esc_html( $siteagent_stats['avg_duration'] ); ?>ms</div>
					<div class="sa-stat-label"><?php esc_html_e( 'Avg Duration', 'wp-siteagent' ); ?></div>
				</div>
				<div class="sa-stat-card">
					<div class="sa-stat-value"><?php echo esc_html( number_format( $siteagent_total ) ); ?></div>
					<div class="sa-stat-label"><?php esc_html_e( 'Total Entries', 'wp-siteagent' ); ?></div>
				</div>
			</div>

			<!-- Filters Bar -->
			<div class="sa-card sa-filters-bar" style="margin-bottom: 24px;">
				<div class="sa-card-body" style="padding: 16px 24px;">
					<form method="get" id="sa-audit-filters" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
						<input type="hidden" name="page" value="wp-siteagent-audit" />

						<select name="token_id" class="sa-select" style="max-width: 180px;" onchange="this.form.submit()">
							<option value=""><?php esc_html_e( 'All Tokens', 'wp-siteagent' ); ?></option>
							<?php foreach ( $siteagent_tokens as $siteagent_token ) : ?>
							<option value="<?php echo esc_attr( $siteagent_token['id'] ); ?>" <?php selected( $siteagent_filters['token_id'] ?? '', $siteagent_token['id'] ); ?>>
								<?php echo esc_html( $siteagent_token['label'] ); ?>
							</option>
							<?php endforeach; ?>
						</select>

						<select name="ability" class="sa-select" style="max-width: 220px;" onchange="this.form.submit()">
							<option value=""><?php esc_html_e( 'All Abilities', 'wp-siteagent' ); ?></option>
							<?php 
							$siteagent_all_abilities = $siteagent_registry->get_all();
							foreach ( $siteagent_ability_names as $siteagent_ability ) : 
								$siteagent_label = $siteagent_all_abilities[ $siteagent_ability ]['label'] ?? $siteagent_ability;
							?>
							<option value="<?php echo esc_attr( $siteagent_ability ); ?>" <?php selected( $siteagent_filters['ability_name'] ?? '', $siteagent_ability ); ?>>
								<?php echo esc_html( $siteagent_label ); ?>
							</option>
							<?php endforeach; ?>
						</select>

						<select name="status" class="sa-select" style="max-width: 150px;" onchange="this.form.submit()">
							<option value=""><?php esc_html_e( 'All Statuses', 'wp-siteagent' ); ?></option>
							<option value="success" <?php selected( $siteagent_filters['status'] ?? '', 'success' ); ?>><?php esc_html_e( 'Success', 'wp-siteagent' ); ?></option>
							<option value="error" <?php selected( $siteagent_filters['status'] ?? '', 'error' ); ?>><?php esc_html_e( 'Error', 'wp-siteagent' ); ?></option>
						</select>

						<div style="display: flex; gap: 8px; align-items: center;">
							<input type="date" name="date_from" class="sa-input" style="width: auto; padding: 6px 12px;" value="<?php echo esc_attr( $siteagent_filters['date_from'] ?? '' ); ?>" onchange="this.form.submit()" />
							<span style="color: var(--sa-text-muted);">&rarr;</span>
							<input type="date" name="date_to" class="sa-input" style="width: auto; padding: 6px 12px;" value="<?php echo esc_attr( $siteagent_filters['date_to'] ?? '' ); ?>" onchange="this.form.submit()" />
						</div>

						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-siteagent-audit' ) ); ?>" class="sa-btn sa-btn--ghost sa-btn--sm" style="margin-left: auto;"><?php esc_html_e( 'Reset', 'wp-siteagent' ); ?></a>
					</form>
				</div>
			</div>

			<!-- Log Table -->
			<div class="sa-card">
				<div class="sa-card-body sa-card--no-pad">
					<?php if ( empty( $siteagent_logs ) ) : ?>
					<div class="sa-empty-state" style="padding: 60px 24px; text-align: center;">
						<p style="color: var(--sa-text-muted);"><?php esc_html_e( 'No log entries found for the current filters.', 'wp-siteagent' ); ?></p>
					</div>
					<?php else : ?>
					<div class="sa-table-wrap">
						<table class="sa-table sa-audit-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Time', 'wp-siteagent' ); ?></th>
									<th><?php esc_html_e( 'Ability', 'wp-siteagent' ); ?></th>
									<th><?php esc_html_e( 'Status', 'wp-siteagent' ); ?></th>
									<th><?php esc_html_e( 'Duration', 'wp-siteagent' ); ?></th>
									<th><?php esc_html_e( 'IP', 'wp-siteagent' ); ?></th>
									<th style="width: 100px;"></th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ( $siteagent_logs as $log ) :
								$siteagent_input_data = json_decode( $log['input_json'], true );
							?>
							<tr class="sa-log-row" id="log-row-<?php echo esc_attr( $log['id'] ); ?>">
								<td class="sa-td--time">
									<?php echo esc_html( human_time_diff( strtotime( $log['executed_at'] ) ) . ' ' . __( 'ago', 'wp-siteagent' ) ); ?>
								</td>
								<td>
									<div style="font-weight: 500;"><?php echo esc_html( $siteagent_all_abilities[ $log['ability_name'] ]['label'] ?? $log['ability_name'] ); ?></div>
								</td>
								<td>
									<span class="sa-badge sa-badge--<?php echo esc_attr( $log['result_status'] ); ?>">
										<?php echo esc_html( $log['result_status'] ); ?>
									</span>
								</td>
								<td style="font-size: 13px; color: var(--sa-text-secondary);"><?php echo esc_html( $log['duration_ms'] !== null ? $log['duration_ms'] . 'ms' : '—' ); ?></td>
								<td class="sa-td--ip"><?php echo esc_html( $log['ip_address'] ); ?></td>
								<td>
									<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="siteagent.expandLogRow(<?php echo absint( $log['id'] ); ?>)">
										<?php esc_html_e( 'Details', 'wp-siteagent' ); ?>
									</button>
								</td>
							</tr>
							<tr class="sa-log-detail" id="log-detail-<?php echo esc_attr( $log['id'] ); ?>" style="display:none; background: var(--sa-bg);">
								<td colspan="6" style="padding: 0;">
									<div class="sa-log-detail-body" style="padding: 24px; border-bottom: 2px solid var(--sa-border);">
										<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
											<div class="sa-log-detail-section">
												<h4 style="margin: 0 0 12px; font-size: 12px; text-transform: uppercase; color: var(--sa-text-muted);"><?php esc_html_e( 'Input Parameters', 'wp-siteagent' ); ?></h4>
												<pre style="margin: 0; padding: 16px; background: #fff; border: 1px solid var(--sa-border); border-radius: 4px; font-size: 12px; overflow-x: auto; color: #1d2327;"><?php echo esc_html( json_encode( $siteagent_input_data, JSON_PRETTY_PRINT ) ); ?></pre>
											</div>
											<?php if ( $log['result_summary'] ) : ?>
											<div class="sa-log-detail-section">
												<h4 style="margin: 0 0 12px; font-size: 12px; text-transform: uppercase; color: var(--sa-text-muted);"><?php esc_html_e( 'Execution Result', 'wp-siteagent' ); ?></h4>
												<pre style="margin: 0; padding: 16px; background: #fff; border: 1px solid var(--sa-border); border-radius: 4px; font-size: 12px; overflow-x: auto; color: #1d2327;"><?php echo esc_html( $log['result_summary'] ); ?></pre>
											</div>
											<?php endif; ?>
										</div>
										<div style="margin-top: 16px; font-size: 11px; color: var(--sa-text-muted);">
											<strong><?php esc_html_e( 'User Agent:', 'wp-siteagent' ); ?></strong> <?php echo esc_html( $log['user_agent'] ?: '—' ); ?>
										</div>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Pagination -->
			<?php if ( $siteagent_pages > 1 ) : ?>
			<div class="sa-pagination" style="margin-top: 24px; display: flex; gap: 4px; justify-content: center;">
				<?php for ( $p = 1; $p <= $siteagent_pages; $p++ ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array_merge( // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$_GET, [ 'paged' => $p ] ) ) ); ?>"
					style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 4px; border: 1px solid var(--sa-border); background: <?php echo ( $siteagent_filters['page'] ?? 1 ) === $p ? 'var(--sa-primary)' : '#fff'; ?>; color: <?php echo ( $siteagent_filters['page'] ?? 1 ) === $p ? '#fff' : 'var(--sa-text)'; ?>; text-decoration: none; font-size: 13px; font-weight: 600;">
					<?php echo esc_html( $p ); ?>
				</a>
				<?php endfor; ?>
			</div>
			<?php endif; ?>
		</div>

		<?php require SITEAGENT_PATH . 'templates/partials/footer.php'; ?>
	</div>
</div>
