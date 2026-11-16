<?php
/** POD Store Theme bootstrap. Presentation only: no migrations or store writes. */
defined( 'ABSPATH' ) || exit;

function pod_store_theme_setup() {
	load_theme_textdomain( 'pod-store-theme', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 320, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce', array( 'product_grid' => array( 'default_columns' => 4, 'min_columns' => 2, 'max_columns' => 4 ) ) );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	register_nav_menus( array( 'primary' => __( 'Main navigation', 'pod-store-theme' ), 'footer' => __( 'Footer navigation', 'pod-store-theme' ) ) );
}
add_action( 'after_setup_theme', 'pod_store_theme_setup' );

function pod_store_theme_assets() {
	$version = wp_get_theme()->get( 'Version' );
	$dependencies = array();
	foreach ( array( 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen' ) as $handle ) {
		if ( wp_style_is( $handle, 'registered' ) ) {
			$dependencies[] = $handle;
		}
	}
	wp_enqueue_style( 'pod-store-theme', get_stylesheet_uri(), $dependencies, $version );
	wp_enqueue_script( 'pod-store-theme', get_template_directory_uri() . '/assets/js/navigation.js', array(), $version, true );
	if ( class_exists( 'WooCommerce' ) ) {
		// Use WooCommerce's own fragment endpoint; no custom cart or checkout logic.
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}
add_action( 'wp_enqueue_scripts', 'pod_store_theme_assets', 20 );

require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/woocommerce.php';
require get_template_directory() . '/inc/customizer.php';
