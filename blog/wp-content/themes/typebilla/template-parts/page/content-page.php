<?php
/**
 * Template part for displaying page content.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Type
 * @since Type 1.0
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	
	<?php if ( get_theme_mod('page_style', 'fimg-classic') == 'fimg-classic' ) : ?>
		<header class="entry-header">
			<?php the_title( '<h1 class="entry-title"><span>', '</span></h1>' ); ?>
		</header><!-- .entry-header -->
	<?php endif; ?>
	
	<?php if ( has_post_thumbnail() && get_theme_mod('page_has_featured_image', 1) && get_theme_mod('page_style', 'fimg-classic') == 'fimg-classic'  ) : ?>
		<figure class="entry-thumbnail">
			<?php if( is_page_template( 'page-templates/fullwidth.php' ) || is_page_template( 'page-templates/centered.php' ) ) {
				the_post_thumbnail('type-fullwidth');
			} else {
				the_post_thumbnail('type-large');	
			}	
			?>
		</figure>
	<?php endif; // Featured Image ?>
	
	<div class="entry-content">
		<?php
			the_content();
			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'type' ),
				'after'  => '</div>',
			) );
		?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
		<?php
			edit_post_link(
				sprintf(
					/* translators: %s: Name of current post */
					esc_html__( 'Edit %s', 'type' ),
					the_title( '<span class="screen-reader-text">"', '"</span>', false )
				),
				'<span class="edit-link">',
				'</span>'
			);
		?>
	</footer><!-- .entry-footer -->
	
</article><!-- #post-## -->
