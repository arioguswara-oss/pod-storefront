# GraphicTeeStore shopping UX v0.2.0 — Issue #4, Phases 1–2

Review checkpoint on `codex/zet-style-shopping-ux-v0.2.0`, starting at plugin
branch commit `76a7326`. The installable plugin becomes **POD Simple Checkout
v1.1.0** (a backwards-compatible feature release over 1.0.0). POD Store Theme
remains **0.1.0**, byte-for-byte unchanged. There is no separate required UX
plugin or second cart engine.

## Implemented

- Homepage and native catalog/category buttons: **Choose Options** opens a
  mobile bottom sheet / centered desktop dialog with canonical product fallback.
- Native WooCommerce simple/variable forms, original product thumbnail/title,
  server-rendered prices, chips generated from actual size/color attributes,
  native variation matching/availability and quantity limits, +/- controls.
- Native AJAX Add to Cart and Buy Now reuse the reviewed plugin command path,
  nonce/session checks, pre-session lock, request UUID and native fragments.
  Successful Add to Cart opens the bag. Sheet Buy Now preserves all existing
  items even if the older replacement option is on, then follows the configured
  direct-checkout flag. Standard Add to Cart remains available.
- Header bag: mobile bottom sheet / desktop right drawer; images, names,
  variation metadata, quantities, native line amounts, removal, subtotal and
  sticky Checkout. Native discounts/shipping/taxes/fees/total remain in an
  expandable estimate panel. Editing shipping/coupons stays on full cart/checkout.
- Absolute quantity/remove commands through `WC_Cart` with stock checks,
  `woocommerce_update_cart_validation`, fragment updates and bounded replay
  protection. Replaying an old accepted command does not overwrite later edits.
- Empty/loading/error/retry states; rapid clicks serialized; uncertain retries
  retain the command ID. Pending writes block dialog dismissal/navigation.
- Close, Escape, backdrop, focus loop/return, scroll locking, safe-area padding,
  reduced motion and responsive bounds. Native dialog/fetch/AJAX unavailable:
  canonical links continue working.
- Canonical Classic Cart keeps its native form, coupon/shipping/totals hooks
  with compact thumbnail cards. Cart Blocks keep their native responsive cards.

## Scope and compatibility

The interaction pattern follows the issue's benchmark specification and linked
QA findings: directly visible choices and cart review without mandatory page
navigation. All code, layouts and fixture assets are original; no Zet assets or
code are copied. The local fixture uses GraphicTeeStore branding with synthetic
products. Screenshots are evidence of this build, not a claim about live UI.

Classic Checkout is the owner-reported live mode. This checkpoint preserves the
1.0.0 Classic and Blocks checkout modes. **Phase 3 US address/contact/billing
restructuring is deferred** until selection and cart receive review. No issue
closure, gateway change, live installation/activation, cache setting change,
order/product migration, database overwrite or production order is included.

WooCommerce manages all product data, prices/currency, sessions, shipping, fees,
taxes, coupons, validation and orders. The fixture uses USD; there is no hardcoded
currency conversion. Existing optional cart replacement is OFF by default. Keep
it OFF: outside the sheet, the earlier explicitly enabled PDP replacement mode
retains its 1.0.0 behavior. The sheet always preserves existing lines.

Native catalog forms/cards are supported. Custom Product Blocks/builders,
composites/bundles/subscriptions and addons needing PDP-only initialization keep
canonical navigation or must opt out with `pod_sc_sheet_product_supported` /
`pod_sc_ajax_supported`. `pod_sc_shopping_sheets_enabled` can disable the whole
surface. Other themes can opt header bag links in with `data-pod-sc-bag`.
The plugin's lock covers POD routes, not unrelated native/Store API/third-party
writes in other tabs. Replay records last 15 minutes. Safari/Firefox, actual
merchant addons, real gateways and production LiteSpeed/CDN need separately
approved compatibility checks; Chromium fixture results do not certify them.

## Build and verification

```
python3 scripts/build.py
POD_ALLOW_TEST_FIXTURE=1 sh tests/setup.sh
POD_CHROMIUM_PATH=/usr/bin/chromium sh tests/run-checks.sh
```

All fixture writes are guarded to loopback WordPress with synthetic data. No
hosted staging website is needed. See `shopping-ux-v0.2.0-verification.md` for
executed results and limitations. GitHub CI status is reported separately from
local runs; a configured workflow is not evidence that a runner executed it.

The actual plugin ZIP, SHA-256, manifest, verification JSON and screenshots live
under `releases/shopping-ux-v0.2.0/`, using the repository's existing review-package
distribution path. Download the **actual .zip via Raw**, not GitHub's source-code
archive. Only `pod-simple-checkout/` is inside the plugin ZIP; fixtures and build
or browser dependencies are excluded.

## Installation and rollback — after explicit owner approval

1. Back up current files and the complete WooCommerce/HPOS database; verify
   restore access and retain the reviewed plugin 1.0.0 ZIP. Record versions,
   cart/checkout page/type, extensions, shipping/tax/coupons and cache exclusions.
2. Verify SHA-256 against `SHA256SUMS`. Review screenshots/results and packaged
   `INSTALLATION.txt`, `COMPATIBILITY.txt`, `SECURITY.txt` first.
3. After approval upload `pod-simple-checkout-v1.1.0.zip` through WordPress Admin
   → Plugins → Add New → Upload Plugin, confirm replacement and activation as
   authorized. No Composer, npm or CLI is needed on the website.
4. Keep cart replacement OFF. AJAX ON enables sheets; direct checkout controls
   Buy Now. Checkout UI remains the pre-existing mode. Test actual buyer/session
   behavior only with separately authorized synthetic cart operations; do not
   submit orders or change gateway/cache settings under this task.
5. UX fallback: disable AJAX to use canonical product/cart links. Full rollback:
   deactivate this plugin, reinstall the prior reviewed 1.0.0 ZIP if needed.
   Theme, native cart and checkout remain. Do not restore an old database over
   newer buyer orders. Settings are retained; request records expire normally.

This PR does not authorize a live upload, activation or deployment. It is a
review candidate, not a claim of paid-checkout or production-cache certification.
