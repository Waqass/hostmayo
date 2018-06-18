<?php
/**
 * The sidebar containing the main widget area.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-template-parts
 *
 * @package Type
 * @since Type 1.0
 */

?>

<aside id="secondary" class="sidebar widget-area" role="complementary">
	<?php if( type_is_woocommerce_active() && is_woocommerce() ) : ?>
		
		<?php if ( is_active_sidebar( 'sidebar-shop' ) ) { dynamic_sidebar( 'sidebar-shop' ); } ?>
		
	<?php else : ?>

		<?php if ( is_active_sidebar( 'sidebar-1' ) ) { dynamic_sidebar( 'sidebar-1' ); } ?>
	
	<?php endif; ?>
</aside><!-- #secondary -->



