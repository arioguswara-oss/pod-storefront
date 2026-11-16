/* Real native WooCommerce UI, isolated loopback site and synthetic users only. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const { base, wp, settings, checkout } = require('./plugin-helpers.cjs');
const results = [];
const directory = path.join(__dirname, 'results');
const launch = { headless: true, ...(process.env.POD_CHROMIUM_PATH ? { executablePath: process.env.POD_CHROMIUM_PATH } : {}) };
const number = text => Number(text.replace(/[^0-9.]/g, ''));
(async () => {
  const browser = await chromium.launch(launch);
  async function test(name, fn) {
    try { await fn(); results.push({ name, status: 'passed' }); console.log('PASS', name); }
    catch (error) { results.push({ name, status: 'failed', error: error.stack }); console.error('FAIL', name, error.message); }
  }
  async function fresh(options = {}) {
    const context = await browser.newContext({ viewport: { width: 390, height: 960 }, reducedMotion: 'reduce', ...options });
    const page = await context.newPage();
    page.setDefaultTimeout(15000);
    const errors = []; page.on('pageerror', error => errors.push(error.message));
    return { context, page, errors };
  }
  async function overflow(page) { assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, 'No horizontal overflow'); }
  async function add(page, product = 'flow-simple-tee') {
    await page.goto(`${base}/product/${product}/`);
    const [response] = await Promise.all([page.waitForResponse(r => r.url().includes('wc-ajax=pod_sc_add')), page.locator('.single_add_to_cart_button').click()]);
    const json = await response.json(); assert.equal(json.success, true, JSON.stringify(json));
    await page.waitForFunction(() => document.querySelector('form.cart')?.getAttribute('aria-busy') === 'false');
    return json;
  }
  async function buy(page, product = 'flow-free-sample') {
    await page.goto(`${base}/product/${product}/`);
    await Promise.all([page.waitForURL('**/classic-checkout/'), page.locator('.pod-sc-buy-now').click()]);
  }
  async function billing(page) {
    for (const [id, value] of Object.entries({ billing_first_name: 'Synthetic', billing_last_name: 'Buyer', billing_address_1: '123 Fixture Street', billing_city: 'Los Angeles', billing_postcode: '90001', billing_phone: '2025550100', billing_email: 'buyer@example.invalid' })) {
      const field = page.locator('#' + id); if (await field.count() && await field.isVisible()) await field.fill(value);
    }
    if (await page.locator('#billing_country').count()) await page.locator('#billing_country').selectOption('US', { force: true });
    if (await page.locator('#billing_state').count()) await page.locator('#billing_state').selectOption('CA', { force: true });
    await page.locator('#billing_postcode').dispatchEvent('change');
    await page.waitForFunction(() => !document.querySelector('.blockUI'));
  }
  try {
    for (const width of [320, 390, 768, 1440]) {
      settings(); checkout('classic');
      await test(`AJAX → variable Buy Now → native Classic Checkout ${width}px`, async () => {
        const { context, page, errors } = await fresh({ viewport: { width, height: 960 } });
        try {
          let calls = 0; page.on('request', request => { if (request.url().includes('wc-ajax=pod_sc_add')) calls++; });
          const json = await add(page); assert.equal(json.count, 1); assert.ok(page.url().includes('/product/flow-simple-tee/'));
          assert.equal((await page.locator('.pod-cart-count').innerText()).trim(), '1');
          assert.equal(calls, 1, 'One native form command, no duplicate core handler');
          await overflow(page);
          await page.goto(base + '/product/flow-variable-tee/');
          await page.locator('select[name="attribute_pa_sizes"]').selectOption('m');
          await page.locator('select[name="attribute_color"]').selectOption('Black');
          await page.waitForFunction(() => Number(document.querySelector('.variation_id')?.value) > 0);
          await page.locator('input.qty').fill('2');
          await Promise.all([page.waitForURL('**/classic-checkout/'), page.locator('.pod-sc-buy-now').click()]);
          await page.locator('.pod-sc-details #billing_email').waitFor();
          assert.equal(await page.locator('.pod-sc-summary .cart_item').count(), 2, 'Existing bag preserved');
          assert.equal(await page.locator('.pod-sc-item-image img').count(), 2);
          const summary = await page.locator('.pod-sc-summary').innerText();
          assert.match(summary, /Flow Variable Tee/); assert.match(summary, /Black/); assert.match(summary, /M/);
          assert.ok(await page.locator('#payment #place_order').count());
          assert.ok(await page.locator('.woocommerce-privacy-policy-text').count());
          assert.equal(await page.locator('#terms').count(), 1);
          assert.ok(await page.locator('#billing_address_1[required], #billing_address_1[aria-required="true"]').count());
          await billing(page);
          await page.locator('#shipping_method input').first().waitFor();
          await page.waitForFunction(() => !document.querySelector('.blockUI'));
          const original = number(await page.locator('.order-total .amount').innerText());
          const shipping = page.locator('#shipping_method input[type="radio"]');
          assert.equal(await shipping.count(), 2);
          await Promise.all([page.waitForResponse(r => r.url().includes('wc-ajax=update_order_review')), shipping.nth(1).check()]);
          await page.waitForFunction(() => !document.querySelector('.blockUI'));
          const express = number(await page.locator('.order-total .amount').innerText()); assert.ok(express > original, 'WooCommerce recalculates shipping/tax');
          assert.match(await page.locator('tfoot').innerText(), /Fixture Tax/);
          await page.locator('.showcoupon').click();
          await page.locator('#coupon_code').fill('POD10');
          await page.locator('button[name="apply_coupon"]').click();
          await page.locator('.cart-discount.coupon-pod10').waitFor();
          await page.waitForFunction(() => !document.querySelector('.blockUI'));
          assert.ok(number(await page.locator('.order-total .amount').innerText()) < express, 'Native coupon discount');
          await overflow(page);
          assert.deepEqual(errors, []);
          if (width === 390 || width === 1440) await page.screenshot({ path: path.join(directory, `plugin-classic-${width}.png`), fullPage: true });
        } finally { await context.close(); }
      });
    }
    settings(); checkout('classic');
    await test('Empty checkout follows WooCommerce back to the accessible empty cart', async () => {
      const { context, page } = await fresh(); try {
        await page.goto(base + '/classic-checkout/');
        assert.ok(page.url().includes('/cart/'));
        assert.ok(await page.locator('.cart-empty, .wc-block-cart__empty-cart__title').count());
      } finally { await context.close(); }
    });
    await test('Rapid repeated clicks and lost-response retry add exactly once', async () => {
      const { context, page } = await fresh(); try {
        await page.goto(base + '/product/flow-simple-tee/');
        let count = 0;
        await page.route('**/*wc-ajax=pod_sc_add*', async route => {
          count++; await route.fetch(); await route.abort('failed');
        });
        await page.locator('.single_add_to_cart_button').click();
        await page.locator('.pod-sc-messages').filter({ hasText: 'connection was interrupted' }).waitFor();
        assert.equal(count, 1);
        await page.unroute('**/*wc-ajax=pod_sc_add*');
        const [response] = await Promise.all([page.waitForResponse(r => r.url().includes('wc-ajax=pod_sc_add')), page.locator('.single_add_to_cart_button').click()]);
        assert.equal((await response.json()).replayed, true);
        await page.waitForFunction(() => document.querySelector('.pod-cart-count')?.textContent.trim() === '1');
        await page.goto(base + '/product/flow-variable-tee/');
        await page.locator('select[name="attribute_pa_sizes"]').selectOption('m');
        await page.locator('select[name="attribute_color"]').selectOption('White');
        await page.waitForFunction(() => Number(document.querySelector('.variation_id')?.value) > 0);
        await page.waitForFunction(() => !document.querySelector('.single_add_to_cart_button').matches('.disabled, .wc-variation-selection-needed, :disabled'));
        let calls = 0; page.on('request', r => { if (r.url().includes('wc-ajax=pod_sc_add')) calls++; });
        await page.locator('.single_add_to_cart_button').evaluate(button => { button.click(); button.click(); button.click(); });
        await page.waitForFunction(() => document.querySelector('form.cart')?.getAttribute('aria-busy') === 'false');
        assert.equal(calls, 1); assert.equal((await page.locator('.pod-cart-count').innerText()).trim(), '2');
      } finally { await context.close(); }
    });
    await test('AJAX off: standard Add to Cart and Buy Now POST/redirect fallback', async () => {
      settings({ ajax: 'no' }); const { context, page } = await fresh(); try {
        await page.goto(base + '/product/flow-simple-tee/');
        await Promise.all([page.waitForNavigation(), page.locator('.single_add_to_cart_button').click()]);
        assert.equal((await page.locator('.pod-cart-count').innerText()).trim(), '1');
        await page.goto(base + '/product/flow-variable-tee/');
        await page.locator('select[name="attribute_pa_sizes"]').selectOption('m');
        await page.locator('select[name="attribute_color"]').selectOption('Black');
        await page.waitForFunction(() => Number(document.querySelector('.variation_id')?.value) > 0);
        await Promise.all([page.waitForURL('**/classic-checkout/'), page.locator('.pod-sc-buy-now').click()]);
        assert.equal(await page.locator('.pod-sc-summary .cart_item').count(), 2);
      } finally { await context.close(); settings(); }
    });
    await test('JavaScript unavailable: native simple Add to Cart and Buy Now work', async () => {
      const { context, page } = await fresh({ javaScriptEnabled: false }); try {
        await page.goto(base + '/product/flow-simple-tee/');
        await Promise.all([page.waitForNavigation(), page.locator('.single_add_to_cart_button').click()]);
        assert.equal((await page.locator('.pod-cart-count').innerText()).trim(), '1');
        await Promise.all([page.waitForURL('**/classic-checkout/'), page.locator('.pod-sc-buy-now').click()]);
        assert.match(await page.locator('.pod-sc-summary').innerText(), /×\s*2/);
      } finally { await context.close(); }
    });
    for (const loggedIn of [false, true]) {
      await test(`Native zero-value synthetic order and validation (${loggedIn ? 'logged in' : 'guest'})`, async () => {
        settings(); checkout('classic'); const { context, page } = await fresh(); try {
          if (loggedIn) {
            await page.goto(base + '/wp-login.php'); await page.locator('#user_login').fill('fixturebuyer'); await page.locator('#user_pass').fill('fixture-buyer-local');
            await Promise.all([page.waitForNavigation(), page.locator('#wp-submit').click()]);
          }
          await buy(page);
          await page.locator('#place_order').click();
          await page.locator('.woocommerce-NoticeGroup-checkout .woocommerce-error').waitFor();
          assert.match(await page.locator('.woocommerce-NoticeGroup-checkout .woocommerce-error').innerText(), /terms/i);
          assert.ok(await page.locator('#billing_email').count());
          await billing(page); await page.locator('#terms').check();
          await Promise.all([page.waitForURL('**/order-received/**'), page.locator('#place_order').click()]);
          assert.ok(await page.locator('.woocommerce-order').count());
          assert.match(await page.locator('.woocommerce-order').innerText(), /Flow Free Sample/);
        } finally { await context.close(); }
      });
    }
    await test('Guest-disabled setting is honored, no checkout field bypass', async () => {
      wp("update_option('woocommerce_enable_guest_checkout','no'); update_option('woocommerce_enable_signup_and_login_from_checkout','no');");
      const { context, page } = await fresh(); try {
        await buy(page); assert.equal(await page.locator('form.checkout').count(), 0);
        assert.match(await page.locator('main').innerText(), /logged in|log in/i);
      } finally { await context.close(); wp("update_option('woocommerce_enable_guest_checkout','yes');"); }
    });
    for (const width of [320, 390, 768, 1440]) {
      await test(`Native Checkout Block compatibility ${width}px`, async () => {
        checkout('block'); const { context, page, errors } = await fresh({ viewport: { width, height: 960 } }); try {
          await page.goto(base + '/product/flow-simple-tee/');
          await Promise.all([page.waitForURL('**/checkout/'), page.locator('.pod-sc-buy-now').click()]);
          await page.locator('.wc-block-components-checkout-place-order-button').waitFor();
          assert.ok((await page.locator('body').getAttribute('class')).includes('pod-sc-blocks'));
          assert.equal(await page.locator('.pod-sc-details').count(), 0);
          assert.ok(await page.locator('.wc-block-components-order-summary-item').count());
          assert.ok(await page.locator('#email').count());
          assert.ok(await page.locator('.wc-block-checkout__terms').count());
          await overflow(page); assert.deepEqual(errors, []);
          if (width === 390 || width === 1440) await page.screenshot({ path: path.join(directory, `plugin-blocks-${width}.png`), fullPage: true });
        } finally { await context.close(); checkout('classic'); }
      });
    }
    await test('WooCommerce admin settings capability, nonce and safe defaults', async () => {
      const { context, page } = await fresh(); try {
        await page.goto(base + '/wp-login.php'); await page.locator('#user_login').fill('fixture'); await page.locator('#user_pass').fill('fixture-only-local');
        await Promise.all([page.waitForNavigation(), page.locator('#wp-submit').click()]);
        await page.goto(base + '/wp-admin/admin.php?page=pod-simple-checkout');
        assert.equal(await page.locator('input[name^="pod_sc_settings["]').count(), 5);
        assert.equal(await page.locator('#pod-sc-replace_cart').isChecked(), false);
        const response = await context.request.post(base + '/wp-admin/options.php', { form: { option_page: 'pod_sc_settings', action: 'update', _wpnonce: 'invalid', 'pod_sc_settings[replace_cart]': 'yes' } });
        assert.equal(response.status(), 403);
        assert.equal(wp("echo POD_SC_Settings::enabled('replace_cart') ? 'yes':'no';"), 'no');
        await page.locator('#pod-sc-ajax').uncheck();
        await Promise.all([page.waitForNavigation(), page.locator('#submit').click()]);
        assert.equal(wp("echo POD_SC_Settings::enabled('ajax') ? 'yes':'no';"), 'no');
        settings();
      } finally { await context.close(); }
      const customer = await fresh(); try {
        await customer.page.goto(base + '/wp-login.php'); await customer.page.locator('#user_login').fill('fixturebuyer'); await customer.page.locator('#user_pass').fill('fixture-buyer-local');
        await Promise.all([customer.page.waitForNavigation(), customer.page.locator('#wp-submit').click()]);
        const response = await customer.page.goto(base + '/wp-admin/admin.php?page=pod-simple-checkout'); assert.equal(response.status(), 403);
      } finally { await customer.context.close(); }
    });
    await test('Independent theme and presentation-disabled compatibility modes', async () => {
      wp("switch_theme('twentytwentyfive');"); const { context, page } = await fresh(); try {
        await buy(page); assert.equal(await page.locator('.pod-sc-details').count(), 1);
        assert.equal(await page.locator('.pod-header').count(), 0);
        settings({ checkout_ui: 'no' }); await page.reload();
        assert.equal(await page.locator('.pod-sc-details').count(), 0); assert.equal(await page.locator('form.checkout').count(), 1);
      } finally { await context.close(); wp("switch_theme('pod-store-theme');"); settings(); }
    });
    await test('Product opt-out and file uploads use standard WooCommerce fallback', async () => {
      const { context, page } = await fresh(); try {
        await page.goto(base + '/product/flow-simple-tee/?fixture_native=1');
        assert.equal(await page.locator('[name="pod_sc_product_id"]').getAttribute('data-pod-sc-ajax'), 'no');
        await Promise.all([page.waitForURL('**/classic-checkout/'), page.locator('.pod-sc-buy-now').click()]);
        await page.goto(base + '/product/flow-free-sample/');
        await page.locator('form.cart').evaluate(form => { const input = document.createElement('input'); input.type = 'file'; input.name = 'fixture_upload'; form.append(input); });
        await page.locator('[name="fixture_upload"]').setInputFiles({ name: 'fixture.txt', mimeType: 'text/plain', buffer: Buffer.from('Synthetic upload compatibility check') });
        let ajax = 0; page.on('request', r => { if (r.url().includes('wc-ajax=pod_sc_add')) ajax++; });
        await Promise.all([page.waitForURL('**/classic-checkout/'), page.locator('.pod-sc-buy-now').click()]);
        assert.equal(ajax, 0); assert.equal(await page.locator('form.checkout').count(), 1);
      } finally { await context.close(); }
    });
  } finally {
    checkout('classic'); settings(); await browser.close();
    fs.writeFileSync(path.join(directory, 'plugin-browser-report.json'), JSON.stringify(results, null, 2));
  }
  if (results.some(result => result.status === 'failed')) process.exitCode = 1;
})().catch(error => { console.error(error); process.exitCode = 1; });
