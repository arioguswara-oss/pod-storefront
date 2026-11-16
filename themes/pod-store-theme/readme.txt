=== POD Store Theme ===
Version: 0.1.0
Requires at least: 6.5
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WooCommerce theme with a white, mobile-first storefront.

== Installation ==

Upload pod-store-theme-0.1.0.zip via Appearance > Themes > Add New > Upload Theme.
Review backup, compatibility and rollback instructions in docs/live-activation.md
in the source repository BEFORE activating on GraphicTeeStore.com. Activation
requires explicit owner authorization; installing a ZIP does not authorize it.

== Setup ==

Assign existing menus to Main navigation and Footer navigation. A catalog-based
menu works as a fallback. Set the logo in Site Identity and edit the hero text
in Customizer > POD Homepage. No product data or site settings are migrated.

The dedicated storefront homepage is shown while this theme is active. Existing
homepage content stays saved. Flatsome/UX Builder content on other pages may
depend on Flatsome and requires a compatibility review before switching themes.

WooCommerce owns category, product, gallery, variation, cart and checkout markup.
There are no WooCommerce template copies, gateway modifications, order writes,
permalink changes, external fonts, trackers or runtime frontend build tools.

== Changelog ==

= 0.1.0 =
Initial storefront: homepage, native catalog/category and single-product layouts,
responsive navigation/search, native gallery support and header bag count.
