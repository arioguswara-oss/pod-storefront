#!/bin/sh
# Requires the synthetic loopback fixture created by tests/setup.sh.
set -eu
cd "$(dirname "$0")/.."
wp() { docker compose -f tests/compose.yml exec -T wordpress php /fixtures/.downloads/wp-cli.phar --allow-root "$@"; }
wp eval 'if (!defined("POD_STOREFRONT_TEST_FIXTURE") || !POD_STOREFRONT_TEST_FIXTURE || wp_parse_url(home_url(), PHP_URL_HOST) !== "127.0.0.1") { throw new RuntimeException("Fixture only"); }'
python3 -m unittest discover -s tests -p 'test_*.py' -v
node --check plugins/pod-simple-checkout/assets/product.js
node --check plugins/pod-simple-checkout/assets/shopping.js
docker compose -f tests/compose.yml exec -T wordpress sh -c 'find wp-content/plugins/pod-simple-checkout -name "*.php" -print0 | xargs -0 -n1 php -l'
wp plugin deactivate pod-simple-checkout
node tests/e2e.cjs
wp plugin activate pod-simple-checkout
node tests/plugin-api.cjs
node tests/plugin-concurrency.cjs
node tests/plugin-browser.cjs
node tests/ux-api.cjs
node tests/ux-product.cjs
node tests/ux-cart.cjs
node tests/ux-edge.cjs
node tests/plugin-lifecycle.cjs
sh tests/rollback.sh
git diff --check
