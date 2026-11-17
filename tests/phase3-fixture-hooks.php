<?php
/** Synthetic compatibility/gateway-display probes. Never ships in plugin ZIP. */
if ( ! defined( 'POD_STOREFRONT_TEST_FIXTURE' ) || ! POD_STOREFRONT_TEST_FIXTURE ) { return; }
add_filter( 'pod_sc_us_checkout_supported', static function ( $enabled ) { return 'native' === get_option( 'pod_fixture_us_mode' ) ? false : $enabled; } );
add_filter( 'woocommerce_checkout_fields', static function ( $fields ) {
	if ( 'addon' === get_option( 'pod_fixture_us_mode' ) ) { $fields['billing']['billing_fixture_required'] = array( 'label'=>'Fixture required addon', 'required'=>true, 'type'=>'text', 'priority'=>15 ); }
	return $fields;
}, 80 );
add_filter( 'woocommerce_cart_shipping_packages', static function ( $packages ) {
	if ( 'packages' === get_option( 'pod_fixture_us_mode' ) && $packages ) { $extra=$packages[0]; $extra['package_name']='Fixture second package'; $packages[]=$extra; }
	return $packages;
} );
add_action( 'woocommerce_cart_calculate_fees', static function ( $cart ) { if ( 'fee' === get_option( 'pod_fixture_us_mode' ) ) { $cart->add_fee( 'Fixture handling', 2, true ); } } );
add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	class POD_Fixture_Display_Gateway extends WC_Payment_Gateway {
		public function __construct() { $this->id='fixture_display'; $this->title='Fixture native payment display'; $this->enabled='yes'; $this->has_fields=true; }
		public function payment_fields() {
			echo '<p>Local display fixture. No payment will be processed.</p><label for="fixture_reference">Fixture reference</label><input id="fixture_reference" name="fixture_reference" type="text">';
			if ( 'iframe' === get_option( 'pod_fixture_us_mode' ) ) { echo '<iframe title="Fixture gateway frame" src="about:blank"></iframe>'; }
		}
		public function process_payment( $order_id ) { throw new RuntimeException( 'Fixture gateway must never process a payment.' ); }
	}
	add_filter( 'woocommerce_payment_gateways', static function ( $gateways ) { if ( in_array( get_option( 'pod_fixture_us_mode' ), array( 'gateway', 'iframe' ), true ) ) { $gateways[]='POD_Fixture_Display_Gateway'; } return $gateways; } );
}, 30 );
add_filter( 'woocommerce_package_rates', static function ( $rates ) {
	if ( 'free-shipping' === get_option( 'pod_fixture_us_mode' ) ) { foreach ( $rates as $rate ) { $rate->set_cost( 0 ); $rate->set_taxes( array() ); } }
	return $rates;
} );
foreach ( array( 'woocommerce_before_checkout_billing_form','woocommerce_after_checkout_billing_form','woocommerce_before_checkout_shipping_form','woocommerce_after_checkout_shipping_form' ) as $hook ) {
	add_action( $hook, static function () use ( $hook ) { if ( 'hooks' === get_option( 'pod_fixture_us_mode' ) ) { echo '<span hidden data-fixture-hook="' . esc_attr( $hook ) . '"></span>'; } } );
}
add_filter( 'woocommerce_process_checkout_field_billing_address_1', static function ( $value ) { if ( 'processors' === get_option( 'pod_fixture_us_mode' ) ) { $GLOBALS['pod_fixture_process_count'] = ( $GLOBALS['pod_fixture_process_count'] ?? 0 ) + 1; } return $value; } );
add_action( 'woocommerce_after_checkout_validation', static function ( $data, $errors ) { if ( 'processors' === get_option( 'pod_fixture_us_mode' ) ) { $errors->add( 'fixture_probe', 'Fixture billing processor calls: ' . (int) ( $GLOBALS['pod_fixture_process_count'] ?? 0 ) ); } }, 100, 2 );
