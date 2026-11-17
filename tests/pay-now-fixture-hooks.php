<?php
/** Local label-contract fixtures only: no SDK, card field, external request or payment. */
if ( ! defined( 'POD_STOREFRONT_TEST_FIXTURE' ) || ! POD_STOREFRONT_TEST_FIXTURE ) { return; }
add_action( 'plugins_loaded', static function () {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	class POD_Label_Paypal extends WC_Payment_Gateway {
		public function __construct() { $this->id='paypal'; $this->title='PayPal'; $this->enabled='yes'; $this->order_button_text='paynow-required' === get_option('pod_fixture_us_mode') ? 'Continue to PayPal' : ''; }
		public function process_payment( $id ) { throw new RuntimeException('Label fixture cannot process payments.'); }
	}
	class POD_Label_Card extends POD_Label_Paypal {
		public function __construct() { $this->id='stripe'; $this->title='Credit/Debit Card'; $this->enabled='yes'; $this->order_button_text=''; $this->has_custom_place_order_button='paynow-sdk' === get_option('pod_fixture_us_mode'); }
	}
	class POD_Label_Offline extends POD_Label_Paypal {
		public function __construct() { $this->id='cod'; $this->title='Offline fixture'; $this->enabled='yes'; $this->order_button_text=''; }
	}
	class POD_Label_Unknown extends POD_Label_Paypal {
		public function __construct() { $this->id='fixture_unknown'; $this->title='Unreviewed gateway fixture'; $this->enabled='yes'; $this->order_button_text=''; }
	}
	class POD_Label_Vccp_Paypal extends POD_Label_Paypal {
		public function __construct() { $this->id='vccp-gateway-paypal'; $this->title='PayPal'; $this->enabled='yes'; $this->order_button_text='paynow-vccp-required' === get_option('pod_fixture_us_mode') ? 'Continue to PayPal' : ''; }
	}
	class POD_Label_Vccp_Banxa extends POD_Label_Paypal {
		public function __construct() { $this->id='vccp-gateway-banxa'; $this->title='Credit/Debit Card (Banxa)'; $this->enabled='yes'; $this->order_button_text=''; $this->has_custom_place_order_button='paynow-vccp-sdk' === get_option('pod_fixture_us_mode'); }
	}
	add_filter( 'woocommerce_payment_gateways', static function($gateways) {
		if ( 0 === strpos((string)get_option('pod_fixture_us_mode'),'paynow-vccp') ) { return array_merge($gateways,array('POD_Label_Vccp_Paypal','POD_Label_Vccp_Banxa','POD_Label_Offline','POD_Label_Unknown')); }
		if ( 0 === strpos((string)get_option('pod_fixture_us_mode'),'paynow') ) { return array_merge($gateways,array('POD_Label_Paypal','POD_Label_Card','POD_Label_Offline','POD_Label_Unknown')); }
		return $gateways;
	} );
},35 );

add_filter( 'pod_sc_pay_now_gateway_ids', static function ( $ids ) { return 'paynow-vccp-optout' === get_option('pod_fixture_us_mode') ? array_values(array_diff($ids,array('vccp-gateway-paypal'))) : $ids; } );
