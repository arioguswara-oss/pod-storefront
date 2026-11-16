# POD Simple Checkout 1.0.0 — executed verification

Development branch: `codex/pod-simple-checkout-v1.0.0`, based on the owner's
existing theme branch. Existing theme source and `releases/v0.1.0/` packages are
unchanged. No live deployment, activation, cart mutation or order submission was
performed. The only live checkout inspection was a read-only page lookup:
GraphicTeeStore.com page 9 contains `[woocommerce_checkout]` (Classic).

## Executed locally: PASS

WordPress **7.1.4**, WooCommerce **11.3.0**, PHP **8.3.36**, MariaDB 11 and headless
Chromium, using a loopback-only disposable fixture with four PHP workers.
The actual 13-file plugin ZIP was installed and activated; no target-side build
tools are required. Fixtures use synthetic images/products/users/orders only.

- **6 packaging tests:** version/header agreement, independent archive roots,
  deterministic output, required files, unsafe symlink/output rejection, archive
  path traversal rejection and the existing theme write-API guard.
- **PHP syntax:** all five plugin PHP files passed on PHP **7.4.33**, **8.3.36**
  and **8.4.27**. Product JavaScript passed Node syntax checking.
- **12 HTTP cases:** native simple/variable additions and fragments; matching
  size/color and quantities; missing/mismatched options; out-of-stock and
  sold-individually limits; nonce/session/request checks; native extension
  validation; normal versus Buy Now routing; existing-cart preservation;
  opt-in replacement, including reducing a stock-limit quantity and restoring
  the original cart on validation failure; flags and native cart redirect policy;
  Settings API sanitization and mode detection.
- **20 concurrency rounds:** two simultaneous identical commands per round,
  against independent fresh sessions, each ended with exactly one cart item.
  Results were success/replay or an explicit busy response, never an extra add.
- **18 browser scenarios:** Classic Checkout at 320/390/768/1440px; native Blocks
  at those widths; simple AJAX plus variable Buy Now with existing bag items;
  images/attributes/quantities/native totals; shipping/tax/coupon recalculation;
  empty cart behavior; rapid clicks and lost-response retry; AJAX-off and
  JavaScript-unavailable fallbacks; guest and logged-in zero-value synthetic
  orders through native Place Order; native required-field/terms validation;
  disabled guest checkout; admin capability/nonce/valid settings save;
  independent theme and UI-off modes; opted-out/file-upload native fallback.
- **Lifecycle/data checks:** original fixture products, variations, images,
  category/product URLs, original orders/items, homepage and recorded settings
  stayed unchanged; activation/deactivation changed no WooCommerce records;
  inactive WooCommerce produced an inert frontend and administrator notice.
  Legitimate new synthetic orders were retained during rollback comparisons.
- **24 existing theme browser checks** passed with the plugin temporarily
  inactive. Theme rollback/reactivation and standalone fallback also passed.
- The final ZIP was read back, CRC checked, compared byte-for-byte per entry
  with current plugin source, and checked for a single `pod-simple-checkout/`
  root and absence of fixtures/development dependencies. SHA-256 is recorded
  with the downloadable package.

Cart deduplication uses atomic insertion of private plugin locks before native
WooCommerce session initialization; locks remain until native shutdown saves
finish. This avoids both repeated additions and stale-session overwrites from
competing POD requests. No WooCommerce database table is directly edited.

## Not executed / limitations

- GitHub Actions success is **not claimed** by these local results. Check the
  plugin PR's status. The previous account-level run was blocked before runner
  steps by a billing lock; repository review downloads do not depend on Actions.
  The [fresh plugin run](https://github.com/arioguswara-oss/pod-storefront/actions/runs/43950610485)
  also finished as failure before test steps: its package job returned an empty
  step list. No CI test or artifact is reported as passed. The current failure
  annotation was not retrievable through this connection, so its specific cause
  is not inferred from the older billing error. The follow-up documentation-only
  commit skips CI to avoid another unproductive run.
- No paid transaction, real order, live gateway, card-data handling, webhook or
  production LiteSpeed/CDN/Redis test occurred. No provider was enabled in the
  fixture; the native payment area remained present for paid baskets. Fixture
  previews can show WooCommerce's expected "no available payment methods" notice.
- Full runtime testing used PHP 8.3, not PHP 7.4/8.4. Other PHP versions received
  syntax checks only. Physical iOS/Android, Safari and Firefox were not tested.
- Third-party gateways, autocomplete providers, product addons/custom purchase
  Blocks/builders and cache edges require their own owner-approved compatibility
  review. Native Checkout Blocks use a styling-only mode; unknown builders get
  no automatic UI replacement. Read the packaged COMPATIBILITY.txt.
- Replay protection is session/request scoped for 15 minutes; it is not a
  transaction coordinator for unrelated extensions, distinct concurrent tabs,
  server crashes or reloads that create a new identifier. Check the bag after
  any uncertain result. Native no-JavaScript variation/cached-nonce limits apply.

WordPress.org update-connectivity warnings and WooCommerce's early-translation
notice were observed during CLI fixture setup/native admin initialization. No
dependency/core code or live setting was altered to suppress those diagnostics.
The disposable fixture explicitly opens its synthetic store and seeds native
default method ordering before the baseline; no real provider is configured.

Machine-readable results and four native-layout screenshots accompany the
review package under `releases/plugin-v1.0.0/`. Full local reports remain in
ignored `tests/results/`. Reproduce using `docs/testing.md`.
