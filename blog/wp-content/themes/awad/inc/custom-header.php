<?php
/**
 * Sample implementation of the Custom Header feature.
 *
 * You can add an optional custom header image to header.php like so ...
 *
	<?php if ( get_header_image() ) : ?>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<img src="<?php header_image(); ?>" width="<?php echo esc_attr( get_custom_header()->width ); ?>" height="<?php echo esc_attr( get_custom_header()->height ); ?>" alt="">
	</a>
	<?php endif; // End header image check. ?>
 *
 * @link https://developer.wordpress.org/themes/functionality/custom-headers/
 *
 * @package Awad
 */

function awad_customiser_settings( $wp_customize ) {

	// Remove the default color settings.
	// $wp_customize->remove_section( 'colors' );
	// $wp_customize->remove_section( 'header_image' );

	$wp_customize->add_section( 'awad_color_settings' , array(
		'title'      => __( 'Awad colors', 'awad' ),
		'priority'   => 30,
	) );

	$wp_customize->add_setting( 'awad_text_color' , array(
		'default'     => '#490a73',
		'transport'   => 'refresh',
		'sanitize_callback' => 'sanitize_hex_color'
	) );

	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'awad_text_color', array(
		'label'        => __( 'Primary color', 'awad' ),
		'section'    => 'awad_color_settings',
		'settings'   => 'awad_text_color',
	) ) );

	$wp_customize->add_setting( 'awad_background_color' , array(
		'default'     => 'white',
		'transport'   => 'refresh',
		'sanitize_callback' => 'sanitize_hex_color'
	) );

	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'awad_background_color', array(
		'label'        => __( 'Secondary color', 'awad' ),
		'section'    => 'awad_color_settings',
		'settings'   => 'awad_background_color',
	) ) );

}
add_action( 'customize_register', 'awad_customiser_settings' );

function awad_customize_css() {
	?>
	<style type="text/css">
		body, kbd, tt, var { 
			color:<?php echo esc_attr ( get_theme_mod( 'awad_text_color', '#490a73' ) ); ?>; 
			background-color:<?php echo esc_attr ( get_theme_mod( 'awad_background_color', '#060c08' ) ); ?>;
		}


		.entry,
		input,
		.site-footer {
			border-color:<?php echo esc_attr ( get_theme_mod( 'awad_text_color', '#490a73' ) ); ?>; 
		}

		.entry .entry-title {
			color:<?php echo esc_attr ( get_theme_mod( 'awad_background_color', '#fff' ) ); ?>; 
			background-color:<?php echo esc_attr ( get_theme_mod( 'awad_text_color', '#490a73' ) ); ?>; 
		}

		.entry .entry-title a {
			color:<?php echo esc_attr ( get_theme_mod( 'awad_background_color', '#fff' ) ); ?>;
		}

		.entry .entry-title a:hover {
			color: <?php echo esc_attr ( get_theme_mod( 'awad_background_color', '#fff' ) ); ?>;
		}

		a:hover {
			color: <?php echo esc_attr ( get_theme_mod( 'awad_background_color', '#fff' ) ); ?>;
			background-color: <?php echo esc_attr ( get_theme_mod( 'awad_text_color', '#490a73' ) ); ?>;
		}

		pre {
			color:<?php echo esc_attr ( get_theme_mod( 'awad_text_color', '#490a73' ) ); ?>;
			background-color:<?php echo esc_attr ( get_theme_mod( 'awad_background_color', '#060c08' ) ); ?>;
		}
	</style>
	<?php
}
add_action( 'wp_head', 'awad_customize_css');
