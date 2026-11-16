<?php
/** Test-only extension behavior, never included in a shipped component. */
if ( ! defined( 'POD_STOREFRONT_TEST_FIXTURE' ) || ! POD_STOREFRONT_TEST_FIXTURE ) { return; }
// This fixture deliberately represents the owner's already-open store.
add_filter( 'pre_option_woocommerce_coming_soon', static function () { return 'no'; } );
add_filter( 'woocommerce_add_to_cart_validation', static function ( $valid ) {
	if ( isset( $_POST['fixture_reject'] ) && '1' === $_POST['fixture_reject'] ) { wc_add_notice( 'Fixture extension rejected the addition.', 'error' ); return false; }
	return $valid;
} );
add_filter( 'pod_sc_ajax_supported', static function ( $enabled, $product ) { return isset( $_GET['fixture_native'] ) ? false : $enabled; }, 10, 2 );
