# POD Store Theme v0.1.0 — review candidate

Review: https://github.com/arioguswara-oss/pod-storefront/pull/2

- `pod-store-theme-0.1.0.zip`: independently installable WordPress theme with a
  white mobile-first homepage, native WooCommerce catalog/category and
  single-product layouts. No product/order migration or gateway changes.
- `pod-simple-checkout-0.1.0-dev.zip`: independently installable scaffold only.
  Shopping-flow functionality awaits theme review; leave inactive on live until
  its separate review.
- `SHA256SUMS` and `manifest.json`: package integrity, versions and status.

Local verification passed: five packaging tests, PHP 8.3/JavaScript syntax,
actual ZIP installation and activation, 24 browser checks at 320/390/768/1440px,
classic cart and Cart Block behavior, data preservation through activation and
rollback, and standalone fallback. GitHub CI has not run because the account is
locked for a billing issue; see `docs/github-unblock.md`. A passing local result
is not a passing GitHub Actions result.

Read `docs/live-activation.md` for backups, Flatsome/UX Builder compatibility,
site-specific checks and rollback before any authorized live activation.
GraphicTeeStore.com is the sole real-world testing website. This release does
not authorize upload, preview, activation, configuration, cart/order mutations,
payment changes or deployment on the live website.

Source, review and package delivery remain on GitHub. Keep this release a draft
until the owner completes review. Do not merge, publish or deploy automatically.
