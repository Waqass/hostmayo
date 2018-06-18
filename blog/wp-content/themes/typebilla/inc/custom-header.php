<?php
/**
 * Custom header implementation
 *
 * @link https://codex.wordpress.org/Custom_Headers
 *
 * @package Type
 * @since Type 1.0
 *


/**
 * Set up the WordPress core custom header feature.
 *
 * @uses type_header_style()
 */
function type_custom_header_setup() {
	add_theme_support( 'custom-header', apply_filters( 'type_custom_header_args', array(
		'default-image'          => '',
		'default-text-color'     => '222222',
		'width'                  => 1600,
		'height'                 => 960,
		'flex-height'            => true,
		'wp-head-callback'       => 'type_header_style',
	) ) );
}
add_action( 'after_setup_theme', 'type_custom_header_setup' );

if ( ! function_exists( 'type_header_style' ) ) :
/**
 * Styles the header image and text displayed on the blog.
 *
 * @see type_custom_header_setup().
 */
function type_header_style() {
	$header_text_color = get_header_textcolor();

	/*
	 * If no custom options for text are set, let's bail.
	 * get_header_textcolor() options: Any hex value, 'blank' to hide text. Default: add_theme_support( 'custom-header' ).
	 */
	if ( get_theme_support( 'custom-header', 'default-text-color' ) === $header_text_color ) {
		return;
	}

	// If we get this far, we have custom styles. Let's do this.
	?>
	<style type="text/css">
	<?php
		// Has the text been hidden?
		if ( ! display_header_text() ) :
	?>
		.site-title,
		.site-description {
			position: absolute;
			clip: rect(1px, 1px, 1px, 1px);
		}
	<?php
		// If the user has set a custom color for the text use that.
		else :
	?>
		.site-title a, .site-title a:hover {
			color: #<?php echo esc_attr( $header_text_color ); ?>;
		}
	<?php endif; ?>
	</style>
	<?php
}
endif;
