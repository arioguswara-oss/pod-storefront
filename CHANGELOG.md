# Changelog

## POD Simple Checkout 1.0.0 — review candidate

- Native simple/variable Buy Now, preserving the bag by default, with direct
  checkout scoped to that intent. Optional post-validation cart replacement.
- Progressive AJAX product forms, native fragments/counts, fresh session/nonce
  context, duplicate-request protection and safe POST fallbacks.
- Native Classic Checkout wrappers/thumbnail summary and Checkout Blocks styling
  mode, preserving required fields, terms, totals, payment and order behavior.
- Capability-protected settings, cache response controls, dependency notice,
  independently installable ZIP and documented compatibility/rollback.
- No changes to the existing theme, live store, gateway integrations or orders.

## POD Store Theme 0.1.0 — review candidate

- White mobile-first storefront with a product-led homepage, editable hero copy,
  existing-catalog category navigation and recent product grid.
- Native WooCommerce category/catalog and single-product layouts, variation
  forms, gallery zoom/lightbox/slider and cart behavior.
- Responsive navigation, product search, accessible focus/skip link and bag
  count using WooCommerce fragments.
- Versioned deterministic packages, checksums, PHP lint/packaging/browser checks,
  and backup, compatibility and rollback instructions.
- No data migration, permalink rewrite or payment gateway change.

## POD Simple Checkout 0.1.0-dev

Installable scaffold retained. Shopping-flow implementation deferred until
theme review; it does not change checkout, orders or payment integrations.
