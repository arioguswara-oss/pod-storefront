<?php
/** Missing content, without rewriting existing URLs. */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="pod-editorial"><p class="pod-eyebrow">404</p><h1><?php esc_html_e( 'This page has moved on.', 'pod-store-theme' ); ?></h1><p><?php esc_html_e( 'Find something you love in the collection.', 'pod-store-theme' ); ?></p><a class="pod-button" href="<?php echo esc_url( pod_store_theme_shop_url() ); ?>"><?php esc_html_e( 'Back to the shop', 'pod-store-theme' ); ?></a></section>
<?php get_footer(); ?>
