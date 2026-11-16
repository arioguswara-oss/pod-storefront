# Verification — 2026-11-17 (Asia/Jakarta)

POD Store Theme **0.1.0** is a review candidate, not an authorized live
activation. POD Simple Checkout **0.1.0-dev** remains a scaffold awaiting the
theme review. Both ZIPs are independently installable.

## Observed local environment

- WordPress 7.1.4, WooCommerce 11.3.0, PHP 8.3.36 and MariaDB 11.
- Actual built ZIPs installed and activated in a disposable loopback-only runtime.
- Synthetic catalog with simple and variable products, global `pa_sizes`
  attributes matching the live store's observed attribute naming, unavailable
  stock, sale prices, images, categories, saved builder homepage and pending order.
- No production data, authentication, payment configuration or hosted staging site.

## Checks

- Five Python packaging tests: deterministic builds, independent folder roots,
  matching versions, missing-file rejection, symlink/output-path rejection and
  theme write-API guard.
- PHP 8.3 syntax lint for every theme/plugin/test PHP file; JavaScript syntax check;
  archive integrity/readback and `git diff --check`.
- Browser suite at 320, 390, 768 and 1440px: homepage/category/product layouts,
  card widths, category navigation, sorting, product search, image loading,
  available/unavailable size selection, variation price and reset.
- Classic cart add, quantity update, persistence and removal with header counts;
  native catalog AJAX plus WooCommerce Cart Block quantity/persistence/removal.
- Before/after activation and rollback snapshots preserve products/variations,
  prices/stock, category/product URLs, attachment URLs/metadata, order/items,
  saved homepage content and recorded settings.
- Standalone theme homepage returns successfully with WooCommerce inactive.

Results, version records and six mobile/desktop screenshots are generated in
`tests/results/` (ignored by Git). CI publishes these as artifacts. The runtime
setup emitted WordPress.org update-connectivity warnings and a WooCommerce
early-translation notice before theme activation; no dependency or core code was
modified to suppress these. These setup diagnostics do not establish a theme
runtime failure.

PHP 7.4/8.4 lint jobs are configured in GitHub Actions; their status must be read
from the PR checks. Full runtime compatibility with those PHP versions and every
active GraphicTeeStore plugin is not established by the PHP 8.3 fixture.

The [first CI run](https://github.com/arioguswara-oss/pod-storefront/actions/runs/43923170659)
did **not start any runner steps**. GitHub's annotation states: "The job was not
started because your account is locked due to a billing issue." The failure is
an account-level blocker, not a successful CI run or a code-test result. No CI
artifacts were produced. Local results above are separate and passed. The owner
must resolve GitHub billing and rerun the checks; see `github-unblock.md`.

Uploading release assets through the current GitHub CLI connection also returned
HTTP 401 from uploads.github.com, although branch and PR writes work. The empty
draft release was removed; ZIPs were not published or deployed. Review packages
remain built locally with recorded checksums. The owner selected **GitHub-only
delivery** through Releases or Actions Artifacts. No ZIP was transferred to
Google Drive, and no approval for another destination is pending.

## Public live observations

Initial GET-only inspection of homepage, a T-shirts category and one variable
product returned HTTP 200. Public pages loaded Flatsome assets and advertised
WordPress 7.1.4; one product exposed seven variation records for `pa_sizes`.
Public metadata is not a substitute for admin/System Status inventory.

A subsequent homepage GET returned **HTTP 503, Scheduled Maintenance**, with
`Retry-After: 600` at 2026-11-17 00:46:08 WIB. Further inspection was stopped;
this task did not upload, activate or configure anything on the live website.
Live maintenance prevents a current end-to-end public compatibility assessment.

The new theme has not been previewed or activated on GraphicTeeStore.com. Existing
Flatsome/UX Builder pages, theme-specific menus/widgets and third-party variation,
shipping, SEO and gateway plugins still require the owner-authorized site-specific
compatibility gate in `live-activation.md`.
