/**
 * WP SiteAgent — Dashboard Connect JavaScript
 *
 * Handles the "Connect AI Client" widget on the dashboard:
 * live token input, client tab switching, OS detection,
 * and command generation.
 */

/* global siteagentAdmin, siteagent */

'use strict';

(function () {

	const cfg = window.siteagentAdmin || {};
	const i18n = cfg.i18n || {};

	// Auto-detect OS.
	function detectOs() {
		const ua = (navigator.userAgentData?.platform || navigator.platform || navigator.userAgent || '').toLowerCase();
		if (ua.includes('mac') || ua.includes('darwin')) return 'mac';
		if (ua.includes('linux') || ua.includes('android')) return 'linux';
		return 'windows';
	}

	window.siteagentDash = {

		selectedOs: detectOs(),
		selectedClient: 'claude',

		/**
		 * Initialize the widget.
		 */
		init: function () {
			// Auto-select OS tab.
			this.switchOs(this.selectedOs);

			// Listen for token input.
			const tokenInput = document.getElementById('sa-dash-token');
			if (tokenInput) {
				tokenInput.addEventListener('input', () => this.updateCommands());
			}
		},

		/**
		 * Switch between AI clients (Claude, Cursor, etc.)
		 * 
		 * @param {string} client - 'claude', 'cursor'
		 */
		switchClient: function (client) {
			this.selectedClient = client;

			// Update tab styles.
			const tabs = ['claude', 'cursor'];
			tabs.forEach(t => {
				const el = document.getElementById('sa-dash-client-tab-' + t);
				if (el) {
					if (t === client) el.classList.add('sa-tab-btn--active');
					else el.classList.remove('sa-tab-btn--active');
				}
			});

			// Toggle panels.
			const claudePanel = document.getElementById('sa-dash-claude-panel');
			const cursorPanel = document.getElementById('sa-dash-cursor-panel');

			if (client === 'claude') {
				if (claudePanel) claudePanel.style.display = 'block';
				if (cursorPanel) cursorPanel.style.display = 'none';
			} else {
				if (claudePanel) claudePanel.style.display = 'none';
				if (cursorPanel) cursorPanel.style.display = 'block';
			}

			this.updateCommands();
		},

		/**
		 * Switch the active OS tab in the Claude connection section.
		 * 
		 * @param {string} os - 'windows', 'mac', 'linux'
		 */
		switchOs: function (os) {
			this.selectedOs = os;

			// Update tab styles.
			const tabs = ['windows', 'mac', 'linux'];
			tabs.forEach(t => {
				const el = document.getElementById('sa-dash-os-tab-' + t);
				if (el) {
					if (t === os) el.classList.add('sa-os-tab--active');
					else el.classList.remove('sa-os-tab--active');
				}
			});

			this.updateCommands();
		},

		/**
		 * Update the generated commands and fields based on the token and selection.
		 */
		updateCommands: function () {
			const token = document.getElementById('sa-dash-token')?.value?.trim() || '';
			const endpoint = cfg.mcpEndpoint || '';

			if (this.selectedClient === 'claude') {
				const commandBlock = document.getElementById('sa-dash-command-block');
				if (!commandBlock) return;

				if (!token) {
					commandBlock.textContent = 'Paste your token above to generate the command';
					return;
				}

				let command = '';
				if (this.selectedOs === 'windows') {
					command = `npm install -g mcp-remote; node -e "const fs=require('fs'),os=require('os'),path=require('path'),cp=require('child_process');const home=os.homedir();const p=path.join(home,'AppData','Roaming','Claude','claude_desktop_config.json');const dir=path.dirname(p);if(!fs.existsSync(dir))fs.mkdirSync(dir,{recursive:true});let d={};try{d=JSON.parse(fs.readFileSync(p,'utf8'))}catch(e){};if(!d.mcpServers)d.mcpServers={};const proxy=path.join(cp.execSync('npm root -g').toString().trim(),'mcp-remote','dist','proxy.js');d.mcpServers['wp-siteagent']={command:'node',args:[proxy,'${endpoint}','--header','Authorization: Bearer ${token}']};fs.writeFileSync(p,JSON.stringify(d,null,2));console.log('Done');" ; Write-Host "Successfully connected. Restart Claude Desktop." -ForegroundColor Green`;
				} else {
					command = `npm install -g mcp-remote && node -e "const fs=require('fs'),os=require('os'),path=require('path'),cp=require('child_process');const home=os.homedir();const configPath=process.platform==='darwin'?path.join(home,'Library','Application Support','Claude','claude_desktop_config.json'):path.join(home,'.config','Claude','claude_desktop_config.json');const dir=path.dirname(configPath);if(!fs.existsSync(dir))fs.mkdirSync(dir,{recursive:true});let d={};try{d=JSON.parse(fs.readFileSync(configPath,'utf8'))}catch(e){};if(!d.mcpServers)d.mcpServers={};const proxy=path.join(cp.execSync('npm root -g').toString().trim(),'mcp-remote','dist','proxy.js');d.mcpServers['wp-siteagent']={command:'node',args:[proxy,'${endpoint}','--header','Authorization: Bearer ${token}']};fs.writeFileSync(configPath,JSON.stringify(d,null,2));console.log('Connected. Restart Claude Desktop.');"`;
				}

				commandBlock.textContent = command;
			} else {
				// Cursor / IDEs
				const authInput = document.getElementById('sa-dash-cursor-auth');
				if (authInput) {
					authInput.value = token ? 'Bearer ' + token : '';
				}
			}
		},

		/**
		 * Copy the currently displayed Claude command to clipboard.
		 */
		copyCommand: function () {
			const commandBlock = document.getElementById('sa-dash-command-block');
			if (!commandBlock) return;

			const text = commandBlock.textContent;
			if (!text || text.includes('Paste your token')) {
				if (window.siteagent && window.siteagent._showToast) {
					window.siteagent._showToast('Please enter a token first.', 'error');
				}
				return;
			}

			// Use a temporary textarea to copy.
			const el = document.createElement('textarea');
			el.value = text;
			el.setAttribute('readonly', '');
			el.style.position = 'absolute';
			el.style.left = '-9999px';
			document.body.appendChild(el);
			el.select();
			document.execCommand('copy');
			document.body.removeChild(el);

			if (window.siteagent && window.siteagent._showToast) {
				window.siteagent._showToast(i18n.copied || 'Copied!');
			}
		}
	};

	// Initialize on DOM ready.
	document.addEventListener('DOMContentLoaded', function () {
		window.siteagentDash.init();
	});

}());
