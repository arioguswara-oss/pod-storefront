# Issue #7 — native Pay Now copy, v1.2.1

Small patch on reviewed plugin v1.2.0 (`2d3f5fc`), separate branch
`codex/pay-now-label-v1.2.1`. The existing native US Classic submit button changes
visible generic text to **Pay Now** for eligible online methods. No new button,
HTML/template replacement, form interception or gateway setting change.

## How the label is selected

WooCommerce already handles payment-radio selection and AJAX refresh, restores
its default label, then permits gateway-specific text/custom buttons. The patch
binds after core's ready handler, and changes only `textContent` when the selected
method is allowed, its native `order_button_text` is empty, the native button has no child-element markup, is
visible and its current text still equals its original `data-value` fallback.
No native ID/name/type/value/data-value or checkout/gateway event is changed.
No timer, observer or SDK label-rewriting loop is added.

Presentation IDs: `paypal`, `ppcp-gateway`, `ppcp-credit-card-gateway`, `stripe`,
`stripe_cc`, `woocommerce_payments`. IDs are read from registered gateways, not
inferred from display titles. Any declared `order_button_text` or
`has_custom_place_order_button` excludes that gateway, and runtime native method
metadata/dynamic non-default text also wins. “Continue/Proceed to PayPal” or an
SDK's custom label stays native. Gateway objects and configuration are not edited.

Unknown/offline methods, free orders, native compatibility layout, Checkout UI
OFF, Checkout Blocks and no-JS retain their native labels. A method becoming
available only after an initially free checkout may retain the conservative
native label until a fresh checkout render. This is intentional fallback rather
than guessing provider semantics from its display title.

`pod_sc_pay_now_gateway_ids` can narrow/disable the allowlist, or enroll an
additional ID only after integration review. The actual merchant gateway IDs,
authorization-vs-charge/redirect semantics and SDK labels need owner acceptance
before activation; local fixtures do not certify the real PayPal/card providers.

## Executed local verification

WordPress 7.1.4 / WooCommerce 11.3.0 / PHP 8.3.36 / Chromium 156.0.8078.79,
using disposable loopback synthetic data only. Actual built ZIP installed.

- **7 focused scenarios passed:** PayPal/card switching at 320×740, 390×844,
  768×1024, 1440×900; repeated selection; native AJAX; one button and unchanged
  attributes/nonce; mandatory/dynamic labels, structured button markup and custom SDK flag; native validation
  and invalid nonce with no order; error sticky fallback; offline/unknown,
  no-JS/native/UI-off/Blocks and free-checkout exclusions.
- PayPal and Credit/Debit Card fixtures use native gateway contracts, no card
  inputs or external SDK/network, and refuse payment processing. No valid paid
  checkout is submitted. Their labels/IDs are not proof of live provider setup.
- Full regression: 6 packaging tests, 24 theme checks, 12 original API scenarios,
  20 duplicate-add concurrency rounds, 18 existing checkout/browser scenarios,
  6 sheet API scenarios, four product viewport cases, 9 cart scenarios, four
  long-name/seven-line cases, 16 Phase 3 scenarios, computed sticky/legal/focus
  probe, lifecycle/original-record checks and rollback passed locally.
- After the final structured-button guard, the actual final ZIP was reinstalled;
  all 7 label scenarios, sticky/legal/focus and lifecycle checks passed again.
- All 7 PHP files lint on 7.4.33, 8.3.36, 8.4.27; existing JS syntax checks pass.
  Full runtime is 8.3 only. No regression in captured checked-flow page errors;
  scoped PHP log and package-byte verification are recorded in evidence.

The inherited theme/Blocks regression still uses its original 960px height;
only new/existing exact UX cases are labelled with the exact requested matrix.
Native regression tests may create local **zero-value** synthetic orders; the
label fixtures process no payments. No production cart, order, customer or
configuration is touched.

## Package and release gate

`pod-simple-checkout-v1.2.1.zip`, one `pod-simple-checkout/` root, 18 production/
documentation files. Headers/constant/readme/manifest agree on 1.2.1. CRC,
independent reproducible build, source/installed-file match and SHA-256 are
verified. No fixture, test or Composer/Node dependency is in the ZIP.

SHA-256: `637df4dd8fe60c81bcb632d8604779a86977fe6292c6f3d8e13f5cdb196cd827`.

GitHub-only ZIP/checksum/manifest/screenshots/reports live under
`releases/plugin-v1.2.1/`. Use the actual ZIP's Raw/download link, not GitHub's
source archive. Prior frozen review packages remain unchanged. Native US address,
shipping, tax/coupon, legal, nonce, stock, order processing and sticky behavior
remain v1.2.0; only the existing label's presentation is added.

GitHub CI is reported separately on the PR. A prior account billing lock is not
a substitute for checking the new run, and local passes never imply CI PASS.
Real provider SDKs/paid transactions, merchant addons, production cache/CDN,
Safari/Firefox, physical devices and manual screen readers are not certified.

## Owner-approved upgrade and rollback

Before any separately approved live operation, retain v1.2.0 ZIP and verified full
files/WooCommerce-HPOS DB backup, record gateway IDs/custom labels and extensions.
Verify SHA-256; use WordPress's normal plugin upload/replacement. No store settings,
page conversion, migrations or provider configuration are changed on activation.
No Node/Composer/CLI is required on the WordPress site.

For label opt-out retain an empty/narrowed `pod_sc_pay_now_gateway_ids` allowlist.
Full patch rollback replaces only this plugin with retained v1.2.0. Do not restore
an old database over newer buyer orders or change gateways/theme. Product/cart
sheets and native checkout remain independent of this label copy.

GO for owner review/isolated non-production acceptance. No automatic merge,
GraphicTeeStore installation/activation/deployment or real payment is authorized.
No extra hosted staging website is created or required.
