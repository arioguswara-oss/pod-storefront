<?php
/** Native checkout presentation: no field removal, totals or gateway processing. */
defined( 'ABSPATH' ) || exit;

final class POD_SC_Checkout {
	public static function mode() {
		$page = get_post( wc_get_page_id( 'checkout' ) );
		$mode = 'unknown';
		if ( $page ) {
			if ( has_block( 'woocommerce/checkout', $page->post_content ) ) { $mode = 'blocks'; }
			elseif ( has_shortcode( $page->post_content, 'woocommerce_checkout' ) ) { $mode = 'classic'; }
		}
		$mode = apply_filters( 'pod_sc_checkout_mode', $mode );
		return in_array( $mode, array( 'classic', 'blocks' ), true ) ? $mode : 'unknown';
	}

	public static function enhanced() {
		return POD_SC_Settings::enabled( 'checkout_ui' ) && 'unknown' !== self::mode() && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! is_wc_endpoint_url( 'order-pay' ) && apply_filters( 'pod_sc_checkout_ui_supported', true );
	}

	public static function classic() {
		$endpoint = isset( $_GET['wc-ajax'] ) && is_scalar( $_GET['wc-ajax'] ) ? sanitize_key( wp_unslash( $_GET['wc-ajax'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only rendering context.
		return POD_SC_Settings::enabled( 'checkout_ui' ) && 'classic' === self::mode() && apply_filters( 'pod_sc_checkout_ui_supported', true ) && ( self::enhanced() || 'update_order_review' === $endpoint );
	}

	public static function register() {
		add_filter( 'body_class', array( __CLASS__, 'body_classes' ) );
		add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'back_link' ), 5 );
		add_action( 'woocommerce_checkout_before_customer_details', array( __CLASS__, 'details_open' ), 0 );
		add_action( 'woocommerce_checkout_after_customer_details', array( __CLASS__, 'details_close' ), PHP_INT_MAX );
		add_action( 'woocommerce_checkout_before_order_review_heading', array( __CLASS__, 'summary_open' ), 0 );
		add_action( 'woocommerce_checkout_after_order_review', array( __CLASS__, 'summary_close' ), PHP_INT_MAX );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'item_image' ), 20, 3 );
	}

	public static function body_classes( $classes ) {
		if ( self::enhanced() ) { $classes[] = 'pod-sc-checkout'; $classes[] = 'pod-sc-' . self::mode(); }
		return $classes;
	}

	public static function back_link() {
		if ( self::classic() ) { echo '<p class="pod-sc-back"><a href="' . esc_url( wc_get_cart_url() ) . '">&larr; ' . esc_html__( 'Return to bag', 'pod-simple-checkout' ) . '</a></p>'; }
	}
	public static function details_open() {
		if ( self::classic() ) { echo '<section class="pod-sc-details" aria-labelledby="pod-sc-details-title"><h2 id="pod-sc-details-title">' . esc_html__( 'Contact & delivery', 'pod-simple-checkout' ) . '</h2>'; }
	}
	public static function details_close() { if ( self::classic() ) { echo '</section>'; } }
	public static function summary_open() { if ( self::classic() ) { echo '<section class="pod-sc-summary" aria-labelledby="order_review_heading">'; } }
	public static function summary_close() { if ( self::classic() ) { echo '</section>'; } }

	public static function item_image( $name, $item, $key ) {
		if ( self::classic() && isset( $item['data'] ) && $item['data'] instanceof WC_Product ) {
			return '<span class="pod-sc-item-image">' . wp_kses_post( $item['data']->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ) ) . '</span><span class="pod-sc-item-name">' . wp_kses_post( $name ) . '</span>';
		}
		return $name;
	}
}
