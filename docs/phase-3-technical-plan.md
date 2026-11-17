# Phase 3 proposal: US Buyer Simple Checkout

Planning only. No Phase 3 checkout redesign is implemented in UX v0.2.0 / plugin
v1.1.0. Start a separate development branch after the owner reviews Phases 1–2.
Use the reviewed 1.1.0 source as the baseline, without changing theme or gateways.

1. **Detect and record checkout mode.** Reuse the configured WooCommerce page
   and `POD_SC_Checkout::mode()`. The owner reports Classic Checkout. Capture
   fixture baseline for guest/account rules, required fields, native shipping,
   tax, coupons, fees, terms/privacy and extension hooks. Do not convert the
   live page or change WordPress options automatically.
2. **Classic-first layout.** Use supported checkout hooks, field priorities and
   scoped CSS for Contact (email first), Shipping Address, shipping methods,
   compact order summary and native payment/legal/Place Order. Keep labels and
   validation messages in English. Show native configured currency (USD for the
   intended US store); do not perform frontend price/currency calculations.
3. **Address semantics before visual simplification.** Preserve first/last,
   address 1, optional address 2, city, state, ZIP and country, plus every field
   required by taxes, shipping, gateways, account rules or extensions. Design
   billing-same-as-shipping with an explicit alternate-billing option using the
   native customer/checkout lifecycle. Validate field mapping on the server;
   keep separately editable billing where an extension needs it. Make phone
   optional only when WooCommerce and installed integrations permit it. Never
   bypass `WC_Checkout` validation or assume all US addresses have identical rules.
4. **Native recalculation and payment.** Keep WooCommerce checkout scripts and
   native `update_checkout` / `updated_checkout` / validation / order-review
   hooks. Address/shipping/coupon changes must recalculate through WooCommerce.
   Keep gateway markup, scripts, iframes, terms/privacy, order creation and the
   existing Place Order element intact. Try sticky positioning of its native
   container on mobile; retain normal flow if sticky positioning would obscure
   methods, errors, legal text or the virtual keyboard. No duplicate submit
   button, card interception, gateway configuration or WhatsApp/chat checkout.
5. **Blocks compatibility mode.** Keep Blocks' native checkout and supported
   extension APIs. If identical field ordering or billing behavior cannot be
   implemented through supported APIs, document Classic as the enhanced mode
   and keep Blocks native with scoped styling. No DOM reparenting of React
   checkout controls, hidden-field workaround or silent page replacement.
6. **Local acceptance before packaging.** On loopback synthetic WordPress test
   320x740, 390x844, 768x1024 and 1440x900: guest/logged-in, account-required,
   US and non-US addresses, invalid state/ZIP, separate billing/shipping,
   physical/virtual/mixed cart, unavailable shipping, multiple shipping packages,
   coupons/fees/taxes and totals, terms/privacy, double submit, network failure,
   keyboard/screen reader labels and virtual-keyboard/sticky overlap. Re-run
   product/cart sheets, canonical cart, Classic and Blocks, lifecycle and
   existing-record preservation. Synthetic zero-value orders only; no real
   gateway calls, paid orders or production cart operations.
7. **Review and release checkpoint.** Produce screenshots, exact executed test
   results, compatibility limits, versioned plugin ZIP, manifest/checksum,
   upgrade and rollback instructions, then a separate PR. No auto-merge, live
   deployment, installation or activation. Owner approval remains required for
   each live operation; the Phase 1–2 RC does not authorize Phase 3 rollout.

Open integration questions can be answered from owner-provided or separately
approved read-only evidence: operational phone requirements, required addon
fields, actual gateway layout and shipping/tax rules. They do not justify any
production configuration changes during development. No extra hosted staging
website is required.

## Implementation contract for owner review

This is a specification, not implemented functionality. The interaction reference
is Zet-style direct selection and a quiet purchase path; branding, copy, code and
assets stay original to GraphicTeeStore. No WhatsApp or Checkout Chat.

| Step | English UI and data contract |
| --- | --- |
| 1 Contact | Email Address, using the native `billing_email` ID/name and validation. Show account/login requirements if enabled. |
| 2 Shipping Address | First Name, Last Name, Street Address, optional Apartment/Suite, City, State, ZIP Code, United States. Preserve every required extension field. |
| 3 Shipping Method | Native WooCommerce package/rate controls and unavailable-shipping notices. |
| 4 Order Summary | Native thumbnail/name, selected size/color, quantity, subtotal, discounts/fees/taxes when applicable, shipping and total. |
| 5 Payment Method | Existing WooCommerce gateway selection/markup/scripts/iframes, privacy and terms acceptance. |
| 6 Place Order | Exactly one native submit button and its original ID/name; sticky only where safe. |

Use WooCommerce's configured USD output and native formatters. Do not hardcode a
currency symbol or change the currency setting. United States is the intended
US-flow default only when allowed by existing WooCommerce countries and no saved
customer/session choice exists. Do not silently replace a saved non-US address,
hide supported countries, or change shipping zones/allowed-country settings.

