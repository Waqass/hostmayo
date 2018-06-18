<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Awad
 */

?>

	</div><!-- #content -->
        <div class="row">
        <div class="columns small-12">
            <footer id="colophon" class="site-footer top section-fullwidth">
		
				<div class="site-info">
                               <?php wp_nav_menu( array( 'theme_location' => 'footer', 'menu_id' => 'footer' ) ); ?>
				</div><!-- .site-info -->
            </footer><!-- #colophon -->
        </div><!-- .small-12 -->
        </div><!-- .row -->
        <div class="row">
        <div class="columns small-12">
            <footer id="colophon" class="site-footer section-fullwidth">
		
				<div class="site-info">
					<a href="<?php echo esc_url( __( 'https://wordpress.org/', 'awad' ) ); ?>"><?php printf( esc_html__( 'Proudly powered by %s', 'awad' ), 'WordPress' ); ?></a>
					<span class="sep"> | </span>
					<?php printf( esc_html__( '%1$s developed by %2$s.', 'awad' ), 'Awad', '<a href="http://lodse.com">Lodse</a>' ); ?>
				</div><!-- .site-info -->
            </footer><!-- #colophon -->
        </div><!-- .small-12 -->
        </div><!-- .row -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>