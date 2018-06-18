<?php
/**
 * The template for displaying search results pages.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
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
	
	<section id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
			
			<header class="page-header">
				<div class="page-header-wrapper">
					<?php if ( have_posts() ) : ?>
						<h1 class="page-title"><?php printf( __( 'Search Results for: %s', 'type' ), '<strong>' . get_search_query() . '</strong>' ); ?></h1>
					<?php else : ?>
						<h1 class="page-title"><?php _e( 'Nothing Found', 'type' ); ?></h1>
					<?php endif; ?>
				</div>
			</header><!-- .page-header -->
			
			<?php if ( have_posts() ) : ?>
		
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
				
				<?php the_posts_navigation(); ?>
					
			<?php else : ?>
	
				<p><?php _e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'type' ); ?></p>
				<?php
					get_search_form();
	
			endif; ?>
		
		</main><!-- #main -->
	</section><!-- #primary -->

<?php 
	// Sidebar
	if ( 'content-sidebar' == $archive_sidebar_position || 'sidebar-content' == $archive_sidebar_position ) {
		get_sidebar();	
	}
?>
<?php get_footer(); ?>
