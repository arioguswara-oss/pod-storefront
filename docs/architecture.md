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

`plugins/pod-simple-checkout` stays at `0.1.0-dev`: metadata and direct-access
guard only, with a declared WooCommerce dependency. Its independent ZIP can be
installed and activated, but has no shopping-flow functionality. After theme
review, requirements for classic checkout versus Checkout Block support should
be agreed before implementation. Payment gateways remain outside scope.

## Releases

`releases.json` stores independent component versions and statuses. Build rejects
mismatching WordPress version headers, missing files, symlinks and output inside
component source. ZIP ordering, timestamps and permissions are stable;
`manifest.json` and `SHA256SUMS` identify the built files.

Keep ZIPs, customer exports, credentials and runtime data out of Git. After review
and merge, release tags can use `theme/v0.1.0` and later `plugin/v0.1.0`.
A version/tag/ZIP does not authorize deployment; there is no deploy workflow.
