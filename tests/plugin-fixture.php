<?php
/** Local synthetic shopping-flow fixtures; explicitly rejects production. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'POD_STOREFRONT_TEST_FIXTURE' ) || ! POD_STOREFRONT_TEST_FIXTURE || '127.0.0.1' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { throw new RuntimeException( 'Fixture only.' ); }
$block_checkout = (int) get_option( 'woocommerce_checkout_page_id' );
$classic = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Simple checkout', 'post_name' => 'classic-checkout', 'post_content' => '[woocommerce_checkout]' ) );
update_option( 'woocommerce_checkout_page_id', $classic );
update_option( 'pod_fixture_block_checkout', $block_checkout );
update_option( 'pod_fixture_classic_checkout', $classic );
update_option( 'woocommerce_enable_guest_checkout', 'yes' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_store_pages_only', 'no' );
// Seed the fresh fixture's native ordering, avoiding Woo Admin's later lazy default write.
// No provider is enabled, configured or used for payment by this test fixture.
update_option( 'woocommerce_gateway_order', array( '_wc_offline_payment_methods_group' => 0, 'bacs' => 1, 'cheque' => 2, 'cod' => 3 ) );
update_option( 'woocommerce_default_country', 'US:CA' );
update_option( 'woocommerce_customer_default_location', 'base' );
update_option( 'woocommerce_calc_taxes', 'yes' );
update_option( 'woocommerce_tax_based_on', 'billing' );
update_option( 'woocommerce_tax_display_cart', 'excl' );
update_option( 'woocommerce_prices_include_tax', 'no' );
update_option( 'woocommerce_allowed_countries', 'specific' );
update_option( 'woocommerce_specific_allowed_countries', array( 'US' ) );
update_option( 'woocommerce_ship_to_countries', '' );
$terms = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Fixture terms', 'post_content' => 'Terms for synthetic test purchases only.' ) );
$privacy = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Fixture privacy', 'post_content' => 'Privacy statement for synthetic test data only.' ) );
update_option( 'woocommerce_terms_page_id', $terms );
update_option( 'wp_page_for_privacy_policy', $privacy );
WC_Tax::_insert_tax_rate( array( 'tax_rate_country' => 'US', 'tax_rate_state' => '', 'tax_rate' => '10.0000', 'tax_rate_name' => 'Fixture Tax', 'tax_rate_priority' => 1, 'tax_rate_compound' => 0, 'tax_rate_shipping' => 1, 'tax_rate_order' => 0, 'tax_rate_class' => '' ) );
$zone = new WC_Shipping_Zone();
$zone->set_zone_name( 'Fixture United States' );
$zone->add_location( 'US', 'country' );
$zone->save();
foreach ( array( '5' => 'Fixture standard shipping', '12' => 'Fixture express shipping' ) as $cost => $label ) {
	$instance = $zone->add_shipping_method( 'flat_rate' );
	update_option( 'woocommerce_flat_rate_' . $instance . '_settings', array( 'title' => $label, 'tax_status' => 'taxable', 'cost' => (string) $cost, 'type' => 'order' ) );
}
$coupon = new WC_Coupon();
$coupon->set_code( 'POD10' );
$coupon->set_discount_type( 'percent' );
$coupon->set_amount( 10 );
$coupon->save();
$buyer = wp_create_user( 'fixturebuyer', 'fixture-buyer-local', 'buyer@example.invalid' );
( new WP_User( $buyer ) )->set_role( 'customer' );

$base_product = wc_get_product( wc_get_product_id_by_sku( 'POD-FIXTURE-0' ) );
$image_id = $base_product->get_image_id();
foreach ( array( 'Flow Simple Tee', 'Flow Limited Tee', 'Flow Free Sample', 'Flow Unavailable Tee' ) as $index => $name ) {
	$p = new WC_Product_Simple();
	$p->set_name( $name ); $p->set_slug( sanitize_title( $name ) ); $p->set_status( 'publish' );
	$p->set_sku( 'POD-FLOW-' . $index ); $p->set_regular_price( 2 === $index ? '0' : '32' );
	$p->set_image_id( $image_id ); $p->set_tax_status( 'taxable' );
	$p->set_category_ids( $base_product->get_category_ids() );
	$p->set_manage_stock( true ); $p->set_stock_quantity( 3 === $index ? 0 : 5 );
	if ( 1 === $index ) { $p->set_sold_individually( true ); }
	if ( 2 === $index ) { $p->set_virtual( true ); $p->set_manage_stock( false ); }
	$p->save();
}
$p = new WC_Product_Variable(); $p->set_name( 'Flow Variable Tee' ); $p->set_slug( 'flow-variable-tee' );
$p->set_sku( 'POD-FLOW-VAR' ); $p->set_status( 'publish' ); $p->set_image_id( $image_id );
$p->set_category_ids( $base_product->get_category_ids() );
$size = new WC_Product_Attribute(); $size->set_id( wc_attribute_taxonomy_id_by_name( 'pa_sizes' ) );
$size->set_name( 'pa_sizes' ); $size->set_options( get_terms( array( 'taxonomy' => 'pa_sizes', 'hide_empty' => false, 'fields' => 'ids' ) ) );
$size->set_visible( true ); $size->set_variation( true );
$color = new WC_Product_Attribute(); $color->set_name( 'Color' ); $color->set_options( array( 'Black', 'White' ) );
$color->set_visible( true ); $color->set_variation( true ); $p->set_attributes( array( $size, $color ) );
$id = $p->save();
foreach ( array( array( 's', 'Black', '32', 5 ), array( 'm', 'Black', '34', 5 ), array( 'm', 'White', '36', 5 ), array( 'l', 'Black', '34', 0 ) ) as $data ) {
	$v = new WC_Product_Variation(); $v->set_parent_id( $id ); $v->set_attributes( array( 'pa_sizes' => $data[0], 'color' => $data[1] ) );
	$v->set_regular_price( $data[2] ); $v->set_manage_stock( true ); $v->set_stock_quantity( $data[3] ); $v->save();
}
WC_Product_Variable::sync( $id );
echo 'Classic and native Blocks checkout, tax/shipping/coupon, customer and flow products created. No gateway configured.';
