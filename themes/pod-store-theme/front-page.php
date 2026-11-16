<?php
/** Storefront homepage. Reads existing products; never replaces saved page content. */
defined( 'ABSPATH' ) || exit;
get_header();

if ( ! class_exists( 'WooCommerce' ) ) {
	get_template_part( 'template-parts/content', 'page' );
	get_footer();
	return;
}

$pod_products = wc_get_products( array( 'status' => 'publish', 'visibility' => 'catalog', 'limit' => 8, 'orderby' => 'date', 'order' => 'DESC' ) );
// Respect WooCommerce catalog visibility, including the store's out-of-stock setting.
$pod_products = array_values( array_filter( $pod_products, static function ( $product ) { return $product->is_visible(); } ) );
$pod_hero = $pod_products ? $pod_products[0] : null;
?>
<section class="pod-hero" aria-labelledby="pod-home-title">
	<div class="pod-hero-copy">
		<p class="pod-eyebrow"><?php echo esc_html( get_theme_mod( 'pod_home_eyebrow', __( 'Wear what feels like you', 'pod-store-theme' ) ) ); ?></p>
		<h1 id="pod-home-title"><?php echo esc_html( get_theme_mod( 'pod_home_title', __( 'Good tees. Great company.', 'pod-store-theme' ) ) ); ?></h1>
		<p class="pod-hero-description"><?php echo esc_html( get_theme_mod( 'pod_home_description', __( 'A fresh perspective on your everyday wardrobe. Find a graphic, make it yours.', 'pod-store-theme' ) ) ); ?></p>
		<a class="pod-button" href="<?php echo esc_url( pod_store_theme_shop_url() ); ?>"><?php esc_html_e( 'Explore the collection', 'pod-store-theme' ); ?><?php pod_store_theme_icon( 'arrow' ); ?></a>
		<a class="pod-text-link" href="#pod-new-arrivals"><?php esc_html_e( 'Discover new arrivals', 'pod-store-theme' ); ?> &darr;</a>
	</div>
	<?php if ( $pod_hero ) : ?>
		<a class="pod-hero-product" href="<?php echo esc_url( $pod_hero->get_permalink() ); ?>">
			<div class="pod-hero-image"><?php echo $pod_hero->get_image( 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce-generated image HTML. ?></div>
			<div class="pod-hero-caption"><span><?php echo esc_html( $pod_hero->get_name() ); ?></span><?php pod_store_theme_icon( 'arrow' ); ?></div>
		</a>
	<?php endif; ?>
</section>
<?php $pod_categories = pod_store_theme_categories(); ?>
<?php if ( $pod_categories ) : ?>
	<nav class="pod-category-nav" aria-label="<?php esc_attr_e( 'Shop by category', 'pod-store-theme' ); ?>">
		<span class="pod-eyebrow"><?php esc_html_e( 'Find your fit', 'pod-store-theme' ); ?></span>
		<div><?php foreach ( $pod_categories as $pod_term ) : ?>
			<?php $pod_term_url = get_term_link( $pod_term ); ?>
			<?php if ( ! is_wp_error( $pod_term_url ) ) : ?><a href="<?php echo esc_url( $pod_term_url ); ?>"><?php echo esc_html( $pod_term->name ); ?> <?php pod_store_theme_icon( 'arrow' ); ?></a><?php endif; ?>
		<?php endforeach; ?></div>
	</nav>
<?php endif; ?>
<section class="pod-arrivals" id="pod-new-arrivals" aria-labelledby="pod-arrivals-title">
	<div class="pod-section-heading"><div><p class="pod-eyebrow"><?php esc_html_e( 'The latest edit', 'pod-store-theme' ); ?></p><h2 id="pod-arrivals-title"><?php esc_html_e( 'New arrivals', 'pod-store-theme' ); ?></h2></div><a class="pod-text-link" href="<?php echo esc_url( add_query_arg( 'orderby', 'date', pod_store_theme_shop_url() ) ); ?>"><?php esc_html_e( 'View all', 'pod-store-theme' ); ?> <?php pod_store_theme_icon( 'arrow' ); ?></a></div>
	<?php if ( $pod_products ) : ?>
		<?php wc_set_loop_prop( 'columns', 4 ); woocommerce_product_loop_start(); ?>
		<?php foreach ( $pod_products as $pod_product ) : ?>
			<?php
			$pod_post = get_post( $pod_product->get_id() );
			$GLOBALS['post'] = $pod_post; // Set standard WooCommerce loop context.
			setup_postdata( $pod_post );
			wc_setup_product_data( $pod_post );
			wc_get_template_part( 'content', 'product' );
			?>
		<?php endforeach; ?>
		<?php woocommerce_product_loop_end(); wp_reset_postdata(); wc_reset_loop(); ?>
	<?php else : ?>
		<p class="pod-empty"><?php esc_html_e( 'The next collection is on its way. Check back soon.', 'pod-store-theme' ); ?></p>
	<?php endif; ?>
</section>
<section class="pod-editorial"><p class="pod-eyebrow"><?php esc_html_e( 'Less noise. More personality.', 'pod-store-theme' ); ?></p><h2><?php esc_html_e( 'Make room for your next favorite.', 'pod-store-theme' ); ?></h2><a class="pod-text-link" href="<?php echo esc_url( pod_store_theme_shop_url() ); ?>"><?php esc_html_e( 'Find it here', 'pod-store-theme' ); ?> <?php pod_store_theme_icon( 'arrow' ); ?></a></section>
<?php get_footer(); ?>
