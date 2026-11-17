# POD Simple Checkout v1.2.1 — Issue #7 review patch

**[Download actual WordPress-installable ZIP](pod-simple-checkout-v1.2.1.zip?raw=true)**.
Use Raw/download, not GitHub's source archive. One `pod-simple-checkout/` root,
18 files, 46,722 bytes, no fixture/development dependency or CLI needed on WordPress.

SHA-256: `637df4dd8fe60c81bcb632d8604779a86977fe6292c6f3d8e13f5cdb196cd827`.
Manifest, SHA256SUMS, verification and raw evidence record the verified package.
Public download and independent build are checked separately from CI.

Only generic visible native submit copy becomes Pay Now, in the supported US
Classic layout, for eligible IDs without custom text/SDK/structured button markup.
All native button attributes and gateway/checkout processing remain unchanged.
Required Continue/Proceed to PayPal and dynamic gateway labels win. Offline,
unknown, Blocks, native fallback, no-JS and free orders keep native text.

## Safe fixture screenshots

PayPal/card fixtures implement native gateway label contracts, not real SDKs.
They refuse processing and contain no card fields/network calls. Offline and
unreviewed methods appear as negative controls only, not production configuration.

| Exact viewport | PayPal selected | Credit/Debit Card selected |
| --- | --- | --- |
| 320×740 | [PayPal](screenshots/pay-now-paypal-320.png) | [Card](screenshots/pay-now-stripe-320.png) |
| 390×844 | [PayPal](screenshots/pay-now-paypal-390.png) | [Card](screenshots/pay-now-stripe-390.png) |
| 768×1024 | [PayPal](screenshots/pay-now-paypal-768.png) | [Card](screenshots/pay-now-stripe-768.png) |
| 1440×900 | [PayPal](screenshots/pay-now-paypal-1440.png) | [Card](screenshots/pay-now-stripe-1440.png) |

All 7 focused scenarios passed; full theme/cart/checkout/Phase 3 regression and
final installed-ZIP label/sticky/lifecycle checks passed locally. See
[implementation, tests, limitations and upgrade/rollback](../../docs/pay-now-v1.2.1.md)
and raw evidence/. GitHub CI status is separate; no runner PASS is assumed.

Retain v1.2.0 and a verified complete backup before any owner-approved upgrade.
Use normal WordPress plugin replacement after verifying SHA-256. No migration or
settings changes on activation. Rollback replaces this plugin with v1.2.0; do not
restore an old DB over new orders or change gateways/theme. Verify actual merchant
IDs/SDK-specific semantics before activation; narrow/disable the documented
allowlist if needed. Actual merchant providers/paid transactions, Safari/Firefox,
physical devices and production cache/extension stack are not certified.

GO for owner review/non-production acceptance only. No automatic merge, live
installation/activation/deployment or real transaction. No new hosted staging
website required. This GitHub review package works independently of CI artifacts.
