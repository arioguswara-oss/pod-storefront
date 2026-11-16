<?php
/** Settings are saved only through an authorized WordPress Settings API form. */
defined( 'ABSPATH' ) || exit;

final class POD_SC_Settings {
	const OPTION = 'pod_sc_settings';

	public static function defaults() {
		return array( 'buy_now' => 'yes', 'ajax' => 'yes', 'direct_checkout' => 'yes', 'checkout_ui' => 'yes', 'replace_cart' => 'no' );
	}

	public static function all() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function enabled( $key ) {
		$settings = self::all();
		return isset( $settings[ $key ] ) && 'yes' === $settings[ $key ];
	}

	public static function sanitize( $value ) {
		$result = array();
		foreach ( self::defaults() as $key => $default ) {
			$result[ $key ] = is_array( $value ) && isset( $value[ $key ] ) && 'yes' === $value[ $key ] ? 'yes' : 'no';
		}
		return $result;
	}

	public static function register() {
		register_setting( 'pod_sc_settings', self::OPTION, array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize' ), 'default' => self::defaults(), 'show_in_rest' => false ) );
	}

	public static function menu() {
		add_submenu_page( 'woocommerce', __( 'POD Simple Checkout', 'pod-simple-checkout' ), __( 'POD Simple Checkout', 'pod-simple-checkout' ), 'manage_woocommerce', 'pod-simple-checkout', array( __CLASS__, 'render' ) );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage these settings.', 'pod-simple-checkout' ) );
		}
		$values = self::all();
		$labels = array(
			'buy_now' => __( 'Enable Buy Now on native simple/variable product forms', 'pod-simple-checkout' ),
			'ajax' => __( 'Enable AJAX Add to Cart on compatible product forms', 'pod-simple-checkout' ),
			'direct_checkout' => __( 'Send Buy Now customers directly to checkout after a successful add', 'pod-simple-checkout' ),
			'checkout_ui' => __( 'Enhance the native checkout presentation', 'pod-simple-checkout' ),
			'replace_cart' => __( 'Replace other cart items after a successful Buy Now (off by default)', 'pod-simple-checkout' ),
		);
		?>
		<div class="wrap"><h1><?php esc_html_e( 'POD Simple Checkout', 'pod-simple-checkout' ); ?></h1>
		<p><?php echo esc_html( sprintf( __( 'Version %1$s · Detected checkout: %2$s', 'pod-simple-checkout' ), POD_SC_VERSION, POD_SC_Checkout::mode() ) ); ?></p>
		<p><?php esc_html_e( 'Classic Checkout gets a two-column native form and image summary. Checkout Blocks keep their native controls with scoped styling. No checkout page, fields, gateways or cache settings are replaced.', 'pod-simple-checkout' ); ?></p>
		<?php if ( 'unknown' === POD_SC_Checkout::mode() ) : ?><div class="notice notice-warning inline"><p><?php esc_html_e( 'Checkout type could not be recognized. Presentation enhancements are disabled; Buy Now still uses the configured WooCommerce checkout URL. Check your page builder compatibility.', 'pod-simple-checkout' ); ?></p></div><?php endif; ?>
		<?php settings_errors(); ?>
		<form action="options.php" method="post">
			<?php settings_fields( 'pod_sc_settings' ); ?>
			<table class="form-table" role="presentation"><tbody>
			<?php foreach ( $labels as $key => $label ) : ?>
				<tr><th scope="row"><label for="pod-sc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></label></th><td><label><input id="pod-sc-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>" type="checkbox" value="yes" <?php checked( $values[ $key ], 'yes' ); ?>> <?php echo esc_html( $label ); ?></label></td></tr>
			<?php endforeach; ?>
			</tbody></table>
			<p><?php esc_html_e( 'Existing cart items are preserved by default. Replacement affects Buy Now only, and occurs only after WooCommerce accepts the addition. Normal Add to Cart is never forced to checkout.', 'pod-simple-checkout' ); ?></p>
			<?php submit_button(); ?>
		</form></div>
		<?php
	}
}
