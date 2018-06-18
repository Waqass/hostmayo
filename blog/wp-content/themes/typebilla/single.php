<?php
/**
 * The template for displaying all single posts.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Type
 * @since Type 1.0
 */

// Sidebar Options
$post_sidebar_position = get_theme_mod('post_sidebar_position', 'content-sidebar');

get_header(); ?>

<?php if ( have_posts() ) : ?>
	
	<?php if ( get_theme_mod('post_style', 'fimg-classic') == 'fimg-fullwidth' ) : ?>
		<div class="featured-image">
			<div class="entry-header">
				<div class="entry-meta entry-category">
					<span class="cat-links"><?php the_category( ', ' ); ?></span>
				</div>
				<?php the_title( '<h1 class="entry-title"><span>', '</span></h1>' ); ?>
				<div class="entry-meta">
					<?php type_posted_on(); ?>
				</div>
			</div>
			<?php if ( has_post_thumbnail() && get_theme_mod('post_has_featured_image', 1) ) : ?>
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

				get_template_part( 'template-parts/post/content', 'single' );
				
				the_post_navigation();
		
				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) :
					comments_template();
				endif;
				
			endwhile; // End of the loop.
		?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php endif; ?>

<?php 
	// Sidebar
	if ( 'content-sidebar' == $post_sidebar_position || 'sidebar-content' == $post_sidebar_position ) {
		get_sidebar();
	}
?>
<?php get_footer(); ?>
