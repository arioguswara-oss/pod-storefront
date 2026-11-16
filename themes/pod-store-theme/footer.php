<?php
/** Shared document footer. */
defined( 'ABSPATH' ) || exit;
?>
</main>
<footer class="pod-footer">
	<div class="pod-container">
		<div class="pod-footer-top">
			<div><a class="pod-site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?><span aria-hidden="true">.</span></a><p><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p></div>
			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<nav aria-label="<?php esc_attr_e( 'Footer navigation', 'pod-store-theme' ); ?>"><?php wp_nav_menu( array( 'theme_location' => 'footer', 'container' => false, 'menu_class' => 'pod-footer-menu', 'depth' => 1, 'fallback_cb' => false ) ); ?></nav>
			<?php else : ?>
				<nav aria-label="<?php esc_attr_e( 'Footer navigation', 'pod-store-theme' ); ?>"><ul class="pod-footer-menu"><li><a href="<?php echo esc_url( pod_store_theme_shop_url() ); ?>"><?php esc_html_e( 'Shop all', 'pod-store-theme' ); ?></a></li><?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?><li><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'My account', 'pod-store-theme' ); ?></a></li><?php endif; ?><?php if ( get_privacy_policy_url() ) : ?><li><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php esc_html_e( 'Privacy', 'pod-store-theme' ); ?></a></li><?php endif; ?></ul></nav>
			<?php endif; ?>
		</div>
		<div class="pod-footer-bottom"><small>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></small><a href="#pod-main"><?php esc_html_e( 'Back to top', 'pod-store-theme' ); ?> &uarr;</a></div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
