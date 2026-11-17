# Executed verification — Simple US Checkout v1.2.0

Baseline: reviewed plugin v1.1.0 / UX v0.2.0 at `db05fc1`. Phase 3 lives on a
separate branch and PR; PR #5, all theme files and product/cart sheet runtime
files remain unchanged. Tests use loopback synthetic WordPress only.

Runtime: WordPress 7.1.4, WooCommerce 11.3.0, PHP 8.3.36, Chromium
156.0.8078.79, POD Store Theme 0.1.0. PHP 7.4/8.4 are syntax checks only.

| Executed check | Result |
| --- | --- |
| Package tests / deterministic builder | 6 passed |
| Theme catalog/PDP/variation/search/cart regression | 24 checks passed |
| Existing product/cart API validation, settings, extension/security tests | 12 scenarios passed |
| Concurrent duplicate Add to Cart | 20 rounds passed, exact count 1 |
| Existing plugin browser suite, guest/logged-in native zero orders, Classic/Blocks, admin, independent theme, fallbacks | 18 scenarios passed |
| UX v0.2.0 sheet API security/session/isolation/stock/replay | 6 scenarios passed |
| Product sheet | All 4 exact UX viewports passed |
| Cart sheet, native card cart, retries/no-JS/preservation/focus | 9 scenarios passed |
| Long names/seven cart lines/scroll/reachable Checkout | All 4 exact UX viewports passed |
| Phase 3 contracts | 16 scenarios passed on final installed ZIP |
| Progressive sticky activation + legal separation + focus fallback | Dedicated computed-style probe passed |
| Lifecycle/original catalog/images/URLs/orders/recorded settings/dependency fallback | 3 groups passed |
| Theme rollback and standalone native fallback | Passed |
| PHP syntax | All 7 plugin PHP files passed on 7.4.33, 8.3.36, 8.4.27 |
| JavaScript syntax | Product, shopping and checkout scripts passed |
| Whitespace/build/archive integrity | Passed |

The full `tests/run-checks.sh` run passed (see raw log). After final checkout
hardening (native field processors once, non-JS shipping semantics and sticky
visibility), the actual final ZIP was reinstalled and all 16 Phase 3 scenarios,
lifecycle and rollback were repeated. This distinguishes the full regression
run from final targeted checks instead of silently relabelling earlier results.

Exact Phase 3 and UX viewports are **320×740, 390×844, 768×1024, 1440×900**.
The inherited theme/plugin checkout matrix still uses its separately recorded
960px height. Screenshots use original synthetic products and fixture branding;
payment-display examples explicitly use a fixture refusing payment processing,
not a real merchant provider or fake claim of a payment integration.

## Phase 3 acceptance coverage

- One native email input and Place Order, original checkout nonce; section DOM
  order and US address field order; required phone fields retained.
- Default same-address and editable alternate billing; AJAX rate/tax/coupon/total
  updates; server mapping independently of mirrored JS; processor hooks once.
- Invalid email/state/ZIP, required terms and tampered adapter nonce reject
  through native validation without order creation.
- Billing-based NY tax vs shipping-based CA tax; native multiple shipping
  packages, fee totals, physical/virtual/mixed carts and unavailable Canadian
  shipping fallback without overwriting the chosen country.
- Saved differing logged-in addresses retained; addon-required field / native
  mode / UI-off / billing-only settings / Blocks preserve native behavior.
- No-JS addresses editable; unchecked native shipping flag retains its real
  server meaning. Extra JS-only primary-address intent cannot alter that path.
- Synthetic native gateway fields/iframe preserved through WooCommerce's own
  fragment refresh; core retains the reference input. No payment processing.
- Native physical zero-total order has matching billing/shipping and exactly
  one request/order after repeated clicks; native virtual zero order succeeds.
- Keyboard flow, errors/focus, no overflow, viewport bounds, one reachable submit;
  iframe/focus/error/small-height conditions use nonsticky fallback. Simulated
  keyboard-height behavior is not real-device/virtual-keyboard certification.
- Native checkout network failure remains visible and creates no order. No
  custom payment/order engine, raw card logic, cloned submit or frontend totals.

An initial logged-in free-order test exposed a persistent **synthetic fixture**
cart from a prior saved-address test. Cleanup now targets only the known fixture
user's native session/persistent cart. The plugin's preserve-cart behavior was
kept intact. No customer or production cart was emptied.

## Release integrity and limits

`pod-simple-checkout-v1.2.0.zip` has one `pod-simple-checkout/` root and **18 files**.
Manifest/header/constant/readme agree on 1.2.0; CRC, independent rebuild and
archive/source/installed-file byte matching are verified. No fixture, test or
Node/Composer dependency is included; WordPress needs no CLI.

SHA-256: `40d54c71085cc24f051ffe28aa482d8c0620f0a97c70c7ad8ef5bd0e09232962`.

Recorded runtime logs contain no plugin fatal/parse/uncaught errors in tested
flows. WordPress.org update/TLS checks and Woo CLI early-translation notices are
fixture infrastructure observations, not hidden CI passes. Browser cases check
captured page errors; no claim of every third-party console/network response.

GitHub CI is reported separately on the Phase 3 PR. The baseline workflow was
blocked before execution by a GitHub account billing lock, proven in
`docs/evidence/ci-44004206951/`. Do not infer the fresh run's cause or claim CI
PASS until its own run/steps/annotations confirm it. Review-package distribution
is available from GitHub without relying on Actions/Release asset uploads.

Real paid gateways/SDKs, merchant addons/autocomplete, production LiteSpeed/CDN,
Safari/Firefox, physical devices, manual screen readers and live paid orders are
not certified. Custom hooks/rate widgets needing the old DOM should use the
integration opt-out or native layout. No pixel-identical comparison to an owner
screenshot unavailable in this thread is claimed; the implementation follows the
approved interaction/field/compatibility contract with original GraphicTeeStore UI.

**GO for owner review and isolated non-production acceptance. NO-GO for automatic
merge or live upgrade/activation.** Retain v1.1.0 and verified full backup. See
`simple-us-checkout-v1.2.0.md` and packaged instructions for upgrade/rollback;
Checkout UI OFF or the Phase 3 opt-out restores native presentation without
removing product/cart sheets. Never restore an old DB over newer orders.
