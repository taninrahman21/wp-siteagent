<?php
/**
 * Abilities list template.
 *
 * @package WP_SiteAgent
 */

defined('ABSPATH') || exit;

$plugin = \WP_SiteAgent\Plugin::get_instance();
$registry = $plugin->get_abilities_registry();
$abilities = $registry->get_all();

$module_labels = [
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
						$disabled_abs = (array) get_option('siteagent_disabled_abilities', []);
						$public_abs = $registry->get_mcp_public();
						$public_count = count($public_abs);
						$disabled_count = count(array_intersect(array_keys($abilities), $disabled_abs));

						printf(
							/* translators: 1: total count, 2: public count, 3: disabled count */
							esc_html__('%1$d registered · %2$d MCP-public · %3$d disabled', 'wp-siteagent'),
							count($abilities),
							$public_count,
							$disabled_count
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
				$module_objs = $plugin->get_modules();
				foreach ($module_labels as $slug => $label):
					$module_obj = $module_objs[$slug] ?? null;
					$module_ability_names = $module_obj ? $module_obj->get_ability_names() : [];

					if ('general' === $slug) {
						$module_abilities = array_filter($abilities, function ($name) use ($module_objs) {
							foreach ($module_objs as $m) {
								if (in_array($name, $m->get_ability_names(), true))
									return false;
							}
							return true;
						}, ARRAY_FILTER_USE_KEY);
					} else {
						$module_abilities = array_intersect_key($abilities, array_flip($module_ability_names));
					}

					if (empty($module_abilities) && 'general' !== $slug)
						continue;
					?>
					<div class="sa-card" style="padding: 0;">
						<div class="sa-card-header"
							style="padding: 16px 24px; background: #fff; border-bottom: 1px solid var(--sa-border);">
							<h3 style="font-size: 14px; font-weight: 600; color: var(--sa-secondary); margin: 0;">
								<?php echo esc_html($label); ?> 	<?php esc_html_e('module', 'wp-siteagent'); ?></h3>
							<span class="sa-tag sa-tag--public"
								style="text-transform: lowercase; font-weight: 500;"><?php printf(esc_html__('%d abilities', 'wp-siteagent'), count($module_abilities)); ?></span>
						</div>
						<div class="sa-abilities-list">
							<?php foreach ($module_abilities as $name => $ability):
								$is_public = !empty($ability['annotations']['meta']['mcp']['public']);
								$is_readonly = !empty($ability['annotations']['readonly']);
								$is_destructive = !empty($ability['annotations']['destructive']);
								$is_enabled = !in_array($name, $disabled_abs, true);
								?>
								<div class="sa-ability-row">
									<div class="sa-ability-info">
										<div class="sa-ability-name"><?php echo esc_html($name); ?></div>
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