# POD Simple Checkout v1.2.0 — Phase 3 owner review

Separate branch `codex/simple-us-checkout-v1.2.0`, from reviewed UX v0.2.0 /
plugin v1.1.0 at `db05fc1`. PR #5 stays unchanged. The implementation follows
`phase-3-technical-plan.md` through native APIs/hooks, without copied templates,
Zet code/assets/branding, a separate checkout engine or gateway changes.

## Buyer journey and native ownership

Compatible Classic checkout has one form and this DOM order: Contact (one native
`billing_email`) → native Shipping Address → editable alternate Billing Address /
required contact fields → native Shipping Method → Your Order → existing native
Payment Method/legal area → one native Place Order with native total. Order notes
remain available through a compact disclosure when optional. Required comments
or unknown order fields trigger compatibility mode.

WooCommerce owns country/localization rules, requiredness, address validation,
stock, sessions, coupons, shipping packages/rates, fees, taxes, totals, guest/login/
account rules, checkout nonce, order creation and payment submission. Configured
currency is displayed natively (USD in the fixture); no currency/country setting
is changed and saved non-US selections are not overwritten. Only supported US
presentation priorities change; country locale validation and labels stay native.
Billing/shipping phone fields remain even when optional, to preserve existing
operational integrations. No WhatsApp, Checkout Chat or promotional widget.

Native form renderers and field hooks are reused. Only core's shipping renderer
moves ahead of core billing; other top-level hooks stay registered. Email remains
in the billing schema and only its duplicate native billing render is suppressed.
The template's shipping rows render once, are captured at native shipping hooks,
and become a separate `woocommerce_update_order_review_fragments` entry. The
review table keeps the native shipping amount and all other native totals/hooks.
No shipping radio IDs are cloned; native package/rate extension output is retained.
Payment HTML/fragments/iframes are neither copied, replaced nor reparented.

## Address adapter and conservative fallback

Same-address defaults ON only for blank/matching native addresses. Saved differing
addresses stay separate. Only declared native name/company/address fields mirror
shipping to billing; email, phone, account, consent and gateway values are not
copied. Alternate billing remains editable. The original shipping flag still means
an explicit shipping address. Without JS both native addresses stay editable,
same-address stays OFF, and the ordinary native checkout remains available.

Core validates its checkout/update-order-review nonce before the adapter runs;
an extra form nonce and compatibility checks gate the adapter. Native AJAX
billing/shipping address payloads are aligned server-side before core customer/
shipping/tax calculations. Final posted-data mapping precedes native validation,
reusing native field-processing filters. A stale/incompatible form fails visibly
and asks for reload rather than dropping fields or guessing an address.

Unknown address/order schema keys, required order notes, changed core renderer
hooks, template overrides, billing-only shipping rules or an explicit opt-out
keep the prior native presentation. Blocks retain native checkout and scoped
v1.1 styling; unknown builders remain native. `Checkout UI` OFF disables all
checkout presentation enhancements while retaining product/cart sheets.

Integration opt-out (installation code only after owner review):
`add_filter('pod_sc_us_checkout_supported', '__return_false');`

Automatic checks cannot certify arbitrary addons using core field names/custom
DOM or rate widgets requiring the old table location. Those integrations need
an explicit opt-out or separately verified adapter. No claim of universal addon,
address-autocomplete, gateway or cache compatibility is made.

## Submit, accessibility and error handling

The original `#place_order` remains in the original payment form/container with
all native legal/nonce hooks. There is exactly one button; custom JS does not
submit it or read payment/card values. Native WooCommerce handles processing and
repeat clicks. The total beside it is server-rendered by WooCommerce.

Sticky positioning is conditional: focused fields, small/zoomed/keyboard viewports,
iframes, errors, processing or overlap with visible payment/legal content use
normal flow. It is deliberately not a floating substitute for the native payment
step. Errors remain native and receive focus; alternate billing becomes visible
after errors. CSS safe-area/reduced-motion support is included. Real device virtual
keyboards/manual screen readers are not certified by simulated Chromium tests.

## Build, testing and delivery

```
python3 scripts/build.py
POD_ALLOW_TEST_FIXTURE=1 sh tests/setup.sh
POD_CHROMIUM_PATH=/usr/bin/chromium sh tests/run-checks.sh
```

This creates only disposable loopback WordPress with synthetic data, not an extra
hosted staging site. Tests may create local zero-value orders; no production
orders, real transactions or gateway calls are permitted. The payment-display
fixture is explicitly synthetic and refuses payment processing; it is not shipped.
See `simple-us-checkout-v1.2.0-verification.md` for actual results and limitations.

The release candidate ZIP, manifest, SHA-256, screenshots and executed evidence
are under `releases/plugin-v1.2.0/`, following GitHub-only review distribution.
The ZIP has one `pod-simple-checkout/` root and production/documentation files;
no fixtures, Composer/Node dependencies or CLI requirements on the website.
GitHub CI status is reported separately from local execution. The prior run was
blocked by a GitHub account billing restriction; no CI PASS is assumed.

## Owner-approved upgrade and rollback

Before any live operation: approve the exact RC, back up files and complete
WooCommerce/HPOS database, verify recovery access, retain reviewed v1.1.0 ZIP,
record versions/settings/checkout mode/extensions, shipping/tax/legal rules and
cache exclusions. Verify SHA-256, then use WordPress's normal plugin upload /
replacement flow only after authorization. Settings remain; no activation
migration/page conversion/configuration change occurs. Keep cart replacement OFF.

First acceptance uses read-only layout comparison; obtain explicit authorization
for live guest cart/address operations. Verify actual addon/gateway rendering,
required fields, saved addresses, shipping/tax/coupon/fee updates and dynamic
cache behavior without changing settings or processing payments under this task.

Presentation rollback: Checkout UI OFF or Phase 3 compatibility opt-out. Full
rollback: deactivate/replace only this plugin with retained v1.1.0. Product/cart
sheets, reviewed theme, native records and gateway settings remain. Do not restore
an old DB over newer orders. No extra hosted staging is required.

This is an owner-review candidate, not production deployment authorization.
NO-GO for live installation/activation, automatic merge or paid checkout until
owner approval and actual-stack acceptance. CI blocking and untested real-device /
merchant integrations are explicit limits, not hidden release claims.
