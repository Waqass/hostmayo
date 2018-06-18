<?php
/**
 * Type functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Type
 * @since Type 1.0
 */


if ( ! function_exists( 'type_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function type_setup() {
	
	// Make theme available for translation. Translations can be filed in the /languages/ directory
	load_theme_textdomain( 'type', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	// Let WordPress manage the document title
	add_theme_support( 'title-tag' );
	
	// Enable support for Post Thumbnail
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'type-medium', 520, 400, true );
	add_image_size( 'type-large', 800, 500, true );
	add_image_size( 'type-fullwidth', 1200, 580, true );
	
	// Set the default content width.
	$GLOBALS['content_width'] = 1160;
	
	// This theme uses wp_nav_menu() in one location.
	register_nav_menus( array(
		'main_menu' => esc_html__( 'Main Menu', 'type' ),
		'social_menu' => esc_html__( 'Social Menu', 'type' ),
	) );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array( 'comment-form', 'comment-list', 'gallery', 'caption' ) );

	// Enable support for Post Formats
	add_theme_support('post-formats', array( 'image', 'video', 'audio', 'gallery', 'quote' ) );
	
	// Enable support for custom logo.
	add_theme_support( 'custom-logo', array(
		'height'      => 400,
		'width'       => 400,
		'flex-width'  => true,
		'flex-height' => true,
	) );
	
	// Set up the WordPress Custom Background Feature.
	$defaults = array(
    'default-color'	=> '#ffffff',
    'default-image'	=> '',
	);
	add_theme_support( 'custom-background', $defaults );
	
	// This theme styles the visual editor to resemble the theme style,
	add_editor_style( array( 'css/editor-style.css', type_fonts_url() ) );
	
	// Custom template tags for this theme
	require get_template_directory() . '/inc/template-tags.php';
	
	// Theme Customizer
	require get_template_directory() . '/inc/customizer.php';
	
	// Custom styles handled by the Theme customizer
	require get_template_directory() . '/inc/custom-styles.php';
	
	// Load Jetpack compatibility file
	require get_template_directory() . '/inc/jetpack.php';
	
}
endif;
add_action( 'after_setup_theme', 'type_setup' );


if ( ! function_exists( 'type_fonts_url' ) ) :
/**
 * Register Google fonts.
 *
 * @return string Google fonts URL for the theme.
 */
function type_fonts_url() {
	$fonts_url = '';
	$fonts     = array();
	$subsets   = 'latin,latin-ext';

	/* translators: If there are characters in your language that are not supported by Nunito Sans, translate this to 'off'. Do not translate into your own language. */
	if ( 'off' !== _x( 'on', 'Nunito Sans: on or off', 'type' ) ) {
		$fonts[] = 'Nunito Sans:400,700,300,400italic,700italic';
	}

	/* translators: If there are characters in your language that are not supported by Poppins, translate this to 'off'. Do not translate into your own language. */
	if ( 'off' !== _x( 'on', 'Poppins: on or off', 'type' ) ) {
		$fonts[] = 'Poppins:400,700';
	}
		
	/* translators: To add an additional character subset specific to your language, translate this to 'greek', 'cyrillic', 'devanagari' or 'vietnamese'. Do not translate into your own language. */
	$subset = _x( 'no-subset', 'Add new subset (greek, cyrillic, devanagari, vietnamese)', 'type' );

	if ( 'cyrillic' == $subset ) {
		$subsets .= ',cyrillic,cyrillic-ext';
	} elseif ( 'greek' == $subset ) {
		$subsets .= ',greek,greek-ext';
	} elseif ( 'devanagari' == $subset ) {
		$subsets .= ',devanagari';
	} elseif ( 'vietnamese' == $subset ) {
		$subsets .= ',vietnamese';
	}

	if ( $fonts ) {
		$fonts_url = add_query_arg( array(
			'family' => urlencode( implode( '|', $fonts ) ),
			'subset' => urlencode( $subsets ),
		), '//fonts.googleapis.com/css' );
	}

	return $fonts_url;
}
endif;


/**
 * Enqueue scripts and styles.
 */
