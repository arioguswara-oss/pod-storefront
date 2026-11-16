<?php
/** Shared document header. */
defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#pod-main"><?php esc_html_e( 'Lewati ke konten', 'pod-store-theme' ); ?></a>
<header class="pod-header">
	<div class="pod-container pod-header-inner">
		<div class="pod-brand">
			<?php if ( has_custom_logo() ) : the_custom_logo(); else : ?>
				<a class="pod-site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?><span aria-hidden="true">.</span></a>
			<?php endif; ?>
		</div>
		<nav class="pod-desktop-nav" aria-label="<?php esc_attr_e( 'Main navigation', 'pod-store-theme' ); ?>">
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'pod-menu', 'menu_id' => 'pod-primary-menu', 'depth' => 3, 'fallback_cb' => 'pod_store_theme_fallback_menu' ) ); ?>
		</nav>
		<div class="pod-header-actions">
			<details class="pod-search">
				<summary><?php pod_store_theme_icon( 'search' ); ?><span class="screen-reader-text"><?php esc_html_e( 'Search', 'pod-store-theme' ); ?></span></summary>
				<div class="pod-search-panel">
					<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<label for="pod-search-input"><?php esc_html_e( 'Find your next favorite', 'pod-store-theme' ); ?></label>
						<div class="pod-search-row"><input id="pod-search-input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search products…', 'pod-store-theme' ); ?>"><button type="submit"><?php esc_html_e( 'Search', 'pod-store-theme' ); ?></button></div>
						<?php if ( class_exists( 'WooCommerce' ) ) : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
					</form>
				</div>
			</details>
			<?php pod_store_theme_cart_link(); ?>
			<details class="pod-mobile-menu">
				<summary><span class="pod-menu-symbol" aria-hidden="true"><span></span><span></span></span><span class="screen-reader-text"><?php esc_html_e( 'Menu', 'pod-store-theme' ); ?></span></summary>
				<nav aria-label="<?php esc_attr_e( 'Mobile navigation', 'pod-store-theme' ); ?>">
					<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'pod-menu', 'menu_id' => 'pod-mobile-primary-menu', 'depth' => 3, 'fallback_cb' => 'pod_store_theme_fallback_menu' ) ); ?>
				</nav>
			</details>
		</div>
	</div>
</header>
<main id="pod-main" class="pod-main pod-container" tabindex="-1">
