/* Native details controls remain usable without JavaScript. */
(() => {
  'use strict';
  const panels = [...document.querySelectorAll('.pod-header details')];
  panels.forEach(panel => {
    panel.addEventListener('toggle', () => {
      if (!panel.open) return;
      panels.forEach(other => { if (other !== panel) other.open = false; });
      if (panel.classList.contains('pod-search')) panel.querySelector('input[type="search"]')?.focus();
    });
  });
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    panels.filter(panel => panel.open).forEach(panel => {
      panel.open = false;
      panel.querySelector('summary').focus();
    });
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.pod-header')) panels.forEach(panel => { panel.open = false; });
  });
  // WooCommerce Blocks and classic AJAX both keep the header bag in sync.
  document.body.addEventListener('wc-blocks_added_to_cart', () => {
    if (window.jQuery) window.jQuery(document.body).trigger('wc_fragment_refresh');
  });
  document.body.addEventListener('wc-blocks_removed_from_cart', () => {
    if (window.jQuery) window.jQuery(document.body).trigger('wc_fragment_refresh');
  });
})();
