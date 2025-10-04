=== KC Donate Box ===
Contributors: kerimcandan
Tags: donate, donation, support, crypto, bitcoin, ethereum, paypal
Requires at least: 6.0
Tested up to: 6.8.3
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
KC Donate Box adds a lightweight donate/support panel after your posts:
- Custom title & message
- Repeatable custom links (e.g., Buy me a coffee, PayPal)
- Multiple crypto wallets with QR (uploaded image or auto-generated)
- Copy-to-clipboard button
- Shortcodes: [kc_donate_box], [kc_support_box] (legacy)
- Reset to defaults, and JSON export/import
- No tracking. No external calls unless you choose “Auto” QR (uses api.qrserver.com)

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/` or upload the ZIP in **Plugins → Add New → Upload**.
2. Activate the plugin through the ‘Plugins’ menu in WordPress.
3. Go to **Settings → KC Donate Box** and configure.

== Frequently Asked Questions ==

= Can I show it on every post type? =
By default it shows on single posts. Uncheck “Show only on single posts” to render it everywhere.

= Does it support emojis? =
You can paste emojis manually in the Message/Link labels.

== Screenshots ==
1. Settings screen
2. Front-end donate box
3. Crypto section with QR

== Changelog ==
= 1.6.0 =
* Added uninstall.php (cleans options on delete; multisite-safe).
* Moved front CSS/JS to `assets/` files (no inline JS).
* Minor admin cosmetics via `assets/css/admin.css`.

= 1.5.0 =
* Public release without emoji picker.
* Simplified crypto fields (removed label/message), copy button, auto/uploaded QR.

= 1.4.1 =
* Bugfix: PHP syntax in build_crypto_uri().

= 1.4.0 =
* (Internal) Emoji picker in settings, repeatable links/cryptos, export/import, reset.

== Upgrade Notice ==
= 1.6.0 =
Switch to file-based assets and clean uninstall. Please re-save settings once after update.
