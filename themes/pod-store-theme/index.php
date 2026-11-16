<?php
/** Minimal fallback template. */
defined( 'ABSPATH' ) || exit;

get_header();
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'pod-entry' ); ?>>
			<?php if ( is_singular() ) : ?>
				<h1><?php the_title(); ?></h1>
			<?php else : ?>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<?php endif; ?>
			<?php the_content(); ?>
			<?php wp_link_pages(); ?>
		</article>
		<?php
	endwhile;
	the_posts_pagination();
else :
	?>
	<p><?php esc_html_e( 'Belum ada konten.', 'pod-store-theme' ); ?></p>
	<?php
endif;
get_footer();