function type_scripts() {
	
	// Add Google Fonts
	wp_enqueue_style( 'type-fonts', type_fonts_url(), array(), null );
	
	// Add Material Icons
	wp_enqueue_style( 'type-material-icons', '//fonts.googleapis.com/icon?family=Material+Icons', array(), null );
	
	// Add Social Icons
	wp_enqueue_style( 'type-social-icons', get_template_directory_uri() . '/fonts/socicon.min.css', array(), '3.5.2' );
	
	// Theme stylesheet
	wp_enqueue_style( 'type-style', get_stylesheet_uri(), array(), '1.0.9' );
	
	wp_enqueue_script( 'type-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
	
	wp_enqueue_script( 'type-script', get_template_directory_uri() . '/js/main.js', array( 'jquery' ), '20171003', true );
	
}
add_action( 'wp_enqueue_scripts', 'type_scripts' );


/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function type_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'type' ),
		'id'            => 'sidebar-1',
		'description'   => __( 'Add widgets here to appear in your sidebar.', 'type' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title"><span>',
		'after_title'   => '</span></h3>',
	) );
	register_sidebar( array(
		'name'          => __( 'Footer Widget Area 1', 'type' ),
		'id'            => 'footer-1',
		'description'   => __( 'Add widgets here to appear in your footer.', 'type' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title"><span>',
		'after_title'   => '</span></h3>',
	) );
	register_sidebar( array(
		'name'          => __( 'Footer Widget Area 2', 'type' ),
		'id'            => 'footer-2',
		'description'   => __( 'Add widgets here to appear in your footer.', 'type' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title"><span>',
		'after_title'   => '</span></h3>',
	) );
	register_sidebar( array(
		'name'          => __( 'Footer Widget Area 3', 'type' ),
		'id'            => 'footer-3',
		'description'   => __( 'Add widgets here to appear in your footer.', 'type' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title"><span>',
		'after_title'   => '</span></h3>',
	) );
	register_sidebar( array(
		'name'          => __( 'Footer Bottom Widget Area', 'type' ),
		'id'            => 'footer-4',
		'description'   => __( 'One Column Widget Area. Add widgets here to appear at the bottom of the page.', 'type' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title"><span>',
		'after_title'   => '</span></h3>',
	) );
	if ( type_is_woocommerce_active() ) {
	register_sidebar( array(
		'name'          => __( 'Shop Sidebar', 'type' ),
		'id'            => 'sidebar-shop',
		'description'   => __( 'Add widgets here to appear in your Shop.', 'type' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title"><span>',
		'after_title'   => '</span></h3>',
	) );
	}
}
add_action( 'widgets_init', 'type_widgets_init' );


/**
 * Implement the Custom Header feature.
 */
require get_parent_theme_file_path( '/inc/custom-header.php' );


/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function type_body_classes( $classes ) {
	// Adds a class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}

	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}
	
	$header_layout = esc_attr( get_theme_mod('header_layout', 'header-layout1') );
	$blog_sidebar_position = get_theme_mod('blog_sidebar_position', 'content-sidebar');
	$archive_sidebar_position = get_theme_mod('archive_sidebar_position', 'content-sidebar');
	$post_sidebar_position = esc_attr( get_theme_mod('post_sidebar_position', 'content-sidebar') );
	$post_style = esc_attr( get_theme_mod('post_style', 'fimg-classic') );
	$page_sidebar_position = esc_attr( get_theme_mod('page_sidebar_position', 'content-sidebar') );
	$page_style = esc_attr( get_theme_mod('page_style', 'fimg-classic') );
	
	// Adds a class for Header Layout
	$classes[] = $header_layout;
	
	if ( type_is_woocommerce_active() && is_woocommerce() ) {
		
		$woo_layout = esc_attr( get_theme_mod('woocommerce_sidebar_position', 'content-sidebar') );
		
		// Check if there is no Sidebar.
		if ( ! is_active_sidebar( 'sidebar-shop' ) ) {
			$classes[] = 'has-no-sidebar';
		} else {
			$classes[] = $woo_layout;
		}
	
	} else {
		
		// Adds a class to Posts
		if ( is_single() ) {
	    	$classes[] = $post_style;
		}
		
		// Adds a class to Pages
		if ( is_page() ) {
	    	$classes[] = $page_style;
		}
	
		// Check if there is no Sidebar.
		if ( ! is_active_sidebar( 'sidebar-1' ) ) {
			$classes[] = 'has-no-sidebar';
		} else {
			if ( is_home() ) {
	    	$classes[] = $blog_sidebar_position;
			}
			if ( is_archive() || is_search() ) {
	    	$classes[] = $archive_sidebar_position;
			}
			if ( is_single() ) {
	    	$classes[] = $post_sidebar_position;
			}
			if ( is_page() && ! is_home() ) {
	    	$classes[] = $page_sidebar_position;
			}
		}
		
	}

	return $classes;
}
add_filter( 'body_class', 'type_body_classes' );


/**
 * Menu Fallback
 *
 */
function type_fallback_menu() {
	$home_url = esc_url( home_url( '/' ) );
	echo '<ul class="main-menu"><li><a href="' . $home_url . '" rel="home">' . __( 'Home', 'type' ) . '</a></li></ul>';
}


/**
 * Display Custom Logo
 *
 */
function type_custom_logo() { 
	
	if ( is_front_page() && is_home() ) {
		if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) { ?>
			<h1 class="site-title site-logo"><?php the_custom_logo(); ?></h1>
		<?php } else { ?>
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
		<?php }
	} else {
		if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) { ?>
			<p class="site-title site-logo"><?php the_custom_logo(); ?></p>
		<?php } else { ?>
			<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
		<?php }
	}
}


/**
 * Filter the except length.
 *
 */
function type_excerpt_length( $excerpt_length ) {
	
	if ( is_admin() ) {
		return $excerpt_length;
	}
	
	if ( is_home() ) {
		$excerpt_length = get_theme_mod( 'blog_excerpt_length', 25 );
	} elseif ( is_archive() || is_search() ) {
		$excerpt_length = get_theme_mod( 'archive_excerpt_length', 25 );
	} else {
		$excerpt_length = 25;
	}
    return intval($excerpt_length);
}
add_filter( 'excerpt_length', 'type_excerpt_length', 999 );


/**
 * Filter the "read more" excerpt string link to the post.
 *
 * @param string $more "Read more" excerpt string.
 */
function type_excerpt_more( $more ) {
    if ( is_admin() ) {
		return $more;
	}
    if ( get_theme_mod( 'show_read_more', 1 ) ) {
		$more = sprintf( '<span class="read-more-link"><a class="read-more" href="%1$s">%2$s</a></span>',
	        esc_url( get_permalink( get_the_ID() ) ),
	        __( 'Read More', 'type' )
	    );
	    return ' [&hellip;] ' . $more;
    }
}
add_filter( 'excerpt_more', 'type_excerpt_more' );


/**
 * Blog: Post Templates
 *
 */
function type_blog_template() {
	$blog_layout = get_theme_mod('blog_layout', 'list');
	
	if ('list' == $blog_layout) { 
		return sanitize_file_name('list');
	} elseif ('grid' == $blog_layout) {
		return sanitize_file_name('grid');
	} else {
		return;
	}
} 


/**
 * Blog: Post Columns
 *
 */
function type_blog_column() {
	$blog_layout = get_theme_mod('blog_layout', 'list');
	$blog_sidebar_position = get_theme_mod('blog_sidebar_position', 'content-sidebar');
		
	if ('list' == $blog_layout) {
		if ( ! is_active_sidebar( 'sidebar-1' ) || 'content-fullwidth' == $blog_sidebar_position ) {
			$blog_column = 'col-6 col-sm-6';
		} else {
			$blog_column = 'col-12';
		}
	} elseif ( 'grid' == $blog_layout ) {
		if ( ! is_active_sidebar( 'sidebar-1' ) || 'content-fullwidth' == $blog_sidebar_position ) {
			$blog_column = 'col-4 col-sm-6';
		} else {
			$blog_column = 'col-6 col-sm-6';
		}
	} else {
		$blog_column = 'col-12';
	}
	return esc_attr($blog_column);
} 


/**
 * Archive: Post Templates
 *
 */
function type_archive_template() {
	$archive_layout = get_theme_mod( 'archive_layout', 'list' );

	if ('list' == $archive_layout) { 
		return sanitize_file_name('list');
	} elseif ('grid' == $archive_layout) {
		return sanitize_file_name('grid');
	} else {
		return;
	}
} 


/**
 * Archive: Post Columns
 *
 */
function type_archive_column() {
	$archive_layout = get_theme_mod('archive_layout', 'list');
	$archive_sidebar_position = get_theme_mod('archive_sidebar_position', 'content-sidebar');
		
	if ('list' == $archive_layout) {
		if ( ! is_active_sidebar( 'sidebar-1' ) || 'content-fullwidth' == $archive_sidebar_position ) {
			$archive_column = 'col-6 col-sm-6';
		} else {
			$archive_column = 'col-12';
		}
	} elseif ( 'grid' == $archive_layout ) {
		if ( ! is_active_sidebar( 'sidebar-1' ) || 'content-fullwidth' == $archive_sidebar_position ) {
			$archive_column = 'col-4 col-sm-6';
		} else {
			$archive_column = 'col-6 col-sm-6';
		}
	} else {
		$archive_column = 'col-12';
	}
	return esc_attr($archive_column);
} 


/**
 * WooCommerce Support
 */
 
if ( ! function_exists( 'type_is_woocommerce_active' ) ) {
	// Query WooCommerce activation
	function type_is_woocommerce_active() {
		return class_exists( 'woocommerce' ) ? true : false;
	}
}

if ( type_is_woocommerce_active() ) {
	
	// Declare WooCommerce support.
	function type_woocommerce_support() {
    add_theme_support( 'woocommerce' );
	}
	add_action( 'after_setup_theme', 'type_woocommerce_support' );
	
	// WooCommerce Hooks.
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);

	add_action('woocommerce_before_main_content', 'type_wrapper_start', 10);
	add_action('woocommerce_after_main_content', 'type_wrapper_end', 10);

	function type_wrapper_start() {
	echo '<div id="primary" class="content-area"><main id="main" class="site-main" role="main"><div class="woocommerce-content">';
	}

	function type_wrapper_end() {
	echo '</div></main></div>';
	}
	
	function type_remove_woocommerce_sidebar() {
    	if ( get_theme_mod('woocommerce_sidebar_position') == 'content-fullwidth' ) {
        	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
    	} else {
	    	return;
	    }
	}
	add_action('woocommerce_before_main_content', 'type_remove_woocommerce_sidebar' );
}


