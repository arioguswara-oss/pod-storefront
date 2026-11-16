/* Real HTTP/session tests against the loopback synthetic fixture only. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { randomUUID } = require('node:crypto');
const { request } = require('playwright');
const { base, wp, settings, checkout, catalog } = require('./plugin-helpers.cjs');
const results = [];
async function test(name, fn) {
  try { await fn(); results.push({ name, status: 'passed' }); console.log('PASS', name); }
  catch (error) { results.push({ name, status: 'failed', error: error.stack }); console.error('FAIL', name, error.message); }
}
(async () => {
  settings(); checkout('classic');
  const products = catalog();
  const simple = products['POD-FLOW-0'].id;
  const variable = products['POD-FLOW-VAR'];
  const good = variable.children.find(v => v.attributes.pa_sizes === 'm' && v.attributes.color === 'Black');
  const unavailable = variable.children.find(v => v.stock === 0);
  async function client() {
    const api = await request.newContext({ baseURL: base });
    const contextResponse = await api.get('/?wc-ajax=pod_sc_context');
    assert.equal(contextResponse.status(), 200);
    assert.match(contextResponse.headers()['cache-control'], /no-store|no-cache/);
    const context = await contextResponse.json();
    const defaults = { pod_sc_product_id: String(simple), 'add-to-cart': String(simple), quantity: '1', pod_sc_intent: 'add', pod_sc_nonce: context.nonce, pod_sc_session_token: context.session_token, pod_sc_request_id: randomUUID() };
    return {
      api, context, defaults,
      async add(changes = {}, expected = 200) {
        const form = { ...defaults, ...changes };
        if (form.pod_sc_intent === 'buy') form.pod_sc_buy_now = form.pod_sc_product_id;
        const response = await api.post('/?wc-ajax=pod_sc_add', { form });
        const json = await response.json(); assert.equal(response.status(), expected, JSON.stringify(json)); return json;
      },
      async cart() { const response = await api.get('/wp-json/wc/store/v1/cart'); assert.equal(response.status(), 200); return response.json(); },
      async close() { await api.dispose(); }
    };
  }
  const variableInput = { pod_sc_product_id: String(variable.id), 'add-to-cart': String(variable.id), variation_id: String(good.id), attribute_pa_sizes: 'm', attribute_color: 'Black' };
  await test('Simple addition, native mini-cart fragments and exact count', async () => {
    const c = await client(); try {
      const added = await c.add(); assert.equal(added.count, 1); assert.equal(added.success, true);
      assert.ok(added.fragments['div.widget_shopping_cart_content'].includes('Flow Simple Tee'));
      assert.ok(added.fragments['a.pod-cart-link'].includes('pod-cart-count'));
      assert.equal((await c.cart()).items_count, 1);
    } finally { await c.close(); }
  });
  await test('Replay and concurrent identical requests add exactly once', async () => {
    const c = await client(); try {
      const responses = await Promise.all([c.api.post('/?wc-ajax=pod_sc_add', { form: c.defaults }), c.api.post('/?wc-ajax=pod_sc_add', { form: c.defaults })]);
      assert.ok(responses.some(r => r.status() === 200));
      assert.ok(responses.every(r => [200, 409].includes(r.status())));
      const replay = await c.add(); assert.equal(replay.replayed, true);
      assert.equal((await c.cart()).items_count, 1);
      await c.add({ quantity: '2' }, 409); assert.equal((await c.cart()).items_count, 1);
    } finally { await c.close(); }
  });
  await test('Invalid nonce/session token/identifier cannot reach the native handler', async () => {
    const c = await client(); try {
      await c.add({ pod_sc_nonce: 'bad' }, 403);
      await c.add({ pod_sc_session_token: 'bad' }, 403);
      await c.add({ pod_sc_request_id: '../bad' }, 400);
      assert.equal((await c.cart()).items_count, 0);
      const response = await c.api.get('/?wc-ajax=pod_sc_add&add-to-cart=' + simple); assert.equal(response.status(), 405);
      assert.equal((await c.cart()).items_count, 0);
    } finally { await c.close(); }
  });
  await test('Variable product requires matching size/color and valid variation', async () => {
    const c = await client(); try {
      await c.add({ pod_sc_product_id: variable.id, 'add-to-cart': variable.id }, 400);
      await c.add({ ...variableInput, attribute_color: '' }, 400);
      await c.add({ ...variableInput, attribute_color: 'White' }, 400);
      assert.equal((await c.cart()).items_count, 0);
      const added = await c.add({ ...variableInput, quantity: '2' }); assert.equal(added.count, 2);
      const item = (await c.cart()).items[0]; assert.equal(item.id, good.id);
      assert.equal(item.quantity, 2); assert.ok(item.variation.some(v => v.value === 'Black'));
    } finally { await c.close(); }
  });
  await test('Unavailable stock, quantity limits and sold-individually products', async () => {
    const c = await client(); try {
      for (const quantity of ['0', '-1', 'not-a-number', '6']) await c.add({ quantity }, 400);
      await c.add({ pod_sc_product_id: products['POD-FLOW-3'].id, 'add-to-cart': products['POD-FLOW-3'].id }, 400);
      await c.add({ ...variableInput, variation_id: unavailable.id, attribute_pa_sizes: 'l' }, 400);
      assert.equal((await c.cart()).items_count, 0);
      const limited = { pod_sc_product_id: products['POD-FLOW-1'].id, 'add-to-cart': products['POD-FLOW-1'].id };
      await c.add({ ...limited, quantity: '2' }, 400);
      await c.add(limited); await c.add({ ...limited, pod_sc_request_id: randomUUID() }, 400);
      assert.equal((await c.cart()).items_count, 1);
    } finally { await c.close(); }
  });
  await test('Extension validation rejects both intents without cart loss', async () => {
    const c = await client(); try {
      for (const intent of ['add', 'buy']) {
        const result = await c.add({ pod_sc_intent: intent, fixture_reject: '1' }, 400);
        assert.match(result.notices, /Fixture extension rejected/);
      }
      assert.equal((await c.cart()).items_count, 0);
    } finally { await c.close(); }
  });
  await test('Buy Now preserves existing cart and only its intent skips cart', async () => {
    const c = await client(); try {
      const normal = await c.add(); assert.equal(normal.redirect, '');
      const buy = await c.add({ ...variableInput, pod_sc_intent: 'buy', pod_sc_request_id: randomUUID() });
      assert.equal(buy.redirect, base + '/classic-checkout/'); assert.equal(buy.count, 2);
      assert.equal((await c.cart()).items.length, 2);
    } finally { await c.close(); }
  });
  await test('Replacement is opt-in, Buy Now only, and invalid input preserves cart', async () => {
    settings({ replace_cart: 'yes' }); const c = await client(); try {
      await c.add();
      await c.add({ ...variableInput, attribute_color: '', pod_sc_intent: 'buy', pod_sc_request_id: randomUUID() }, 400);
      assert.equal((await c.cart()).items_count, 1);
      const replaced = await c.add({ ...variableInput, pod_sc_intent: 'buy', pod_sc_request_id: randomUUID() });
      assert.equal(replaced.count, 1); assert.equal((await c.cart()).items[0].id, good.id);
      await c.add({ pod_sc_request_id: randomUUID() }); assert.equal((await c.cart()).items.length, 2);
    } finally { await c.close(); settings(); }
  });
  await test('Feature flags enforce disabled Buy Now/AJAX/direct-checkout behavior', async () => {
    const c = await client(); try {
      settings({ buy_now: 'no' }); await c.add({ pod_sc_intent: 'buy' }, 400);
      settings({ ajax: 'no' }); await c.add({}, 400);
      settings({ direct_checkout: 'no' }); assert.equal((await c.add({ pod_sc_intent: 'buy' })).redirect, '');
      assert.equal((await c.cart()).items_count, 1);
    } finally { await c.close(); settings(); }
  });
  await test('Replacement can reduce a stock-limit quantity and rolls back failed candidate validation', async () => {
    settings({ replace_cart: 'yes' }); const c = await client(); try {
      await c.add({ quantity: '5' });
      const replaced = await c.add({ pod_sc_intent: 'buy', pod_sc_request_id: randomUUID() });
      assert.equal(replaced.count, 1); assert.equal((await c.cart()).items[0].quantity, 1);
      await c.add({ ...variableInput, pod_sc_intent: 'buy', fixture_reject: '1', pod_sc_request_id: randomUUID() }, 400);
      const cart = await c.cart(); assert.equal(cart.items_count, 1); assert.equal(cart.items[0].id, simple);
    } finally { await c.close(); settings(); }
  });
  await test('WooCommerce native cart redirect preference remains respected', async () => {
    wp("update_option('woocommerce_cart_redirect_after_add','yes');"); const c = await client(); try {
      assert.equal((await c.add()).redirect, base + '/cart/');
    } finally { await c.close(); wp("update_option('woocommerce_cart_redirect_after_add','no');"); }
  });
  await test('Settings sanitization permits known booleans only and preserves safe defaults', async () => {
    const json = JSON.parse(wp("echo wp_json_encode(array('defaults'=>POD_SC_Settings::defaults(),'sanitized'=>POD_SC_Settings::sanitize(array('buy_now'=>'yes','replace_cart'=>'<script>','unknown'=>'yes')),'mode'=>POD_SC_Checkout::mode()));"));
    assert.equal(json.defaults.replace_cart, 'no'); assert.equal(json.sanitized.buy_now, 'yes');
    assert.equal(json.sanitized.replace_cart, 'no'); assert.equal(json.sanitized.unknown, undefined); assert.equal(json.mode, 'classic');
  });
  fs.writeFileSync(path.join(__dirname, 'results/plugin-api-report.json'), JSON.stringify(results, null, 2));
  if (results.some(r => r.status === 'failed')) process.exitCode = 1;
})().catch(error => { console.error(error); process.exitCode = 1; });
