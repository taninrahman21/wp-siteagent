/**
 * WP SiteAgent — Admin JavaScript
 *
 * Core utilities: clipboard, cache clearing, danger actions, option saving,
 * log row expansion, and audit filter helpers.
 */

/* global siteagentAdmin */

'use strict';

(function () {

	const cfg = window.siteagentAdmin || {};
	const restUrl = cfg.restUrl || '';
	const restNonce = cfg.restNonce || '';
	const i18n = cfg.i18n || {};

	/** -----------------------------------------------------------------------
	 * Core utilities
	 * ---------------------------------------------------------------------- */
	window.siteagent = {
		/**
		 * Toggle an individual ability via AJAX.
		 * 
		 * @param {string} name - Ability name.
		 * @param {boolean} isEnabled - Toggle state.
		 */
		toggleAbility: function (name, isEnabled) {
			fetch(cfg.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'siteagent_toggle_ability',
					ability_name: name,
					is_enabled: isEnabled ? 1 : 0,
					nonce: cfg.nonce
				})
			})
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					this._showToast(i18n.saved || 'Saved!');
				} else {
					this._showToast(data.data?.message || i18n.error || 'Error.', 'error');
				}
			})
			.catch(() => this._showToast(i18n.error || 'Error occurred.', 'error'));
		},

		/**
		 * Copy the text of an element to the clipboard.
		 *
		 * @param {string} elementId - ID of the element to copy.
		 */
		copyText: function (elementId) {
			const el = document.getElementById(elementId);
			if (!el) return;

			const text = el.tagName === 'INPUT' || el.tagName === 'TEXTAREA'
				? el.value
				: el.textContent;

			navigator.clipboard.writeText(text).then(() => {
				this._showToast(i18n.copied || 'Copied!');
			}).catch(() => {
				// Fallback for non-secure contexts.
				const ta = document.createElement('textarea');
				ta.value = text;
				ta.style.position = 'fixed';
				ta.style.opacity = '0';
				document.body.appendChild(ta);
				ta.select();
				document.execCommand('copy');
				document.body.removeChild(ta);
				this._showToast(i18n.copied || 'Copied!');
			});
		},

		/**
		 * Copy the MCP config snippet for the active client.
		 */
		copyMcpConfig: function () {
			const activeTab = document.querySelector('.sa-tab-btn--active');
			if (!activeTab) return;
			
			const client = activeTab.getAttribute('data-client');
			const codeId = 'sa-mcp-config-code-' + client;
			const textId = 'sa-copy-config-text-' + client;
			
			const el = document.getElementById(codeId);
			if (!el) return;
			
			this.copyText(codeId);

			const btnText = document.getElementById(textId);
			if (btnText) {
				const originalText = btnText.textContent;
				btnText.textContent = i18n.copied || 'Copied!';
				setTimeout(() => { btnText.textContent = originalText; }, 2000);
			}
		},

		/**
		 * Switch between MCP client setup tabs.
		 * 
		 * @param {string} client - Client slug (claude|cursor|windsurf).
		 */
		switchClientTab: function (client) {
			// Update buttons
			document.querySelectorAll('.sa-tab-btn').forEach(btn => {
				btn.classList.toggle('sa-tab-btn--active', btn.getAttribute('data-client') === client);
			});

			// Update content
			document.querySelectorAll('.sa-tab-content').forEach(content => {
				content.classList.toggle('sa-tab-content--active', content.getAttribute('data-client') === client);
			});
		},

		/**
		 * Clear all SiteAgent caches via REST API.
		 */
		clearCache: function () {
			const btn = document.getElementById('sa-clear-cache-btn');
			const result = document.getElementById('sa-cache-result');

			if (btn) btn.disabled = true;
			if (result) { result.textContent = i18n.saving || 'Clearing…'; result.classList.add('sa-inline-result--visible'); }

			fetch(restUrl + 'cache/clear', {
				method: 'POST',
				headers: { 'X-WP-Nonce': restNonce }
			})
			.then(r => r.json())
			.then(data => {
				if (result) result.textContent = data.message || (i18n.cacheCleared || 'Cache cleared!');
				setTimeout(() => {
					if (result) result.classList.remove('sa-inline-result--visible');
				}, 3000);
			})
			.catch(() => {
				if (result) result.textContent = i18n.error || 'Error occurred.';
			})
			.finally(() => {
				if (btn) btn.disabled = false;
			});
		},

		/**
		 * Execute a danger-zone action with confirmation.
		 *
		 * @param {string} action - Action name (delete_tokens|delete_logs).
		 * @param {string} nonce  - Admin nonce.
		 */
		dangerAction: function (action, nonce) {
			const confirmMessages = {
				delete_tokens: 'This will revoke ALL API tokens and immediately disconnect all MCP clients. Are you sure?',
				delete_logs: 'This will permanently delete all audit log entries. Are you sure?'
			};

			if (!confirm(confirmMessages[action] || 'Are you sure?')) return;

			fetch(cfg.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'siteagent_danger_action',
					danger_action: action,
					nonce: nonce || cfg.nonce
				})
			})
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					this._showToast(data.data?.message || 'Done.');
					setTimeout(() => window.location.reload(), 1500);
				} else {
					this._showToast(data.data?.message || i18n.error || 'Error.', 'error');
				}
			})
			.catch(() => this._showToast(i18n.error || 'Error occurred.', 'error'));
		},

		/**
		 * Save a single option via AJAX.
		 *
		 * @param {string} optionName  - Option key.
		 * @param {any} value - Option value.
		 */
		saveOption: function (optionName, value) {
			fetch(cfg.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'siteagent_save_option',
					option_name: optionName,
					option_value: value,
					nonce: cfg.nonce
				})
			}).then(r => r.json()).then(data => {
				if (data.success) {
					this._showToast(i18n.saved || 'Saved!');
				} else {
					this._showToast(data.data?.message || i18n.error || 'Error.', 'error');
				}
			}).catch(() => this._showToast(i18n.error || 'Error occurred.', 'error'));
		},

		/**
		 * Toggle a module in the active list via AJAX.
		 * 
		 * @param {string} slug - Module slug.
		 * @param {boolean} isEnabled - Toggle state.
		 */
		toggleModule: function (slug, isEnabled) {
			fetch(cfg.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					action: 'siteagent_toggle_module',
					module_slug: slug,
					is_enabled: isEnabled ? 1 : 0,
					nonce: cfg.nonce
				})
			})
			.then(r => r.json())
			.then(data => {
				if (data.success) {
					this._showToast(i18n.saved || 'Saved!');
				} else {
					this._showToast(data.data?.message || i18n.error || 'Error.', 'error');
				}
			})
			.catch(() => this._showToast(i18n.error || 'Error occurred.', 'error'));
		},

		/**
		 * Toggle the details row of an audit log entry.
		 *
		 * @param {number} logId - Log row ID.
		 */
		expandLogRow: function (logId) {
			const row = document.getElementById('log-detail-' + logId);
			if (!row) return;
			const isVisible = row.style.display !== 'none';
			row.style.display = isVisible ? 'none' : 'table-row';
		},

		/**
		 * Display a brief floating toast notification.
		 *
		 * @param {string} message - Text to display.
		 * @param {string} type    - 'success' (default) or 'error'.
		 * @private
		 */
		_showToast: function (message, type = 'success') {
			const existing = document.getElementById('sa-toast');
			if (existing) existing.remove();

			const toast = document.createElement('div');
			toast.id = 'sa-toast';
			toast.textContent = message;
			Object.assign(toast.style, {
				position: 'fixed',
				bottom: '32px',
				right: '28px',
				background: type === 'error' ? '#ef4444' : '#22c55e',
				color: '#fff',
				padding: '12px 20px',
				borderRadius: '10px',
				fontSize: '13px',
				fontWeight: '600',
				fontFamily: "'Inter', sans-serif",
				boxShadow: '0 8px 32px rgba(0,0,0,0.4)',
				zIndex: '999999',
				animation: 'sa-fade-in 0.15s ease-out',
				cursor: 'pointer'
			});

			toast.addEventListener('click', () => toast.remove());
			document.body.appendChild(toast);

			setTimeout(() => {
				toast.style.opacity = '0';
				toast.style.transition = 'opacity 0.3s ease';
				setTimeout(() => toast.remove(), 300);
			}, 3000);
		}
	};

	/** -----------------------------------------------------------------------
	 * DOM Ready
	 * ---------------------------------------------------------------------- */
	document.addEventListener('DOMContentLoaded', function () {
		// Override WordPress admin background to match our dark theme.
		const wrap = document.querySelector('.sa-wrap');
		if (wrap) {
			const wpBody = document.getElementById('wpbody-content');
			if (wpBody) {
				wpBody.style.background = '#0f0f1a';
			}
			const wpWrap = document.getElementById('wpbody');
			if (wpWrap) {
				wpWrap.style.background = '#0f0f1a';
			}
		}

		// Close modals on overlay click.
		document.addEventListener('click', function (e) {
			if (e.target && e.target.classList.contains('sa-modal-overlay')) {
				const modal = e.target;
				modal.style.display = 'none';
			}
		});

		// Close modals on Escape.
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				const modal = document.querySelector('.sa-modal-overlay[style*="flex"], .sa-modal-overlay[style*="block"]');
				if (modal) modal.style.display = 'none';
			}
		});
	});

	/** -----------------------------------------------------------------------
	 * Register AJAX handlers (danger zone, save option)
	 * ---------------------------------------------------------------------- */
	// Register WordPress AJAX actions via PHP (see class-plugin.php boot).

}());
