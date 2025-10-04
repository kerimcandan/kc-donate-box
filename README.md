# KC Donate Box

A lightweight donate/support box for WordPress with repeatable links and multiple crypto wallets (with QR).  
Shortcodes: `[kc_donate_box]`, `[kc_support_box]` (legacy)

> WordPress.org: https://wordpress.org/plugins/kc-donate-box/ *(becomes active after approval)*

---

## Features

- Custom title & message
- Repeatable custom links (e.g. Buy me a coffee, PayPal)
- Multiple crypto wallets with QR (uploaded image or auto-generated)
- Copy-to-clipboard button
- Shortcodes + automatic insert after content
- Reset to defaults, JSON export/import
- No tracking. No external calls unless you enable “Auto” QR (uses `api.qrserver.com`)

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

**From WordPress admin (recommended)**
1. Plugins → Add New → Upload Plugin → upload the ZIP.
2. Activate the plugin.

**From source (GitHub)**
1. Clone/copy this folder into `wp-content/plugins/kc-donate-box`.
2. Activate via **Plugins**.

## Usage

By default the box renders after single posts.  
You can also place it anywhere via shortcode:

```text
[kc_donate_box]

Settings

Settings → KC Donate Box

Enable plugin: Master switch.

Show only on single posts: If unchecked, shows on all singulars.

Box title & Message

Custom links: Repeatable label + URL (open in new tab, nofollow).

Crypto wallets (repeatable):

Type: Bitcoin / Ethereum / Litecoin / Custom

Address (required)

Custom scheme (when Type = Custom)

QR mode: Uploaded image / Auto (api.qrserver.com) / None

QR image URL (when Uploaded)

Show copy button

Export / Import

Export generates a JSON blob of all settings.

Import: paste the JSON and Save.

Reset

Reset to defaults button (nonce-protected).

Uninstall

Deleting the plugin removes the options:

kc_donate_box_opts

legacy: kc_support_box_opts

(Multisite-safe.)

Screenshots

Settings screen

Front-end donate box

Crypto section with QR

Changelog

See readme.txt
 – Stable tag: 1.6.2

Contributing

Issues and PRs are welcome. Please follow WordPress Coding Standards (WPCS).
Security reports: please open a private security advisory or contact the author.

License

GPLv2 or later. See LICENSE
.
