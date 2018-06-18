<?php
/**
 * The template for displaying Featured Posts
 *
 * @package Type
 * @since Type 1.0
 */

// Featured Posts Settings
$featured_posts_cat = absint( get_theme_mod('featured_posts_category', get_option('default_category') ) );
$featured_posts_cat_link = get_category_link( $featured_posts_cat );
$featured_posts_cat_name = get_cat_name( $featured_posts_cat );

$query_args = array (
    'post_type'			=> 'post',
    'posts_per_page'	=> 3,
    'orderby'			=> 'date',
    'order'				=> 'DESC',
    'cat' 				=> $featured_posts_cat,
    'ignore_sticky_posts' => true,
);

$featured_posts_query = new WP_Query ($query_args);
$i = 1;
?>

<section class="featured-posts clear">
	<div class="container">
		
	<?php if ( get_theme_mod( 'show_featured_posts_title' ) ) : ?> 
		<h3 class="category-title">
			<a href="<?php echo esc_url( $featured_posts_cat_link ); ?>">
				<?php if (  get_theme_mod( 'featured_posts_title' ) ) {
					echo esc_html( get_theme_mod( 'featured_posts_title' ) );
				} else {
					echo esc_html( $featured_posts_cat_name ); 
				}
				?>
			</a>
		</h3>
	<?php endif; ?>
	
		<div class="row">
	
	<?php
    if ( $featured_posts_query->have_posts() ) :
    
		while ( $featured_posts_query->have_posts() ) : $featured_posts_query->the_post();
		?>
			
			<?php /* grab the url for the large size featured image */
			$featured_img_url = get_the_post_thumbnail_url( get_the_ID(),'large' ); 
			?>
			
			<div class="<?php if ($i == 1) { echo 'col-8 col-sm-12'; } else { echo 'col-4 col-sm-6'; } ?>">
				<div class="featured-item <?php if ($i == 1) { echo 'featured-large'; } else { echo 'featured-small'; } ?> <?php if ($i == 2) { echo 'first'; } ?>" <?php if ( has_post_thumbnail() ) { ?>style="background-image: url(<?php echo $featured_img_url; ?>);" <?php } ?>>
					<a class="featured-link" href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"></a>
					<div class="featured-overlay">
						<div class="entry-meta">
							<span class="posted-on">
								<?php the_time( get_option( 'date_format' ) ); ?>
							</span>
						</div>
						<h4 class="entry-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
					</div>
				</div>
			</div>
						
		<?php 
		$i++; endwhile;
		wp_reset_postdata();
		
	endif; ?>
	
		</div><!-- .row -->
	</div>
</section>
