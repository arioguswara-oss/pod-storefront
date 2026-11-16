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
})();
