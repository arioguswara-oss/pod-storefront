# Issue #9 — vccp native Pay Now label patch (v1.2.2)

Baseline: reviewed v1.2.1 branch `codex/pay-now-label-v1.2.1`, commit
`29d45b09c4eb01ba3e21c53b68e1883307d19bba`. This separate patch does not
update PR #8, the frozen theme or any live website.

## Reproduced cause and minimal change

The owner supplied the actual merchant IDs `vccp-gateway-paypal` (PayPal) and
`vccp-gateway-banxa` (Credit/Debit Card / Banxa). Neither was in v1.2.1's
presentation allowlist. Safe local native-gateway fixtures using these exact
IDs reproduce both methods showing Place order with empty native custom text.
The baseline runtime evidence records `podSCPayNow.gateways: []`, checkout.js
HTTP 200, and the script executing in the supported Classic layout.

The runtime change adds only those two IDs to the existing PHP allowlist.
The six previous IDs, the `pod_sc_pay_now_gateway_ids` integration filter and
all exclusion guards remain. checkout.js and its enqueue/localization code
are byte-identical to v1.2.1: a missing script was not reproduced locally,
so no speculative enqueue or JavaScript repair was made. This is local runtime
verification, not a claim that the live store's scripts/cache were inspected.

Only generic plain native fallback text becomes Pay Now. `order_button_text`,
`has_custom_place_order_button`, dynamically changed non-generic labels and
structured gateway button markup retain ownership. The one native button keeps
its ID, name, type, value and data-value. Unknown/offline methods, free carts,
Checkout UI OFF, unsupported native fallback, no-JS and Blocks remain native.
No endpoint, submit interception, new button, SDK, card field, gateway settings,
checkout validation, order pipeline or schema migration is introduced.

## Executable verification and evidence

The existing local WordPress/WooCommerce fixture installs the actual built ZIP.
Run `POD_ALLOW_TEST_FIXTURE=1 sh tests/setup.sh`, then `sh tests/run-checks.sh`
(with `POD_CHROMIUM_PATH` if needed). It is disposable loopback-only synthetic
WordPress, not an additional hosted staging website.

The label suite runs against both legacy PayPal/card fixtures and the two exact
vccp IDs. It checks method switching and repeated selection, AJAX order-review
replacement, page reload, one button/nonce and unchanged native attributes;
custom/SDK/dynamic/structured labels, owner allowlist opt-out, invalid address /
terms / nonce with no order, error/sticky fallback, free/native/no-JS/UI-off/Blocks.
Runtime evidence also checks the global configuration, both eligible IDs,
inline configuration before checkout.js, HTTP 200 and script execution at
320×740, 390×844, 768×1024 and 1440×900.

Full regression additionally covers the unchanged theme, simple/variable
products, stock/variation errors, duplicate and concurrent requests, preserved
cart lines, product/cart sheets, long names/seven items, scrolling/sticky footer,
Classic/Blocks, US address/shipping/tax/coupon validation, synthetic free orders,
plugin lifecycle, record preservation and rollback. Inherited theme/browser
checks also use 960px heights; focused checkout/sheet cases use exact viewports.

Final executed counts, raw reports, screenshots, package verification and
checksums are in `releases/plugin-v1.2.2/`. Local results and GitHub CI are
reported separately on the PR; no GitHub PASS is inferred from local success.
Safe gateway fixtures refuse payment processing and have no external SDK/card
calls. Real vccp SDK/provider transactions, physical devices, Safari/Firefox and
the live extension/cache stack are not certified. Core WordPress update/TLS
warnings are recorded separately from plugin/fatal errors.

## Upgrade, acceptance and rollback

GO for owner review and isolated acceptance when the recorded local gates pass.
CI failure/blockage remains explicitly visible on the PR. No additional hosted
staging website is required. Live upload/activation/cart smoke tests require
separate explicit owner authorization; this task grants none.

1. Retain v1.2.1 ZIP and verify complete files/database/HPOS backups and restore
   access. Record current versions, checkout type and both actual methods.
2. Verify v1.2.2 SHA-256 and single `pod-simple-checkout/` ZIP directory. Use the
   actual plugin ZIP, not GitHub's source archive. No CLI/Composer/npm on the site.
3. After separate approval, replace only this plugin via WordPress Admin >
   Plugins > Add New > Upload Plugin. No settings changes or migration occur.
4. In separately authorized acceptance, verify both generic native labels show
   Pay Now across switching/AJAX/reload, custom labels/SDK buttons remain owned
   by the provider, and address errors/terms/totals/payment rendering are native.
   Do not perform a real payment without a separate authorized payment test.
5. Roll back by reinstalling retained v1.2.1. Deactivating this plugin restores
   native presentation; Checkout UI OFF is another existing fallback. Do not
   restore an old database over new orders or change the theme/gateways/cache.

The remaining compatibility risk is provider-specific dynamic SDK behavior in
the actual merchant stack, beyond these safe native-contract fixtures. The
allowlist filter can narrow/disable eligible IDs without altering processing.
