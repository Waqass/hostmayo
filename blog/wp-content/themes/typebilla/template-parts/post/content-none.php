<?php
/**
 * Template part for displaying a message that posts cannot be found.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Type
 * @since Type 1.0
 */

?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		
		<section class="no-results not-found">
			<header class="entry-header">
				<div class="page-header-wrapper">
					<h1 class="page-title"><?php _e( 'Nothing Found', 'type' ); ?></h1>
				</div>
			</header><!-- .entry-header -->
		
			<div class="entry-content">
				<?php
				if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>
		
					<p><?php printf( __( 'Ready to publish your first post? <a href="%1$s">Get started here</a>.', 'type' ), esc_url( admin_url( 'post-new.php' ) ) ); ?></p>
					
				<?php else : ?>
		
					<p><?php _e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'type' ); ?></p>
					<?php
						get_search_form();
						
				endif; ?>
			</div><!-- .entry-content -->
		</section><!-- .no-results -->
		
	</main><!-- #main -->
</div><!-- #primary -->