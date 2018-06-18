<?php
/**
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Awad
 */


/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
require get_template_directory() . '/inc/jetpack.php';


if ( ! function_exists( 'awad_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function awad_setup() {

	load_theme_textdomain( 'awad', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support( 'title-tag' );

	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
	 */
	add_theme_support( 'post-thumbnails' );

	/**
	 * Remove the margin-top added from WP
	 */
	add_theme_support( 'admin-bar', array( 'callback' => '__return_false') );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus( array(
		'primary' => esc_html__( 'Primary', 'awad' ),
		'footer' => esc_html__( 'Footer', 'awad' ),
	) );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	) );

	/*
	 * Enable support for Post Formats.
	 * See https://developer.wordpress.org/themes/functionality/post-formats/
	 */
	add_theme_support( 'post-formats', array(
		'aside',
		'image',
		'video',
		'quote',
		'link',
	) );

	// Set up the WordPress core custom background feature.
	add_theme_support( 'custom-background', apply_filters( 'awad_custom_background_args', array(
		'default-color' => '0f110c',
		'default-image' => '',
	) ) );

	add_theme_support( 'custom-header', array(
		'width'         		=> 1920,
		'default-text-color' 	=> false,
		'header-text' 			=> false
	) );
}
endif;
add_action( 'after_setup_theme', 'awad_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function awad_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'awad_content_width', 640 );
}
add_action( 'after_setup_theme', 'awad_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function awad_widgets_init() {
	register_sidebar( array(
		'name'          => esc_html__( 'Sidebar', 'awad' ),
		'id'            => 'sidebar-1',
		'description'   => '',
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'awad_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function awad_scripts() {
	wp_enqueue_style( 'awad-style', get_template_directory_uri() . '/assets/css/master.css' );
	wp_enqueue_script( 'awad-navigation', get_template_directory_uri() . '/assets/scripts/scripts.min.js', array( "jquery" ), '20151215', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'awad_scripts' );

function awad_add_editor_style() {
	add_editor_style( 'assets/css/editor-style.css' );
}
add_action( "admin_init", "awad_add_editor_style" );
