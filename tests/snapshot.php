<?php
/** Compare store records before and after theme activation, on fixture only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'POD_STOREFRONT_TEST_FIXTURE' ) || ! POD_STOREFRONT_TEST_FIXTURE || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	throw new RuntimeException( 'Fixture-only snapshot.' );
}
$snapshot = array( 'products' => array(), 'orders' => array(), 'categories' => array(), 'images' => array() );
foreach ( wc_get_products( array( 'limit' => -1, 'orderby' => 'ID', 'order' => 'ASC', 'status' => 'publish' ) ) as $product ) {
	$data = $product->get_data();
	// Object props are serialized consistently by wp_json_encode.
	$snapshot['products'][ $product->get_id() ] = array( 'data' => $data, 'url' => $product->get_permalink(), 'children' => array() );
	foreach ( $product->get_children() as $child ) {
		$snapshot['products'][ $product->get_id() ]['children'][ $child ] = wc_get_product( $child )->get_data();
	}
}
foreach ( wc_get_orders( array( 'limit' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) ) as $order ) {
	$snapshot['orders'][ $order->get_id() ] = array( 'data' => $order->get_data(), 'items' => array() );
	foreach ( $order->get_items() as $key => $item ) { $snapshot['orders'][ $order->get_id() ]['items'][ $key ] = $item->get_data(); }
}
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'term_id' ) ) as $term ) {
	$snapshot['categories'][ $term->term_id ] = array( 'term' => $term, 'url' => get_term_link( $term ) );
}
foreach ( get_posts( array( 'post_type' => 'attachment', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) ) as $attachment ) {
	$snapshot['images'][ $attachment->ID ] = array( 'url' => wp_get_attachment_url( $attachment->ID ), 'meta' => wp_get_attachment_metadata( $attachment->ID ) );
}
$snapshot['homepage_content'] = get_post_field( 'post_content', get_option( 'page_on_front' ) );
$snapshot['settings'] = array( get_option( 'permalink_structure' ), get_option( 'woocommerce_permalinks' ), get_option( 'woocommerce_gateway_order' ), get_option( 'woocommerce_payment_gateways' ) );
echo wp_json_encode( $snapshot, JSON_PRETTY_PRINT );
