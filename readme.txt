=== KC Donate Box ===
Contributors: kerimcandan
Tags: donate, donation, support, crypto, paypal
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Tested up to PHP: 8.4
Stable tag: 1.6.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html


A lightweight donate/support box with custom links and multiple crypto wallets with QR.

== Description ==
KC Donate Box adds a lightweight donate/support panel after your posts:
- Custom title & message
- Repeatable custom links (e.g., Buy me a coffee, PayPal)
- Multiple crypto wallets with QR (uploaded image or auto-generated)
- Copy-to-clipboard button
- Shortcodes: [kcdobo_donate_box] (new), [kcdobo_support_box] (alias), and legacy [kc_donate_box], [kc_support_box]
- Reset to defaults, and JSON export/import
- No tracking. No external calls unless you choose “Auto” QR (uses api.qrserver.com)

== External Services ==

This plugin can optionally generate QR images via an external API when the “QR mode” is set to “Auto”.
In that case, the plugin sends the wallet URI (e.g., “bitcoin:...”, “ethereum:...”) to the external service to get a QR image.

Service: GOQR — QR code API (api.qrserver.com)
- What it is used for: Generating QR images for the configured wallet URIs.
- What data is sent: Only the wallet URI (as the `data` parameter) and the requested QR size. No personal data is sent by the plugin.
- When data is sent: On front-end render (when the donate box is visible) and only for entries where QR Mode = “Auto”.
- Terms of Service: https://goqr.me/legal/tos-api.html
- Privacy Policy: https://goqr.me/privacy-safety-security/
- API docs: https://goqr.me/api/doc/create-qr-code/

If you prefer not to contact external services, set “QR mode” to “Upload” (use a locally uploaded QR image) or “None”.

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/` or upload the ZIP in **Plugins → Add New → Upload**.
2. Activate the plugin through the ‘Plugins’ menu in WordPress.
3. Go to **Settings → KC Donate Box** and configure.

== Frequently Asked Questions ==

= Can I show it on every post type? =
By default it shows on single posts. Uncheck “Show only on single posts” to render it everywhere.

= Does it support emojis? =
You can paste emojis manually in the Message/Link labels.

= What shortcodes are supported? =
Use [kcdobo_donate_box] (recommended) or [kcdobo_support_box]. Legacy shortcodes [kc_donate_box] and [kc_support_box] still work for backward compatibility.

== Screenshots ==
1. Settings screen
2. Front-end donate box
3. Crypto section with QR

== Changelog ==
= 1.6.3 =
* Fix: Removed inline <script> output; all admin/front assets are enqueued via wp_enqueue_*.
* Fix: Settings sanitization — removing all rows now saves empty arrays instead of restoring defaults; when a section is not posted, previous values are kept.
* Docs: Added “External Services” section (QR API: what/why/when/where + ToS/Privacy links). Clarified that “Auto” QR uses api.qrserver.com.
* Dev: Introduced longer internal prefix (kcdobo_) in preparation for a broader namespace refactor; legacy shortcodes and options are migrated/aliased for backward compatibility.

= 1.6.2 =
* Fix: escape rows attribute in textarea (Plugin Check).

= 1.6.1 =
* Hardening: escape output everywhere; PHPCS/Plugin Check fixes.
* Admin JS: safer template insertion (split/join).
* Readme: short description + ≤5 tags; “Tested up to: 6.8”.
* Removed deprecated textdomain loader call (WP.org handles it automatically).

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
= 1.6.1 =
Security hardening and Plugin Check fixes. Please update.
