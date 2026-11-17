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
