# POD Storefront — v0.1.0 review packages

Two independent WordPress-installable ZIP packages rebuilt from the source on branch `codex/pod-store-theme-v0.1.0`, as an alternative to unavailable GitHub Actions artifacts and release uploads.

- **pod-store-theme-0.1.0.zip** — Theme review candidate; white mobile-first WooCommerce storefront.
- **pod-simple-checkout-0.1.0-dev.zip** — Plugin *scaffold only*. It does **not** implement Buy Now, AJAX cart, or simplified checkout. **Do not activate it on the live site** in this phase.

Both packages contain exactly one top-level folder matching their WordPress slugs, with the source file selection described by `scripts/build.py`. These packages use ZIP **STORE** compression for compatibility, rather than the DEFLATE packaging used by the Python script. They are valid ZIPs but **not bit-identical** to its outputs. Verify integrity using `SHA256SUMS`; `manifest.json` records versions, size and file counts.

The original source branch used for these packages was commit `1f205ab9152b518a16d543aa148ec69f6bb230cf`.

**Live site is unchanged.** Before any theme activation on GraphicTeeStore.com, verify backups and Flatsome/UX Builder compatibility and request owner approval. See [live activation safeguards](../../docs/live-activation.md) and [test status](../../docs/verification.md). Local tests previously reported by Codex do not establish live compatibility. GitHub Actions billing and release asset authorization remain unresolved.
