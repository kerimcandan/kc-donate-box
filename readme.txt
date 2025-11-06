=== KC Donate Box ===
Contributors: kerimcandan
Tags: donate, donation, support, crypto, paypal
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Tested up to PHP: 8.4
Stable tag: 1.6.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight donate/support box with custom links and multiple crypto wallets with QR.

== Description ==
KC Donate Box adds a lightweight donate/support panel after your posts:

- Custom title & message
- Repeatable custom links (e.g., Buy me a coffee, PayPal)
- Multiple crypto wallets with QR (uploaded image or auto-generated)
- Copy-to-clipboard button
- Shortcode: [kcdobo_donate_box]
- Reset to defaults, and JSON export/import
- No tracking. No external calls unless you choose “Auto” QR (uses api.qrserver.com)

== External Services ==
This plugin can optionally generate QR images via an external API when the “QR mode” is set to “Auto”.
In that case, the plugin sends the wallet URI (e.g., “bitcoin:…”, “ethereum:…”) to the external service to get a QR image.

Service: GOQR — QR code API (api.qrserver.com)  
- Purpose: Generating QR images for the configured wallet URIs.  
- Data sent: Only the wallet URI (as the `data` parameter) and the requested QR size. No personal data is sent by the plugin.  
- When: On front-end render (when the donate box is visible) and only for entries where QR Mode = “Auto”.  
- Terms of Service: https://goqr.me/legal/tos-api.html  
- Privacy Policy: https://goqr.me/privacy-safety-security/  
- API docs: https://goqr.me/api/doc/create-qr-code/

If you prefer not to contact external services, set “QR mode” to “Upload” (use a locally uploaded QR image) or “None”.

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/` or upload the ZIP in **Plugins ? Add New ? Upload**.
2. Activate the plugin through the “Plugins” menu in WordPress.
3. Go to **Settings ? KC Donate Box** and configure.

== Frequently Asked Questions ==

= Can I show it on every post type? =
By default it shows on single posts. Uncheck “Show only on single posts” to render it everywhere.

= Does it support emojis? =
You can paste emojis manually in the Message/Link labels.

= What shortcode is supported? =
Use `[kcdobo_donate_box]`.

== Screenshots ==
1. Settings screen
2. Front-end donate box
3. Crypto section with QR

== Changelog ==
= 1.6.4 =
* Prefix/Namespacing: Consolidated all shortcodes, option names, and admin/front CSS/JS selectors to the longer `kcdobo` scheme; removed remaining `kc_` traces.
* Asset Enqueue: Admin/front assets are loaded only via `wp_enqueue_*`. Inline `<script>` was removed; admin JS config is injected with `wp_add_inline_script( ..., 'before' )`.
* Security: Nonce validation now applies `sanitize_text_field( wp_unslash( ... ) )` before `wp_verify_nonce()`. Invalid requests fail safely.
* Admin UI: Repeater buttons and row selectors (add/remove) use the new prefix and delegated jQuery handlers.
* Uninstall: Cleans options on single-site and per-site on multisite.
* Docs: “External Services” section clarifies that only the optional “Auto” QR mode calls api.qrserver.com.

= 1.6.3 =
* Security/Hardening: Nonce validation path updated — unslash + sanitize before verification.
* Fix: Removed inline <script> output; all admin/front assets are enqueued via wp_enqueue_*.
* Fix: Settings sanitization — removing all rows now saves empty arrays instead of restoring defaults; when a section is not posted, previous values are kept.
* Docs: Added “External Services” section (QR API: what/why/when/where + ToS/Privacy links). Clarified that “Auto” QR uses api.qrserver.com.
* Dev: Switched to a longer internal prefix (kcdobo_). Removed legacy shortcodes and aliases.

= 1.6.2 =
* Fix: escape rows attribute in textarea (Plugin Check).

= 1.6.1 =
* Hardening: escape output everywhere; PHPCS/Plugin Check fixes.
* Admin JS: safer template insertion (split/join).
* Readme: short description + =5 tags; “Tested up to: 6.8”.

= 1.6.0 =
* Added uninstall.php (cleans options on delete; multisite-safe).
* Moved front CSS/JS to `assets/` files (no inline JS).
* Minor admin cosmetics via `assets/css/admin.css`.

= 1.5.0 =
* Public release without emoji picker.
* Simplified crypto fields, copy button, auto/uploaded QR.

= 1.4.1 =
* Bugfix: PHP syntax in build_crypto_uri().

= 1.4.0 =
* (Internal) Emoji picker, repeatable links/cryptos, export/import, reset.

== Upgrade Notice ==
= 1.6.4 =
Security hardening, strict prefixing, and enqueue fixes per review. Please update.
