/**
 * WP SiteAgent — Token Manager JavaScript
 *
 * Handles the generate-token modal: opening, form submission,
 * token reveal, copy, confirmation checkbox, and token revocation.
 */

/* global siteagentAdmin, siteagent */

'use strict';

(function () {

	const cfg = window.siteagentAdmin || {};
	const restUrl = cfg.restUrl || '';
	const restNonce = cfg.restNonce || '';
	const i18n = cfg.i18n || {};

	let generatedToken = null;

	window.siteagentTokens = {

		/**
		 * Open the generate token modal.
		 */
		openGenerateModal: function () {
			const modal = document.getElementById('sa-generate-modal');
			if (!modal) return;

			// Reset form state.
			this._resetModal();

			modal.style.display = 'flex';

			// Focus label input after animation.
			setTimeout(() => {
				const label = document.getElementById('sa-token-label');
				if (label) label.focus();
			}, 150);
		},

		/**
		 * Close the generate token modal.
		 */
		closeModal: function () {
			const modal = document.getElementById('sa-generate-modal');
			if (modal) modal.style.display = 'none';

			// Reload to show new/revoked tokens in table.
			if (generatedToken) {
				window.location.reload();
			}
		},

		/**
		 * Submit token generation request.
		 */
		generateToken: function () {
			const label = document.getElementById('sa-token-label')?.value?.trim();

			if (!label) {
				siteagent._showToast('Label is required.', 'error');
				document.getElementById('sa-token-label')?.focus();
				return;
			}

			const expiresAt = document.getElementById('sa-token-expires')?.value || null;

			// Collect checked abilities.
			const abilityCheckboxes = document.querySelectorAll('input[name="abilities[]"]:checked');
			const abilities = Array.from(abilityCheckboxes).map(cb => cb.value);

			// Build payload.
			const payload = {
				label: label,
				abilities: abilities,
				expires_at: expiresAt || null
			};

			const btn = document.getElementById('sa-submit-token');
			if (btn) { btn.disabled = true; btn.textContent = i18n.saving || 'Generating…'; }

			fetch(restUrl + 'tokens', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce
				},
				body: JSON.stringify(payload)
			})
			.then(r => {
				if (!r.ok) return r.json().then(d => { throw new Error(d.message || 'Request failed'); });
				return r.json();
			})
			.then(data => {
				generatedToken = data.token;
				this._revealToken(data.token);
			})
			.catch(err => {
				siteagent._showToast(err.message || i18n.error || 'Error occurred.', 'error');
				if (btn) { btn.disabled = false; btn.textContent = 'Generate Token'; }
			});
		},

		/**
		 * Copy the newly generated token to clipboard.
		 */
		copyNewToken: function () {
			if (!generatedToken) return;

			navigator.clipboard.writeText(generatedToken).then(() => {
				siteagent._showToast(i18n.copied || 'Copied!');
			}).catch(() => {
				const ta = document.createElement('textarea');
				ta.value = generatedToken;
				ta.style.cssText = 'position:fixed;opacity:0';
				document.body.appendChild(ta);
				ta.select();
				document.execCommand('copy');
				document.body.removeChild(ta);
				siteagent._showToast(i18n.copied || 'Copied!');
			});
		},

		/**
		 * Revoke a token via REST API.
		 *
		 * @param {number} tokenId  - Token ID to revoke.
		 * @param {string} label    - Token label for confirmation.
		 */
		revokeToken: function (tokenId, label) {
			const msg = (i18n.confirmRevoke || 'Are you sure you want to revoke this token?')
				.replace('this token', '"' + label + '"');

			if (!confirm(msg)) return;

			fetch(restUrl + 'tokens/' + tokenId, {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': restNonce }
			})
			.then(r => r.json())
			.then(data => {
				if (data.revoked) {
					siteagent._showToast('Token revoked.');
					setTimeout(() => window.location.reload(), 1000);
				} else {
					siteagent._showToast(data.message || i18n.error, 'error');
				}
			})
			.catch(() => siteagent._showToast(i18n.error || 'Error.', 'error'));
		},

		/**
		 * Show the token value and switch UI to reveal mode.
		 *
		 * @param {string} token - Raw token string to reveal.
		 * @private
		 */
		_revealToken: function (token) {
			// Hide the generate form / button.
			const form = document.getElementById('sa-generate-token-form');
			if (form) {
				Array.from(form.elements).forEach(el => {
					if (el.id !== 'sa-confirm-copied') el.disabled = true;
				});
			}

			const submitBtn = document.getElementById('sa-submit-token');
			if (submitBtn) submitBtn.style.display = 'none';

			// Show token.
			const reveal = document.getElementById('sa-token-reveal');
			if (reveal) reveal.style.display = 'block';

			const tokenEl = document.getElementById('sa-new-token-value');
			if (tokenEl) tokenEl.textContent = token;

			// Show done button; enable when checkbox is checked.
			const doneBtn = document.getElementById('sa-close-after-copy');
			if (doneBtn) doneBtn.style.display = 'inline-flex';

			const checkbox = document.getElementById('sa-confirm-copied');
			if (checkbox) {
				checkbox.addEventListener('change', function () {
					if (doneBtn) doneBtn.disabled = !this.checked;
				});
			}
		},

		/**
		 * Reset the modal form back to initial state.
		 *
		 * @private
		 */
		_resetModal: function () {
			generatedToken = null;

			const form = document.getElementById('sa-generate-token-form');
			if (form) form.reset();

			const reveal = document.getElementById('sa-token-reveal');
			if (reveal) reveal.style.display = 'none';

			const submitBtn = document.getElementById('sa-submit-token');
			if (submitBtn) { submitBtn.style.display = 'inline-flex'; submitBtn.disabled = false; submitBtn.textContent = 'Generate Token'; }

			const doneBtn = document.getElementById('sa-close-after-copy');
			if (doneBtn) { doneBtn.style.display = 'none'; doneBtn.disabled = true; }

			// Re-enable form elements.
			if (form) {
				Array.from(form.elements).forEach(el => { el.disabled = false; });
			}
		}
	};

}());
