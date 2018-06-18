<?php
/**
 * Template part for displaying posts.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Type
 * @since Type 1.0
 */

// Sidebar Options
$blog_sidebar_position = get_theme_mod('blog_sidebar_position', 'content-sidebar');
$archive_sidebar_position = get_theme_mod('archive_sidebar_position', 'content-sidebar');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('large-post'); ?>>
	
	<div class="entry-header">
		<?php if ( 'post' === get_post_type() ) : ?>
			<div class="entry-meta">
				<?php echo '<span class="posted-on">' . type_time_link() . '</span>'; ?>
				<span class="sep">/</span>
				<span class="cat-links"><?php the_category( ', ' ); ?></span>
			</div>
		<?php endif; ?>
		<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	</div><!-- .entry-header -->
	
	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="entry-thumbnail">
			<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">				
				<?php if ( is_home() && $blog_sidebar_position == 'content-fullwidth' ) {
					the_post_thumbnail('type-fullwidth');
				} elseif ( ( is_archive() || is_search() ) && $archive_sidebar_position == 'content-fullwidth') {
					the_post_thumbnail('type-fullwidth');
				} else {
					the_post_thumbnail('type-large');
				}	
				?>
			</a>
		</figure>
	<?php endif; ?>
	
	<div class="entry-summary">
		 <?php the_excerpt(); ?>
    </div><!-- .entry-content -->
    
</article><!-- #post-## -->