### Address and account states

- Physical cart: native `shipping_first_name`, `shipping_last_name`,
  `shipping_address_1`, `shipping_address_2`, `shipping_city`, `shipping_state`,
  `shipping_postcode`, `shipping_country` remain the destination data. Native
  shipping-required and field validation stay authoritative.
- “Billing address is the same as shipping” defaults on only in the verified
  compatible path. Copy declared address/name values to the corresponding
  `billing_*` fields; never copy/replace contact email, consent, account or
  payment fields. Show editable native billing fields when unchecked or when
  an integration requires them. Do not invent an extra billing address model.
- UI may mirror values for native recalculation, but the server must validate
  the same-address flag and map the declared fields before native final
  validation/customer update/order processing. `ship_to_different_address` must
  retain its real WooCommerce meaning; do not relabel the native shipping flag
  as a billing flag without a tested adapter. Test the native AJAX address
  payload as well as final posted data; displayed totals must agree with final
  totals for both billing-based and shipping-based tax configurations.
- Virtual-only cart: omit shipping UI only when WooCommerce says it is not
  needed. Keep native required billing/tax/payment fields. Mixed and multiple
  shipping-package carts keep their native sections and package selections.
- Preserve the native phone field when required by WooCommerce/extensions;
  optional presentation needs explicit compatibility evidence. Guest checkout,
  login/create-account and saved customer addresses follow existing settings.
- If safe address mapping cannot be proven for an integration, fall back to
  native billing/shipping presentation, explain the compatibility mode, and
  block release of that enhanced mode. Do not drop required fields to match a
  screenshot.

### Supported implementation points and fallbacks

Keep the configured Classic shortcode/page and existing `WC_Checkout` instance.
Use native checkout/customer-details/order-review hooks for section wrappers,
`woocommerce_checkout_fields` priorities for DOM order without deleting required
schema, and `woocommerce_form_field` for any separately rendered native field.
If email is rendered in a Contact section, retain `billing_email` in the native
schema and suppress only its duplicate visual render within the native billing
render context. Render exactly one input with that key; do not globally remove
it or filter out the email field on admin/account/other forms. An addon requiring
the original DOM location must use native presentation compatibility mode.

Use the checkout posted-data/validation extension points for the explicit
same-address adapter and retain WooCommerce's native nonce, error notices,
customer updates and order-processing pipeline. On address changes, let native
checkout JS submit the actual billing/shipping data and trigger the usual
`update_checkout` / `updated_checkout` lifecycle. No custom order/totals API,
frontend price maths or mutation of gateway configuration. Prove that AJAX and
final submit have matching address semantics before enabling this path.

Keep `class-checkout.php`'s existing Classic/Blocks mode detection and presentation
opt-out. Unknown page builders and incompatible addons keep native layout;
Blocks use supported extension APIs/scoped CSS and keep their native controls.
Do not clone gateway/checkout templates or move React nodes/iframes. If supported
hooks cannot meet a proposed field layout, return to owner review with a concrete
compatibility tradeoff before changing that contract.

Sticky submission must remain in the native form/payment container; never clone
`#place_order` or intercept card fields. Reserve sufficient scroll/safe-area space,
show errors/terms above it, and switch to normal flow when viewport/keyboard,
focus or gateway UI would be obscured. Disable repeated submission through the
native WooCommerce lifecycle; do not bypass payment/legal/checkout validation.

### Acceptance and release gates

Before implementation, capture native field schema, hook behavior and fixture
screenshots from reviewed v1.1.0. Use original synthetic products and local
WordPress only; no new hosted staging is required. Build incremental review
checkpoints: contact/address → native recalculation/summary → sticky submit and
compatibility. Keep the Phase 1–2 product/cart code stable and retain a native
presentation fallback throughout.

Acceptance covers all six sections at 320x740, 390x844, 768x1024, 1440x900; guest,
logged-in and account-required; saved/blank addresses; same/separate billing;
US/non-US address fallback; missing/invalid state and ZIP; physical/virtual/mixed
cart; multiple/unavailable shipping; coupon/fees/tax and totals after AJAX/final
validation; default required phone/extension fields; gateway rerender/selection;
unchecked terms; repeated submit/network error; keyboard/focus/error announcement;
long names, zoom, scroll, safe area and virtual keyboard. Re-run 1.1.0 product/cart
and theme regressions, Classic/Blocks, data preservation, lifecycle and rollback.
Native zero-value synthetic orders can test native processing; real gateway
transactions remain excluded and must not be represented as tested.

Deliver a separately versioned ZIP/manifest/SHA-256, screenshots, executed test
results, addon/browser limitations and a separate PR. Back up full files/database
and retain v1.1.0 before any separately approved upgrade. Rollback is presentation
opt-out or plugin rollback to the retained package, without restoring an old DB
over new orders. NO-GO if native totals/validation, required fields, gateway DOM,
legal acceptance, saved address semantics, product/cart preservation or reachable
submission regress. Owner reviews the plan and compatibility evidence before
Phase 3 implementation; this document authorizes no production operation.
