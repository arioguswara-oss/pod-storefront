/* Progressive enhancement of native WooCommerce forms; no checkout/payment submission. */
(() => {
  'use strict';
  const config = window.podSC;
  if (!config) return;
  const uuid = () => {
    if (window.crypto?.randomUUID) return crypto.randomUUID();
    if (!window.crypto?.getRandomValues) return null;
    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
    const hex = [...bytes].map(byte => byte.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
  };
  document.querySelectorAll('form.cart').forEach(form => {
    const product = form.querySelector('[name="pod_sc_product_id"]');
    const identifier = form.querySelector('[name="pod_sc_request_id"]');
    const messages = form.querySelector('.pod-sc-messages');
    if (!product || !identifier || !messages) return;
    identifier.value = uuid() || identifier.value;
    const state = { busy: false, native: false };
    const announce = (text, error = false) => {
      messages.setAttribute('role', error ? 'alert' : 'status');
      messages.textContent = text;
      if (error) messages.focus();
    };
    const busy = active => {
      state.busy = active;
      form.classList.toggle('pod-sc-loading', active);
      form.setAttribute('aria-busy', String(active));
      form.querySelectorAll('button[type="submit"]').forEach(button => {
        if (active) { button.dataset.podScWasDisabled = String(button.disabled); button.disabled = true; }
        else { button.disabled = button.dataset.podScWasDisabled === 'true'; delete button.dataset.podScWasDisabled; }
      });
    };
    const buy = form.querySelector('.pod-sc-buy-now');
    if (window.jQuery && form.matches('.variations_form') && buy) {
      window.jQuery(form).on('found_variation', (_event, variation) => { buy.disabled = state.busy || !variation.is_purchasable || !variation.is_in_stock || !variation.variation_is_visible; });
      window.jQuery(form).on('reset_data hide_variation', () => { buy.disabled = true; });
      // Keep the initial button operable to provide an accessible validation message.
    }
    form.addEventListener('submit', async event => {
      const button = event.submitter || form.querySelector('.single_add_to_cart_button');
      if (!button || !button.matches('.single_add_to_cart_button, .pod-sc-buy-now')) return;
      if (state.native) { state.native = false; return; }
      if (event.defaultPrevented) return;
      if (state.busy) { event.preventDefault(); return; }
      const isBuy = button.matches('.pod-sc-buy-now');
      if (form.matches('.variations_form') && !Number(form.querySelector('[name="variation_id"]')?.value)) {
        event.preventDefault(); announce(config.messages.variation, true); return;
      }
      const files = [...form.querySelectorAll('input[type="file"]')].some(input => input.files?.length);
      if (product.dataset.podScAjax !== 'yes' || !window.fetch || !window.FormData || files) return;
      event.preventDefault();
      if (!form.reportValidity()) return;
      const data = new FormData(form);
      data.set('add-to-cart', product.value);
      data.set('pod_sc_intent', isBuy ? 'buy' : 'add');
      if (isBuy) data.set('pod_sc_buy_now', product.value);
      announce(config.messages.working);
      busy(true);
      try {
        const contextResponse = await fetch(config.contextUrl, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
        if (!contextResponse.ok) {
          const problem = await contextResponse.json();
          messages.setAttribute('role', 'alert'); messages.innerHTML = problem.notices || '';
          messages.focus(); return;
        }
        const context = await contextResponse.json();
        if (!context.ajax) {
          busy(false); state.native = true; form.requestSubmit(button); return;
        }
        data.set('pod_sc_nonce', context.nonce);
        data.set('pod_sc_session_token', context.session_token);
        const response = await fetch(config.addUrl, { method: 'POST', credentials: 'same-origin', cache: 'no-store', body: data, headers: { Accept: 'application/json' } });
        const payload = await response.json();
        messages.setAttribute('role', payload.success ? 'status' : 'alert');
        messages.innerHTML = payload.notices || '';
        if (!payload.success) {
          if (!payload.retryable) identifier.value = uuid() || context.request_id;
          messages.focus(); return;
        }
        Object.entries(payload.fragments || {}).forEach(([selector, html]) => {
          document.querySelectorAll(selector).forEach(element => { element.outerHTML = html; });
        });
        if (window.jQuery) {
          window.jQuery(document.body).trigger('added_to_cart', [payload.fragments, payload.cart_hash, window.jQuery(button)]);
          if (payload.replayed) window.jQuery(document.body).trigger('wc_fragment_refresh');
        }
        identifier.value = uuid() || context.request_id;
        if (payload.redirect) {
          const destination = new URL(payload.redirect, location.href);
          if (destination.origin !== location.origin) throw new Error('Unexpected redirect');
          location.assign(destination.href);
        }
      } catch (_error) {
        // Do not automatically resubmit an uncertain request. Retry keeps its identifier.
        announce(config.messages.network, true);
        const link = document.createElement('a'); link.href = config.cartUrl; link.textContent = config.messages.cart;
        messages.append(' ', link);
      } finally { if (state.busy) busy(false); }
    });
  });
})();
