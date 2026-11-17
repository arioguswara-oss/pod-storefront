<?php
/** Progressive shopping dialogs; WooCommerce remains the only cart and pricing engine. */
defined( 'ABSPATH' ) || exit;

final class POD_SC_Shopping_UX {
	public static function enabled() {
		return POD_SC_Settings::enabled( 'ajax' ) && (bool) apply_filters( 'pod_sc_shopping_sheets_enabled', true );
	}

	public static function register() {
		add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'catalog_button' ), 20, 3 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 40 );
		add_action( 'wp_footer', array( __CLASS__, 'dialog' ) );
		add_action( 'wc_ajax_pod_sc_product', array( __CLASS__, 'product' ) );
		add_action( 'wc_ajax_pod_sc_bag', array( __CLASS__, 'bag' ) );
		add_action( 'wc_ajax_pod_sc_bag_change', array( __CLASS__, 'change' ) );
	}

	public static function catalog_button( $html, $product, $args ) {
		if ( ! self::enabled() || ! POD_SC_Plugin::supported_product( $product ) || ! apply_filters( 'pod_sc_sheet_product_supported', true, $product ) || ! apply_filters( 'pod_sc_ajax_supported', true, $product ) ) { return $html; }
		return '<a class="button pod-sc-choose" data-pod-sc-product="' . esc_attr( $product->get_id() ) . '" href="' . esc_url( $product->get_permalink() ) . '" aria-haspopup="dialog" aria-label="' . esc_attr( sprintf( __( 'Choose options for %s', 'pod-simple-checkout' ), $product->get_name() ) ) . '">' . esc_html__( 'Choose Options', 'pod-simple-checkout' ) . '</a>';
	}

	public static function assets() {
		if ( ! self::enabled() || is_checkout() || is_account_page() ) { return; }
		wp_enqueue_style( 'pod-sc-sheets', POD_SC_URL . 'assets/shopping.css', array(), POD_SC_VERSION );
		wp_enqueue_script( 'pod-sc-sheets', POD_SC_URL . 'assets/shopping.js', array( 'pod-simple-checkout', 'wc-add-to-cart-variation' ), POD_SC_VERSION, true );
		wp_add_inline_script( 'pod-sc-sheets', 'window.podSheets = ' . wp_json_encode( array(
			'productUrl' => WC_AJAX::get_endpoint( 'pod_sc_product' ),
			'cartUrl' => wc_get_cart_url(),
			'bagUrl' => WC_AJAX::get_endpoint( 'pod_sc_bag' ),
			'changeUrl' => WC_AJAX::get_endpoint( 'pod_sc_bag_change' ),
		), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
	}

	public static function dialog() {
		if ( ! self::enabled() || is_checkout() || is_account_page() ) { return; }
		?>
		<dialog id="pod-sc-sheet" class="pod-sc-sheet" aria-labelledby="pod-sc-sheet-title">
			<header class="pod-sc-sheet-header"><h2 id="pod-sc-sheet-title"><?php esc_html_e( 'Choose your options', 'pod-simple-checkout' ); ?></h2><button type="button" class="pod-sc-close" aria-label="<?php esc_attr_e( 'Close dialog', 'pod-simple-checkout' ); ?>">×</button></header>
			<div class="pod-sc-sheet-body" aria-live="polite"></div>
		</dialog>
		<?php
	}

	public static function product() {
		POD_SC_Plugin::no_cache();
		$id = isset( $_GET['product_id'] ) && is_scalar( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only public catalog lookup.
		$product = wc_get_product( $id );
		if ( ! self::enabled() || ! POD_SC_Plugin::supported_product( $product ) || 'publish' !== $product->get_status() || post_password_required( $id ) || ! apply_filters( 'pod_sc_sheet_product_supported', true, $product ) || ! apply_filters( 'pod_sc_ajax_supported', true, $product ) ) {
			wp_send_json( array( 'success' => false ), 404 );
		}
		// Render the native form, including extension hooks and WooCommerce's variation data/limits.
		$previous_product = $GLOBALS['product'] ?? null;
		$previous_post = $GLOBALS['post'] ?? null;
		$GLOBALS['product'] = $product;
		$GLOBALS['post'] = get_post( $id );
		ob_start();
		?>
		<div class="pod-sc-product-heading">
			<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- native WooCommerce image HTML. ?>
			<div><h3><?php echo esc_html( $product->get_name() ); ?></h3><div class="pod-sc-sheet-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php esc_html_e( 'View full details', 'pod-simple-checkout' ); ?></a></div>
		</div>
		<?php
		add_filter( 'woocommerce_available_variation', static function ( $data, $parent, $variation ) { $data['price_html'] = '<span class="price">' . $variation->get_price_html() . '</span>'; return $data; }, 20, 3 );
		woocommerce_template_single_add_to_cart();
		$html = ob_get_clean();
		$GLOBALS['product'] = $previous_product;
		$GLOBALS['post'] = $previous_post;
		wp_send_json( array( 'success' => true, 'html' => $html ) );
	}

	/** Always render totals and prices through WooCommerce, including extension filters. */
	public static function bag_html() {
		ob_start();
		if ( WC()->cart->is_empty() ) {
			echo '<div class="pod-sc-empty"><p>' . esc_html__( 'Your bag is empty.', 'pod-simple-checkout' ) . '</p><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Continue shopping', 'pod-simple-checkout' ) . '</a></div>';
		} else {
			echo '<ul class="pod-sc-bag-items">';
			foreach ( WC()->cart->get_cart() as $key => $item ) {
				$product = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );
				if ( ! $product || ! $product->exists() || ! apply_filters( 'woocommerce_widget_cart_item_visible', true, $item, $key ) ) { continue; }
				$args = apply_filters( 'woocommerce_quantity_input_args', array( 'input_value' => $item['quantity'], 'min_value' => $product->get_min_purchase_quantity(), 'max_value' => $product->get_max_purchase_quantity(), 'step' => 1 ), $product );
				?>
				<li class="pod-sc-bag-item" data-key="<?php echo esc_attr( $key ); ?>">
					<a class="pod-sc-bag-image" href="<?php echo esc_url( $product->get_permalink( $item ) ); ?>"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'woocommerce_thumbnail' ), $item, $key ) ); ?></a>
					<div class="pod-sc-bag-detail"><a class="pod-sc-bag-name" href="<?php echo esc_url( $product->get_permalink( $item ) ); ?>"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', esc_html( $product->get_name() ), $item, $key ) ); ?></a>
					<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $item ) ); ?>
					<div class="pod-sc-line-price"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $item['quantity'] ), $item, $key ) ); ?></div>
					<div class="pod-sc-line-actions"><div class="quantity"><button class="pod-sc-step" type="button" data-delta="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'pod-simple-checkout' ); ?>">−</button><input class="qty" type="number" aria-label="<?php echo esc_attr( sprintf( __( 'Quantity for %s', 'pod-simple-checkout' ), $product->get_name() ) ); ?>" value="<?php echo esc_attr( $item['quantity'] ); ?>" min="<?php echo esc_attr( $args['min_value'] ); ?>" <?php if ( $args['max_value'] > 0 ) : ?>max="<?php echo esc_attr( $args['max_value'] ); ?>"<?php endif; ?> step="<?php echo esc_attr( $args['step'] ); ?>"><button class="pod-sc-step" type="button" data-delta="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'pod-simple-checkout' ); ?>">+</button></div><button class="pod-sc-remove" type="button" aria-label="<?php echo esc_attr( sprintf( __( 'Remove %s', 'pod-simple-checkout' ), $product->get_name() ) ); ?>"><?php esc_html_e( 'Remove', 'pod-simple-checkout' ); ?></button></div>
					</div>
				</li>
				<?php
			}
			echo '</ul><details class="pod-sc-native-totals"><summary>' . esc_html__( 'Shipping, discounts & totals', 'pod-simple-checkout' ) . '</summary>';
			// Native totals are an estimate here. Shipping/address editing stays on native cart/checkout.
			ob_start();
			woocommerce_cart_totals();
			$totals = new WP_HTML_Tag_Processor( ob_get_clean() );
			while ( $totals->next_tag() ) {
				if ( in_array( $totals->get_tag(), array( 'INPUT', 'SELECT', 'BUTTON' ), true ) ) { $totals->set_attribute( 'disabled', true ); }
				if ( $totals->has_class( 'shipping-calculator-button' ) ) { $totals->remove_class( 'shipping-calculator-button' ); $totals->set_attribute( 'href', wc_get_cart_url() ); }
			}
			echo $totals->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- native WooCommerce template with constrained attributes.
			echo '<p>' . esc_html__( 'Edit shipping and coupons in the full bag or at checkout.', 'pod-simple-checkout' ) . '</p>';
			echo '</details><footer class="pod-sc-bag-footer"><div><span>' . esc_html__( 'Subtotal', 'pod-simple-checkout' ) . '</span><strong>';
			wc_cart_totals_subtotal_html();
			echo '</strong></div><p>' . esc_html__( 'Shipping and taxes confirmed at checkout.', 'pod-simple-checkout' ) . '</p><a class="pod-sc-checkout" href="' . esc_url( wc_get_checkout_url() ) . '">' . esc_html__( 'Checkout', 'pod-simple-checkout' ) . '</a><a class="pod-sc-full-bag" href="' . esc_url( wc_get_cart_url() ) . '">' . esc_html__( 'View full bag & coupons', 'pod-simple-checkout' ) . '</a></footer>';
		}
		return ob_get_clean();
	}

	private static function response( $extra = array() ) {
		WC()->cart->calculate_totals();
		wp_send_json( array_merge( array( 'success' => true, 'html' => self::bag_html(), 'count' => WC()->cart->get_cart_contents_count(), 'cart_hash' => WC()->cart->get_cart_hash(), 'fragments' => POD_SC_Cart::fragments(), 'notices' => wp_kses_post( wc_print_notices( true ) ) ), $extra ) );
	}

	public static function bag() {
		POD_SC_Plugin::no_cache();
		if ( ! self::enabled() ) { wp_send_json( array( 'success' => false ), 404 ); }
		self::response();
	}

	/** Absolute quantities + session-scoped command IDs make an uncertain retry safe. */
	public static function change() {
		POD_SC_Plugin::no_cache();
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_send_json( array( 'success' => false ), 405 ); }
		try {
			$data = wc_clean( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification -- verified below before any mutation.
			$scalar = static function ( $key ) use ( $data ) { return isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ? (string) $data[ $key ] : ''; };
			$expected = WC()->session->get( 'pod_sc_csrf', '' );
			if ( ! self::enabled() || ! wp_verify_nonce( $scalar( 'pod_sc_nonce' ), 'pod_sc_add' ) || ! is_string( $expected ) || ! $expected || ! hash_equals( $expected, $scalar( 'pod_sc_session_token' ) ) ) { throw new RuntimeException( __( 'Your bag session has changed. Reopen your bag and retry.', 'pod-simple-checkout' ), 403 ); }
			$id = $scalar( 'pod_sc_request_id' );
			$key = $scalar( 'key' );
			$action = $scalar( 'action' );
			if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id ) || ! preg_match( '/^[a-f0-9]{32}$/', $key ) || ! in_array( $action, array( 'quantity', 'remove' ), true ) ) { throw new RuntimeException( __( 'Invalid bag update. Reopen your bag.', 'pod-simple-checkout' ), 400 ); }
			$request_key = 'pod_sc_bag_req_' . hash_hmac( 'sha256', WC()->session->get_customer_id() . $id, wp_salt( 'nonce' ) );
			$fingerprint = hash( 'sha256', wp_json_encode( array( $key, $action, $scalar( 'quantity' ) ) ) );
			$previous = get_transient( $request_key );
			if ( $previous ) {
				if ( ! hash_equals( $previous, $fingerprint ) ) { throw new RuntimeException( __( 'This request was already used. Reopen your bag to see the latest quantities.', 'pod-simple-checkout' ), 409 ); }
				self::response( array( 'replayed' => true ) );
			}
			$item = WC()->cart->get_cart_item( $key );
			if ( ! $item ) { throw new RuntimeException( __( 'This item is no longer in your bag. Reopen your bag to refresh.', 'pod-simple-checkout' ), 409 ); }
			$before = WC()->cart->get_cart();
			$removed = WC()->cart->get_removed_cart_contents();
			try {
				if ( 'remove' === $action ) {
					if ( ! WC()->cart->remove_cart_item( $key ) ) { throw new RuntimeException( __( 'This item cannot be removed here. Use the full bag.', 'pod-simple-checkout' ), 400 ); }
				} else {
					$raw = $scalar( 'quantity' );
					$quantity = apply_filters( 'woocommerce_stock_amount_cart_item', wc_stock_amount( $raw ), $key );
					$product = $item['data'];
					$args = apply_filters( 'woocommerce_quantity_input_args', array( 'input_value' => $item['quantity'], 'min_value' => $product->get_min_purchase_quantity(), 'max_value' => $product->get_max_purchase_quantity(), 'step' => 1 ), $product );
					if ( ! is_numeric( $raw ) || ! is_finite( (float) $raw ) || (float) $raw !== (float) $quantity || $quantity < max( 1, $args['min_value'] ) || ( $args['max_value'] > 0 && $quantity > $args['max_value'] ) || ( $product->is_sold_individually() && $quantity > 1 ) || ( is_numeric( $args['step'] ) && $args['step'] > 0 && abs( ( ( $quantity - $args['min_value'] ) / $args['step'] ) - round( ( $quantity - $args['min_value'] ) / $args['step'] ) ) > 0.00001 ) ) { throw new RuntimeException( __( 'Choose a quantity within the available purchase limits. Use Remove to delete an item.', 'pod-simple-checkout' ), 400 ); }
					if ( ! apply_filters( 'woocommerce_update_cart_validation', true, $key, $item, $quantity ) ) { throw new RuntimeException( __( 'This quantity was not accepted. Check your options in the full bag.', 'pod-simple-checkout' ), 400 ); }
					WC()->cart->set_quantity( $key, $quantity, false );
					$stock = WC()->cart->check_cart_item_stock();
					if ( is_wp_error( $stock ) ) { throw new RuntimeException( $stock->get_error_message(), 400 ); }
				}
				apply_filters( 'woocommerce_update_cart_action_cart_updated', true );
				WC()->cart->calculate_totals();
				if ( ! set_transient( $request_key, $fingerprint, POD_SC_Cart::TTL ) ) { throw new RuntimeException( __( 'Unable to confirm this update. Retry safely.', 'pod-simple-checkout' ), 503 ); }
				WC()->session->save_data();
			} catch ( Throwable $error ) {
				WC()->cart->set_cart_contents( $before );
				WC()->cart->set_removed_cart_contents( $removed );
				WC()->cart->calculate_totals();
				throw $error;
			}
			self::response( array( 'replayed' => false ) );
		} catch ( Throwable $error ) {
			$status = in_array( (int) $error->getCode(), array( 400, 403, 409, 503 ), true ) ? (int) $error->getCode() : 400;
			wp_send_json( array( 'success' => false, 'message' => $error instanceof RuntimeException ? wp_strip_all_tags( $error->getMessage() ) : __( 'Unable to update your bag. Please retry or use the full bag.', 'pod-simple-checkout' ), 'notices' => wp_kses_post( wc_print_notices( true ) ) ), $status );
		}
	}
}
