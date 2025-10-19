# KC Donate Box

[![WP Tested](https://img.shields.io/badge/WordPress-6.8%20tested-brightgreen?logo=wordpress&logoColor=white)](#)
![License](https://img.shields.io/badge/License-GPLv2%2B-blue)
![Stable tag](https://img.shields.io/badge/stable-1.6.2-informational)

A lightweight donate/support box for WordPress with repeatable links and multiple crypto wallets (with QR).

**Shortcodes**
- New (preferred): `[kcdobo_donate_box]`, `[kcdobo_support_box]`
- Legacy (still supported): `[kc_donate_box]`, `[kc_support_box]`

- **WordPress.org:** https://wordpress.org/plugins/kc-donate-box/ *(will be active after approval)*
- **Homepage / Docs:** https://kerimcandan.com/kc-donate-box/
- **Author:** https://kerimcandan.com/

---

## Features
- Custom title & message
- Repeatable custom links (e.g., Buy me a coffee, PayPal)
- Multiple crypto wallets with QR (uploaded image or auto-generated)
- Copy-to-clipboard button
- Automatic insert after content or via shortcode
- Reset to defaults, JSON export/import
- No tracking. No external calls unless you enable **Auto** QR (uses `api.qrserver.com`)

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
[kcdobo_donate_box]
```

Legacy:
```text
[kc_donate_box]
```

Then configure under:
```
Settings → KC Donate Box
```

### Settings include
- Enable plugin
- Show only on single posts
- Box title & message
- **Custom links** (repeatable: label, URL, enabled)
- **Crypto wallets** (repeatable): Type (BTC/ETH/LTC/Custom), Address, Custom scheme, QR mode (Upload/Auto/None), QR image URL, Copy button
- Export/Import (JSON), Reset to defaults

## External services (QR)
If **QR mode = Auto**, the plugin requests a QR image from **GOQR’s API** (`api.qrserver.com`) and sends only the wallet URI (e.g., `bitcoin:...`) as the `data` parameter.
- ToS: https://goqr.me/legal/tos-api.html  
- Privacy: https://goqr.me/privacy-safety-security/  
- API: https://goqr.me/api/doc/create-qr-code/  

Prefer **Upload** or **None** if you don’t want any external request.

## Uninstall
Deleting the plugin removes its options:
- Current: `kcdobo_options`
- Legacy (cleaned for compatibility): `kc_donate_box_opts`, `kc_support_box_opts`  
Multisite-safe.

## Screenshots
1. Settings screen  
2. Front-end donate box  
3. Crypto section with QR

## Changelog
See `readme.txt`.

### Next (RC) — 1.6.3-rc1
- Fix: Removed inline `<script>` output; all admin/front assets are enqueued via WordPress.
- Fix: Settings sanitization — removing all rows now stores empty arrays instead of restoring defaults; sections not posted keep previous values.
- Docs: “External services” section (QR API: what/why/when/where + ToS/Privacy).
- Dev: Introduced longer internal prefix (`kcdobo_`) in preparation for broader namespacing; legacy shortcodes/options remain compatible.

## Contributing
Issues and PRs are welcome. Please follow WordPress Coding Standards (WPCS).

## License
GPLv2 or later. See `LICENSE`.
