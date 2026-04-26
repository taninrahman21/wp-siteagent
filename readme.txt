=== WP SiteAgent ===
Contributors: siteagent
Tags: ai, mcp, model-context-protocol, claude, cursor, agent, automation
Requires at least: 6.9
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect AI agents (Claude Desktop, Cursor, and more) to your WordPress site via the Model Context Protocol (MCP).

== Description ==

WP SiteAgent exposes your WordPress site to AI coding agents and assistants through the [Model Context Protocol (MCP)](https://modelcontextprotocol.io/) — an open standard for AI-to-tool communication.

**Key Features:**

* 🤖 **MCP JSON-RPC 2.0 endpoint** — fully compatible with Claude Desktop, Cursor, Windsurf, and any MCP-compliant client
* ⚡ **50+ Abilities** — content management, SEO analysis, media library, users, diagnostics, WooCommerce
* 🔑 **Secure API tokens** — SHA-256 hashed, per-token ability restrictions, expiry dates
* 🛡️ **Rate limiting** — configurable hourly/daily limits per token
* 📋 **Audit logging** — every API call logged with input, status, duration, and IP
* ⚙️ **Modular** — enable/disable modules (Content, SEO, WooCommerce, Diagnostics, Media, Users)
* 🔍 **SEO module** — Yoast SEO and RankMath integration, keyword density, readability scoring, broken link checking
* 🛒 **WooCommerce module** — products, orders, coupons, customers, store analytics (auto-enabled when WooCommerce is active)
* 🩺 **Diagnostics** — site health report, error logs, cron jobs, DB table sizes, transients

**Included Abilities (50+):**

*Content:* list-posts, get-post, create-post, update-post, delete-post, bulk-update-posts, list-post-types, list-taxonomies, get-post-revisions

*SEO:* analyze-seo, set-meta-description, set-focus-keyword, bulk-seo-audit, get-sitemap-urls, check-broken-links

*WooCommerce:* woo-list/get/create/update/delete-product, woo-list/get-order, woo-update-order-status, woo-store-summary, woo-list-coupons, woo-create-coupon, woo-list-customers

*Diagnostics:* site-health-report, list-plugin-updates, get-error-logs, list-cron-jobs, get-site-options, list-transients, get-db-table-sizes

*Media:* list-media, get-unattached-media, update-media-alt-text, bulk-update-alt-text, get-large-media, get-media-library-stats

*Users:* list-users, get-user, create-user, update-user-role, list-roles, get-user-stats

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-siteagent` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **SiteAgent > API Tokens** and generate a new token.
4. Copy the token and add it to your MCP client configuration.

**Claude Desktop configuration:**

    {
      "mcpServers": {
        "wp-siteagent": {
          "type": "http",
          "url": "https://your-site.com/wp-json/siteagent/v1/mcp/streamable",
          "headers": {
            "Authorization": "Bearer YOUR_TOKEN_HERE"
          }
        }
      }
    }

== Frequently Asked Questions ==

= Does this require WooCommerce? =

No. The WooCommerce module is optional and automatically activates only if WooCommerce is installed and active.

= Is this compatible with Yoast SEO and RankMath? =

Yes. The SEO module detects both and uses their metadata fields when available.

= How are tokens stored? =

Tokens are stored as SHA-256 hashes — the raw token is shown only once at generation and never stored in plain text.

= Can I restrict what an AI agent can do? =

Yes. When generating a token, you can restrict it to a specific set of abilities.

= What WordPress version do I need? =

WordPress 6.9+ is required for full WordPress Abilities API compatibility.

== Screenshots ==

1. Dashboard overview with API call statistics and module status
2. API Token manager with one-time token reveal
3. Abilities browser showing all registered MCP tools
4. Audit log with filtering and CSV export
5. Settings page with module enable/disable controls

== Changelog ==

= 1.0.0 =
* Initial release.
* Full MCP JSON-RPC 2.0 server implementation.
* 50+ abilities across 6 modules.
* Secure token management with SHA-256 hashing.
* Rate limiting, audit logging, and caching.
* Full admin UI with dark-theme dashboard.
* Yoast SEO, RankMath, and WooCommerce integration.

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
