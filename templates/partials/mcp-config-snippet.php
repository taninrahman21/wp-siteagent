<?php
/**
 * MCP configuration snippet partial - Multi-client edition.
 *
 * @package WP_SiteAgent
 */

defined( 'ABSPATH' ) || exit;

$siteagent_mcp_endpoint = get_rest_url( null, 'siteagent/v1/mcp' );
$siteagent_config_json  = wp_json_encode( [
	'mcpServers' => [
		'siteagent' => [
			'command' => 'npx',
			'args'    => [ '-y', '@builtbytanin/mcp-remote', '--url', $siteagent_mcp_endpoint ],
		],
	],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
?>
<div class="sa-mcp-snippet">
	<!-- Client Tabs -->
	<div class="sa-client-tabs">
		<button type="button" class="sa-tab-btn sa-tab-btn--active" data-client="claude" onclick="siteagent.switchClientTab('claude')">
			Claude Desktop
		</button>
		<button type="button" class="sa-tab-btn" data-client="cursor" onclick="siteagent.switchClientTab('cursor')">
			Cursor
		</button>
		<button type="button" class="sa-tab-btn" data-client="windsurf" onclick="siteagent.switchClientTab('windsurf')">
			Windsurf
		</button>
		<button type="button" class="sa-tab-btn" data-client="generic" onclick="siteagent.switchClientTab('generic')">
			Generic
		</button>
	</div>

	<!-- Tab Content: Claude Desktop -->
	<div class="sa-tab-content sa-tab-content--active" data-client="claude">
		<div class="sa-snippet-header">
			<div class="sa-snippet-title">claude_desktop_config.json</div>
			<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="siteagent.copyMcpConfig()">
				<span id="sa-copy-config-text-claude"><?php esc_html_e( 'Copy Json', 'siteagent' ); ?></span>
			</button>
		</div>
		<div class="sa-snippet-code-container">
			<pre id="sa-mcp-config-code-claude" class="sa-snippet-code"><code><?php echo esc_html( $config_json ); ?></code></pre>
		</div>
		<div class="sa-setup-instructions">
			<h4><?php esc_html_e( 'Setup Instructions', 'siteagent' ); ?></h4>
			<ul class="sa-setup-steps">
				<li><?php esc_html_e( 'Locate your Claude Desktop config file.', 'siteagent' ); ?></li>
				<li><?php esc_html_e( 'Add the JSON block above to your mcpServers section.', 'siteagent' ); ?></li>
				<li><?php esc_html_e( 'Restart Claude Desktop.', 'siteagent' ); ?></li>
			</ul>
		</div>
	</div>

	<!-- Tab Content: Cursor -->
	<div class="sa-tab-content" data-client="cursor">
		<div class="sa-snippet-header">
			<div class="sa-snippet-title">Cursor Settings</div>
			<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="siteagent.copyMcpConfig()">
				<span id="sa-copy-config-text-cursor"><?php esc_html_e( 'Copy URL', 'siteagent' ); ?></span>
			</button>
		</div>
		<div class="sa-snippet-code-container" style="display:none;">
			<pre id="sa-mcp-config-code-cursor" class="sa-snippet-code"><code><?php echo esc_url( $mcp_endpoint ); ?></code></pre>
		</div>
		<div class="sa-setup-instructions">
			<h4><?php esc_html_e( 'Setup Instructions', 'siteagent' ); ?></h4>
			<ul class="sa-setup-steps">
				<li><?php esc_html_e( 'Open Cursor and navigate to Settings > Features > MCP.', 'siteagent' ); ?></li>
				<li><?php esc_html_e( 'Click "+ Add Server" and paste the endpoint URL.', 'siteagent' ); ?></li>
				<li><?php esc_html_e( 'Add an Authorization header with your Bearer token.', 'siteagent' ); ?></li>
			</ul>
		</div>
	</div>

	<!-- Tab Content: Windsurf -->
	<div class="sa-tab-content" data-client="windsurf">
		<div class="sa-snippet-header">
			<div class="sa-snippet-title">Windsurf Settings</div>
			<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="siteagent.copyMcpConfig()">
				<span id="sa-copy-config-text-windsurf"><?php esc_html_e( 'Copy Json', 'siteagent' ); ?></span>
			</button>
		</div>
		<div class="sa-snippet-code-container">
			<pre id="sa-mcp-config-code-windsurf" class="sa-snippet-code"><code><?php echo esc_html( $config_json ); ?></code></pre>
		</div>
		<div class="sa-setup-instructions">
			<h4><?php esc_html_e( 'Setup Instructions', 'siteagent' ); ?></h4>
			<ul class="sa-setup-steps">
				<li><?php esc_html_e( 'Open Windsurf and access MCP configuration.', 'siteagent' ); ?></li>
				<li><?php esc_html_e( 'Add a new HTTP server with the provided configuration.', 'siteagent' ); ?></li>
			</ul>
		</div>
	</div>

	<!-- Tab Content: Generic -->
	<div class="sa-tab-content" data-client="generic">
		<div class="sa-snippet-header">
			<div class="sa-snippet-title">HTTP Endpoint</div>
			<button type="button" class="sa-btn sa-btn--ghost sa-btn--sm" onclick="siteagent.copyMcpConfig()">
				<span id="sa-copy-config-text-generic"><?php esc_html_e( 'Copy URL', 'siteagent' ); ?></span>
			</button>
		</div>
		<div class="sa-snippet-code-container">
			<pre id="sa-mcp-config-code-generic" class="sa-snippet-code"><code><?php echo esc_url( $mcp_endpoint ); ?></code></pre>
		</div>
		<div class="sa-setup-instructions">
			<p style="color:var(--sa-text-secondary);"><?php esc_html_e( 'Use this standard HTTP endpoint with any MCP-compatible client.', 'siteagent' ); ?></p>
		</div>
	</div>

	<div class="sa-snippet-footer">
		<p>
			<?php esc_html_e( 'Need a token?', 'siteagent' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=siteagent-tokens' ) ); ?>"><?php esc_html_e( 'Generate one here', 'siteagent' ); ?></a>
		</p>
	</div>
</div>

