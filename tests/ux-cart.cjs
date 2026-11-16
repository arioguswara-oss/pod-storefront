const assert=require('node:assert/strict');
const fs=require('node:fs');
const {chromium}=require('playwright');
const {base,settings,checkout,wp}=require('./plugin-helpers.cjs');
const results=[];
(async()=>{
 settings();checkout('classic');
 // Branding belongs only to this disposable fixture, never a production option mutation.
 wp("update_option('blogname','GraphicTeeStore');");
 const browser=await chromium.launch({headless:true,...(process.env.POD_CHROMIUM_PATH?{executablePath:process.env.POD_CHROMIUM_PATH}:{})});
 async function test(name,fn){try{await fn();console.log('PASS',name);results.push({name,status:'passed'})}catch(e){console.error('FAIL',name,e);results.push({name,status:'failed',error:e.stack})}}
 async function fresh(width=390,height=844){const context=await browser.newContext({viewport:{width,height},reducedMotion:'reduce'});const page=await context.newPage();page.setDefaultTimeout(15000);return{context,page}}
 async function add(page){await page.goto(base+'/product/flow-simple-tee/');const [r]=await Promise.all([page.waitForResponse(r=>r.url().includes('wc-ajax=pod_sc_add')),page.locator('.single_add_to_cart_button').click()]);assert.equal((await r.json()).success,true);await page.waitForFunction(()=>document.querySelector('form.cart').getAttribute('aria-busy')==='false')}
 async function bag(page){await page.locator('a.pod-cart-link').click();await page.locator('.pod-sc-bag-items,.pod-sc-empty').waitFor()}
 async function change(page,action){const [r]=await Promise.all([page.waitForResponse(r=>r.url().includes('wc-ajax=pod_sc_bag_change')),action()]);const payload=await r.json();assert.equal(payload.success,true,JSON.stringify(payload));await page.waitForFunction(()=>!document.querySelector('.pod-sc-sheet-body[aria-busy="true"]'));return payload}
 try{
 for(const [width,height] of [[320,740],[390,844],[768,1024],[1440,900]]){
 await test(`cart sheet ${width}x${height}: empty, add, update, persistence, remove, keyboard`,async()=>{const{context,page}=await fresh(width,height);const errors=[];page.on('pageerror',e=>errors.push(e.message));try{
 await page.goto(base+'/');await bag(page);assert.match(await page.locator('.pod-sc-empty').innerText(),/empty/);await page.keyboard.press('Escape');await page.waitForFunction(()=>!document.querySelector('#pod-sc-sheet').open && document.documentElement.style.overflow !== 'hidden');
 await add(page);await bag(page);assert.equal(await page.locator('.pod-sc-bag-item').count(),1);
 await change(page,()=>page.getByRole('button',{name:'Increase quantity',exact:true}).click());assert.equal(await page.locator('.pod-sc-bag-item input.qty').inputValue(),'2');assert.equal((await page.locator('.pod-cart-count').innerText()).trim(),'2');
 assert.match(await page.locator('.pod-sc-line-price').innerText(),/64.00/);
 await page.screenshot({path:`tests/results/ux-cart-${width}.png`});
 assert.equal(await page.locator('#pod-sc-sheet').evaluate(d=>d.scrollWidth>d.clientWidth+1),false);
 const rect=await page.locator('.pod-sc-checkout').boundingBox();assert.ok(rect.x>=0&&rect.x+rect.width<=width+1&&rect.y+rect.height<=height+1,'Checkout visible inside viewport');
 await page.keyboard.press('Escape');await page.waitForFunction(()=>!document.querySelector('#pod-sc-sheet').open && document.documentElement.style.overflow !== 'hidden');assert.notEqual(await page.evaluate(()=>document.documentElement.style.overflow),'hidden');
 await page.reload();assert.equal((await page.locator('.pod-cart-count').innerText()).trim(),'2');await bag(page);assert.equal(await page.locator('.pod-sc-bag-item input.qty').inputValue(),'2');
 // Native dialog wraps focus and returns to the replaced bag fragment's current link.
 await page.locator('.pod-sc-close').focus();await page.keyboard.press('Shift+Tab');assert.equal(await page.evaluate(()=>document.activeElement.closest('dialog')?.id),'pod-sc-sheet');
 await change(page,()=>page.getByRole('button',{name:'Remove Flow Simple Tee',exact:true}).click());assert.match(await page.locator('.pod-sc-empty').innerText(),/empty/);assert.equal((await page.locator('.pod-cart-count').innerText()).trim(),'0');
 await page.keyboard.press('Escape');await page.waitForFunction(()=>!document.querySelector('#pod-sc-sheet').open && document.documentElement.style.overflow !== 'hidden');assert.equal(await page.locator('.pod-cart-link').evaluate(a=>a===document.activeElement),true);assert.deepEqual(errors,[]);
 }finally{await context.close()}})
 }
 await test('rapid gestures and lost response retry change exactly once',async()=>{const{context,page}=await fresh();try{await add(page);await bag(page);let calls=0;page.on('request',r=>{if(r.url().includes('pod_sc_bag_change'))calls++});
 await change(page,()=>page.getByRole('button',{name:'Increase quantity',exact:true}).evaluate(b=>{b.click();b.click();b.click()}));assert.equal(calls,1);assert.equal(await page.locator('dialog input.qty').inputValue(),'2');
 let lose=true;await page.route('**/*wc-ajax=pod_sc_bag_change*',async route=>{if(lose){lose=false;await route.fetch();await route.abort('failed')}else await route.continue()});
 await page.getByRole('button',{name:'Increase quantity',exact:true}).click();await page.getByRole('button',{name:'Retry update',exact:true}).waitFor();
 const response=await change(page,()=>page.getByRole('button',{name:'Retry update',exact:true}).click());assert.equal(response.replayed,true);assert.equal(await page.locator('dialog input.qty').inputValue(),'3');
 }finally{await context.close()}});
 await test('unavailable GET + network retry + disabled AJAX/JS keep canonical URLs',async()=>{const{context,page}=await fresh();try{await page.goto(base+'/shop/?orderby=date');let offline=true;await page.route('**/*wc-ajax=pod_sc_product*',route=>offline?route.abort():route.continue());await page.locator('.pod-sc-choose').first().click();await page.getByRole('button',{name:'Retry',exact:true}).waitFor();assert.ok(await page.locator('dialog a[href*="/product/"]').count());offline=false;await page.getByRole('button',{name:'Retry',exact:true}).click();await page.locator('dialog form.cart').waitFor();await page.keyboard.press('Escape');await page.waitForFunction(()=>!document.querySelector('#pod-sc-sheet').open && document.documentElement.style.overflow !== 'hidden');
 settings({ajax:'no'});await page.reload();assert.equal(await page.locator('.pod-sc-choose').count(),0);settings();
 }finally{await context.close()}
 const c=await browser.newContext({javaScriptEnabled:false});try{const p=await c.newPage();await p.goto(base+'/shop/?orderby=date');await Promise.all([p.waitForURL('**/product/**'),p.locator('.pod-sc-choose').first().click()]);assert.ok(p.url().includes('/product/'))}finally{await c.close()}});
 await test('sheet Buy Now preserves existing buyer items even if legacy replacement option is enabled',async()=>{settings({replace_cart:'yes'});const{context,page}=await fresh();try{await add(page);await page.goto(base+'/shop/?orderby=date');await page.locator('[href*="flow-variable-tee"][data-pod-sc-product]').click();await page.locator('dialog .pod-sc-chips button[data-value="m"]').click();await page.locator('dialog .pod-sc-chips button[data-value="Black"]').click();await Promise.all([page.waitForURL('**/classic-checkout/'),page.locator('dialog .pod-sc-buy-now').click()]);assert.equal(await page.locator('.pod-sc-summary .cart_item').count(),2);await page.goBack();await bag(page);assert.equal(await page.locator('.pod-sc-bag-item').count(),2)}finally{settings();await context.close()}});
 await test('canonical Classic Cart cards preserve native coupons, shipping, totals and update',async()=>{const old=wp("echo get_option('woocommerce_cart_page_id');");wp("update_option('woocommerce_cart_page_id',wc_get_page_id('shop') ? get_page_by_path('classic-cart')->ID : 0);");try{for(const[width,height]of [[320,740],[390,844],[768,1024],[1440,900]]){const{context,page}=await fresh(width,height);try{await add(page);await page.goto(base+'/classic-cart/');await page.locator('.woocommerce-cart-form .cart_item').waitFor();assert.equal(await page.locator('td.product-thumbnail').isVisible(),true);await page.locator('#coupon_code').fill('POD10');await page.getByRole('button',{name:'Apply coupon',exact:true}).click();await page.locator('.cart-discount').waitFor();await page.screenshot({path:`tests/results/ux-canonical-cart-${width}.png`,fullPage:true});assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1),false);assert.match(await page.locator('.cart-discount').innerText(),/3.20/)}finally{await context.close()}}}finally{wp(`update_option('woocommerce_cart_page_id',${Number(old)});`)}});
 }finally{settings();await browser.close();fs.writeFileSync('tests/results/ux-cart.json',JSON.stringify(results,null,2))}
 if(results.some(r=>r.status!=='passed'))process.exitCode=1;
})().catch(e=>{console.error(e);process.exit(1)});
