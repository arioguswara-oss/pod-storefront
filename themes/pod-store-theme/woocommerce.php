<?php
/** Keep WooCommerce content inside the theme's shared layout. */
defined( 'ABSPATH' ) || exit;

get_header();
if ( function_exists( 'woocommerce_content' ) ) {
	woocommerce_content();
}
get_footer();
