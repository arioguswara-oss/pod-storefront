=== POD Simple Checkout ===
Requires at least: 7.0
Requires PHP: 7.4
Stable tag: 1.1.0
Requires Plugins: woocommerce
WC requires at least: 11.3
WC tested up to: 11.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Buy Now, progressive AJAX Add to Cart, and a clean native WooCommerce checkout.

== Description ==

Catalog > Choose Options > product sheet > Add to Cart / Buy Now.
Header bag > cart sheet > native checkout. Canonical product/cart pages remain.
UX checkpoint v0.2.0 (Issue #4 Phases 1 and 2) ships as plugin v1.1.0.
Phase 3 US checkout restructuring is deferred to the next review checkpoint.

Supports native simple and variable product forms, required size/color choices,
quantities, stock and purchase validation, WooCommerce extension validation,
mini-cart fragments, header counts and guest/logged-in checkout. The usual Add
to Cart and cart remain available. Existing cart items are kept by default.

WooCommerce owns sessions, cart lines, prices, coupons, shipping, taxes, addresses,
terms, validation, payment area and orders. This plugin does not implement an
e-commerce engine, process card data, add gateways or alter gateway settings.

== Installation ==

Requires WordPress 7.0+, PHP 7.4+ and active WooCommerce 11.3+. Runtime verification
uses WordPress 7.1.4, WooCommerce 11.3.0 and PHP 8.3; other PHP versions are lint
targets, not a claim of runtime verification. No Composer, Node or CLI is needed
on the target WordPress installation.

1. Read INSTALLATION.txt, COMPATIBILITY.txt and SECURITY.txt first.
2. Back up files/database and retain the prior plugin ZIP. Record current cart,
   checkout page/type, shipping/tax display and gateway behavior.
3. Upload pod-simple-checkout-v1.1.0.zip via Plugins > Add New > Upload Plugin.
4. Activate ONLY after the owner explicitly approves the exact live operation.
5. Open WooCommerce > POD Simple Checkout and review the defaults.

GraphicTeeStore.com is the real-world testing website. No additional hosted
staging site is required. Development and synthetic orders use an isolated local
fixture only. This package does not authorize live installation or activation.

== Settings ==

Buy Now: on. AJAX Add to Cart: on. Direct checkout for Buy Now: on.
Checkout UI: on. Cart replacement: OFF.

Disabling direct checkout leaves Buy Now additions on the normal shopping flow;
it does not redirect every shopper. WooCommerce's own cart-after-add preference
is preserved for ordinary additions. Replacement affects Buy Now only and
removes other items only after WooCommerce accepts the addition.

== Checkout compatibility ==

Classic: native [woocommerce_checkout], fields, validation, coupons, addresses,
shipping, payment area, terms, privacy and Place Order remain intact. Optional
wrappers create a mobile-first layout and native item names gain thumbnails.

Checkout Blocks: native Blocks retain their own layout, data store, address
controls, shipping, summaries and validation; only scoped visual styling is
applied. No classic wrappers, field filters or template copies are injected.

Unknown page-builder checkout: UI enhancements are disabled automatically;
Buy Now still uses the configured native checkout URL. The admin page reports
the detected mode. See COMPATIBILITY.txt for supported opt-out filters.

== Cache and failure handling ==

AJAX retrieves fresh nonce/session context from a no-store WooCommerce endpoint;
it does not trust a cached nonce from the product HTML. Native form fallback
verifies the WordPress nonce. A stale no-JavaScript cached form fails visibly
and safely; refresh/bypass the stale product page, or use the normal cart.

Repeated submissions share an identifier, with a short-lived session-scoped
server lock and 15-minute replay record. After an uncertain network failure,
retry from the same page or inspect the bag. Do not blindly reload/resubmit.
See SECURITY.txt for scope and expiry limitations.

Dynamic cart, checkout, account and POD AJAX responses send no-cache headers
and the documented LiteSpeed per-response no-cache action. No LiteSpeed/Redis
options, cache exclusions, scheduled tasks or production settings are changed.

== Rollback ==

Deactivate POD Simple Checkout. Native WooCommerce forms/cart/checkout remain.
No theme switch, product/order restore or gateway change is needed. Settings are
retained for reactivation; no uninstall data deletion or migration is performed.
Never restore a database over new orders as a routine plugin rollback.

== Changelog ==

= 1.1.0 =
Issue #4 Phases 1–2: native product option sheet, cart sheet/drawer, quantity
steppers, safe request replay, compact Classic Cart cards, responsive/accessibility
checks. Sheet purchases preserve existing bag items. Theme and checkout behavior
remain compatible. Phase 3 US checkout restructuring is deferred.

= 1.0.0 =
Functional Buy Now; progressive AJAX product forms; native validation and
fragments; repeated-request protection; opt-in replacement; Classic/Blocks
checkout presentation; capability-protected settings; independent installation.

= 0.1.0-dev =
Initial scaffold only.
