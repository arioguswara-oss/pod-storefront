# UX v0.2.0 final review — PR #5

Review of shipped POD Simple Checkout v1.1.0, source `810e110`, review package
`55aaeba`. This follow-up changes documentation/evidence only. No new features,
checkout implementation, runtime source, ZIP bytes, theme or production changes.

## GitHub CI: BLOCKED (verified external cause)

Run [44004206951](https://github.com/arioguswara-oss/pod-storefront/actions/runs/44004206951)
is **failure**, not PASS. GitHub's annotations on both failed jobs state exactly:

> The job was not started because your account is locked due to a billing issue.

[Package annotation](evidence/ci-44004206951/packages-7.4-annotations.json) ·
[Browser annotation](evidence/ci-44004206951/browser-annotations.json) ·
[Full job records](evidence/ci-44004206951/jobs.json).

| Job | GitHub conclusion | Executed steps | Runner ID |
| --- | --- | --- | --- |
| packages (7.4), 133775422196 | failure | `[]` | 0 |
| browser, 133775422453 | failure | `[]` | 0 |
| packages (8.3), 133775422452 | cancelled | `[]` | 0 |
| packages (8.4), 133775422486 | cancelled | `[]` | 0 |

The browser job-log endpoint returned HTTP 404 `BlobNotFound`; the package
step endpoint returned `[]`. No source checkout, dependency installation, build,
lint or tests ran on GitHub in this attempt. Classification: **GitHub account /
Actions billing restriction before runner allocation**. This run supplies no
source/dependency/workflow failure to reproduce or fix. The Ubuntu 26 migration
annotation is a notice, not the stated job-blocking failure.

The connected GitHub fetch tool rejects check-run annotation URLs. The exact
annotations were retrieved successfully through the public GitHub REST API
(read-only, no credential/account change); evidence records the actual response.
The workflow `checks.yml` was read and reviewed: read-only repository permission,
PHP syntax/build matrix, Node/Playwright loopback fixture, artifacts and fixture
cleanup. No speculative source/workflow edits or blind reruns were made.

Owner action: review GitHub **Settings → Billing and licensing/account status**
for the restriction; contact GitHub Support if the lock is unexpected. This
report does not request a purchase or authorize billing/account changes. Once
GitHub confirms the restriction is resolved, rerun the workflow and inspect actual
steps/results before claiming CI PASS. The docs-only follow-up skips another
unchanged CI attempt; it does not bypass or mark the original failure successful.

## Final UX review

Reviewed native form/cart command paths, dialog/stepper/focus/fallback code, the
four-viewport interaction reports and original screenshot evidence. Visually
inspected the product sheet, desktop drawer and long-name mobile bag. Zet is the
interaction reference specified in Issue #4, not a source of code/assets; no new
competitor-site interaction or live buyer cart operation was performed.

| Scope | Finding |
| --- | --- |
| Product | Mobile bottom sheet, actual size/color chips, quantity ±, native stock/variation validation, Add to Cart and Buy Now; desktop centered dialog and canonical URL fallback. |
| Cart | Bag opens sheet/drawer; native thumbnails/names/variation metadata/line amounts, quantity/remove, subtotal, empty state and sticky Checkout. Seven-line carts scroll to final lines. |
| Identity | White/dark minimal UI, original GraphicTeeStore branding, English controls, WooCommerce-supplied currency/prices (USD in fixture). No WhatsApp or Checkout Chat in runtime code. |
| Native ownership | WooCommerce owns sessions/cart, inventory, discounts, fees, tax/shipping/totals, validation and checkout. No gateway/configuration/card processing changes. |
| Accessibility | Dialog semantics, focus loop/return, Escape/backdrop, scroll lock, focus states, CSS safe-area/reduced-motion support. Manual screen-reader/physical-device certification remains untested. |
| Visual observations | Native variation price is also repeated in the original variation block; 320px fixture wordmark wraps inherited from theme. Both are cosmetic/non-blocking within this review and no theme edits are proposed here. |

Native simple/variable scope for Phases 1–2 is ready for owner review. Custom
Product Blocks/builders or PDP-dependent addon JS may need documented opt-outs;
those are not certified by native-form tests. Browser/device and actual merchant
extension/cache checks remain a non-production acceptance gate.

## Package, compatibility and upgrade

The ZIP at [the frozen review package](../releases/shopping-ux-v0.2.0/README.md)
was rechecked: 36,710 bytes, one `pod-simple-checkout/` directory, 16 files,
CRC valid, 1.1.0 headers/constant/readme stable tag, matching manifest/checksum,
all archived bytes identical to current runtime source. Public GitHub download
and independent rebuild match exactly. All 20 PNG checksums/dimensions match.
No fixtures/build dependencies/credentials are in the ZIP.

SHA-256: `09eae65a28850daacbd8566dfd15df598b06a6730e6971158f41f3ef7c42c6a1`.

A Git comparison against plugin v1.0.0 baseline `76a7326` confirms the entire
POD Store Theme v0.1.0 source, `includes/class-checkout.php` and `assets/checkout.css`
are unchanged. Native Classic/Blocks detection and existing checkout hooks remain;
no live checkout page/options or gateway integrations were touched.

Upgrade risks and controls:

- Existing AJAX ON now enables catalog/bag sheets immediately; review actual
  addons/custom templates before activation. Disable AJAX for canonical navigation
  or use the documented integration opt-out. A third-party checkout omitting or
  rearranging native hooks may need Checkout UI OFF/native presentation.
- Keep legacy cart replacement **OFF**. Sheet Buy Now always preserves the bag;
  explicitly enabled legacy PDP replacement remains its prior behavior outside
  the sheet. Native Store API/addon requests are outside POD's lock; UUID replay
  protection lasts 15 minutes, not forever or across all integrations.
- No cart/order schema migration or native record overwrite is introduced.
  Core cache headers cannot certify actual LiteSpeed/CDN behavior; retain current
  cache settings and verify dynamic responses on the real stack only as authorized.
- Before any owner-approved upgrade, back up files plus the complete WooCommerce/
  HPOS DB, verify recovery access, retain reviewed v1.0.0 ZIP and record settings/
  checkout mode/required extensions. Verify SHA-256, then use WordPress's normal
  plugin replacement flow; no npm/Composer/CLI is needed on the website.
- Rollback: disable AJAX for native navigation or deactivate/replace only this
  plugin with retained v1.0.0. Keep theme/gateways/native records. Do not restore
  an old database over newer orders. Plugin settings are retained.

## Test evidence and current checks

The [executed regression report](shopping-ux-v0.2.0-verification.md) and
[raw reports/logs](../releases/shopping-ux-v0.2.0/evidence/) cover: 6 package tests,
24 theme checks, 12 original plugin API scenarios, 20 duplicate-add concurrency
rounds, 18 Classic/Blocks/browser scenarios, 6 new sheet API cases, four exact
product-sheet viewport cases, 9 cart scenarios, four long-name/seven-line cases,
3 lifecycle groups and rollback. They passed locally on the shipped runtime
source; no GitHub pass is inferred. Exact UX viewports are 320×740, 390×844,
768×1024, 1440×900. Existing checkout/theme regression heights remain separately
reported. The previous native tests created only local zero-value synthetic orders.

This review reran the 6 package tests, PHP syntax on 7.4/8.3/8.4, both JS syntax
checks, shell syntax, deterministic build and archive/source/download/screenshot
integrity checks. Runtime code did not change, so the completed integration suites
were reviewed from their matching frozen evidence rather than repeated/relabeled
as newly executed. WordPress.org update/TLS warnings were previously recorded;
no new plugin fatal/parse/uncaught or captured page errors were found in those flows.
Safari/Firefox, physical keyboard/notch devices, manual screen readers, merchant
addons, production CDN/cache and paid transactions remain untested.

## GO / NO-GO and Phase 3

**GO** for owner review and isolated non-production acceptance testing of this
unchanged RC with synthetic products. Use the existing local disposable fixture;
an already available, owner-authorized staging environment is optional. No new
hosted staging website is created or required. This recommendation authorizes no
installation or configuration change by the assistant.

**NO-GO** for production deployment/upgrade, automatic merge, payment testing or
claiming CI-green readiness. GitHub CI is BLOCKED, real-stack compatibility remains
to be reviewed, and live upload/activation requires separate explicit approval.

[Phase 3 specification](phase-3-technical-plan.md) is finalized for owner review:
Contact/Email → Shipping Address → native Shipping Method → native Order Summary
→ existing Payment Method → one safe sticky native Place Order. It includes field
keys, billing/shipping/account states, supported hooks, Blocks/builder fallback,
US/English/USD rules, required-field/gateway/legal preservation, acceptance and
rollback gates. It is a plan only; no Phase 3 code or production operation occurs.
