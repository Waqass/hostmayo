<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-template-parts
 *
 * @package Type
 * @since Type 1.0
 */

?>
			</div><!-- .inside -->
		</div><!-- .container -->
	</div><!-- #content -->

	<footer id="colophon" class="site-footer" role="contentinfo">
		
		<?php if ( is_active_sidebar( 'footer-1' ) || is_active_sidebar( 'footer-2' ) || is_active_sidebar( 'footer-3' ) ) : ?>
			<div class="widget-area" role="complementary">
				<div class="container">
					<div class="row">
						<div class="col-4 col-md-4" id="footer-area-1">
							<?php if ( is_active_sidebar( 'footer-1' ) ) {
								dynamic_sidebar( 'footer-1' );
							} // end footer widget area 1 ?>
						</div>	
						<div class="col-4 col-md-4" id="footer-area-2">
							<?php if ( is_active_sidebar( 'footer-2' ) ) {
								dynamic_sidebar( 'footer-2' );
							} // end footer widget area 2 ?>
						</div>
						<div class="col-4 col-md-4" id="footer-area-3">
							<?php if ( is_active_sidebar( 'footer-3' ) ) {
								dynamic_sidebar( 'footer-3' );
							} // end footer widget area 3 ?>
						</div>
					</div>
				</div><!-- .container -->
			</div><!-- .widget-area -->
		<?php endif; ?>
		
		<?php if ( is_active_sidebar( 'footer-4' ) ) : ?>
			<div class="widget-area" role="complementary">
				<div class="container">
					<div class="row">
						<div class="col-12 col-sm-12" id="footer-area-4">
							<?php if ( is_active_sidebar( 'footer-4' ) ) {
								dynamic_sidebar( 'footer-4' );
							} // end footer widget area 4 ?>
						</div>	
					</div>
				</div><!-- .container -->
			</div><!-- .widget-area -->
		<?php endif; ?>
		
		<div class="footer-copy">
			<div class="container">
				<div class="row">
					<div class="col-12 col-sm-12">
						<div class="site-credits">Copyright © 2015-18<a href="https://hostmayo.com/"> Hostmayo.com</a></div>
					</div>
				</div>
			</div><!-- .container -->
		</div><!-- .footer-copy -->
		
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
