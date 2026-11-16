# GraphicTeeStore.com — backup, compatibility and rollback

This is the real-world testing website. Do not create or require another staging
website. Complete offline/package checks first. The disposable local automated
fixture uses synthetic data only; it is not another hosted website.

**No live upload, activation, setting change, cart mutation or order placement is
authorized by the development request alone.** Obtain explicit owner
authorization for the exact live operation before performing it.

## 1. Back up before any authorized live change

1. Record the active theme **and child theme slugs/versions**, menu assignments,
   widgets, homepage/shop/cart/checkout/account page IDs, permalink settings,
   Customizer/CSS settings, cache/CDN settings and visible checkout behavior.
2. Take a complete database backup including WordPress/WooCommerce options,
   product variations, taxonomies and order tables. Include HPOS tables when
   enabled. A product CSV export alone is not a recoverable store backup.
3. Back up files: uploads, current parent/child themes, plugins, wp-config.php and
   server rewrite configuration. Retain the exact current Flatsome package and
   child-theme files. Keep the old theme installed for immediate rollback.
4. Store backups securely outside the publicly served webroot and repository.
   Check archive readability, dump completeness, timestamps and sufficient
   storage. Confirm the hosting restore procedure and access to file manager,
   WordPress admin or WP-CLI if the frontend fails.
5. Record the latest order IDs/time and who will handle new orders during the
   change. Use a quiet window agreed with the owner. A database restore could
   erase orders placed after the backup.

No credentials or customer exports belong in Git, PRs, screenshots or this chat.

## 2. Compatibility gate before activation

- Read actual WordPress, PHP and WooCommerce versions from admin/System Status;
  a public generator tag is not an authoritative inventory. Theme targets
  WordPress 6.5+ and PHP 7.4+; check WooCommerce's own requirements separately.
- Review the verified test matrix in `docs/verification.md` and unresolved items.
  Automated results do not certify every live plugin or PHP version.
- Inventory Flatsome/UX Builder pages, shortcodes, widgets, child-theme hooks,
  custom snippets and theme-specific navigation. Existing page content remains
  saved but dependent shortcodes may no longer render after switching themes.
  Do not activate if critical content has no confirmed replacement or fallback.
- Preserve existing products, category hierarchy, slugs, variation IDs/attributes,
  prices, stock, images/alt text, canonical URLs and order data. No imports,
  sample content, permalink re-saves or migrations are needed for this theme.
- Compare current shop URL, representative category/product URLs, SEO output,
  gallery, variation plugins, reviews, taxes/shipping display, currency, account,
  empty/populated cart and chosen cart/checkout type (classic or Blocks).
- Identify active payment, shipping, security, SEO and cache plugins. Gateways
  stay untouched. Confirm their required hooks/assets remain available.
- Reuse existing menus, footer policy links and logo. New theme locations may
  require manually assigning the current menus **only after authorization**.
- Verify ZIP checksum against SHA256SUMS. Plugin ZIP is still a scaffold; leave
  it inactive on live until its separate review.

## 3. Authorized preview and activation

1. Obtain authorization to upload the exact theme version and use WordPress
   theme preview. Previewing can save changesets; it is a live action too.
2. Upload only `pod-store-theme-0.1.0.zip` via Appearance → Themes → Add New.
   Preview homepage, categories and representative simple/variable products.
3. Confirm the owner accepts the new dedicated storefront homepage and the
   loss/replacement of any Flatsome-specific presentation. Do not overwrite the
   saved homepage or install demo content.
4. Activate only with explicit owner authorization. Record time, version,
   operator and previous active theme. Reassign menus/logo only as authorized.
5. Purge page/CDN caches only as authorized; exclude cart, checkout, account and
   WooCommerce AJAX/Store API routes from full-page caching according to the
   existing store's configuration.

## 4. Non-destructive live smoke test

Start with GET-only browsing at 320/390/768/1440px: homepage, category links,
sorting/pagination, product links/images, gallery, variation options and
displayed prices/stock. Check keyboard navigation, errors and horizontal
overflow. Confirm pre-existing public URLs still resolve.

Adding/updating/removing a cart item changes the live visitor session and may
trigger tracking or third-party hooks. Obtain separate explicit authorization
for that scope; use a clean guest session and agreed products, remove test
items, and do not proceed to order/payment submission. No live stock/order or
gateway changes are part of theme verification.

Compare URL samples and product/order totals using existing read-only admin
reports only if authorized. Do not share customer records. Stop if checkout,
catalog, site content or critical integrations regress.

## 5. Rollback immediately if needed

1. Activate the exact previously recorded theme/child theme through Appearance
   → Themes. If admin is unavailable, use the hosting file manager or existing
   WP-CLI access; for example, `wp theme activate <previous-theme-slug>`.
   Use the recorded slug, not an assumed Flatsome parent slug.
2. Restore prior menu/widget assignments only if they changed. Clear caches as
   authorized and recheck homepage, product/category URLs, variations and cart.
3. The theme performs no data migration, so switching back is the primary
   rollback. Do **not** restore the database as the routine first step.
4. Restore damaged theme files from backup only if switching back is
   insufficient. A full database restore is a last resort requiring explicit
   authorization and a plan to preserve/reconcile all newer orders, payments,
   refunds and stock changes.
5. Record the failure and keep the POD ZIP inactive for a follow-up fix. Do not
   modify gateway configurations as a rollback workaround.
