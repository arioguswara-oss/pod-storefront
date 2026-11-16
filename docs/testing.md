# Automated testing

## Fast checks

Python 3.9+, PHP and Node are enough for packaging and syntax checks:

```sh
python3 -m unittest discover -s tests -p 'test_*.py' -v
find themes plugins tests -name '*.php' -print0 | xargs -0 -n1 php -l
node --check themes/pod-store-theme/assets/js/navigation.js
node --check plugins/pod-simple-checkout/assets/product.js
python3 scripts/build.py
git diff --check
```

The package suite checks version/header agreement, archive roots and consistency,
required templates, deterministic builds, unsafe symlinks and a guard against
store-write APIs in theme source. It is supplemented by runtime data comparison,
not a claim of complete static security analysis.

## Disposable runtime and browser checks

Requires Docker Compose, Python 3.9+, curl, Node 22+ and a Chromium browser. This
loopback-only runtime has synthetic products/images, sizes, prices, categories,
saved homepage content and a synthetic pending order. No production database,
credentials, customer data, gateway configuration or hosted staging site is used.

```sh
npm ci --prefix tests
cd tests && npx playwright install chromium && cd ..
POD_ALLOW_TEST_FIXTURE=1 sh tests/setup.sh
sh tests/run-checks.sh
```

If Chromium is already installed, use:
`POD_CHROMIUM_PATH=/usr/bin/chromium sh tests/run-checks.sh`.

The fixture downloads WP-CLI 2.12.0 and WooCommerce 11.3.0 over HTTPS, uses the
official WordPress PHP 8.3 Alpine image and MariaDB 11, and binds only
`127.0.0.1:8889`. Local test passwords in Compose are disposable fixture values,
not production credentials. PHP fixture/snapshot scripts reject non-fixture or
non-loopback installations. Docker image tags may update; version records are
captured in `tests/results/` each run.

Setup builds and installs the actual ZIPs, then compares before/after JSON for
products, variations, prices/stock, category and product URLs, attachment URLs
and metadata, order/items, saved homepage content and relevant settings.

Browser checks cover 320/390/768/1440px, homepage/category/product layouts,
category links, sorting, search, available/unavailable variation choices and
prices, reset, classic cart add/quantity/persist/remove and header counts, plus
native catalog AJAX and WooCommerce Cart Block behavior. The plugin suite also
checks Buy Now, idempotency, settings, Classic/Blocks compatibility, shipping,
tax/coupon recalculation, native validation and guest/logged-in checkout with
synthetic zero-value orders. No paid transaction, gateway processing or live
checkout submission occurs. Browser results and desktop/mobile screenshots are
saved under `tests/results/`.

After the browser suite, `sh tests/rollback.sh` verifies theme rollback and
reactivation without record changes, then checks the standalone theme fallback
with WooCommerce inactive. It is restricted to the named disposable fixture.
The plugin lifecycle suite separately checks original-record preservation and
activation/deactivation after legitimate synthetic orders. Rollback compares the
state immediately before/after the theme switch, so newly created synthetic
orders are retained. Product-flow fixtures keep the local store publicly open;
no production visibility settings or real gateways are configured.

Clean up only this fixture:

```sh
docker compose -f tests/compose.yml down -v
```

This deletes only the explicitly named test Compose project's disposable
containers and volume. Run it before a fresh setup; do not reuse the fixture
with customer data or point these scripts at GraphicTeeStore.com.

## CI

`.github/workflows/checks.yml` runs PHP lint on 7.4/8.3/8.4 and Python package
checks, then runs the synthetic browser suite in a separate job. Artifacts
include both versioned ZIPs, checksums, manifest and browser results. Workflow
has read-only repository permissions and no live deployment steps.

See `verification.md` for observed results; unrun CI jobs must not be represented
as passed. Live preview/activation and site-specific plugin checks follow
`live-activation.md` after explicit authorization.