/**
 * Exclude Featured Posts from the Main Loop
 */
if ( get_theme_mod( 'show_featured_posts' ) && get_theme_mod('exclude_featured_posts', 1) ) {
	
	function type_get_featured_posts_ids() {
		$featured_posts_cat = absint( get_theme_mod('featured_posts_category', get_option('default_category') ) );
		$featured_posts_not_in = array();
		
		$featured_posts = get_posts( array(
		'post_type'			=> 'post',
		'posts_per_page'	=> 3,
    	'orderby'			=> 'date',
    	'order'				=> 'DESC',
    	'cat' 				=> $featured_posts_cat,
    	'ignore_sticky_posts' => true,
		) );
		
		if ( $featured_posts ) {
			foreach ( $featured_posts as $post ) :
			$featured_posts_not_in[] = $post->ID;
			endforeach; 
			wp_reset_postdata();
		}
		return $featured_posts_not_in;
	}
	
	function type_exclude_featured_posts( $query ) {
		if ( $query->is_main_query() && $query->is_home() ) {
			$query->set( 'post__not_in', type_get_featured_posts_ids() );
		}
	}
	add_action( 'pre_get_posts', 'type_exclude_featured_posts' );
}


/**
 * Prints Credits in the Footer
 */
function type_credits() {
	$website_credits = '';
	$website_author = get_bloginfo('name');
	$website_date = date_i18n(__( 'Y', 'type' ) );
	$website_credits = '&copy; ' . $website_date . ' ' . $website_author;	
	echo esc_html($website_credits);
}


