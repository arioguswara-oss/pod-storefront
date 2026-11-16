# Architecture — POD Store Theme 0.1.0

## Presentation boundary

The standalone classic theme owns the header/footer, product-led homepage,
typography, whitespace and responsive CSS. It uses existing site identity and
catalog URLs. It makes no database migrations, product/order writes, rewrite
changes or gateway registrations. WordPress itself stores theme activation and
owner-saved Customizer/menu settings in the normal way.

The design takes inspiration from simple white storefronts, including Zet
Project, without copying its code, branding, images or claims. Hero copy is
editable; no shipping, refund, review-count or sales claims are invented.

## WooCommerce integration

Native archive-product and single-product templates remain in WooCommerce.
Theme hooks remove only duplicate outer wrappers and the default sidebar;
header/footer supply the main landmark. Core hooks for prices, stock, variations,
plugins, notices, structured data, galleries and related products remain intact.

There is deliberately no `woocommerce.php` catch-all or copied template override.
Native gallery zoom, lightbox and slider support are declared. Existing
WooCommerce assets remain loaded; theme CSS follows them. Header bag counts use
WooCommerce's fragments API. Blocks events refresh fragments after additions and
removals; quantity counts are authoritative on the next page load.

The homepage reads up to eight recent visible published products. Existing image
attachments, links, category terms and prices are rendered through WooCommerce
APIs. Hidden or hidden-out-of-stock products do not appear. It neither deletes
nor rewrites the previously configured homepage; its saved content returns when
the old theme is restored. A site-specific review is still needed for
Flatsome/UX Builder content on other pages and theme-provided widgets/menus.

## Shopping-flow plugin

`plugins/pod-simple-checkout` is now version `1.0.0`, developed on a dedicated
branch from the existing theme branch after the owner confirmed the theme is
active. Native WooCommerce APIs handle validated simple/variable additions,
cart sessions and all checkout/order behavior. Buy Now adds a scoped redirect;
standard cart behavior remains. Small PHP classes separate cart commands,
capability-protected settings and Classic/Blocks presentation modes.

AJAX commands use fresh nonce/session context, native fragments and bounded
request replay protection. Classic UI wraps existing template hooks and adds
thumbnails; Checkout Blocks remain native with scoped CSS. No checkout page,
WooCommerce fields or gateway implementation is replaced. See
`plugin-v1.0.0.md` and the plugin's packaged COMPATIBILITY.txt/SECURITY.txt.

## Releases

`releases.json` stores independent component versions and statuses. Build rejects
mismatching WordPress version headers, missing files, symlinks and output inside
component source. ZIP ordering, timestamps and permissions are stable;
`manifest.json` and `SHA256SUMS` identify the built files.

Keep `dist/`, customer exports, credentials and runtime data out of Git. Frozen,
versioned review ZIPs and synthetic preview screenshots may be copied to
`releases/` for the owner's GitHub-only download requirement when Actions or
Release asset upload is unavailable. Package hashes must match the build.
After review
and merge, release tags can use `theme/v0.1.0` and later `plugin/v0.1.0`.
A version/tag/ZIP does not authorize deployment; there is no deploy workflow.
