<?php
/** Standard WordPress content fallback. */
defined( 'ABSPATH' ) || exit;
while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'pod-page' ); ?>>
		<h1><?php the_title(); ?></h1>
		<?php the_content(); wp_link_pages(); ?>
	</article>
	<?php
endwhile;
