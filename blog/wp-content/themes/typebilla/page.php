<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Type
 * @since Type 1.0
 */

get_header(); ?>

<?php if ( have_posts() ) : ?>

	<?php if ( get_theme_mod('page_style', 'fimg-classic') == 'fimg-fullwidth' ) : ?>
		<div class="featured-image">
			<div class="entry-header">
				<?php the_title( '<h1 class="entry-title"><span>', '</span></h1>' ); ?>
			</div>
			<?php if ( has_post_thumbnail() && get_theme_mod('page_has_featured_image', 1) ) : ?>
				<figure class="entry-thumbnail">
					<?php the_post_thumbnail('type-fullwidth'); ?>
				</figure>
			<?php endif; // Featured Image ?>
		</div>
	<?php endif; ?>
	
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<?php
			while ( have_posts() ) : the_post();

				get_template_part( 'template-parts/page/content', 'page' );

				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;

			endwhile; // End of the loop.
			?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php endif; ?>


<?php get_footer(); ?>