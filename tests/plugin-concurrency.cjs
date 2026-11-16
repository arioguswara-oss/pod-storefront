/* Reproducible duplicate-submission stress check against local synthetic data. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { randomUUID } = require('node:crypto');
const { request } = require('playwright');
const { base, catalog } = require('./plugin-helpers.cjs');
(async () => {
  const id = catalog()['POD-FLOW-0'].id;
  let passed = 0;
  for (let round = 0; round < 20; round++) {
    const client = await request.newContext({ baseURL: base });
    try {
      const contextResponse = await client.get('/?wc-ajax=pod_sc_context');
      assert.equal(contextResponse.status(), 200);
      const context = await contextResponse.json();
      const form = { pod_sc_product_id: id, 'add-to-cart': id, quantity: '1', pod_sc_intent: 'add', pod_sc_nonce: context.nonce, pod_sc_session_token: context.session_token, pod_sc_request_id: randomUUID() };
      const responses = await Promise.all([client.post('/?wc-ajax=pod_sc_add', { form }), client.post('/?wc-ajax=pod_sc_add', { form })]);
      const codes = responses.map(response => response.status());
      assert.ok(codes.includes(200) && codes.every(code => [200, 409].includes(code)), `Round ${round}: ${codes}`);
      const cartResponse = await client.get('/wp-json/wc/store/v1/cart');
      assert.equal(cartResponse.status(), 200);
      assert.equal((await cartResponse.json()).items_count, 1, `Round ${round}: exact bag count`);
      passed++;
    } finally { await client.dispose(); }
  }
  fs.writeFileSync(path.join(__dirname, 'results/plugin-concurrency-report.json'), JSON.stringify({ passed, parallel_requests_per_round: 2, expected_count: 1 }, null, 2));
  console.log(`${passed} concurrent duplicate rounds passed with exact count 1`);
})().catch(error => { console.error(error); process.exitCode = 1; });
