<?php
/**
 * MCP configuration snippet partial - Multi-client edition.
 *
 * @package MySiteHand
 */

defined( 'ABSPATH' ) || exit;

$msh_mcp_endpoint = get_rest_url( null, 'my-site-hand/v1/mcp' );
$msh_config_json  = wp_json_encode( [
	'mcpServers' => [
		'my-site-hand' => [
			'command' => 'npx',
			'args'    => [ '-y', '@builtbytanin/mcp-remote', '--url', $msh_mcp_endpoint ],
		],
	],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
?>
<div class="sa-mcp-snippet">
	<!-- Client Tabs -->
	<div class="sa-client-tabs">
		<button type="button" class="sa-tab-btn sa-tab-btn--active" data-client="claude" onclick="msh.switchClientTab('claude')">
			Claude Desktop
		</button>
		<button type="button" class="sa-tab-btn" data-client="cursor" onclick="msh.switchClientTab('cursor')">
			Cursor
		</button>
		<button type="button" class="sa-tab-btn" data-client="windsurf" onclick="msh.switchClientTab('windsurf')">
			Windsurf
		</button>
		<button type="button" class="sa-tab-btn" data-client="generic" onclick="msh.switchClientTab('generic')">
			Generic
		</button>
	</div>

	<!-- Tab Content: Claude Desktop -->
	<div class="sa-tab-content sa-tab-content--active" data-client="claude">
		<div class="sa-snippet-header">
			<div class="sa-snippet-title">claude_desktop_config.json</div>
			<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="msh.copyMcpConfig()">
				<span id="sa-copy-config-text-claude"><?php echo esc_html__( 'Copy Json', 'my-site-hand' ); ?></span>
			</button>
		</div>
		<div class="sa-snippet-code-container">
			<pre id="sa-mcp-config-code-claude" class="sa-snippet-code"><code><?php echo esc_html( $msh_config_json ); ?></code></pre>
		</div>
		<div class="sa-setup-instructions">
			<h4><?php echo esc_html__( 'Setup Instructions', 'my-site-hand' ); ?></h4>
			<ul class="sa-setup-steps">
				<li><?php echo esc_html__( 'Locate your Claude Desktop config file.', 'my-site-hand' ); ?></li>
				<li><?php echo esc_html__( 'Add the JSON block above to your mcpServers section.', 'my-site-hand' ); ?></li>
				<li><?php echo esc_html__( 'Restart Claude Desktop.', 'my-site-hand' ); ?></li>
			</ul>
		</div>
	</div>

	<!-- Tab Content: Cursor -->
	<div class="sa-tab-content" data-client="cursor">
		<div class="sa-snippet-header">
			<div class="sa-snippet-title">Cursor Settings</div>
			<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="msh.copyMcpConfig()">
				<span id="sa-copy-config-text-cursor"><?php echo esc_html__( 'Copy URL', 'my-site-hand' ); ?></span>
			</button>
		</div>
		<div class="sa-snippet-code-container" style="display:none;">
			<pre id="sa-mcp-config-code-cursor" class="sa-snippet-code"><code><?php echo esc_url( $msh_mcp_endpoint ); ?></code></pre>
		</div>
		<div class="sa-setup-instructions">
			<h4><?php echo esc_html__( 'Setup Instructions', 'my-site-hand' ); ?></h4>
			<ul class="sa-setup-steps">
				<li><?php echo esc_html__( 'Open Cursor and navigate to Settings > Features > MCP.', 'my-site-hand' ); ?></li>
				<li><?php echo esc_html__( 'Click "+ Add Server" and paste the endpoint URL.', 'my-site-hand' ); ?></li>
				<li><?php echo esc_html__( 'Add an Authorization header with your Bearer token.', 'my-site-hand' ); ?></li>
			</ul>
		</div>
	</div>

	<!-- Tab Content: Windsurf -->
	<div class="sa-tab-content" data-client="windsurf">
		<div class="sa-snippet-header">
			<div class="sa-snippet-title">Windsurf Settings</div>
			<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="msh.copyMcpConfig()">
				<span id="sa-copy-config-text-windsurf"><?php echo esc_html__( 'Copy Json', 'my-site-hand' ); ?></span>
			</button>
		</div>
		<div class="sa-snippet-code-container">
			<pre id="sa-mcp-config-code-windsurf" class="sa-snippet-code"><code><?php echo esc_html( $msh_config_json ); ?></code></pre>
		</div>
		<div class="sa-setup-instructions">
			<h4><?php echo esc_html__( 'Setup Instructions', 'my-site-hand' ); ?></h4>
			<ul class="sa-setup-steps">
				<li><?php echo esc_html__( 'Open Windsurf and access MCP configuration.', 'my-site-hand' ); ?></li>
				<li><?php echo esc_html__( 'Add a new HTTP server with the provided configuration.', 'my-site-hand' ); ?></li>
			</ul>
		</div>
	</div>

	<!-- Tab Content: Generic -->
	<div class="sa-tab-content" data-client="generic">
		<div class="sa-snippet-header">
			<div class="sa-snippet-title">HTTP Endpoint</div>
			<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="msh.copyMcpConfig()">
				<span id="sa-copy-config-text-generic"><?php echo esc_html__( 'Copy URL', 'my-site-hand' ); ?></span>
			</button>
		</div>
		<div class="sa-snippet-code-container">
			<pre id="sa-mcp-config-code-generic" class="sa-snippet-code"><code><?php echo esc_url( $msh_mcp_endpoint ); ?></code></pre>
		</div>
		<div class="sa-setup-instructions">
			<p style="color:var(--sa-text-secondary);"><?php echo esc_html__( 'Use this standard HTTP endpoint with any MCP-compatible client.', 'my-site-hand' ); ?></p>
		</div>
	</div>

	<div class="sa-snippet-footer">
		<p>
			<?php echo esc_html__( 'Need a token?', 'my-site-hand' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=my-site-hand-tokens' ) ); ?>"><?php echo esc_html__( 'Generate one here', 'my-site-hand' ); ?></a>
		</p>
	</div>
</div>




