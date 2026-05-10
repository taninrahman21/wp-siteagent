<?php
/**
 * Audit log template.
 *
 * @package MySiteHand
 */

defined( 'ABSPATH' ) || exit;

$msh_plugin   = \MySiteHand\Plugin::get_instance();
$msh_audit    = $msh_plugin->get_audit_logger();
$msh_auth     = $msh_plugin->get_auth_manager();
$msh_registry = $msh_plugin->get_abilities_registry();

// Current filters.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$msh_filters = [
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

$msh_filters = array_filter( $msh_filters );
$msh_filters['per_page'] = 25;

$msh_result      = $msh_audit->get_logs( $msh_filters );
$msh_logs        = $msh_result['logs'];
$msh_total       = $msh_result['total'];
$msh_pages       = $msh_result['pages'];
$msh_stats       = $msh_audit->get_stats();
$msh_tokens      = $msh_auth->list_tokens( 0 );
$msh_ability_names = array_keys( $msh_registry->get_all() );
$msh_nonce       = wp_create_nonce( 'msh_admin' );
?>
<div class="sa-wrap">
	<?php require MSH_PATH . 'templates/partials/header.php'; ?>

	<div class="sa-main-content">
		<div class="sa-container">
			<div class="sa-page-header" style="display: flex; justify-content: space-between; align-items: center;">
				<div>
					<h2><?php echo esc_html__( 'Audit Log', 'my-site-hand' ); ?></h2>
					<p class="sa-page-desc"><?php echo esc_html__( 'Monitor all MCP requests and their execution results.', 'my-site-hand' ); ?></p>
				</div>
				<a href="<?php echo esc_url( rest_url( 'my-site-hand/v1/audit-log/export?nonce=' . $msh_nonce ) ); ?>" class="sa-btn sa-btn--ghost">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
					<?php echo esc_html__( 'Export CSV', 'my-site-hand' ); ?>
				</a>
			</div>

			<!-- Summary Stats -->
			<div class="sa-stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 32px;">
				<div class="sa-stat-card">
					<div class="sa-stat-value"><?php echo esc_html( number_format( $msh_stats['calls_today'] ) ); ?></div>
					<div class="sa-stat-label"><?php echo esc_html__( 'Calls Today', 'my-site-hand' ); ?></div>
				</div>
				<div class="sa-stat-card">
					<div class="sa-stat-value"><?php echo esc_html( $msh_stats['error_rate'] ); ?>%</div>
					<div class="sa-stat-label"><?php echo esc_html__( 'Error Rate', 'my-site-hand' ); ?></div>
				</div>
				<div class="sa-stat-card">
					<div class="sa-stat-value"><?php echo esc_html( $msh_stats['avg_duration'] ); ?>ms</div>
					<div class="sa-stat-label"><?php echo esc_html__( 'Avg Duration', 'my-site-hand' ); ?></div>
				</div>
				<div class="sa-stat-card">
					<div class="sa-stat-value"><?php echo esc_html( number_format( $msh_total ) ); ?></div>
					<div class="sa-stat-label"><?php echo esc_html__( 'Total Entries', 'my-site-hand' ); ?></div>
				</div>
			</div>

			<!-- Filters Bar -->
			<div class="sa-card sa-filters-bar" style="margin-bottom: 24px;">
				<div class="sa-card-body" style="padding: 16px 24px;">
					<form method="get" id="sa-audit-filters" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
						<input type="hidden" name="page" value="my-site-hand-audit" />

						<select name="token_id" class="sa-select" style="max-width: 180px;" onchange="this.form.submit()">
							<option value=""><?php echo esc_html__( 'All Tokens', 'my-site-hand' ); ?></option>
							<?php foreach ( $msh_tokens as $msh_token ) : ?>
							<option value="<?php echo esc_attr( $msh_token['id'] ); ?>" <?php selected( $msh_filters['token_id'] ?? '', $msh_token['id'] ); ?>>
								<?php echo esc_html( $msh_token['label'] ); ?>
							</option>
							<?php endforeach; ?>
						</select>

						<select name="ability" class="sa-select" style="max-width: 220px;" onchange="this.form.submit()">
							<option value=""><?php echo esc_html__( 'All Abilities', 'my-site-hand' ); ?></option>
							<?php 
							$msh_all_abilities = $msh_registry->get_all();
							foreach ( $msh_ability_names as $msh_ability ) : 
								$msh_label = $msh_all_abilities[ $msh_ability ]['label'] ?? $msh_ability;
							?>
							<option value="<?php echo esc_attr( $msh_ability ); ?>" <?php selected( $msh_filters['ability_name'] ?? '', $msh_ability ); ?>>
								<?php echo esc_html( $msh_label ); ?>
							</option>
							<?php endforeach; ?>
						</select>

						<select name="status" class="sa-select" style="max-width: 150px;" onchange="this.form.submit()">
							<option value=""><?php echo esc_html__( 'All Statuses', 'my-site-hand' ); ?></option>
							<option value="success" <?php selected( $msh_filters['status'] ?? '', 'success' ); ?>><?php echo esc_html__( 'Success', 'my-site-hand' ); ?></option>
							<option value="error" <?php selected( $msh_filters['status'] ?? '', 'error' ); ?>><?php echo esc_html__( 'Error', 'my-site-hand' ); ?></option>
						</select>

						<div style="display: flex; gap: 8px; align-items: center;">
							<input type="date" name="date_from" class="sa-input" style="width: auto; padding: 6px 12px;" value="<?php echo esc_attr( $msh_filters['date_from'] ?? '' ); ?>" onchange="this.form.submit()" />
							<span style="color: var(--sa-text-muted);">&rarr;</span>
							<input type="date" name="date_to" class="sa-input" style="width: auto; padding: 6px 12px;" value="<?php echo esc_attr( $msh_filters['date_to'] ?? '' ); ?>" onchange="this.form.submit()" />
						</div>

						<a href="<?php echo esc_url( admin_url( 'admin.php?page=my-site-hand-audit' ) ); ?>" class="sa-btn sa-btn--ghost sa-btn--sm" style="margin-left: auto;"><?php echo esc_html__( 'Reset', 'my-site-hand' ); ?></a>
					</form>
				</div>
			</div>

			<!-- Log Table -->
			<div class="sa-card">
				<div class="sa-card-body sa-card--no-pad">
					<?php if ( empty( $msh_logs ) ) : ?>
					<div class="sa-empty-state" style="padding: 60px 24px; text-align: center;">
						<p style="color: var(--sa-text-muted);"><?php echo esc_html__( 'No log entries found for the current filters.', 'my-site-hand' ); ?></p>
					</div>
					<?php else : ?>
					<div class="sa-table-wrap">
						<table class="sa-table sa-audit-table">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'Time', 'my-site-hand' ); ?></th>
									<th><?php echo esc_html__( 'Ability', 'my-site-hand' ); ?></th>
									<th><?php echo esc_html__( 'Status', 'my-site-hand' ); ?></th>
									<th><?php echo esc_html__( 'Duration', 'my-site-hand' ); ?></th>
									<th><?php echo esc_html__( 'IP', 'my-site-hand' ); ?></th>
									<th style="width: 100px;"></th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ( $msh_logs as $msh_log ) :
								$msh_input_data = json_decode( $msh_log['input_json'], true );
							?>
							<tr class="sa-log-row" id="log-row-<?php echo esc_attr( $msh_log['id'] ); ?>">
								<td>
									<?php 
									echo esc_html( 
										sprintf( 
											/* translators: %s: relative time */
											__( '%s ago', 'my-site-hand' ), 
											human_time_diff( strtotime( $msh_log['executed_at'] ) ) 
										) 
									); 
									?>
								</td>
								<td>
									<div style="font-weight: 500;"><?php echo esc_html( $msh_all_abilities[ $msh_log['ability_name'] ]['label'] ?? $msh_log['ability_name'] ); ?></div>
								</td>
								<td>
									<span class="sa-badge sa-badge--<?php echo esc_attr( $msh_log['result_status'] ); ?>">
										<?php 
										$msh_status = $msh_log['result_status'];
										if ( 'success' === $msh_status ) {
											echo esc_html__( 'Success', 'my-site-hand' );
										} elseif ( 'error' === $msh_status ) {
											echo esc_html__( 'Error', 'my-site-hand' );
										} elseif ( 'rate_limited' === $msh_status ) {
											echo esc_html__( 'Rate Limited', 'my-site-hand' );
										} else {
											echo esc_html( $msh_status );
										}
										?>
									</span>
								</td>
								<td style="font-size: 13px; color: var(--sa-text-secondary);">
									<?php 
									echo esc_html( 
										$msh_log['duration_ms'] !== null 
											? sprintf( 
												/* translators: %d: duration in milliseconds */
												__( '%dms', 'my-site-hand' ), 
												$msh_log['duration_ms'] 
											) 
											: '—' 
									); 
									?>
								</td>
								<td class="sa-td--ip"><?php echo esc_html( $msh_log['ip_address'] ); ?></td>
								<td>
									<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="msh.expandLogRow(<?php echo absint( $msh_log['id'] ); ?>)">
										<?php echo esc_html__( 'Details', 'my-site-hand' ); ?>
									</button>
								</td>
							</tr>
							<tr class="sa-log-detail" id="log-detail-<?php echo esc_attr( $msh_log['id'] ); ?>" style="display:none; background: var(--sa-bg);">
								<td colspan="6" style="padding: 0;">
									<div class="sa-log-detail-body" style="padding: 24px; border-bottom: 2px solid var(--sa-border);">
										<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
											<div class="sa-log-detail-section">
												<h4 style="margin: 0 0 12px; font-size: 12px; text-transform: uppercase; color: var(--sa-text-muted);"><?php echo esc_html__( 'Input Parameters', 'my-site-hand' ); ?></h4>
												<pre style="margin: 0; padding: 16px; background: #fff; border: 1px solid var(--sa-border); border-radius: 4px; font-size: 12px; overflow-x: auto; color: #1d2327;"><?php echo esc_html( json_encode( $msh_input_data, JSON_PRETTY_PRINT ) ); ?></pre>
											</div>
											<?php if ( $msh_log['result_summary'] ) : ?>
											<div class="sa-log-detail-section">
												<h4 style="margin: 0 0 12px; font-size: 12px; text-transform: uppercase; color: var(--sa-text-muted);"><?php echo esc_html__( 'Execution Result', 'my-site-hand' ); ?></h4>
												<pre style="margin: 0; padding: 16px; background: #fff; border: 1px solid var(--sa-border); border-radius: 4px; font-size: 12px; overflow-x: auto; color: #1d2327;"><?php echo esc_html( $msh_log['result_summary'] ); ?></pre>
											</div>
											<?php endif; ?>
										</div>
										<div style="margin-top: 16px; font-size: 11px; color: var(--sa-text-muted);">
											<strong><?php echo esc_html__( 'User Agent:', 'my-site-hand' ); ?></strong> <?php echo esc_html( $msh_log['user_agent'] ?: '—' ); ?>
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
			<?php if ( $msh_pages > 1 ) : ?>
			<div class="sa-pagination" style="margin-top: 24px; display: flex; gap: 4px; justify-content: center;">
				<?php for ( $msh_p = 1; $msh_p <= $msh_pages; $msh_p++ ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'paged', $msh_p ) ); ?>"
					style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 4px; border: 1px solid var(--sa-border); background: <?php echo ( $msh_filters['page'] ?? 1 ) === $msh_p ? 'var(--sa-primary)' : '#fff'; ?>; color: <?php echo ( $msh_filters['page'] ?? 1 ) === $msh_p ? '#fff' : 'var(--sa-text)'; ?>; text-decoration: none; font-size: 13px; font-weight: 600;">
					<?php echo esc_html( $msh_p ); ?>
				</a>
				<?php endfor; ?>
			</div>
			<?php endif; ?>
		</div>

		<?php require MSH_PATH . 'templates/partials/footer.php'; ?>
	</div>
</div>




