# POD Simple Checkout v1.0.0 — review package

[**Download pod-simple-checkout-v1.0.0.zip**](https://raw.githubusercontent.com/arioguswara-oss/pod-storefront/refs/heads/codex/pod-simple-checkout-v1.0.0/releases/plugin-v1.0.0/pod-simple-checkout-v1.0.0.zip)

This is the actual independently installable plugin ZIP, containing one
`pod-simple-checkout/` directory and 13 production/documentation files. It does
not contain the theme, fixtures or Composer/Node/CLI dependencies. No GitHub
Actions run or Release asset upload is needed for this repository download.

SHA-256:

```text
b1ddb3bc1b76e829a77eaeedef7bee73955d931dd81b7244aa27d4394da686e5
```

See [SHA256SUMS](SHA256SUMS), [manifest](manifest.json), and
[executed verification data](verification.json).

**Features:** native simple/variable Buy Now, normal Add to Cart retained,
progressive AJAX and native fragments/counts, validated direct checkout for
Buy Now only, preserved bag by default, opt-in replacement, Classic/Blocks
presentation modes, capability-protected settings and request replay protection.
No gateway changes, order migration or theme file changes.

**Install only after owner approval:** WordPress Admin → Plugins → Add New →
Upload Plugin → choose this ZIP. Back up first, use normal replacement
confirmation for the old scaffold, then review **WooCommerce → POD Simple
Checkout**. Cart replacement defaults off. This review package does not authorize
upload or activation on GraphicTeeStore.com.

[Installation, configuration, compatibility and rollback](../../docs/plugin-v1.0.0.md)
· [Executed tests and known limits](../../docs/plugin-v1.0.0-verification.md)

## Synthetic previews

- Classic checkout: [390px](previews/plugin-classic-390.png) · [1440px](previews/plugin-classic-1440.png)
- Native Checkout Blocks: [390px](previews/plugin-blocks-390.png) · [1440px](previews/plugin-blocks-1440.png)

These are screenshots of the actual installed plugin on synthetic local data,
not the live website. No paid provider is configured in the fixture, so its
native payment area can show an expected unavailable-method notice. Existing
live gateway settings were never changed. No real payment or live order was tested.

All available local tests passed. GitHub CI success is not claimed; check the
PR status separately. Do not merge, deploy or activate automatically. Rollback
by deactivating this plugin; native WooCommerce remains available.
