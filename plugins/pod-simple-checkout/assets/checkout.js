/* Address mirroring only; native WooCommerce owns checkout, totals, validation and submit. */
(() => {
  'use strict';
  const form = document.querySelector('.pod-sc-us form.checkout');
  if (!form || !form.querySelector('[name="pod_sc_us_form"]') || !window.jQuery) return;
  const $ = window.jQuery;
  const names = ['first_name','last_name','company','country','address_1','address_2','city','state','postcode'];
  const same = form.querySelector('#pod-sc-same-address');
  let syncing = false, submitting = false;
  document.body.classList.add('pod-sc-us-js');
  const nativeShipping = form.querySelector('#ship-to-different-address-checkbox');
  if (nativeShipping) { const primary = document.createElement('input'); primary.type='hidden'; primary.name='pod_sc_shipping_primary'; primary.value='1'; form.append(primary); nativeShipping.checked = true; $(nativeShipping).trigger('change'); }
  function mirror() {
    if (syncing || !same?.checked) return;
    syncing = true;
    for (const name of names) {
      const from = form.querySelector('#shipping_'+name), to = form.querySelector('#billing_'+name);
      if (from && to && from.value !== to.value) { to.value = from.value; $(to).trigger('change'); }
    }
    syncing = false;
  }
  function addressUI() {
    for (const name of names) {
      const row = form.querySelector('#billing_'+name+'_field');
      if (row) row.hidden = !!same?.checked;
    }
    mirror();
  }
  if (same) {
    same.closest('p').hidden = false; same.checked = same.dataset.defaultSame === 'yes'; addressUI();
    same.addEventListener('change', () => { addressUI(); $(document.body).trigger('update_checkout'); });
    form.addEventListener('input', event => { if (event.target.name?.startsWith('shipping_')) mirror(); });
    $(form).on('change', '[name^="shipping_"]', mirror);
    $(document.body).on('country_to_state_changed', () => { addressUI(); });
  }
  function sticky() {
    const bar = form.querySelector('.pod-sc-submit'); if (!bar) return;
    const height = window.visualViewport?.height || innerHeight;
    const focus = document.activeElement;
    const editing = form.contains(focus) && focus.matches('input,select,textarea,[contenteditable="true"]');
    const unsafe = height < 600 || height < innerHeight * 0.8 || (window.visualViewport?.scale || 1) > 1.1 || editing || submitting || !!document.querySelector('.woocommerce-error,.blockUI,#payment iframe');
    const bottom = height - bar.offsetHeight - 12;
    const covering = [...form.querySelectorAll('#payment .payment_methods,#payment .payment_box,.woocommerce-terms-and-conditions-wrapper')].some(node => { const r = node.getBoundingClientRect(); return r.height && r.bottom > bottom; });
    form.classList.toggle('pod-sc-sticky-safe', !unsafe && !covering);
  }
  $(document.body).on('updated_checkout', () => { submitting = false; addressUI(); sticky(); });
  $(document.body).on('checkout_error', () => {
    submitting = false; if (same) { same.checked = false; addressUI(); } sticky();
    const notice = document.querySelector('.woocommerce-error'); if (notice) { notice.tabIndex=-1; notice.focus(); }
  });
  // This does not submit or intercept a gateway; it only disables sticky placement during core processing.
  $(form).on('checkout_place_order', () => { submitting = true; sticky(); });
  ['scroll','resize','focusin','focusout'].forEach(event => window.addEventListener(event, sticky, {passive:true}));
  window.visualViewport?.addEventListener('resize', sticky);
  sticky();
})();
