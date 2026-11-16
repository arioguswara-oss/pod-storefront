<?php
/** Preserve standard pages, including native WooCommerce cart/checkout blocks. */
defined( 'ABSPATH' ) || exit;
get_header();
get_template_part( 'template-parts/content', 'page' );
get_footer();
