# KC Donate Box

[![WP Tested](https://img.shields.io/badge/WordPress-6.8%20tested-brightgreen?logo=wordpress&logoColor=white)](#)
![License](https://img.shields.io/badge/License-GPLv2%2B-blue)
![Stable tag](https://img.shields.io/badge/stable-1.6.2-informational)

A lightweight donate/support box for WordPress with repeatable links and multiple crypto wallets (with QR).  
Shortcodes: `[kc_donate_box]`, `[kc_support_box]` (legacy)

- **WordPress.org:** https://wordpress.org/plugins/kc-donate-box/ *(becomes active after approval)*
- **Homepage / Docs (DE):** https://kerimcandan.com/kc-donate-box/
- **Author:** https://kerimcandan.com/

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
**From WordPress admin**
1. Plugins → Add New → Upload Plugin → upload the ZIP.
2. Activate the plugin.

**From source (GitHub)**
1. Copy this folder into `wp-content/plugins/kc-donate-box`.
2. Activate via **Plugins**.

## Usage
By default the box renders after single posts.  
You can also place it anywhere via shortcode:
```text
[kc_donate_box]
Settings

Settings → KC Donate Box

Enable plugin

Show only on single posts

Box title & Message

Custom links (repeatable label + URL)

Crypto wallets (repeatable): Type (BTC/ETH/LTC/Custom), Address, Custom scheme, QR mode (Upload/Auto/None), QR image URL, Copy button

Export/Import (JSON), Reset to defaults

Uninstall

Deleting the plugin removes:

kc_donate_box_opts

legacy: kc_support_box_opts
(Multisite-safe.)

Screenshots

Settings screen

Front-end donate box

Crypto section with QR

Changelog

See readme.txt
 — Stable tag: 1.6.2

Contributing

Issues and PRs are welcome. Please follow WordPress Coding Standards (WPCS).

License

GPLv2 or later. See LICENSE
.