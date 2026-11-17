<?php
/** Classic presentation adapter. All validation, rates, totals and orders remain native. */
defined( 'ABSPATH' ) || exit;

final class POD_SC_US_Checkout {
	private static $billing_render = false;
	private static $prepared = false;
	private static $shipping_html = '';
	private static $capturing_shipping = false;
	private static $capturing_review = false;
	const ADDRESS = array( 'first_name', 'last_name', 'company', 'country', 'address_1', 'address_2', 'city', 'state', 'postcode' );

	public static function register() {
		add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'setup' ), 15 );
		add_filter( 'woocommerce_get_script_data', array( __CLASS__, 'script_data' ), 20, 2 );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'field_order' ), 900 );
		add_action( 'woocommerce_before_order_notes', static function () { if ( self::supported() ) { echo '<details class="pod-sc-notes"><summary>' . esc_html__( 'Order notes (optional)', 'pod-simple-checkout' ) . '</summary>'; } }, -100 );
		add_action( 'woocommerce_after_order_notes', static function () { if ( self::supported() ) { echo '</details>'; } }, PHP_INT_MAX );
		add_filter( 'body_class', static function ( $classes ) { if ( self::supported() ) { $classes[] = 'pod-sc-us'; } return $classes; } );
		add_action( 'woocommerce_checkout_before_customer_details', array( __CLASS__, 'contact' ), 5 );
		add_action( 'woocommerce_before_checkout_billing_form', array( __CLASS__, 'billing_before' ), -100 );
		add_action( 'woocommerce_after_checkout_billing_form', static function () { self::$billing_render = false; }, PHP_INT_MAX );
		add_filter( 'woocommerce_form_field', array( __CLASS__, 'field_html' ), 100, 4 );
		add_filter( 'gettext_woocommerce', array( __CLASS__, 'heading' ), 20, 3 );
		add_filter( 'woocommerce_ship_to_different_address_checked', static function ( $checked ) { return self::supported() && WC()->cart->needs_shipping_address() ? 1 : $checked; } );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'shipping_intent' ), 0 );
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'ajax_address' ), 0 );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_adapter' ), 20, 2 );
		add_action( 'woocommerce_checkout_before_order_review_heading', array( __CLASS__, 'review_start' ), 5 );
		add_action( 'woocommerce_checkout_after_order_review', array( __CLASS__, 'review_end' ), PHP_INT_MAX - 1 );
		add_action( 'woocommerce_review_order_before_shipping', array( __CLASS__, 'shipping_start' ), PHP_INT_MAX );
		add_action( 'woocommerce_review_order_after_shipping', array( __CLASS__, 'shipping_end' ), -PHP_INT_MAX );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'fragments' ), 30 );
		add_action( 'woocommerce_review_order_before_payment', static function () { if ( self::supported() ) { echo '<h2 class="pod-sc-payment-title">' . esc_html__( 'Payment Method', 'pod-simple-checkout' ) . '</h2>'; } } );
		add_action( 'woocommerce_review_order_before_submit', array( __CLASS__, 'submit_open' ), PHP_INT_MAX );
		add_action( 'woocommerce_review_order_after_submit', static function () { if ( self::supported() ) { echo '</div>'; } }, -PHP_INT_MAX );
	}

	/** Presentation allowlist only; never edits a gateway, its label, or its settings. */
	public static function pay_now_config() {
		$ids = (array) apply_filters( 'pod_sc_pay_now_gateway_ids', array( 'paypal', 'ppcp-gateway', 'ppcp-credit-card-gateway', 'stripe', 'stripe_cc', 'woocommerce_payments' ) );
		$eligible = array();
		if ( WC()->cart && WC()->cart->needs_payment() ) {
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( in_array( $gateway->id, $ids, true ) && empty( $gateway->order_button_text ) && empty( $gateway->has_custom_place_order_button ) ) { $eligible[] = $gateway->id; }
			}
		}
		return array( 'gateways' => $eligible, 'text' => __( 'Pay Now', 'pod-simple-checkout' ) );
	}

	private static function context() {
		$endpoint = isset( $_GET['wc-ajax'] ) && is_scalar( $_GET['wc-ajax'] ) ? sanitize_key( wp_unslash( $_GET['wc-ajax'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- rendering selection only.
		return POD_SC_Settings::enabled( 'checkout_ui' ) && 'classic' === POD_SC_Checkout::mode() && apply_filters( 'pod_sc_checkout_ui_supported', true ) && ( POD_SC_Checkout::enhanced() || in_array( $endpoint, array( 'checkout', 'update_order_review' ), true ) );
	}

	public static function supported() {
		if ( ! self::context() || ! WC()->cart || ! apply_filters( 'pod_sc_us_checkout_supported', true ) || wc_ship_to_billing_address_only() ) { return false; }
		// Builders/template overrides and unknown address schemas retain the original native layout.
		foreach ( array( 'form-checkout.php', 'form-billing.php', 'form-shipping.php', 'review-order.php' ) as $file ) {
			if ( wp_normalize_path( wc_locate_template( 'checkout/' . $file ) ) !== wp_normalize_path( WC()->plugin_path() . '/templates/checkout/' . $file ) ) { return false; }
		}
		$checkout = WC()->checkout();
		if ( ! self::$prepared && ( 10 !== has_action( 'woocommerce_checkout_billing', array( $checkout, 'checkout_form_billing' ) ) || 10 !== has_action( 'woocommerce_checkout_shipping', array( $checkout, 'checkout_form_shipping' ) ) ) ) { return false; }
		return self::schema( $checkout->get_checkout_fields() );
	}

	private static function schema( $fields ) {
		foreach ( array( 'billing', 'shipping' ) as $group ) {
			$allowed = array_map( static function ( $name ) use ( $group ) { return $group . '_' . $name; }, self::ADDRESS );
			$allowed[] = $group . '_phone';
			if ( 'billing' === $group ) { $allowed[] = 'billing_email'; }
			if ( array_diff( array_keys( $fields[ $group ] ?? array() ), $allowed ) ) { return false; }
		}
		if ( WC()->cart && WC()->cart->needs_shipping_address() ) { foreach ( self::ADDRESS as $name ) { if ( isset( $fields['billing'][ 'billing_' . $name ] ) !== isset( $fields['shipping'][ 'shipping_' . $name ] ) ) { return false; } } }
		return isset( $fields['billing']['billing_email'] ) && empty( $fields['order']['order_comments']['required'] ) && ! array_diff( array_keys( $fields['order'] ?? array() ), array( 'order_comments' ) );
	}

	public static function field_order( $fields ) {
		if ( ! self::context() || ! apply_filters( 'pod_sc_us_checkout_supported', true ) || ! self::schema( $fields ) || wc_ship_to_billing_address_only() ) { return $fields; }
		foreach ( array( 'form-checkout.php', 'form-billing.php', 'form-shipping.php', 'review-order.php' ) as $file ) {
			if ( wp_normalize_path( wc_locate_template( 'checkout/' . $file ) ) !== wp_normalize_path( WC()->plugin_path() . '/templates/checkout/' . $file ) ) { return $fields; }
		}
		foreach ( array( 'billing', 'shipping' ) as $group ) {
			foreach ( array( 'first_name'=>10, 'last_name'=>20, 'company'=>25, 'address_1'=>30, 'address_2'=>40, 'city'=>50, 'state'=>60, 'postcode'=>70, 'country'=>80, 'phone'=>90 ) as $name=>$priority ) {
				if ( isset( $fields[ $group ][ $group . '_' . $name ] ) ) { $fields[ $group ][ $group . '_' . $name ]['priority'] = $priority; }
			}
		}
		return $fields;
	}

	public static function script_data( $params, $handle ) {
		if ( 'wc-address-i18n' !== $handle || ! self::supported() || empty( $params['locale'] ) ) { return $params; }
		$locale = json_decode( $params['locale'], true );
		if ( ! is_array( $locale ) || ! isset( $locale['US'] ) ) { return $params; }
		// Keep core locale validation/labels; align only US presentation priorities with server fields.
		foreach ( array( 'first_name'=>10, 'last_name'=>20, 'company'=>25, 'address_1'=>30, 'address_2'=>40, 'city'=>50, 'state'=>60, 'postcode'=>70, 'country'=>80, 'phone'=>90 ) as $name=>$priority ) {
			$locale['US'][ $name ]['priority'] = $priority;
		}
		$params['locale'] = wp_json_encode( $locale, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
		return $params;
	}

	public static function setup( $checkout ) {
		if ( ! self::supported() || ! WC()->cart->needs_shipping_address() || 10 !== has_action( 'woocommerce_checkout_shipping', array( $checkout, 'checkout_form_shipping' ) ) || 10 !== has_action( 'woocommerce_checkout_billing', array( $checkout, 'checkout_form_billing' ) ) ) { return; }
		// Move only core's renderer. All top-level and before/after field extension hooks still execute once.
		self::$prepared = true;
		remove_action( 'woocommerce_checkout_shipping', array( $checkout, 'checkout_form_shipping' ), 10 );
		add_action( 'woocommerce_checkout_billing', array( __CLASS__, 'shipping_first' ), 5 );
	}

	public static function contact() {
		if ( ! self::supported() ) { return; }
		$checkout = WC()->checkout();
		$field = $checkout->get_checkout_fields( 'billing' )['billing_email'] ?? null;
		if ( ! $field ) { return; }
		echo '<section class="pod-sc-contact pod-sc-card" aria-labelledby="pod-sc-contact-title"><h2 id="pod-sc-contact-title">' . esc_html__( 'Contact', 'pod-simple-checkout' ) . '</h2>';
		woocommerce_form_field( 'billing_email', $field, $checkout->get_value( 'billing_email' ) );
		echo '</section>';
		wp_nonce_field( 'pod_sc_address', 'pod_sc_address_nonce', false );
		echo '<input type="hidden" name="pod_sc_us_form" value="1">';
	}

	public static function shipping_first() {
		if ( WC()->cart->needs_shipping_address() ) { echo '<h2 class="pod-sc-address-title">' . esc_html__( 'Shipping Address', 'pod-simple-checkout' ) . '</h2>'; }
		WC()->checkout()->checkout_form_shipping();
	}

	public static function billing_before() {
		self::$billing_render = self::supported();
		if ( ! self::$billing_render || ! WC()->cart->needs_shipping_address() ) { return; }
		$same = true;
		foreach ( self::ADDRESS as $name ) {
			$b = (string) WC()->checkout()->get_value( 'billing_' . $name );
			$s = (string) WC()->checkout()->get_value( 'shipping_' . $name );
			if ( $b !== $s && ( '' !== $b || '' !== $s ) ) { $same = false; }
		}
		// Unchecked without JS: both native address forms remain editable and required.
		echo '<p class="pod-sc-same-row" hidden><label><input type="checkbox" name="pod_sc_same_address" id="pod-sc-same-address" value="1" data-default-same="' . ( $same ? 'yes' : 'no' ) . '"> ' . esc_html__( 'Billing address is the same as shipping', 'pod-simple-checkout' ) . '</label></p>';
	}

	public static function field_html( $html, $key, $args, $value ) {
		return self::$billing_render && 'billing_email' === $key ? '' : $html;
	}

	public static function heading( $translated, $text, $domain ) {
		if ( ( ! did_action( 'wp' ) && ! wp_doing_ajax() ) || ! in_array( $text, array( 'Billing details', 'Your order' ), true ) || ! self::supported() ) { return $translated; }
		if ( 'Billing details' === $text ) { return __( 'Billing Address', 'pod-simple-checkout' ); }
		if ( 'Your order' === $text ) { return __( 'Your Order', 'pod-simple-checkout' ); }
		return $translated;
	}

	private static function scalar( $data, $key ) {
		return isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ? wc_clean( wp_unslash( (string) $data[ $key ] ) ) : '';
	}

	private static function verified( $data ) {
		return '1' === self::scalar( $data, 'pod_sc_us_form' ) && wp_verify_nonce( self::scalar( $data, 'pod_sc_address_nonce' ), 'pod_sc_address' );
	}

	public static function shipping_intent() {
		// Core verifies its checkout nonce before this hook, then processes/validates every field once.
		if ( ! self::supported() || ! self::verified( $_POST ) || ! WC()->cart->needs_shipping_address() ) { return; }
		$same = '1' === self::scalar( $_POST, 'pod_sc_same_address' );
		if ( $same || '1' === self::scalar( $_POST, 'pod_sc_shipping_primary' ) ) { $_POST['ship_to_different_address'] = 1; }
		if ( ! $same ) { return; }
		$fields = WC()->checkout()->get_checkout_fields();
		foreach ( self::ADDRESS as $name ) {
			if ( ! isset( $fields['billing'][ 'billing_' . $name ], $fields['shipping'][ 'shipping_' . $name ] ) ) { continue; }
			// Preserve WP-slashing; native get_posted_data() unslashes, cleans and runs field filters once.
			$_POST[ 'billing_' . $name ] = isset( $_POST[ 'shipping_' . $name ] ) && is_scalar( $_POST[ 'shipping_' . $name ] ) ? (string) $_POST[ 'shipping_' . $name ] : '';
		}
	}

	public static function ajax_address( $encoded ) {
		// This native hook runs after check_ajax_referer('update-order-review').
		parse_str( $encoded, $data );
		if ( ! self::supported() || ! self::verified( $data ) || '1' !== self::scalar( $data, 'pod_sc_same_address' ) || ! WC()->cart->needs_shipping_address() ) { return; }
		foreach ( array( 'country' => 'country', 'state' => 'state', 'postcode' => 'postcode', 'city' => 'city', 'address' => 'address_1', 'address_2' => 'address_2' ) as $native => $name ) {
			$_POST[ $native ] = wp_slash( self::scalar( $data, 'shipping_' . $name ) );
			$_POST[ 's_' . $native ] = $_POST[ $native ];
		}
	}

	public static function validate_adapter( $data, $errors ) {
		if ( '1' === self::scalar( $_POST, 'pod_sc_us_form' ) && ( ! self::verified( $_POST ) || ! self::supported() ) ) {
			$errors->add( 'pod_sc_layout_changed', __( 'Checkout compatibility has changed. Reload checkout and review the native address fields before ordering.', 'pod-simple-checkout' ) );
		}
	}

	public static function review_start() {
		if ( self::supported() ) { self::$shipping_html = ''; self::$capturing_review = true; ob_start(); }
	}

	public static function review_end() {
		if ( ! self::$capturing_review ) { return; }
		$html = ob_get_clean(); self::$capturing_review = false;
		echo self::shipping_section(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped/native template HTML.
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- unchanged native checkout hook output including payment.
	}

	public static function shipping_start() {
		if ( self::supported() ) { self::$capturing_shipping = true; ob_start(); }
	}

	public static function shipping_end() {
		if ( ! self::$capturing_shipping ) { return; }
		self::$shipping_html = ob_get_clean(); self::$capturing_shipping = false;
		echo '<tr class="pod-sc-shipping-total"><th>' . esc_html__( 'Shipping', 'pod-simple-checkout' ) . '</th><td>' . wp_kses_post( WC()->cart->get_cart_shipping_total() ) . '</td></tr>';
	}

	private static function shipping_section() {
		$html = '<section id="pod-sc-shipping-methods" class="pod-sc-card" aria-labelledby="pod-sc-shipping-title">';
		if ( WC()->cart->needs_shipping() ) {
			$html .= '<h2 id="pod-sc-shipping-title">' . esc_html__( 'Shipping Method', 'pod-simple-checkout' ) . '</h2>';
			$html .= self::$shipping_html ? '<table class="pod-sc-rates"><tbody>' . self::$shipping_html . '</tbody></table>' : '<p>' . esc_html__( 'Enter your address to see shipping methods.', 'pod-simple-checkout' ) . '</p>';
		}
		return $html . '</section>';
	}

	public static function fragments( $fragments ) {
		if ( self::supported() ) { $fragments['#pod-sc-shipping-methods'] = self::shipping_section(); }
		return $fragments;
	}

	public static function submit_open() {
		if ( ! self::supported() ) { return; }
		echo '<div class="pod-sc-submit"><div class="pod-sc-submit-total"><span>' . esc_html__( 'Total', 'pod-simple-checkout' ) . '</span><strong>';
		wc_cart_totals_order_total_html();
		echo '</strong></div>';
	}
}
