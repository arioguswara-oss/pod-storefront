#!/bin/sh
# Run only after setup.sh and e2e.cjs, against this named disposable fixture.
set -eu
cd "$(dirname "$0")/.."
wp() { docker compose -f tests/compose.yml exec -T wordpress php /fixtures/.downloads/wp-cli.phar --allow-root "$@"; }
previous=$(cat tests/results/previous-theme.txt)
wp eval-file /fixtures/snapshot.php > tests/results/before-rollback.json
wp theme activate "$previous"
wp eval-file /fixtures/snapshot.php > tests/results/rollback.json
cmp tests/results/before-rollback.json tests/results/rollback.json
wp theme activate pod-store-theme
wp eval-file /fixtures/snapshot.php > tests/results/reactivated.json
cmp tests/results/before-rollback.json tests/results/reactivated.json
wp plugin deactivate pod-simple-checkout woocommerce
curl -fsS --max-time 20 http://127.0.0.1:8889/ -o tests/results/without-woocommerce.html
if grep -q 'Fatal error\|critical error' tests/results/without-woocommerce.html; then exit 1; fi
wp plugin activate woocommerce pod-simple-checkout
printf '%s\n' 'Rollback data preservation and standalone theme fallback: PASS' > tests/results/rollback-report.txt
