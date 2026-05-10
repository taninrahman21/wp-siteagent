<?php
/**
 * Abilities list template.
 *
 * @package MySiteHand
 */

defined('ABSPATH') || exit;

$msh_plugin = \MySiteHand\Plugin::get_instance();
$msh_registry = $msh_plugin->get_abilities_registry();
$msh_abilities = $msh_registry->get_all();

$msh_module_labels = [
	'content'     => __( 'Content', 'my-site-hand' ),
	'seo'         => __( 'SEO', 'my-site-hand' ),
	'woocommerce' => __( 'WooCommerce', 'my-site-hand' ),
	'diagnostics' => __( 'Diagnostics', 'my-site-hand' ),
	'media'       => __( 'Media', 'my-site-hand' ),
	'users'       => __( 'Users', 'my-site-hand' ),
];
?>
<div class="sa-wrap">
	<?php require MSH_PATH . 'templates/partials/header.php'; ?>

	<div class="sa-main-content">
		<div class="sa-container">
			<div class="sa-page-header"
				style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; border-bottom: 1px solid var(--sa-border); padding-bottom: 24px;">
				<div class="sa-header-left">
					<h2 style="margin: 0; font-size: 24px; font-weight: 700; color: var(--sa-secondary);">
						<?php echo esc_html__('Abilities', 'my-site-hand'); ?></h2>
					<div style="font-size: 13px; color: var(--sa-text-muted); margin-top: 4px;">
						<?php
						$msh_disabled_abs = (array) get_option('msh_disabled_abilities', []);
						$msh_public_abs = $msh_registry->get_mcp_public();
						$msh_public_count = count($msh_public_abs);
						$msh_disabled_count = count(array_intersect(array_keys($msh_abilities), $msh_disabled_abs));

						printf(
							/* translators: 1: total count, 2: public count, 3: disabled count */
							esc_html__('%1$d registered · %2$d MCP-public · %3$d disabled', 'my-site-hand'),
							(int) count($msh_abilities),
							(int) $msh_public_count,
							(int) $msh_disabled_count
						);
						?>
					</div>
				</div>
				<div class="sa-header-right">
					<div class="sa-status-mini-bar">
						<span class="sa-status-dot sa-status-dot--active"></span>
						<span
							style="font-weight: 500; color: var(--sa-secondary);"><?php echo esc_html__('MCP Active', 'my-site-hand'); ?></span>
					</div>
				</div>
			</div>

			<div class="sa-abilities-grid">
				<?php
				$msh_module_objs = $msh_plugin->get_modules();
				foreach ($msh_module_labels as $msh_slug => $msh_label):
					$msh_module_obj = $msh_module_objs[$msh_slug] ?? null;
					$msh_module_ability_names = $msh_module_obj ? $msh_module_obj->get_ability_names() : [];

					if ('general' === $msh_slug) {
						$msh_module_abilities = array_filter($msh_abilities, function ($name) use ($msh_module_objs) {
							foreach ($msh_module_objs as $m) {
								if (in_array($name, $m->get_ability_names(), true))
									return false;
							}
							return true;
						}, ARRAY_FILTER_USE_KEY);
					} else {
						$msh_module_abilities = array_intersect_key($msh_abilities, array_flip($msh_module_ability_names));
					}

					if (empty($msh_module_abilities) && 'general' !== $msh_slug)
						continue;
					?>
					<div class="sa-card" style="padding: 0;">
						<div class="sa-card-header"
							style="padding: 16px 24px; background: #fff; border-bottom: 1px solid var(--sa-border);">
								<h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--sa-secondary);">
									<?php 
									printf( 
										/* translators: %s: module name */
										esc_html__( '%s module', 'my-site-hand' ), 
										esc_html( $msh_label ) 
									); 
									?>
								</h3>
							<span class="sa-tag sa-tag--public"
								style="text-transform: lowercase; font-weight: 500;"><?php
								printf(
									/* translators: %d: number of abilities */
									esc_html__('%d abilities', 'my-site-hand'),
									count($msh_module_abilities)
								); ?></span>
						</div>
						<div class="sa-abilities-list">
							<?php foreach ($msh_module_abilities as $msh_name => $msh_ability):
								$msh_is_public = !empty($msh_ability['annotations']['meta']['mcp']['public']);
								$msh_is_readonly = !empty($msh_ability['annotations']['readonly']);
								$msh_is_destructive = !empty($msh_ability['annotations']['destructive']);
								$msh_is_enabled = !in_array($msh_name, $msh_disabled_abs, true);
								?>
								<div class="sa-ability-row">
									<div class="sa-ability-info" style="padding: 12px 0;">
										<div class="sa-ability-label" style="font-weight: 600; font-size: 15px; color: var(--sa-secondary);"><?php echo esc_html($msh_ability['label'] ?? $msh_name); ?></div>
										<div class="sa-ability-description" style="font-size: 13px; color: var(--sa-text-muted); margin-top: 6px; line-height: 1.5; max-width: 500px;"><?php echo esc_html($msh_ability['description'] ?? ''); ?></div>
									</div>
									<div class="sa-ability-actions">
										<div class="sa-ability-tags" style="display: flex; gap: 8px;">
											<?php if ($msh_is_readonly): ?>
												<span class="sa-tag sa-tag--readonly"><?php echo esc_html__('readonly', 'my-site-hand'); ?></span>
											<?php endif; ?>
											<?php if ($msh_is_destructive): ?>
												<span class="sa-tag sa-tag--destructive"><?php echo esc_html__('destructive', 'my-site-hand'); ?></span>
											<?php endif; ?>
											<?php if ($msh_is_public): ?>
												<span class="sa-tag sa-tag--public"><?php echo esc_html__('public', 'my-site-hand'); ?></span>
											<?php endif; ?>
										</div>
										<label class="sa-switch">
											<input type="checkbox" <?php checked($msh_is_enabled); ?>
												onchange="msh.toggleAbility('<?php echo esc_js($msh_name); ?>', this.checked)">
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

		<?php require MSH_PATH . 'templates/partials/footer.php'; ?>
	</div>
</div>




