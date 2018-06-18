<?php
/**
 * Template part for displaying posts.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Type
 * @since Type 1.0
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('list-post'); ?>>
	
	<?php if ( has_post_thumbnail() ) { ?>
		<figure class="entry-thumbnail">
			<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">				
				<?php the_post_thumbnail('type-medium'); ?>
			</a>
		</figure>
	<?php } ?>
	
	<div class="entry-header">
		<?php if ( 'post' === get_post_type() ) { ?>
			<div class="entry-meta">
				<span class="cat-links"><?php the_category( ', ' ); ?></span>
				<span class="sep">/</span>
				<?php echo '<span class="posted-on">' . type_time_link() . '</span>'; ?>
			</div>
		<?php } ?>
		<h2 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	</div><!-- .entry-header -->
	
	<div class="entry-summary">
		<?php the_excerpt(); ?>
    </div><!-- .entry-content -->
            
</article><!-- #post-## -->
