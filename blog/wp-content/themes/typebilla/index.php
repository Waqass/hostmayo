<?php
/**
 * The main template file.
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Type
 * @since Type 1.0
 */

get_header(); ?>
	
	<?php
	/* Blog Options */
	$blog_layout = get_theme_mod('blog_layout', 'list');
	$blog_sidebar_position = get_theme_mod('blog_sidebar_position', 'content-sidebar');
	$post_template = type_blog_template();
	$post_column = type_blog_column();
	?>
		
	<?php if ( have_posts() ) : ?>

		<div id="primary" class="content-area">
			<main id="main" class="site-main" role="main">
				
				<section class="row posts-loop <?php if ('grid' == $blog_layout) { echo esc_attr('flex-row'); } ?>">
					<?php /* Start the Loop */
					while ( have_posts() ) : the_post(); ?>
						<div class="post-wrapper <?php echo $post_column; ?>">
							<?php get_template_part( 'template-parts/post/content', $post_template ); ?>
						</div>
					<?php endwhile; ?>
				</section>
				<?php the_posts_navigation(); ?>
				
			</main><!-- #main -->
		</div><!-- #primary -->
	
	<?php else : ?>

			<?php get_template_part( 'template-parts/post/content', 'none' ); ?>
		
	<?php endif; ?>

<?php 
	// Sidebar
	if ( 'content-sidebar' == $blog_sidebar_position || 'sidebar-content' == $blog_sidebar_position ) {
		get_sidebar();	
	}
?>
<?php get_footer(); ?>
