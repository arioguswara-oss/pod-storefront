<?php
/** Frontend integration without theme edits or activation migrations. */
defined( 'ABSPATH' ) || exit;

final class POD_SC_Plugin {
	public static function boot() {
		if ( ! class_exists( 'WooCommerce' ) || ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '11.3', '<' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
			return;
		}
		add_action( 'init', static function () { load_plugin_textdomain( 'pod-simple-checkout', false, dirname( plugin_basename( POD_SC_FILE ) ) . '/languages' ); } );
		add_action( 'admin_init', array( 'POD_SC_Settings', 'register' ) );
		add_action( 'admin_menu', array( 'POD_SC_Settings', 'menu' ) );
		add_filter( 'option_page_capability_pod_sc_settings', static function () { return 'manage_woocommerce'; } );
		add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'buttons' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 30 );
		add_action( 'template_redirect', array( __CLASS__, 'dynamic_headers' ), 1 );
		POD_SC_Cart::register();
		POD_SC_Checkout::register();
		POD_SC_US_Checkout::register();
		POD_SC_Shopping_UX::register();
	}

	public static function dependency_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'POD Simple Checkout needs WooCommerce 11.3 or newer active. Its shopping-flow enhancements are currently inactive; no store settings have been changed.', 'pod-simple-checkout' ) . '</p></div>';
		}
	}

	public static function supported_product( $product ) {
		return $product instanceof WC_Product && $product->is_type( array( 'simple', 'variable' ) ) && $product->is_purchasable() && $product->is_in_stock();
	}

	public static function buttons() {
		global $product;
		if ( ! self::supported_product( $product ) ) { return; }
		$ajax = POD_SC_Settings::enabled( 'ajax' ) && apply_filters( 'pod_sc_ajax_supported', true, $product );
		?>
		<input type="hidden" name="pod_sc_product_id" value="<?php echo esc_attr( $product->get_id() ); ?>" data-pod-sc-ajax="<?php echo $ajax ? 'yes' : 'no'; ?>">
		<input type="hidden" name="pod_sc_request_id" value="<?php echo esc_attr( wp_generate_uuid4() ); ?>">
		<?php wp_nonce_field( 'pod_sc_add', 'pod_sc_nonce', false ); ?>
		<?php if ( POD_SC_Settings::enabled( 'buy_now' ) ) : ?>
			<button type="submit" class="button pod-sc-buy-now" name="pod_sc_buy_now" value="<?php echo esc_attr( $product->get_id() ); ?>"><?php esc_html_e( 'Buy Now', 'pod-simple-checkout' ); ?></button>
		<?php endif; ?>
		<div class="pod-sc-messages" role="status" aria-live="polite" tabindex="-1"></div>
		<?php
	}

	public static function assets() {
		$enhance_product = ( is_product() || ( POD_SC_Shopping_UX::enabled() && ! is_checkout() && ! is_account_page() ) ) && ( POD_SC_Settings::enabled( 'buy_now' ) || POD_SC_Settings::enabled( 'ajax' ) );
		if ( $enhance_product ) {
			wp_enqueue_style( 'pod-simple-checkout-product', POD_SC_URL . 'assets/product.css', array(), POD_SC_VERSION );
			wp_enqueue_script( 'pod-simple-checkout', POD_SC_URL . 'assets/product.js', array( 'jquery', 'wc-add-to-cart', 'wc-cart-fragments' ), POD_SC_VERSION, true );
			$config = array(
				'contextUrl' => WC_AJAX::get_endpoint( 'pod_sc_context' ), 'addUrl' => WC_AJAX::get_endpoint( 'pod_sc_add' ), 'cartUrl' => wc_get_cart_url(),
				'messages' => array(
					'variation' => __( 'Please select all required product options before continuing.', 'pod-simple-checkout' ),
					'working' => __( 'Adding to your bag…', 'pod-simple-checkout' ),
					'network' => __( 'The connection was interrupted. Retry to check this addition, or view your bag. Your request will not be added twice.', 'pod-simple-checkout' ),
					'cart' => __( 'View bag', 'pod-simple-checkout' ),
				),
			);
			wp_add_inline_script( 'pod-simple-checkout', 'window.podSC = ' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
		}
		if ( POD_SC_Checkout::enhanced() ) {
			wp_enqueue_style( 'pod-simple-checkout-ui', POD_SC_URL . 'assets/checkout.css', array(), POD_SC_VERSION );
			if ( POD_SC_US_Checkout::supported() ) { wp_enqueue_script( 'pod-sc-us-checkout', POD_SC_URL . 'assets/checkout.js', array( 'jquery', 'wc-checkout' ), POD_SC_VERSION, true ); wp_localize_script( 'pod-sc-us-checkout', 'podSCPayNow', POD_SC_US_Checkout::pay_now_config() ); }
		}
	}

	public static function no_cache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
		wc_nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'POD Simple Checkout dynamic response' );
	}

	public static function dynamic_headers() {
		if ( is_cart() || is_checkout() || is_account_page() ) { self::no_cache(); }
	}
}
