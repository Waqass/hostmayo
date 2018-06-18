<?php
function register_my_menu() {
 register_nav_menu('Header menu',__( 'Header menu'));
 register_nav_menu('Sidebar menu',__( 'Sidebar menu'));
 register_nav_menu('Footer menu',__( 'Footer menu'));
 register_nav_menu('Footer menu2',__( 'Footer menu2'));
 register_nav_menu('Footer menu3',__( 'Footer menu3'));
 
}
add_action( 'init', 'register_my_menu' );  //for menu

add_theme_support( 'post-thumbnails'); // Add it for featured image

function remove_menus () {
global $menu;
                $restricted = array(__('Links'), __('Settings'), __('Updates'), __('Comments'), __('Posts'), __('Media'), __('Users'), __('Tools'), __('Plugins'));
                end ($menu);
                while (prev($menu)){
                        $value = explode(' ',$menu[key($menu)][0]);
                        if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}
                }
}
add_action('admin_menu', 'remove_menus');


//Remove unnecessary dashboard widgets
function jg_remove_dashboard_widgets() {
//Remove WordPress default dashboard widgets
remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal');
remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal');
remove_meta_box( 'dashboard_primary', 'dashboard', 'side');
remove_meta_box( 'dashboard_secondary', 'dashboard', 'side');
//Remove additional plugin widgets
remove_meta_box( 'wp125_widget', 'dashboard', 'normal');
remove_meta_box( 'yoast_db_widget', 'dashboard', 'normal');
}
add_action('wp_dashboard_setup', 'jg_remove_dashboard_widgets' );

// Remove Admin Bar

function remove_admin_bar_links() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu('wp-logo');
	$wp_admin_bar->remove_menu('view-site');
	$wp_admin_bar->remove_menu('new-content');
	$wp_admin_bar->remove_menu('comments');
}
add_action( 'wp_before_admin_bar_render', 'remove_admin_bar_links' );

// Remove Admin Footer Text 

function change_footer_admin () {return 'Copyright 2013 By  Par Techno.';}
add_filter('admin_footer_text', 'change_footer_admin', 9999);

function change_footer_version() {return '1.0';}
add_filter( 'update_footer', 'change_footer_version', 9999);

// Client LOGO

function codex_custom_init() {
    $args = array(
      'public' => true,
      'label'  => 'Logos',
	  'show_ui' => true,
	  'supports' => array( 'title', 'editor','thumbnail')
    );
    register_post_type( 'logos', $args );
}
add_action( 'init', 'codex_custom_init' );

function codex_custom_initt() {
    $args = array(
      'public' => true,
      'label'  => 'Testimonials',
	  'show_ui' => true,
	  'supports' => array( 'title', 'editor','thumbnail')

    );
    register_post_type( 'testimonials', $args );
}
add_action( 'init', 'codex_custom_initt' );


?>