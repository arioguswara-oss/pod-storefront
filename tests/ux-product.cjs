const assert = require('node:assert/strict');
const fs = require('node:fs');
const { chromium } = require('playwright');
const { base, settings, checkout, catalog } = require('./plugin-helpers.cjs');
const sizes = [[320,740],[390,844],[768,1024],[1440,900]];
(async()=>{
  settings(); checkout('classic'); const products = catalog();
  const browser = await chromium.launch({headless:true,...(process.env.POD_CHROMIUM_PATH ? {executablePath:process.env.POD_CHROMIUM_PATH}:{})});
  const results=[];
  try { for (const [width,height] of sizes) {
    const context=await browser.newContext({viewport:{width,height},reducedMotion:'reduce'});const page=await context.newPage(); const errors=[]; page.on('pageerror',e=>errors.push(e.message));
    try {
      await page.goto(base+'/'); await page.locator('.pod-sc-choose').first().click();
      await page.locator('#pod-sc-sheet form.cart').waitFor();
      assert.equal(await page.evaluate(()=>innerWidth),width); assert.equal(await page.evaluate(()=>innerHeight),height);
      assert.equal(await page.evaluate(()=>document.documentElement.style.overflow),'hidden');
      await page.keyboard.press('Escape'); assert.equal(await page.locator('#pod-sc-sheet').evaluate(d=>d.open),false);
      assert.equal(await page.locator('.pod-sc-choose').first().evaluate(a=>a===document.activeElement),true);
      await page.goto(base+'/shop/?orderby=date'); const original=page.url();
      const choose=page.locator(`[data-pod-sc-product="${products['POD-FLOW-VAR'].id}"]`); await choose.click();
      const dialog=page.locator('#pod-sc-sheet'); await dialog.locator('form.cart').waitFor();
      assert.equal(page.url(),original);
      assert.equal(await dialog.getByRole('button',{name:'Add to cart',exact:true}).isDisabled(),true);
      assert.equal(await dialog.locator('.pod-sc-chips button[data-value="l"]').isDisabled(),true, 'WooCommerce disables unavailable size');
      await dialog.locator('.pod-sc-chips button[data-value="m"]').click();
      await dialog.locator('.pod-sc-chips button[data-value="Black"]').click();
      await page.waitForFunction(()=>Number(document.querySelector('#pod-sc-sheet .variation_id')?.value)>0);
      await dialog.locator('.pod-sc-step[aria-label="Increase quantity"]').click();
      assert.equal(await dialog.locator('input.qty').inputValue(),'2');
      const box=await dialog.boundingBox(); assert.ok(box.x>=0 && box.x+box.width<=width+1 && box.y>=0 && box.y+box.height<=height+1);
      assert.equal(await dialog.evaluate(d=>d.scrollWidth>d.clientWidth+1),false);
      await page.screenshot({path:`tests/results/ux-product-${width}.png`});
      const [response]=await Promise.all([page.waitForResponse(r=>r.url().includes('wc-ajax=pod_sc_add')),dialog.locator('.single_add_to_cart_button').click()]);
      const payload=await response.json();assert.equal(payload.success,true);assert.equal(payload.count,2);
      await page.waitForFunction(()=>document.querySelector('#pod-sc-sheet form.cart').getAttribute('aria-busy')==='false');
      assert.equal((await page.locator('.pod-cart-count').innerText()).trim(),'2');
      await page.keyboard.press('Escape'); await page.reload(); assert.equal((await page.locator('.pod-cart-count').innerText()).trim(),'2');
      assert.deepEqual(errors,[]);results.push({viewport:`${width}x${height}`,status:'passed'});console.log('PASS product sheet',width,height);
    } finally {await context.close();}
  }} finally {await browser.close();fs.writeFileSync('tests/results/ux-product.json',JSON.stringify(results,null,2));}
})().catch(e=>{console.error(e);process.exit(1)});
