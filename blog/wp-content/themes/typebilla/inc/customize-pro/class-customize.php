<?php
/**
 * Singleton class for handling the theme's customizer integration.
 *
 * @since  1.0.0
 * @access public
 */
final class Type_Pro_Customize {

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Sets up initial actions.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function setup_actions() {

		// Register panels, sections, settings, controls, and partials.
		add_action( 'customize_register', array( $this, 'sections' ) );

		// Register scripts and styles for the controls.
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_control_scripts' ), 0 );
	}

	/**
	 * Sets up the customizer sections.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  object  $manager
	 * @return void
	 */
	public function sections( $manager ) {

		// Load custom sections.
		require_once( trailingslashit( get_template_directory() ) . '/inc/customize-pro/section-pro.php' );

		// Register custom section types.
		$manager->register_section_type( 'Type_Pro_Customize_Section_Pro' );

		// Register sections.
		$manager->add_section(
			new Type_Pro_Customize_Section_Pro(
				$manager,
				'type_pro',
				array(
					'title'    => esc_html__( 'Type Plus', 'type' ),
					'pro_text' => esc_html__( 'View Type Plus', 'type' ),
					'pro_url'  => esc_url_raw('https://www.designlabthemes.com/type-plus-wordpress-theme/?utm_source=customizer_button&utm_medium=wordpress_dashboard&utm_campaign=type_upsell'),
					'priority' => 1
				)
			)
		);
	}

	/**
	 * Loads theme customizer CSS.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue_control_scripts() {

		wp_enqueue_script( 'type-customize-controls', trailingslashit( get_template_directory_uri() ) . '/inc/customize-pro/customize-controls.js', array( 'customize-controls' ) );

		wp_enqueue_style( 'type-customize-controls', trailingslashit( get_template_directory_uri() ) . '/inc/customize-pro/customize-controls.css' );
	}
}

// Doing this customizer thang!
Type_Pro_Customize::get_instance();
