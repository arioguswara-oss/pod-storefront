<?php
/** Theme bootstrap for POD Store Theme. */

defined( 'ABSPATH' ) || exit;

function pod_store_theme_setup() {
	load_theme_textdomain( 'pod-store-theme', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'pod_store_theme_setup' );

function pod_store_theme_enqueue_styles() {
	wp_enqueue_style(
		'pod-store-theme',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'pod_store_theme_enqueue_styles' );
