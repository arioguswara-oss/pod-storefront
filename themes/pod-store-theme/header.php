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
	<div class="pod-container">
		<a class="pod-site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
	</div>
</header>
<main id="pod-main" class="pod-main pod-container">
