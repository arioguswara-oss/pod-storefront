<?php
/** Small read-only helpers shared by templates. */
defined( 'ABSPATH' ) || exit;

function pod_store_theme_shop_url() {
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}

function pod_store_theme_categories( $limit = 4 ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => $limit, 'orderby' => 'count', 'order' => 'DESC' ) );
	// A store may put all apparel under one parent, e.g. Clothing.
	if ( ! is_wp_error( $terms ) && 1 === count( $terms ) ) {
		$parent = reset( $terms );
		$children = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => $parent->term_id, 'number' => $limit, 'orderby' => 'count', 'order' => 'DESC' ) );
		if ( ! is_wp_error( $children ) && $children ) {
			$terms = $children;
		}
	}
	return is_wp_error( $terms ) ? array() : $terms;
}

function pod_store_theme_fallback_menu( $args ) {
	?>
	<ul class="pod-menu">
		<li><a href="<?php echo esc_url( pod_store_theme_shop_url() ); ?>"><?php esc_html_e( 'Shop all', 'pod-store-theme' ); ?></a></li>
		<?php foreach ( pod_store_theme_categories( 3 ) as $term ) : ?>
			<?php $url = get_term_link( $term ); ?>
			<?php if ( ! is_wp_error( $url ) ) : ?>
				<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $term->name ); ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
	<?php
}

function pod_store_theme_cart_link() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$count = WC()->cart->get_cart_contents_count();
	?>
	<a class="pod-cart-link" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View bag, %d items', 'pod-store-theme' ), $count ) ); ?>">
		<?php pod_store_theme_icon( 'bag' ); ?>
		<span class="pod-action-label"><?php esc_html_e( 'Bag', 'pod-store-theme' ); ?></span><span class="pod-cart-count"><?php echo esc_html( $count ); ?></span>
	</a>
	<?php
}

function pod_store_theme_icon( $name ) {
	$paths = array(
		'bag' => '<path d="M5 7h14l1 14H4L5 7Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
		'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
		'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
	);
	if ( isset( $paths[ $name ] ) ) {
		// Only the hard-coded SVG paths above may be output here.
		echo '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
