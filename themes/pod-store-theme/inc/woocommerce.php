<?php
/** Keep WooCommerce templates, hooks, variation forms and cart behavior intact. */
defined( 'ABSPATH' ) || exit;

function pod_store_theme_woocommerce_layout() {
	// header.php/footer.php already provide the semantic main element.
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
}
add_action( 'after_setup_theme', 'pod_store_theme_woocommerce_layout', 20 );

function pod_store_theme_cart_fragments( $fragments ) {
	ob_start();
	pod_store_theme_cart_link();
	$fragments['a.pod-cart-link'] = ob_get_clean();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'pod_store_theme_cart_fragments' );
