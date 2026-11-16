/* Native dialogs and native WooCommerce forms. Canonical URLs remain the fallback. */
(() => {
  'use strict';
  const config = window.podSheets;
  const dialog = document.querySelector('#pod-sc-sheet');
  if (!config || !dialog || !window.fetch || !dialog.showModal || !window.jQuery) return;
  const body = dialog.querySelector('.pod-sc-sheet-body');
  const title = dialog.querySelector('h2');
  const closeButton = dialog.querySelector('.pod-sc-close');
  let opener, controller, previousOverflow, mode, pending = false;
  const open = (kind, trigger) => {
    controller?.abort(); controller = new AbortController();
    mode = kind; opener = trigger;
    dialog.classList.toggle('pod-sc-bag-sheet', kind === 'bag');
    title.textContent = kind === 'bag' ? 'Your bag' : 'Choose your options';
    body.textContent = 'Loading…'; body.setAttribute('aria-busy', 'true');
    if (!dialog.open) {
      previousOverflow = document.documentElement.style.overflow;
      document.documentElement.style.overflow = 'hidden';
      dialog.showModal(); closeButton.focus();
    }
  };
  const close = () => { if (!pending && !body.querySelector('form[aria-busy="true"]')) dialog.close(); };
  closeButton.addEventListener('click', close);
  dialog.addEventListener('keydown', event => {
    if (event.key !== 'Tab') return;
    const controls = [...dialog.querySelectorAll('a[href],button:not(:disabled),input:not(:disabled),select:not(:disabled),textarea:not(:disabled),summary,[tabindex="0"]')].filter(node => node.tabIndex >= 0 && node.getClientRects().length && getComputedStyle(node).visibility !== 'hidden');
    const first = controls[0], last = controls[controls.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
  });
  dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
  dialog.addEventListener('click', event => {
    if (event.target !== dialog) return;
    const r = dialog.getBoundingClientRect();
    if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) close();
  });
  dialog.addEventListener('close', () => {
    controller?.abort(); document.documentElement.style.overflow = previousOverflow || '';
    if (opener?.isConnected) opener.focus();
    else document.querySelector('.pod-cart-link, .pod-sc-choose')?.focus();
  });
  function error(fallback) {
    body.removeAttribute('aria-busy'); body.replaceChildren();
    const text = document.createElement('p'); text.setAttribute('role', 'alert'); text.textContent = 'Unable to load this view. Please retry or use the full page.';
    const retry = document.createElement('button'); retry.type = 'button'; retry.textContent = 'Retry'; retry.addEventListener('click', () => opener?.click());
    const link = document.createElement('a'); link.href = fallback; link.textContent = mode === 'bag' ? 'View full bag' : 'View product';
    body.append(text, retry, link);
  }
  function quantityControls(root) {
    root.querySelectorAll('.quantity').forEach(wrapper => {
      const input = wrapper.querySelector('input.qty');
      if (!input || input.type === 'hidden' || wrapper.querySelector('.pod-sc-step')) return;
      for (const [direction, label] of [[-1, 'Decrease quantity'], [1, 'Increase quantity']]) {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'pod-sc-step'; button.textContent = direction < 0 ? '−' : '+'; button.setAttribute('aria-label', label);
        button.addEventListener('click', () => {
          try { direction < 0 ? input.stepDown() : input.stepUp(); input.dispatchEvent(new Event('change', { bubbles: true })); } catch (_) { input.focus(); }
        });
        direction < 0 ? wrapper.prepend(button) : wrapper.append(button);
      }
    });
  }
  function productForm() {
    const form = body.querySelector('form.cart');
    if (!form) return;
    const surface = document.createElement('input'); surface.type = 'hidden'; surface.name = 'pod_sc_surface'; surface.value = 'sheet'; form.append(surface);
    document.dispatchEvent(new CustomEvent('pod-sc-form-ready', { detail: body }));
    const jq = window.jQuery(form);
    form.querySelectorAll('.variations select').forEach(select => {
      const group = document.createElement('div'); group.className = 'pod-sc-chips'; group.setAttribute('role', 'group');
      group.setAttribute('aria-label', select.closest('tr')?.querySelector('label')?.textContent || select.name);
      const sync = () => group.querySelectorAll('button').forEach(button => {
        const option = [...select.options].find(o => o.value === button.dataset.value);
        button.disabled = !option || option.disabled; button.setAttribute('aria-pressed', String(select.value === button.dataset.value));
      });
      [...select.options].filter(option => option.value).forEach(option => {
        const button = document.createElement('button'); button.type = 'button'; button.dataset.value = option.value; button.textContent = option.textContent;
        button.addEventListener('click', () => { select.value = option.value; window.jQuery(select).trigger('change'); sync(); }); group.append(button);
      });
      select.after(group); select.classList.add('pod-sc-native-select'); select.tabIndex = -1; select.setAttribute('aria-hidden', 'true');
      window.jQuery(select).on('change', sync); jq.on('woocommerce_update_variation_values reset_data', sync); sync();
    });
    if (form.matches('.variations_form')) {
      const add = form.querySelector('.single_add_to_cart_button'); add.disabled = true;
      jq.on('hide_variation reset_data', () => { add.disabled = true; });
      jq.on('show_variation', (_event, variation, purchasable) => { add.disabled = !purchasable; });
      jq.on('found_variation', (_event, variation) => {
        const price = body.querySelector('.pod-sc-sheet-price');
        if (variation.price_html) price.innerHTML = variation.price_html;
        if (variation.image?.src) { const image = body.querySelector('.pod-sc-product-heading img'); if (image) { image.src = variation.image.src; image.removeAttribute('srcset'); } }
      });
      jq.wc_variation_form(); jq.trigger('check_variations');
    }
    quantityControls(form);
  }
  document.addEventListener('click', async event => {
    const link = event.target.closest('a[data-pod-sc-product]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || pending || body.querySelector('form[aria-busy="true"]')) return;
    event.preventDefault(); open('product', link);
    const signal = controller.signal;
    try {
      const url = new URL(config.productUrl, location.href); url.searchParams.set('product_id', link.dataset.podScProduct);
      const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', signal });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error('Unavailable');
      body.innerHTML = payload.html; body.removeAttribute('aria-busy'); productForm();
    } catch (problem) { if (problem.name !== 'AbortError') error(link.href); }
  });
  function fragments(payload) {
    Object.entries(payload.fragments || {}).forEach(([selector, html]) => document.querySelectorAll(selector).forEach(node => { node.outerHTML = html; }));
    window.jQuery(document.body).trigger('wc_fragments_refreshed');
  }
  async function loadBag(focusKey = null) {
    const response = await fetch(config.bagUrl, { credentials:'same-origin', cache:'no-store', signal:controller.signal });
    const payload = await response.json();
    if (!response.ok || !payload.success) throw new Error('Bag unavailable');
    body.innerHTML = payload.html; body.removeAttribute('aria-busy'); fragments(payload);
    if (focusKey) body.querySelector(`[data-key="${CSS.escape(focusKey)}"] input.qty`)?.focus();
  }
  async function openBag(trigger) {
    open('bag', trigger);
    try { await loadBag(); }
    catch (problem) { if (problem.name !== 'AbortError') error(config.cartUrl); }
  }
  document.addEventListener('click', event => {
    const link = event.target.closest('a');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const url = new URL(link.href, location.href), cart = new URL(config.cartUrl, location.href);
    if (!link.matches('.pod-cart-link, [data-pod-sc-bag]') && !(link.closest('header') && url.origin === cart.origin && url.pathname === cart.pathname && !url.search)) return;
    event.preventDefault();
    if (!pending && !body.querySelector('form[aria-busy="true"]')) openBag(link);
  });
  document.addEventListener('pod-sc-added', event => {
    if (!event.detail.payload.redirect && dialog.open && mode === 'product') openBag(opener);
  });
  function bagMessage(message, notices = '') {
    let area = body.querySelector('.pod-sc-bag-message');
    if (!area) { area = document.createElement('div'); area.className = 'pod-sc-bag-message'; area.setAttribute('role','alert'); area.tabIndex = -1; body.prepend(area); }
    area.textContent = message;
    if (notices) { const native = document.createElement('div'); native.innerHTML = notices; area.append(native); }
    area.focus(); return area;
  }
  async function changeBag(command, key) {
    if (pending) return;
    pending = true; body.setAttribute('aria-busy','true');
    body.querySelectorAll('button,input').forEach(element => { element.disabled = true; });
    closeButton.disabled = true;
    try {
      const response = await fetch(config.changeUrl, {method:'POST', credentials:'same-origin', cache:'no-store', body:command});
      const payload = await response.json();
      if (!response.ok || !payload.success) {
        await loadBag(key); bagMessage(payload.message || 'Bag update could not be completed. Please retry.', payload.notices); return;
      }
      body.innerHTML = payload.html; fragments(payload);
      const next = body.querySelector(`[data-key="${CSS.escape(key)}"] input.qty`);
      if (next) next.focus(); else closeButton.focus();
    } catch (_) {
      const area = bagMessage('Connection interrupted. Retry this same update safely, or reopen your bag to check its latest state.');
      const retry = document.createElement('button'); retry.type='button'; retry.textContent='Retry update';
      retry.addEventListener('click', () => changeBag(command,key)); area.append(retry);
    } finally {
      pending = false; closeButton.disabled = false; body.removeAttribute('aria-busy');
      body.querySelectorAll('button,input').forEach(element => { element.disabled = false; });
    }
  }
  async function commandFor(item, action, quantity) {
    if (pending) return;
    // Serialize the context read too, so double taps cannot create two commands.
    pending = true; closeButton.disabled = true;
    try {
      const response = await fetch(window.podSC.contextUrl, {credentials:'same-origin',cache:'no-store'});
      const context = await response.json();
      if (!response.ok) throw new Error('Context unavailable');
      const command = new FormData();
      command.set('pod_sc_nonce',context.nonce); command.set('pod_sc_session_token',context.session_token); command.set('pod_sc_request_id',context.request_id);
      command.set('key',item.dataset.key); command.set('action',action);
      if (action === 'quantity') command.set('quantity',quantity);
      pending = false; await changeBag(command,item.dataset.key);
    } catch (_) { bagMessage('Unable to start this update. Please retry. Your bag has not been changed.'); }
    finally { pending = false; closeButton.disabled = false; }
  }
  body.addEventListener('click', event => {
    if (mode !== 'bag') return;
    const item = event.target.closest('.pod-sc-bag-item'); if (!item || pending) return;
    if (event.target.closest('.pod-sc-remove')) { commandFor(item,'remove'); return; }
    const step = event.target.closest('[data-delta]'); if (!step) return;
    const input = item.querySelector('input.qty');
    try { Number(step.dataset.delta) < 0 ? input.stepDown() : input.stepUp(); } catch (_) { input.focus(); return; }
    if (input.reportValidity()) commandFor(item,'quantity',input.value);
  });
  body.addEventListener('change', event => {
    if (mode !== 'bag' || pending || !event.target.matches('input.qty')) return;
    if (event.target.reportValidity()) commandFor(event.target.closest('.pod-sc-bag-item'),'quantity',event.target.value);
  });
  window.addEventListener('pageshow', event => { if (event.persisted && dialog.open && mode === 'bag' && !pending) openBag(opener); });
})();
