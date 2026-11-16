<?php
/** WooCommerce cart commands, CSRF checks and short-lived request deduplication. */
defined( 'ABSPATH' ) || exit;

final class POD_SC_Cart {
	const TTL = 900;
	private static $request_lock = null;

	public static function register() {
		add_action( 'init', array( __CLASS__, 'early_lock' ), -1 );
		add_action( 'shutdown', array( __CLASS__, 'release_request_lock' ), PHP_INT_MAX );
		add_action( 'wp_loaded', array( __CLASS__, 'guard_native_handler' ), 0 );
		add_action( 'wc_ajax_pod_sc_context', array( __CLASS__, 'context' ) );
		add_action( 'wc_ajax_pod_sc_add', array( __CLASS__, 'ajax' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'fallback' ), 19 );
	}

	public static function early_lock() {
		$endpoint = isset( $_GET['wc-ajax'] ) && is_scalar( $_GET['wc-ajax'] ) ? sanitize_key( wp_unslash( $_GET['wc-ajax'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lock selection does not authorize a cart command.
		$is_ajax = in_array( $endpoint, array( 'pod_sc_add', 'pod_sc_context', 'pod_sc_bag', 'pod_sc_bag_change', 'pod_sc_product' ), true );
		$is_buy = 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) && ! empty( $_POST['pod_sc_buy_now'] ); // phpcs:ignore WordPress.Security.NonceVerification -- nonce checked before any mutation.
		if ( ! $is_ajax && ! $is_buy ) { return; }
		// Lock before WooCommerce init reads session data, and keep it until native shutdown saves finish.
		// The public cookie verifier does not initialize or write a WC session.
		$probe = new WC_Session_Handler();
		$cookie = $probe->get_session_cookie();
		$identity = is_user_logged_in() ? 'user:' . get_current_user_id() : ( $cookie ? 'guest:' . $cookie[0] : 'new:' . wp_generate_uuid4() );
		try {
			self::$request_lock = self::lock( hash_hmac( 'sha256', $identity, wp_salt( 'nonce' ) ) );
		} catch ( RuntimeException $error ) {
			POD_SC_Plugin::no_cache();
			if ( $is_ajax ) {
				wp_send_json( array( 'success' => false, 'retryable' => true, 'notices' => '<ul class="woocommerce-error" role="alert"><li>' . esc_html( $error->getMessage() ) . '</li></ul>' ), 409 );
			}
			wp_die( esc_html( $error->getMessage() ), esc_html__( 'Bag update in progress', 'pod-simple-checkout' ), array( 'response' => 409, 'back_link' => true ) );
		}
	}

	public static function release_request_lock() {
		if ( self::$request_lock ) { self::unlock( self::$request_lock ); self::$request_lock = null; }
	}

	public static function guard_native_handler() {
		$endpoint = isset( $_GET['wc-ajax'] ) && is_scalar( $_GET['wc-ajax'] ) ? sanitize_key( wp_unslash( $_GET['wc-ajax'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- prevents native mutation; does not authorize one.
		if ( in_array( $endpoint, array( 'pod_sc_add', 'pod_sc_context', 'pod_sc_bag', 'pod_sc_bag_change', 'pod_sc_product' ), true ) ) {
			// WC_Form_Handler runs before WC AJAX dispatch. Only our verified command may add this POST.
			unset( $_REQUEST['add-to-cart'] );
		}
	}

	private static function session() {
		if ( ! WC()->cart || ! WC()->session ) { wc_load_cart(); }
		if ( ! WC()->session->has_session() ) { WC()->session->set_customer_session_cookie( true ); }
	}

	private static function save() {
		WC()->cart->calculate_totals();
		if ( is_callable( array( WC()->session, 'save_data' ) ) ) { WC()->session->save_data(); }
	}

	public static function context() {
		POD_SC_Plugin::no_cache();
		self::session();
		$token = WC()->session->get( 'pod_sc_csrf' );
		if ( ! is_string( $token ) || strlen( $token ) < 32 ) {
			$token = wp_generate_password( 48, false, false );
			WC()->session->set( 'pod_sc_csrf', $token );
		}
		if ( is_callable( array( WC()->session, 'save_data' ) ) ) { WC()->session->save_data(); }
		wp_send_json( array( 'nonce' => wp_create_nonce( 'pod_sc_add' ), 'session_token' => $token, 'request_id' => wp_generate_uuid4(), 'ajax' => POD_SC_Settings::enabled( 'ajax' ) ) );
	}

	private static function scalar( $data, $key, $default = '' ) {
		return isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ? wc_clean( wp_unslash( (string) $data[ $key ] ) ) : $default;
	}

	private static function canonical( $data ) {
		if ( ! is_array( $data ) ) { return $data; }
		ksort( $data );
		foreach ( $data as $key => $value ) { $data[ $key ] = self::canonical( $value ); }
		return $data;
	}

	/** Atomic lock in a non-autoloaded plugin option. Never writes WooCommerce tables. */
	private static function lock( $scope ) {
		global $wpdb;
		$key = 'pod_sc_lock_' . $scope;
		$value = wp_json_encode( array( 'owner' => wp_generate_uuid4(), 'expires' => time() + 300 ) );
		// add_option() can upsert a competing value after a cached miss. INSERT IGNORE is atomic.
		$insert = static function () use ( $wpdb, $key, $value ) {
			$created = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)", $key, $value, 'no' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- atomic, non-autoloaded plugin metadata only.
			if ( 1 === $created ) { wp_cache_set( $key, $value, 'options' ); wp_cache_delete( 'notoptions', 'options' ); return true; }
			return false;
		};
		if ( $insert() ) { return array( $key, $value ); }
		$previous = get_option( $key );
		$decoded = is_string( $previous ) ? json_decode( $previous, true ) : null;
		if ( is_array( $decoded ) && isset( $decoded['expires'] ) && $decoded['expires'] < time() ) {
			self::unlock( array( $key, $previous ) );
			if ( $insert() ) { return array( $key, $value ); }
		}
		throw new RuntimeException( __( 'Another bag update is in progress. Please wait a moment and retry.', 'pod-simple-checkout' ), 409 );
	}

	private static function unlock( $lock ) {
		global $wpdb;
		// Compare-and-delete only this exact plugin lock, so an expired owner cannot remove a new lock.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $lock[0], $lock[1] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- atomic ownership check; no WooCommerce records are touched.
		wp_cache_delete( $lock[0], 'options' );
		wp_cache_delete( 'notoptions', 'options' );
	}

	public static function process( $data, $ajax ) {
		self::session();
		if ( ! wp_verify_nonce( self::scalar( $data, 'pod_sc_nonce' ), 'pod_sc_add' ) ) {
			throw new RuntimeException( __( 'This form has expired. Reload this product page and try again.', 'pod-simple-checkout' ), 403 );
		}
		if ( $ajax ) {
			$expected = WC()->session->get( 'pod_sc_csrf', '' );
			if ( ! is_string( $expected ) || ! $expected || ! hash_equals( $expected, self::scalar( $data, 'pod_sc_session_token' ) ) ) {
				throw new RuntimeException( __( 'Your bag session has changed. Reload the product and try again.', 'pod-simple-checkout' ), 403 );
			}
			if ( ! POD_SC_Settings::enabled( 'ajax' ) ) { throw new RuntimeException( __( 'AJAX is disabled. Reload and use the standard product form.', 'pod-simple-checkout' ), 400 ); }
		}
		$buy = 'buy' === self::scalar( $data, 'pod_sc_intent' ) || absint( self::scalar( $data, 'pod_sc_buy_now' ) ) > 0;
		if ( $buy && ! POD_SC_Settings::enabled( 'buy_now' ) ) { throw new RuntimeException( __( 'Buy Now is currently disabled. Please use Add to Cart.', 'pod-simple-checkout' ), 400 ); }
		$id = self::scalar( $data, 'pod_sc_request_id' );
		if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id ) ) {
			throw new RuntimeException( __( 'Invalid request identifier. Reload this product page.', 'pod-simple-checkout' ), 400 );
		}
		$scope = hash_hmac( 'sha256', (string) WC()->session->get_customer_id(), wp_salt( 'nonce' ) );
		$request_key = 'pod_sc_req_' . hash_hmac( 'sha256', $scope . $id, wp_salt( 'nonce' ) );
		$fingerprint_data = wp_unslash( $data );
		foreach ( array( 'pod_sc_nonce', 'pod_sc_session_token', 'pod_sc_request_id' ) as $field ) { unset( $fingerprint_data[ $field ] ); }
		$fingerprint = hash( 'sha256', wp_json_encode( self::canonical( $fingerprint_data ) ) );
		$owns_lock = ! self::$request_lock;
		$lock = $owns_lock ? self::lock( $scope ) : self::$request_lock;
		try {
			$previous = get_transient( $request_key );
			if ( is_array( $previous ) ) {
				if ( ! hash_equals( $previous['fingerprint'], $fingerprint ) ) { throw new RuntimeException( __( 'This request was already used with different details. Check your bag and reload the product.', 'pod-simple-checkout' ), 409 ); }
				wc_add_notice( __( 'This item was already added. Your bag has not been changed again.', 'pod-simple-checkout' ), 'success' );
				$previous['replayed'] = true;
				return $previous;
			}
			return self::add( $data, $buy, $ajax, $request_key, $fingerprint );
		} finally {
			if ( $owns_lock ) { self::unlock( $lock ); }
		}
	}

	private static function add( $data, $buy, $ajax, $request_key, $fingerprint ) {
		$product_id = apply_filters( 'woocommerce_add_to_cart_product_id', absint( self::scalar( $data, 'pod_sc_product_id' ) ) );
		$product = wc_get_product( $product_id );
		$_REQUEST['add-to-cart'] = $product_id; // Native extension validation context; the core handler has already been bypassed.
		if ( ! POD_SC_Plugin::supported_product( $product ) || 'publish' !== $product->get_status() ) { throw new RuntimeException( __( 'This product is not available for purchase.', 'pod-simple-checkout' ), 400 ); }
		if ( $ajax && ! apply_filters( 'pod_sc_ajax_supported', true, $product ) ) { throw new RuntimeException( __( 'This product requires the standard Add to Cart form. Reload and try again.', 'pod-simple-checkout' ), 400 ); }
		$raw_quantity = self::scalar( $data, 'quantity', '1' );
		if ( ! is_numeric( $raw_quantity ) || ! is_finite( (float) $raw_quantity ) || (float) $raw_quantity <= 0 ) { throw new RuntimeException( __( 'Please enter a valid quantity.', 'pod-simple-checkout' ), 400 ); }
		$quantity = wc_stock_amount( $raw_quantity );
		$variation_id = absint( self::scalar( $data, 'variation_id' ) );
		$attributes = array();
		$purchase_product = $product;
		if ( $product->is_type( 'variable' ) ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation || ! $variation->is_type( 'variation' ) || $variation->get_parent_id() !== $product->get_id() ) { throw new RuntimeException( __( 'Please select all required product options before continuing.', 'pod-simple-checkout' ), 400 ); }
			foreach ( $product->get_variation_attributes() as $name => $options ) {
				$field = 'attribute_' . sanitize_title( $name );
				$value = self::scalar( $data, $field );
				if ( '' === $value ) { throw new RuntimeException( __( 'Please select all required product options before continuing.', 'pod-simple-checkout' ), 400 ); }
				$attributes[ $field ] = $value;
			}
			$purchase_product = $variation;
		} elseif ( $variation_id ) { throw new RuntimeException( __( 'Invalid product variation.', 'pod-simple-checkout' ), 400 ); }
		$minimum = $purchase_product->get_min_purchase_quantity();
		$maximum = $purchase_product->get_max_purchase_quantity();
		if ( $quantity < $minimum || ( $maximum > 0 && $quantity > $maximum ) ) { throw new RuntimeException( __( 'The requested quantity is outside the available purchase limits.', 'pod-simple-checkout' ), 400 ); }
		$cart = WC()->cart;
		$before = $cart->get_cart();
		$removed = $cart->get_removed_cart_contents();
		$coupons = $cart->get_applied_coupons();
		$replace = $buy && POD_SC_Settings::enabled( 'replace_cart' ) && 'sheet' !== self::scalar( $data, 'pod_sc_surface' );
		try {
			// Validate replacement against a candidate cart in memory; restore on any failure.
			if ( $replace ) { $cart->set_cart_contents( array() ); }
			$valid = $product->is_type( 'variable' ) ? apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $attributes ) : apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
			if ( ! $valid ) { throw new RuntimeException( __( 'Please check the product options and quantity, then try again.', 'pod-simple-checkout' ), 400 ); }
			$key = $cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes );
			if ( ! $key ) { throw new RuntimeException( __( 'WooCommerce could not add this item. Please check its stock and options.', 'pod-simple-checkout' ), 400 ); }
			if ( $replace ) {
				$accepted = $cart->get_cart();
				$cart->set_cart_contents( array_replace( $before, $accepted ) );
				// Native removal hooks see the accepted candidate before old lines leave.
				foreach ( array_keys( $before ) as $existing ) { if ( ! isset( $accepted[ $existing ] ) ) { $cart->remove_cart_item( $existing ); } }
			}
			self::save();
			$redirect = '';
			if ( $buy && POD_SC_Settings::enabled( 'direct_checkout' ) ) { $redirect = wc_get_checkout_url(); }
			elseif ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) { $redirect = wc_get_cart_url(); }
			$result = array( 'fingerprint' => $fingerprint, 'redirect' => $redirect, 'cart_key' => $key, 'replayed' => false );
			if ( ! set_transient( $request_key, $result, self::TTL ) ) { throw new RuntimeException( __( 'Unable to confirm this request safely. Please retry.', 'pod-simple-checkout' ), 503 ); }
			wc_add_to_cart_message( array( $product_id => $quantity ), true );
			return $result;
		} catch ( Throwable $error ) {
			$cart->set_cart_contents( $before );
			$cart->set_removed_cart_contents( $removed );
			$cart->set_applied_coupons( $coupons );
			self::save();
			throw $error;
		}
	}

	private static function fragments() {
		ob_start();
		woocommerce_mini_cart();
		$mini_cart = ob_get_clean();
		return apply_filters( 'woocommerce_add_to_cart_fragments', array( 'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>' ) );
	}

	public static function ajax() {
		POD_SC_Plugin::no_cache();
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_send_json( array( 'success' => false, 'notices' => esc_html__( 'Use POST for bag updates.', 'pod-simple-checkout' ) ), 405 ); }
		try {
			$result = self::process( $_POST, true ); // phpcs:ignore WordPress.Security.NonceVerification -- process validates nonce and session token before mutation.
			wp_send_json( array( 'success' => true, 'redirect' => $result['redirect'], 'replayed' => $result['replayed'], 'fragments' => self::fragments(), 'cart_hash' => WC()->cart->get_cart_hash(), 'count' => WC()->cart->get_cart_contents_count(), 'notices' => wp_kses_post( wc_print_notices( true ) ) ) );
		} catch ( Throwable $error ) {
			if ( ! wc_notice_count( 'error' ) ) { wc_add_notice( $error instanceof RuntimeException ? $error->getMessage() : __( 'Unable to finish this bag update. Check your bag and retry.', 'pod-simple-checkout' ), 'error' ); }
			$status = in_array( (int) $error->getCode(), array( 400, 403, 409, 503 ), true ) ? (int) $error->getCode() : 400;
			wp_send_json( array( 'success' => false, 'notices' => wp_kses_post( wc_print_notices( true ) ), 'retryable' => in_array( $status, array( 409, 503 ), true ) ), $status );
		}
	}

	public static function fallback() {
		if ( isset( $_GET['wc-ajax'] ) && is_scalar( $_GET['wc-ajax'] ) && in_array( sanitize_key( wp_unslash( $_GET['wc-ajax'] ) ), array( 'pod_sc_add', 'pod_sc_context', 'pod_sc_bag', 'pod_sc_bag_change', 'pod_sc_product' ), true ) ) { return; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- AJAX has its own verified command.
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['pod_sc_buy_now'] ) ) { return; } // phpcs:ignore WordPress.Security.NonceVerification -- process verifies nonce before any mutation.
		POD_SC_Plugin::no_cache();
		$product_id = absint( self::scalar( $_POST, 'pod_sc_product_id' ) );
		$url = get_permalink( $product_id );
		try {
			$_POST['add-to-cart'] = $product_id;
			$_REQUEST['add-to-cart'] = $product_id;
			$result = self::process( $_POST, false );
			if ( $result['redirect'] ) { $url = $result['redirect']; }
		} catch ( Throwable $error ) {
			if ( ! wc_notice_count( 'error' ) ) { wc_add_notice( $error instanceof RuntimeException ? $error->getMessage() : __( 'Unable to finish this bag update. Check your bag and retry.', 'pod-simple-checkout' ), 'error' ); }
		}
		// This POST was handled once; the native form handler must not add it again.
		unset( $_POST['add-to-cart'], $_REQUEST['add-to-cart'] );
		wp_safe_redirect( $url ? $url : wc_get_cart_url(), 303 );
		exit;
	}
}
