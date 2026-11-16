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
}
