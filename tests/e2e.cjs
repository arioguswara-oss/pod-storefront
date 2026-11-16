/* Only the loopback fixture is allowed. No live site writes, orders or payment tests. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const base = 'http://127.0.0.1:8889';
const results = path.join(__dirname, 'results');
fs.mkdirSync(results, { recursive: true });
fs.rmSync(path.join(results, 'browser-report.json'), { force: true });
(async () => {
  const browser = await chromium.launch({ headless: true, ...(process.env.POD_CHROMIUM_PATH ? { executablePath: process.env.POD_CHROMIUM_PATH } : {}) });
  const errors = [];
  const checks = [];
  try {
    for (const width of [320, 390, 768, 1440]) {
      const context = await browser.newContext({ viewport: { width, height: 960 }, reducedMotion: 'reduce' });
      const page = await context.newPage();
      page.on('pageerror', error => errors.push(error.message));
      for (const [name, url] of [ ['home', '/'], ['category', '/product-category/clothing/tshirts/'], ['product', '/product/everyday-studio-tee/'] ]) {
        const response = await page.goto(base + url);
        assert.equal(response.status(), 200, `${name} HTTP status at ${width}`);
        await page.locator('main h1').first().waitFor();
        assert.equal(await page.locator('main').count(), 1, 'Exactly one main landmark');
        await page.evaluate(() => document.fonts.ready);
        const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1);
        assert.equal(horizontalOverflow, false, `${name} overflow at ${width}px`);
        const broken = await page.locator('main img').evaluateAll(images => images.filter(img => img.complete && img.naturalWidth === 0).length);
        assert.equal(broken, 0, `${name} broken product images`);
        const undersizedCards = await page.locator('ul.products').evaluateAll(grids => grids.flatMap(grid => {
          const expected = Number.parseFloat(getComputedStyle(grid).gridTemplateColumns.split(' ')[0]);
          return [...grid.children].filter(card => card.matches('li.product') && Math.abs(card.getBoundingClientRect().width - expected) > 2).map(card => card.className);
        }));
        assert.deepEqual(undersizedCards, [], `${name} product cards fill their grid columns at ${width}px`);
        if (name === 'home') {
          const visibleProducts = await page.locator('.pod-arrivals .product').count();
          assert.ok(visibleProducts > 0 && visibleProducts <= 8, 'Up to eight visible products; out-of-stock candidates may be excluded');
          assert.equal(await page.getByText('Flow Unavailable Tee', { exact: true }).count(), 0);
          assert.equal(await page.getByText('Hidden Test Product', { exact: true }).count(), 0);
          assert.equal(await page.getByText('Out of Stock Test Product', { exact: true }).count(), 0);
          assert.ok(await page.locator('.pod-category-nav a').count() >= 3);
          const columns = await page.locator('.pod-arrivals ul.products').evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length);
          assert.equal(columns, width >= 960 ? 4 : 2, 'Responsive product grid');
          await page.locator('.pod-category-nav a').filter({ hasText: 'T-shirts' }).click();
          assert.ok(page.url().includes('/product-category/clothing/tshirts/'));
          if (width < 960) {
            await page.locator('.pod-mobile-menu summary').click();
            assert.equal(await page.locator('.pod-mobile-menu').getAttribute('open'), '');
            await page.keyboard.press('Escape');
            assert.equal(await page.locator('.pod-mobile-menu').getAttribute('open'), null);
          }
          await page.goto(base);
        }
        if (name === 'category') {
          await page.locator('select.orderby').selectOption('price-desc');
          await page.waitForURL(/orderby=price-desc/);
          assert.ok(await page.locator('ul.products .product').count() > 0);
        }
        if (name === 'product') {
          await page.locator('form.variations_form').waitFor();
          await page.locator('select[name="attribute_pa_sizes"]').selectOption('m');
          await page.waitForFunction(() => Number(document.querySelector('input.variation_id').value) > 0);
          assert.ok((await page.locator('.single_variation .price').innerText()).includes('34.00'));
          const unavailable = page.locator('select[name="attribute_pa_sizes"] option[value="l"]');
          assert.ok(await unavailable.count() === 0 || await unavailable.isDisabled(), 'Unavailable size cannot be selected');
          assert.equal(await page.locator('.single_add_to_cart_button').isEnabled(), true);
          await page.locator('.reset_variations').click();
          await page.waitForFunction(() => !Number(document.querySelector('input.variation_id').value));
          assert.equal(await page.locator('.single_add_to_cart_button').getAttribute('class').then(value => value.includes('disabled')), true);
          await page.locator('.single_variation').waitFor({ state: 'hidden' });
        }
        if (width === 390 || width === 1440) await page.screenshot({ path: path.join(results, `${name}-${width}.png`), fullPage: true });
        checks.push(`${name} ${width}px: PASS`);
      }
      await page.goto(base + '/product/everyday-studio-tee/');
      await page.locator('select[name="attribute_pa_sizes"]').selectOption('m');
      await page.waitForFunction(() => Number(document.querySelector('input.variation_id').value) > 0);
      await page.locator('input.qty').fill('2');
      await Promise.all([page.waitForNavigation(), page.locator('.single_add_to_cart_button').click()]);
      await page.goto(base + '/classic-cart/');
      await page.locator('.woocommerce-cart-form').waitFor();
      assert.ok((await page.locator('.woocommerce-cart-form').innerText()).includes('Everyday Studio Tee'));
      assert.ok((await page.locator('.woocommerce-cart-form').innerText()).includes('M'));
      assert.equal(await page.locator('.cart_item input.qty').inputValue(), '2');
      assert.equal((await page.locator('.pod-cart-count').innerText()).trim(), '2');
      await page.locator('.cart_item input.qty').fill('1');
      await page.locator('.cart_item input.qty').dispatchEvent('change');
      await page.locator('button[name="update_cart"]').click();
      await page.waitForFunction(() => document.querySelector('.cart_item input.qty')?.value === '1' && !document.querySelector('.blockUI'));
      await page.reload();
      assert.equal((await page.locator('.pod-cart-count').innerText()).trim(), '1');
      await page.locator('.cart_item .remove').click();
      await page.locator('.cart-empty').waitFor();
      await page.reload();
      assert.equal((await page.locator('.pod-cart-count').innerText()).trim(), '0');
      checks.push(`Variable product: add/quantity/persist/remove/header count ${width}px: PASS`);
      await page.locator('.pod-search summary').click();
      await page.locator('#pod-search-input').fill('Studio');
      await Promise.all([page.waitForNavigation(), page.locator('.pod-search button[type="submit"]').click()]);
      assert.ok(page.url().includes('post_type=product'));
      assert.ok(await page.locator('ul.products .product').count() > 0);
      checks.push(`Product search ${width}px: PASS`);
      // Native catalog AJAX and the default WooCommerce Cart Block, no orders.
      await page.goto(base);
      const catalogButton = page.locator('.pod-arrivals .ajax_add_to_cart').first();
      const catalogProductName = await catalogButton.locator('xpath=ancestor::li[contains(@class,"product")]').locator('.woocommerce-loop-product__title').innerText();
      await catalogButton.click();
      await page.waitForFunction(() => document.querySelector('.pod-cart-count').textContent.trim() === '1');
      await page.goto(base + '/cart/');
      await page.locator('.wc-block-cart-item__remove-link').first().waitFor();
      await page.locator('.wc-block-cart-items__row').waitFor();
      assert.ok((await page.locator('.wc-block-cart-items__row').innerText()).includes(catalogProductName));
      const [quantityResponse] = await Promise.all([
        page.waitForResponse(response => response.url().includes('/cart/update-item') && response.request().method() === 'POST'),
        page.locator('.wc-block-cart-items__row .wc-block-components-quantity-selector__button--plus').click()
      ]);
      assert.equal(quantityResponse.ok(), true, 'Store API quantity update succeeds');
      const updatedCart = await quantityResponse.json();
      assert.equal(updatedCart.items[0].quantity, 2);
      await page.waitForFunction(() => document.querySelector('.wc-block-components-quantity-selector__input')?.value === '2');
      // Wait for the Store API update before checking persistence on navigation.
      await page.waitForFunction(() => !document.querySelector('.wc-block-cart-items__row .wc-block-components-quantity-selector__input:disabled'));
      await page.reload();
      await page.locator('.wc-block-cart-item__remove-link').first().waitFor();
      await page.locator('.wc-block-cart-items__row').waitFor();
      assert.equal(await page.locator('.wc-block-components-quantity-selector__input').inputValue(), '2');
      assert.equal((await page.locator('.pod-cart-count').innerText()).trim(), '2');
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false, 'Cart Block responsive layout');
      const [removeResponse] = await Promise.all([
        page.waitForResponse(response => response.url().includes('/wc/store/v1/batch') && response.request().method() === 'POST'),
        page.locator('.wc-block-cart-item__remove-link').click()
      ]);
      assert.equal(removeResponse.ok(), true, 'Store API removal succeeds');
      const removedCart = await removeResponse.json();
      assert.ok(removedCart.responses.some(response => response.status === 200 && response.body.items?.length === 0));
      await page.waitForFunction(() => !document.querySelector('.wc-block-cart-items__row'));
      await page.reload();
      assert.equal((await page.locator('.pod-cart-count').innerText()).trim(), '0');
      checks.push(`Native AJAX add + Cart Block quantity/persist/remove ${width}px: PASS`);
      await context.close();
    }
    assert.deepEqual(errors, [], 'No uncaught browser errors');
    fs.writeFileSync(path.join(results, 'browser-report.json'), JSON.stringify({ checks, errors }, null, 2));
    console.log(checks.join('\n'));
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
