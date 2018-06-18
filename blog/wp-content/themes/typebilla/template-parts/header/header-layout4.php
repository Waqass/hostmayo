<?php
/**
 * Template part for displaying Header.
 *
 * @package Type
 * @since Type 1.0
 */

?>
		
	<div class="site-title-left clear">
		
		<div class="row">
			<div class="col-3">
				<div class="site-branding">
					<?php type_custom_logo(); ?>
					<?php $description = get_bloginfo( 'description', 'display' );
					if ( $description || is_customize_preview() ) : ?>
						<p class="site-description"><?php echo $description; ?></p>
					<?php
					endif; ?>
				</div><!-- .site-branding -->
			</div>
			<div class="col-9">
				<?php get_template_part( 'template-parts/navigation/navigation', 'main' ); // Main Menu ?>
			</div>

		</div><!-- .row -->
		
	</div>
	
	<?php if ( get_header_image() ) { ?>
		<div class="header-image" style="background-image: url(<?php echo esc_url( get_header_image() ); ?>)"></div>
	<?php } // Header Image ?>
	