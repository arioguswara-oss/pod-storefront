# POD Simple Checkout v1.1.0 — UX v0.2.0 review package

**[Download the actual WordPress plugin ZIP](pod-simple-checkout-v1.1.0.zip?raw=true)**.
Use the Raw/download link; GitHub's source-code archive is not the plugin ZIP.

SHA-256: `09eae65a28850daacbd8566dfd15df598b06a6730e6971158f41f3ef7c42c6a1`.
One `pod-simple-checkout/` root, 16 files, no development dependencies.
`manifest.json`, `SHA256SUMS`, `verification.json` and `evidence/ux-package.json`
record the checked package and tested source. Native runtime remains standalone:
no Node, Composer or CLI is required on WordPress.

This GitHub-only review-package distribution follows the repository's existing
approach and does not depend on Actions or Release asset uploads. Local passes
are recorded separately from CI; no GitHub runner pass is claimed here.

## Screenshot evidence

All screenshots use original synthetic products and GraphicTeeStore fixture
branding, not the production website. The PNG manifest records dimensions and
checksums; canonical cart screenshots are full-page captures. See the actual
interaction results in `evidence/`, not screenshots alone.

| Exact CSS viewport | Product sheet | Cart sheet | Seven lines, scrolled | Long name, top | Native Classic Cart cards |
| --- | --- | --- | --- | --- | --- |
| 320×740 | [Product](screenshots/ux-product-320.png) | [Bag](screenshots/ux-cart-320.png) | [Bottom](screenshots/ux-cart-long-320.png) | [Top](screenshots/ux-cart-long-top-320.png) | [Cart](screenshots/ux-canonical-cart-320.png) |
| 390×844 | [Product](screenshots/ux-product-390.png) | [Bag](screenshots/ux-cart-390.png) | [Bottom](screenshots/ux-cart-long-390.png) | [Top](screenshots/ux-cart-long-top-390.png) | [Cart](screenshots/ux-canonical-cart-390.png) |
| 768×1024 | [Product](screenshots/ux-product-768.png) | [Bag](screenshots/ux-cart-768.png) | [Bottom](screenshots/ux-cart-long-768.png) | [Top](screenshots/ux-cart-long-top-768.png) | [Cart](screenshots/ux-canonical-cart-768.png) |
| 1440×900 | [Product](screenshots/ux-product-1440.png) | [Bag](screenshots/ux-cart-1440.png) | [Bottom](screenshots/ux-cart-long-1440.png) | [Top](screenshots/ux-cart-long-top-1440.png) | [Cart](screenshots/ux-canonical-cart-1440.png) |

## Review, upgrade and rollback

Read [implementation/upgrade/rollback](../../docs/shopping-ux-v0.2.0.md),
[executed tests and limits](../../docs/shopping-ux-v0.2.0-verification.md), and the
packaged `INSTALLATION.txt`, `COMPATIBILITY.txt`, `CHANGELOG.txt`, `SECURITY.txt`.
Retain the prior reviewed 1.0.0 ZIP and verify a complete backup before any
separately approved live upload/replacement/activation. Keep cart replacement OFF.
Disable AJAX for canonical-navigation fallback, or deactivate and reinstall the
prior package to roll back; do not overwrite newer buyer orders with an old DB.

Phases 1–2 are the supported review checkpoint. Phase 3 is
[a technical plan only](../../docs/phase-3-technical-plan.md). Third-party addons,
real gateways, production cache/CDN, Safari/Firefox and physical-device/screen
reader checks remain outside local Chromium certification. No live installation,
activation, deployment, configuration change, paid order or automatic merge.
