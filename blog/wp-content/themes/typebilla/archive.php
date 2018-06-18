<?php
/**
 * The template for displaying archive pages.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Type
 * @since Type 1.0
 */

get_header(); ?>
	
	<?php
	/* Archive Options */
	$archive_layout = get_theme_mod('archive_layout', 'list');
	$archive_sidebar_position = get_theme_mod('archive_sidebar_position', 'content-sidebar');
	$post_template = type_archive_template();
	$post_column = type_archive_column();
	?>
	
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
		
		<?php if ( have_posts() ) : ?>
		
			<header class="page-header">
				<div class="page-header-wrapper">
				<?php
					the_archive_title( '<h1 class="page-title">', '</h1>' );
					the_archive_description( '<div class="taxonomy-description">', '</div>' );
				?>
				</div>
			</header><!-- .page-header -->
			
			<section class="row posts-loop <?php if ('grid' == $archive_layout) { echo esc_attr('flex-row'); } ?>">
				<?php
				/* Start the Loop */
				while ( have_posts() ) : the_post();
				?>
					<div class="post-wrapper <?php echo $post_column; ?>">
						<?php get_template_part( 'template-parts/post/content', $post_template ); ?>
					</div>
				<?php endwhile; ?>
			</section>
			
			<?php the_posts_navigation();
				
		else :
		
			get_template_part( 'template-parts/post/content', 'none' );
		
		endif; ?>
		
		</main><!-- #main -->
	</div><!-- #primary -->

<?php 
	// Sidebar
	if ( 'content-sidebar' == $archive_sidebar_position || 'sidebar-content' == $archive_sidebar_position ) {
		get_sidebar();	
	}
?>
<?php get_footer(); ?>
