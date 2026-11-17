#!/bin/sh
# Creates only a disposable local fixture, never a staging or production website.
set -eu
if [ "${POD_ALLOW_TEST_FIXTURE:-}" != "1" ]; then
  echo 'Set POD_ALLOW_TEST_FIXTURE=1 to create the loopback-only synthetic fixture.' >&2
  exit 1
fi
cd "$(dirname "$0")/.."
mkdir -p tests/.downloads tests/results
if [ ! -f tests/.downloads/wp-cli.phar ]; then
  curl -fLS --retry 2 https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar -o tests/.downloads/wp-cli.phar
fi
if [ ! -f tests/.downloads/woocommerce.zip ]; then
  curl -fLS --retry 2 https://downloads.wordpress.org/plugin/woocommerce.11.3.0.zip -o tests/.downloads/woocommerce.zip
fi
python3 scripts/build.py
docker compose -f tests/compose.yml up -d --wait --wait-timeout 120
wp() { docker compose -f tests/compose.yml exec -T wordpress php /fixtures/.downloads/wp-cli.phar --allow-root "$@"; }
attempt=0
until wp core version > /dev/null 2>&1; do
  attempt=$((attempt + 1))
  if [ "$attempt" -gt 30 ]; then echo 'Fixture did not become ready.' >&2; exit 1; fi
  sleep 1
done
if wp core is-installed > /dev/null 2>&1; then
  echo 'Fixture already installed. Remove only this fixture with docker compose -f tests/compose.yml down -v, then rerun.' >&2
  exit 1
fi
wp core install --url=http://127.0.0.1:8889 --title='POD Test Fixture' --admin_user=fixture --admin_password=fixture-only-local --admin_email=fixture@example.invalid --skip-email
wp plugin install /fixtures/.downloads/woocommerce.zip --activate
wp rewrite structure '/%postname%/'
wp eval-file /fixtures/fixture.php
wp eval-file /fixtures/plugin-fixture.php
wp option get stylesheet > tests/results/previous-theme.txt
wp eval-file /fixtures/snapshot.php > tests/results/before.json
container=$(docker compose -f tests/compose.yml ps -q wordpress)
docker cp dist/pod-store-theme-0.1.0.zip "$container:/tmp/pod-store-theme.zip"
docker cp dist/$(python3 -c 'import json; print(json.load(open("releases.json"))["plugin"]["archive_name"])') "$container:/tmp/pod-simple-checkout.zip"
wp theme install /tmp/pod-store-theme.zip --activate
wp plugin install /tmp/pod-simple-checkout.zip --activate
docker compose -f tests/compose.yml exec -T wordpress mkdir -p wp-content/mu-plugins
docker cp tests/plugin-fixture-hooks.php "$container:/var/www/html/wp-content/mu-plugins/pod-fixture-hooks.php"
docker cp tests/phase3-fixture-hooks.php "$container:/var/www/html/wp-content/mu-plugins/pod-phase3-fixture-hooks.php"
docker cp tests/pay-now-fixture-hooks.php "$container:/var/www/html/wp-content/mu-plugins/pod-pay-now-fixture-hooks.php"
wp eval-file /fixtures/snapshot.php > tests/results/after.json
cmp tests/results/before.json tests/results/after.json
wp core version > tests/results/wordpress-version.txt
wp plugin get woocommerce --field=version > tests/results/woocommerce-version.txt
wp theme get pod-store-theme --field=version > tests/results/theme-version.txt
docker compose -f tests/compose.yml exec -T wordpress php -v > tests/results/php-version.txt
echo 'ZIP installation, activation and preservation of fixture data: PASS'