/**
 * Add Upsell "pro" link to the customizer
 *
 */
require_once( trailingslashit( get_template_directory() ) . '/inc/customize-pro/class-customize.php' );

////////////////////////////////////////////////////////////////////////
// MyCred User Ranks and Badges Integration ////////////////////////////
////////////////////////////////////////////////////////////////////////
add_filter('wpdiscuz_after_label', 'wpdiscuz_mc_after_label_html', 110, 2);
function wpdiscuz_mc_after_label_html($afterLabelHtml, $comment) {
    if ($comment->user_id) {
        if (function_exists('mycred_get_users_rank')) { //User Rank
            $afterLabelHtml .= mycred_get_users_rank($comment->user_id, 'logo', 'post-thumbnail', array('class' => 'mycred-rank'));
        }
        if (function_exists('mycred_get_users_badges')) { //User Badges
            $users_badges = mycred_get_users_badges($comment->user_id);
            if (!empty($users_badges)) {
                foreach ($users_badges as $badge_id => $level) {
                    $imageKey = ( $level > 0 ) ? 'level_image' . $level : 'main_image';
                    $afterLabelHtml .= '<img src="' . get_post_meta($badge_id, $imageKey, true) . '" width="22" height="22" class="mycred-badge earned" alt="' . get_the_title($badge_id) . '" title="' . get_the_title($badge_id) . '" />';
                }
            }
        }        
    }
    return $afterLabelHtml;
}