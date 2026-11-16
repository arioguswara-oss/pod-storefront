# POD Simple Checkout 1.0.0

Development branch: `codex/pod-simple-checkout-v1.0.0`, starting at the existing
`codex/pod-store-theme-v0.1.0` branch. Theme files and old review ZIPs are retained.
The owner reports POD Store Theme active on GraphicTeeStore.com. Read-only MCP
inspection confirmed checkout page 9 contains `[woocommerce_checkout]`.

## Download and install

The review package is distributed through this repository under
`releases/plugin-v1.0.0/pod-simple-checkout-v1.0.0.zip`, with SHA256SUMS and a
version manifest. Use the actual component ZIP, not GitHub's whole-repository
source ZIP. GitHub Actions/Release uploads are not required to download it.

WordPress 7.0+, PHP 7.4+ and active WooCommerce 11.3+ are required. Runtime checks
use WordPress 7.1.4 / WooCommerce 11.3.0 / PHP 8.3. No Composer, npm or CLI is
needed on the target website.

Before any live operation, back up the database including HPOS/order tables and
files; retain the previous plugin ZIP, record checkout/extensions/settings and
verify restore access. Obtain explicit authorization to upload/replace/activate.
Upload only the plugin ZIP through Plugins → Add New → Upload Plugin. Use normal
WordPress confirmation if replacing the existing scaffold. No theme reinstall,
new page, gateway change, Redis enablement or cache reconfiguration is needed.

## Behavior and safe defaults

- Buy Now, AJAX product forms, direct checkout for Buy Now and UI enhancements
  default on. Cart replacement defaults **off**.
- Buy Now submits the selected simple/variable product and quantity through
  native validation and redirects only after successful addition. Existing bag
  items remain unless the owner opts into replacement.
- Normal Add to Cart and the cart remain available. WooCommerce's existing
  redirect-after-add preference is preserved. File-upload and opted-out product
  forms use native POST; uncertain AJAX failures are not blindly resubmitted.
- Nonces/session tokens, a per-session in-flight lock and a 15-minute request
  replay record prevent accidental repeated POD additions. Product-option,
  quantity and stock errors remain visible. WooCommerce owns actual cart lines.

Configure at **WooCommerce → POD Simple Checkout**. Saving requires
`manage_woocommerce` plus the Settings API nonce. Unknown keys are discarded.
Activation itself makes no configuration migration or checkout page edit.

## Checkout modes

**Classic** (the current live mode): existing hooks/template/fields remain.
Optional layout wrappers place contact/address fields beside the native order
review on desktop and stack them on mobile. Item names gain thumbnails. Native
variation data, quantity, subtotal, coupons, shipping, tax, total, payment area,
privacy/terms and Place Order remain WooCommerce's responsibility. No fields
are removed, renamed, reordered or made optional.

**Blocks**: native Checkout Blocks retain their form, data store, summary,
shipping, validation and Place Order. Only scoped CSS is applied; no Classic
wrappers/field overrides are inserted. Buy Now uses the configured checkout URL
in both modes. Product Add to Cart Blocks/custom product types retain their
native controls rather than being silently reimplemented.

**Unknown/custom builders**: UI enhancements are automatically disabled. An
administrator sees the detected mode; the configured native checkout remains
the destination. Disable Checkout UI for third-party Classic templates that
omit or reorder standard wrapping hooks. Integration filters are documented in
the packaged COMPATIBILITY.txt.

## Cache and compatibility limits

Cart/checkout/account and POD AJAX responses send no-cache headers and the
documented LiteSpeed no-cache response action. Product pages can remain cached;
AJAX obtains fresh nonce/session context separately. No LiteSpeed/Redis/CDN
options are written. Real upstream caches, autocomplete vendors, gateway plugins
and custom cart extensions require an owner-approved integration check.

Replacement validates a candidate cart in memory through native WooCommerce
hooks, restores the original bag on failure, and persists only the accepted
result. Replays are scoped to the same session/request, expire after 15
minutes and do not coordinate unrelated extensions or distinct tabs. Inspect the
bag after an uncertain result before reloading. Cached no-JavaScript form nonces
may expire and fail safely; native variable selection has WooCommerce's own
no-JavaScript limits.

No raw card data, gateway requests/configuration or custom order engine is used.
No real paid order or live modification is performed by development tests.

## Acceptance and rollback

Inspect layouts/options first at 320/390/768/1440px. Live cart mutations require
separate explicit authorization; do not submit live orders or payments under
this task. Use agreed guest sessions and test products, clear those cart items,
and inspect server errors/cache behavior. See the verification report for what
was actually executed locally and which CI/integration checks remain unverified.

Rollback by deactivating POD Simple Checkout only. Native product forms/cart/
checkout remain, with the same data and theme. Plugin settings are retained for
reactivation; temporary request records expire. Restore the previous plugin ZIP
only if necessary. Do not restore a database over newer orders as routine rollback.

The package includes INSTALLATION.txt, COMPATIBILITY.txt, SECURITY.txt,
CHANGELOG.txt and readme.txt for administrators who do not use the repository.
