const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const { request } = require('playwright');
const { base, root, wp, settings, checkout } = require('./plugin-helpers.cjs');
function command(args) { return execFileSync('docker', ['compose', '-f', 'tests/compose.yml', 'exec', '-T', 'wordpress', 'php', '/fixtures/.downloads/wp-cli.phar', '--allow-root', ...args], { cwd: root, encoding: 'utf8', timeout: 30000, stdio: ['ignore', 'pipe', 'pipe'] }); }
function snapshot() { return JSON.parse(command(['eval-file', '/fixtures/snapshot.php'])); }
(async () => {
  settings(); checkout('classic');
  const report = [];
  const baseline = JSON.parse(fs.readFileSync(path.join(__dirname, 'results/before.json')));
  const afterFlow = snapshot();
  for (const [id, value] of Object.entries(baseline.products)) {
    if (value.data.sku.startsWith('POD-FIXTURE-')) assert.deepEqual(afterFlow.products[id], value, 'Existing product/variation/URL preserved');
  }
  for (const [id, value] of Object.entries(baseline.orders)) assert.deepEqual(afterFlow.orders[id], value, 'Existing order and items preserved');
  assert.deepEqual(afterFlow.categories, baseline.categories);
  assert.deepEqual(afterFlow.images, baseline.images);
  assert.equal(afterFlow.homepage_content, baseline.homepage_content);
  assert.deepEqual(afterFlow.settings, baseline.settings);
  report.push('Existing catalog, variations, images, URLs, original orders, homepage and recorded settings: PASS');
  const beforeToggle = snapshot();
  command(['plugin', 'deactivate', 'pod-simple-checkout']);
  assert.deepEqual(snapshot(), beforeToggle, 'Deactivation does not migrate/delete/overwrite records');
  const api = await request.newContext({ baseURL: base });
  try {
    const response = await api.get('/product/flow-simple-tee/');
    assert.equal(response.status(), 200); assert.ok(!(await response.text()).includes('class="button pod-sc-buy-now"'));
    command(['plugin', 'activate', 'pod-simple-checkout']);
    assert.deepEqual(snapshot(), beforeToggle, 'Activation does not change existing WooCommerce records');
    const active = await api.get('/product/flow-simple-tee/'); assert.ok((await active.text()).includes('class="button pod-sc-buy-now"'));
    report.push('Deactivate/reactivate without fatal errors or record changes: PASS');
    const activePlugins = wp("echo wp_json_encode(get_option('active_plugins'));");
    const encoded = Buffer.from(activePlugins).toString('base64');
    try {
      wp("update_option('active_plugins', array_values(array_filter(get_option('active_plugins'), static function($p) { return $p !== 'woocommerce/woocommerce.php'; }))); ");
      const standalone = await api.get('/'); assert.equal(standalone.status(), 200); assert.ok(!(await standalone.text()).includes('Fatal error'));
      const notice = wp("wp_set_current_user(get_user_by('login','fixture')->ID); ob_start(); do_action('admin_notices'); $notice=ob_get_clean(); echo strpos($notice,'POD Simple Checkout needs WooCommerce') !== false ? 'present':'missing';");
      assert.equal(notice, 'present');
      report.push('Missing WooCommerce: inert frontend and clear administrator notice: PASS');
    } finally { wp(`update_option('active_plugins', json_decode(base64_decode('${encoded}'),true));`); }
  } finally { await api.dispose(); }
  fs.writeFileSync(path.join(__dirname, 'results/plugin-lifecycle-report.json'), JSON.stringify(report, null, 2));
  console.log(report.join('\n'));
})().catch(error => { console.error(error); process.exitCode = 1; });
