const { execFileSync } = require('node:child_process');
const path = require('node:path');
const base = 'http://127.0.0.1:8889';
const root = path.resolve(__dirname, '..');
function wp(code) {
  const guard = "if (!defined('POD_STOREFRONT_TEST_FIXTURE') || !POD_STOREFRONT_TEST_FIXTURE || wp_parse_url(home_url(), PHP_URL_HOST) !== '127.0.0.1') { throw new RuntimeException('Fixture only'); } ";
  return execFileSync('docker', ['compose', '-f', 'tests/compose.yml', 'exec', '-T', 'wordpress', 'php', '/fixtures/.downloads/wp-cli.phar', '--allow-root', 'eval', guard + code], { cwd: root, encoding: 'utf8', timeout: 30000, stdio: ['ignore', 'pipe', 'pipe'] }).trim();
}
function settings(overrides = {}) {
  const values = { buy_now: 'yes', ajax: 'yes', direct_checkout: 'yes', checkout_ui: 'yes', replace_cart: 'no', ...overrides };
  const encoded = Buffer.from(JSON.stringify(values)).toString('base64');
  wp(`update_option('pod_sc_settings', json_decode(base64_decode('${encoded}'),true));`);
}
function checkout(mode) {
  if (!['classic', 'block'].includes(mode)) throw new Error('Invalid fixture mode');
  wp(`update_option('woocommerce_checkout_page_id', (int)get_option('pod_fixture_${mode}_checkout'));`);
}
function catalog() {
  return JSON.parse(wp("$result=array(); foreach(array('POD-FLOW-0','POD-FLOW-1','POD-FLOW-2','POD-FLOW-3','POD-FLOW-VAR') as $sku) {$p=wc_get_product(wc_get_product_id_by_sku($sku)); $result[$sku]=array('id'=>$p->get_id(),'children'=>array()); foreach($p->get_children() as $id){$v=wc_get_product($id);$result[$sku]['children'][]=array('id'=>$id,'attributes'=>$v->get_attributes(),'stock'=>$v->get_stock_quantity());}} echo wp_json_encode($result);"));
}
module.exports = { base, root, wp, settings, checkout, catalog };
