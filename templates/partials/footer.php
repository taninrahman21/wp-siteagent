<?php
/**
 * Admin page footer partial.
 *
 * @package MySiteHand
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="sa-footer">
	<div class="sa-footer-left">
		<strong>My Site Hand <?php echo esc_html( MSH_VERSION ); ?></strong>
		<span class="sa-footer-sep">&bull;</span>
		<a href="https://modelcontextprotocol.io" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'MCP Specification', 'my-site-hand' ); ?></a>
	</div>
	<div class="sa-footer-right">
		<span>WordPress <?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
		<span class="sa-footer-sep">&bull;</span>
		<span>PHP <?php echo esc_html( PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ); ?></span>
	</div>
</footer>




