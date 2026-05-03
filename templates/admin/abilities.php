<?php
/**
 * Abilities list template.
 *
 * @package WP_SiteAgent
 */

defined('ABSPATH') || exit;

$siteagent_plugin = \WP_SiteAgent\Plugin::get_instance();
$siteagent_registry = $siteagent_plugin->get_abilities_registry();
$siteagent_abilities = $siteagent_registry->get_all();

$siteagent_module_labels = [
	'content'     => __( 'Content', 'wp-siteagent' ),
	'seo'         => __( 'SEO', 'wp-siteagent' ),
	'woocommerce' => __( 'WooCommerce', 'wp-siteagent' ),
	'diagnostics' => __( 'Diagnostics', 'wp-siteagent' ),
	'media'       => __( 'Media', 'wp-siteagent' ),
	'users'       => __( 'Users', 'wp-siteagent' ),
];
?>
<div class="sa-wrap">
	<?php require SITEAGENT_PATH . 'templates/partials/header.php'; ?>

	<div class="sa-main-content">
		<div class="sa-container">
			<div class="sa-page-header"
				style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; border-bottom: 1px solid var(--sa-border); padding-bottom: 24px;">
				<div class="sa-header-left">
					<h2 style="margin: 0; font-size: 24px; font-weight: 700; color: var(--sa-secondary);">
						<?php esc_html_e('Abilities', 'wp-siteagent'); ?></h2>
					<div style="font-size: 13px; color: var(--sa-text-muted); margin-top: 4px;">
						<?php
						$siteagent_disabled_abs = (array) get_option('siteagent_disabled_abilities', []);
						$siteagent_public_abs = $siteagent_registry->get_mcp_public();
						$siteagent_public_count = count($siteagent_public_abs);
						$siteagent_disabled_count = count(array_intersect(array_keys($siteagent_abilities), $siteagent_disabled_abs));

						printf(
							/* translators: 1: total count, 2: public count, 3: disabled count */
							esc_html__('%1$d registered · %2$d MCP-public · %3$d disabled', 'wp-siteagent'),
							(int) count($siteagent_abilities),
							(int) $siteagent_public_count,
							(int) $siteagent_disabled_count
						);
						?>
					</div>
				</div>
				<div class="sa-header-right">
					<div class="sa-status-mini-bar">
						<span class="sa-status-dot sa-status-dot--active"></span>
						<span
							style="font-weight: 500; color: var(--sa-secondary);"><?php esc_html_e('MCP Active', 'wp-siteagent'); ?></span>
					</div>
				</div>
			</div>

			<div class="sa-abilities-grid">
				<?php
				$siteagent_module_objs = $siteagent_plugin->get_modules();
				foreach ($siteagent_module_labels as $slug => $label):
					$siteagent_module_obj = $siteagent_module_objs[$slug] ?? null;
					$siteagent_module_ability_names = $siteagent_module_obj ? $siteagent_module_obj->get_ability_names() : [];

					if ('general' === $slug) {
						$siteagent_module_abilities = array_filter($siteagent_abilities, function ($name) use ($siteagent_module_objs) {
							foreach ($siteagent_module_objs as $m) {
								if (in_array($name, $m->get_ability_names(), true))
									return false;
							}
							return true;
						}, ARRAY_FILTER_USE_KEY);
					} else {
						$siteagent_module_abilities = array_intersect_key($siteagent_abilities, array_flip($siteagent_module_ability_names));
					}

					if (empty($siteagent_module_abilities) && 'general' !== $slug)
						continue;
					?>
					<div class="sa-card" style="padding: 0;">
						<div class="sa-card-header"
							style="padding: 16px 24px; background: #fff; border-bottom: 1px solid var(--sa-border);">
							<h3 style="font-size: 14px; font-weight: 600; color: var(--sa-secondary); margin: 0;">
								<?php echo esc_html($label); ?> 	<?php esc_html_e('module', 'wp-siteagent'); ?></h3>
							<span class="sa-tag sa-tag--public"
								style="text-transform: lowercase; font-weight: 500;"><?php
								printf(
									/* translators: %d: number of abilities */
									esc_html__('%d abilities', 'wp-siteagent'),
									count($siteagent_module_abilities)
								); ?></span>
						</div>
						<div class="sa-abilities-list">
							<?php foreach ($siteagent_module_abilities as $name => $ability):
								$is_public = !empty($ability['annotations']['meta']['mcp']['public']);
								$is_readonly = !empty($ability['annotations']['readonly']);
								$is_destructive = !empty($ability['annotations']['destructive']);
								$is_enabled = !in_array($name, $siteagent_disabled_abs, true);
								?>
								<div class="sa-ability-row">
									<div class="sa-ability-info" style="padding: 12px 0;">
										<div class="sa-ability-label" style="font-weight: 600; font-size: 15px; color: var(--sa-secondary);"><?php echo esc_html($ability['label'] ?? $name); ?></div>
										<div class="sa-ability-description" style="font-size: 13px; color: var(--sa-text-muted); margin-top: 6px; line-height: 1.5; max-width: 500px;"><?php echo esc_html($ability['description'] ?? ''); ?></div>
									</div>
									<div class="sa-ability-actions">
										<div class="sa-ability-tags" style="display: flex; gap: 8px;">
											<?php if ($is_readonly): ?>
												<span
													class="sa-tag sa-tag--readonly"><?php esc_html_e('readonly', 'wp-siteagent'); ?></span>
											<?php else: ?>
												<span
													class="sa-tag sa-tag--write"><?php esc_html_e('write', 'wp-siteagent'); ?></span>
											<?php endif; ?>

											<?php if ($is_public): ?>
												<span
													class="sa-tag sa-tag--public"><?php esc_html_e('public', 'wp-siteagent'); ?></span>
											<?php else: ?>
												<span
													class="sa-tag sa-tag--disabled"><?php esc_html_e('disabled', 'wp-siteagent'); ?></span>
											<?php endif; ?>

											<?php if ($is_destructive): ?>
												<span
													class="sa-tag sa-tag--destructive"><?php esc_html_e('destructive', 'wp-siteagent'); ?></span>
											<?php endif; ?>
										</div>
										<label class="sa-switch">
											<input type="checkbox" <?php checked($is_enabled); ?>
												onchange="siteagent.toggleAbility('<?php echo esc_js($name); ?>', this.checked)">
											<span class="sa-slider"></span>
										</label>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<?php require SITEAGENT_PATH . 'templates/partials/footer.php'; ?>
	</div>
</div>