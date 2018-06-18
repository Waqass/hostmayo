<?php
/**
 * Displays Social Menu
 *
 * @package Type
 * @since Type 1.0
 */

?>

	<div id="social-links" class="social-links">
		<?php if ( has_nav_menu( 'social_menu' ) && get_theme_mod( 'show_header_social', 1 ) ) {
			wp_nav_menu(
				array(
					'theme_location'  => 'social_menu',
					'container'       => false,
					'menu_id'         => 'social-menu',
					'menu_class'      => 'social-menu',
					'depth'           => 1,
					'link_before'     => '<span class="screen-reader-text">',
					'link_after'      => '</span>',
					'fallback_cb'     => '',
				)
			);
		} // Social Links ?>
	</div>