# POD Simple Checkout v1.2.0 — Phase 3 review candidate

**[Download the actual WordPress-installable ZIP](pod-simple-checkout-v1.2.0.zip?raw=true)**.
Use Raw/download, not the GitHub source archive.

SHA-256: `40d54c71085cc24f051ffe28aa482d8c0620f0a97c70c7ad8ef5bd0e09232962`.
18 files, one `pod-simple-checkout/` root, 45,196 bytes. No fixture, development
package, Node, Composer or CLI dependency on WordPress. See manifest, SHA256SUMS,
verification and exact package-byte checks in evidence/.

## Screenshots of synthetic local checkout

The payment-display fixture explicitly refuses processing. It is not a real
provider and is not shipped. Images demonstrate original GraphicTeeStore UI;
merchant gateway compatibility still needs separately authorized acceptance.

| Exact CSS viewport | Default compatible billing | Alternate billing | Reachable native submit |
| --- | --- | --- | --- |
| 320×740 | [Default](screenshots/phase3-default-320.png) | [Alternate](screenshots/phase3-checkout-320.png) | [Submit](screenshots/phase3-submit-320.png) |
| 390×844 | [Default](screenshots/phase3-default-390.png) | [Alternate](screenshots/phase3-checkout-390.png) | [Submit](screenshots/phase3-submit-390.png) |
| 768×1024 | [Default](screenshots/phase3-default-768.png) | [Alternate](screenshots/phase3-checkout-768.png) | [Submit](screenshots/phase3-submit-768.png) |
| 1440×900 | [Default](screenshots/phase3-default-1440.png) | [Alternate](screenshots/phase3-checkout-1440.png) | [Submit](screenshots/phase3-submit-1440.png) |

[Computed safe sticky + visible legal text](screenshots/phase3-sticky-safe-390.png) ·
[Native Blocks mobile](screenshots/plugin-blocks-390.png) ·
[Native Blocks desktop](screenshots/plugin-blocks-1440.png) ·
[Unchanged product sheet](screenshots/ux-product-390.png) ·
[Unchanged cart sheet](screenshots/ux-cart-390.png).
PNG dimensions/checksums are recorded in screenshots.json; checkout captures are
full-page except submit/sticky viewport captures. The inherited Blocks matrix
uses its original 960px height, not the exact new checkout viewport dimensions.

## Results and compatibility

[Executed report](../../docs/simple-us-checkout-v1.2.0-verification.md) ·
[Implementation, native fallback, upgrade and rollback](../../docs/simple-us-checkout-v1.2.0.md).
Raw test reports/logs live in evidence/. Native Classic/Blocks, guest/logged-in,
shipping/tax/coupon/fees, address validation/preservation, native zero orders,
nonce/duplicate protection, seven-line carts and previous theme/sheet regressions
passed locally. GitHub CI is reported separately on the PR; this package does not
claim a GitHub runner pass.

Unknown/custom address schemas, required order notes, template/renderer overrides,
billing-only shipping and opt-out use native presentation. Actual merchant
addons/rate widgets/autocomplete, paid gateways, production CDN/cache,
Safari/Firefox, physical devices and manual screen readers are not certified.
Native required phone, legal/account/country/validation rules stay in place.
Sticky is conditional; normal native placement is the intentional safe fallback.

Before an explicitly approved upgrade: verify complete files/WooCommerce/HPOS
backup and recovery access, retain reviewed v1.1.0 ZIP, verify SHA-256, then use
normal WordPress plugin replacement. Settings remain; no schema/page/configuration
migration occurs. Checkout UI OFF or Phase 3 opt-out restores native presentation;
full rollback reinstalls v1.1.0 without changing theme/gateways or restoring an
old DB over new orders. Product/cart sheets remain independent of Phase 3.

GO for owner review and isolated non-production acceptance. NO automatic merge,
live upload/activation/deployment, production configuration change or paid order.
No extra hosted staging website is created or required. This repository review
package remains downloadable when Actions/Release asset uploads are unavailable.
