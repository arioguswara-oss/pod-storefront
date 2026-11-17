# POD Simple Checkout v1.2.2 — Issue #9 review patch

**[Download actual WordPress-installable ZIP](pod-simple-checkout-v1.2.2.zip?raw=true)**.
Use Raw/download, not GitHub's source archive. One `pod-simple-checkout/` root,
18 files, 46,979 bytes; no development fixture/dependency or site CLI required.

SHA-256: `5e7e81b2aa79a62c01cd7ddb310025e6b954d6042c0b89750adc2f1ee0e2c56c`.
Manifest, SHA256SUMS, verification and raw evidence record the verified package.

The only runtime logic change adds `vccp-gateway-paypal` and `vccp-gateway-banxa`
to the existing generic-label allowlist. Both now show Pay Now when the plain
native generic button qualifies. Custom/SDK/structured/dynamic labels retain
ownership. The single native submit attributes, nonce, validation, checkout and
payment processing remain unchanged. Existing candidates/filter/fallbacks stay.

Baseline reproduction confirms the missing IDs; local Classic runtime confirms
`podSCPayNow` exists, checkout.js HTTP 200, configuration ordering and execution.
checkout.js and its enqueue/localization are unchanged from v1.2.1. This does not
claim a live runtime inspection or provider SDK/payment certification.

## Safe exact-ID fixture screenshots

These test-only gateways refuse payment and have no real SDK/card/network calls.
Offline/unreviewed options are negative controls, not production configuration.

| Exact viewport | vccp PayPal | vccp Credit/Debit Card (Banxa) |
| --- | --- | --- |
| 320×740 | [PayPal](screenshots/vccp-pay-now-vccp-gateway-paypal-320.png) | [Card](screenshots/vccp-pay-now-vccp-gateway-banxa-320.png) |
| 390×844 | [PayPal](screenshots/vccp-pay-now-vccp-gateway-paypal-390.png) | [Card](screenshots/vccp-pay-now-vccp-gateway-banxa-390.png) |
| 768×1024 | [PayPal](screenshots/vccp-pay-now-vccp-gateway-paypal-768.png) | [Card](screenshots/vccp-pay-now-vccp-gateway-banxa-768.png) |
| 1440×900 | [PayPal](screenshots/vccp-pay-now-vccp-gateway-paypal-1440.png) | [Card](screenshots/vccp-pay-now-vccp-gateway-banxa-1440.png) |

See [implementation, executable checks, limitations and upgrade/rollback](../../docs/pay-now-v1.2.2.md)
and evidence/. Local regression and GitHub CI statuses are reported separately
on the PR; no runner PASS is assumed from local results.

Retain reviewed v1.2.1 and a verified complete files/database backup before an
owner-approved upgrade. Verify SHA-256 and use normal WordPress plugin
replacement. No migration or settings changes occur. Rollback replaces only this
plugin with v1.2.1; do not restore an old database over newer orders or change
gateways/theme/cache. Confirm actual merchant SDK-specific behavior separately.

GO for owner review/isolated acceptance after recorded local gates pass.
No merge, live installation/activation/deployment or real transaction is
authorized. No additional hosted staging website is required.
