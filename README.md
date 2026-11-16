# POD Storefront

A lightweight WordPress / WooCommerce project for **GraphicTeeStore.com**.

- **POD Store Theme 0.1.0**: review candidate, white and mobile-first; homepage, category/catalog and single-product pages.
- **POD Simple Checkout 0.1.0-dev**: independently installable scaffold only. Shopping-flow development starts after the theme review. No payment gateway changes.

## Packages

```sh
python3 scripts/build.py
# Or: python3 scripts/build.py --component theme
```

Outputs: `dist/pod-store-theme-0.1.0.zip`, `dist/pod-simple-checkout-0.1.0-dev.zip`,
`manifest.json` and `SHA256SUMS`. Each ZIP contains one component folder at its
root and can be uploaded separately through the WordPress dashboard. Plugin
scaffold is not the final shopping-flow release.

Release metadata lives in `releases.json`; versions must match WordPress headers.
Builds require Python 3.9+ and use only the standard library. Theme runtime has no
build dependencies, external fonts or trackers. The theme adds one stylesheet and
a small navigation script, while retaining WooCommerce's scripts and styles.

## Theme

The homepage reads the existing published catalog. Hero copy is editable through
**Appearance → Customize → POD Homepage**; branding uses the existing site name
and optional Custom Logo. Assign existing menus to **Main navigation** and
**Footer navigation**; otherwise navigation falls back to catalog categories.

Categories, product details, variations, gallery, cart and checkout use native
WooCommerce templates and hooks. No WooCommerce templates are copied. Product,
variation, category, image, order and permalink records are not migrated or
rewritten. Existing saved homepage content remains in the database; this theme
renders its own storefront homepage while active. Other Flatsome/UX Builder
pages need a compatibility review before changing themes.

## Checks

```sh
python3 -m unittest discover -s tests -p 'test_*.py' -v
find themes plugins tests -name '*.php' -print0 | xargs -0 -n1 php -l
node --check themes/pod-store-theme/assets/js/navigation.js
git diff --check
```

For automated browser checks, see [testing](docs/testing.md). The test harness
creates a disposable loopback-only WordPress runtime with synthetic data, installs
the actual ZIPs, and checks data preservation and shopping behavior. It does not
create a staging website or use customer data.

GitHub Actions runs packaging tests, PHP lint for 7.4/8.3/8.4 and the browser suite.
It uploads ZIPs and test results as artifacts. There is no deploy job.

## Live testing policy

**GraphicTeeStore.com is the real-world testing website. No additional staging
website is required.** Public read-only inspection may run first. Uploading,
activating, configuring or testing any live write requires explicit owner
authorization. Never place live test orders or change gateways in this phase.

Before live activation, follow [backup, compatibility and rollback](docs/live-activation.md).
See [verification results](docs/verification.md), [architecture](docs/architecture.md)
and [Codex setup](docs/codex-cloud.md).

## Layout

```text
themes/pod-store-theme/       Independent theme
plugins/pod-simple-checkout/  Independent plugin scaffold
scripts/build.py             Deterministic versioned ZIP builder
tests/                       Unit tests and isolated browser fixture
.github/workflows/checks.yml Automated checks; no deployment
docs/                        Review and activation instructions
```

Submit changes through branches and pull requests. No automatic merge,
activation or deployment.
