<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


// WordPress to WP Manual actions
add_action( 'plugins_loaded',             'wpmanual_loaded',                           10    );
add_action( 'init',                       'wpmanual_init',                             10    );
add_action( 'wp',                         'wpmanual_ready',                            10    );
add_action( 'set_current_user',           'wpmanual_setup_current_user',               12    );
add_action( 'setup_theme',                'wpmanual_setup_theme',                      10    );
add_action( 'after_theme_setup',          'wpmanual_after_theme_setup',                10    );
add_action( 'wp_enqueue_scripts',         'wpmanual_enqueue_scripts',                  10    );
add_action( 'admin_bar_menu',             'wpmanual_setup_admin_bar',                  20    ); // After WP core
add_action( 'template_redirect',          'wpmanual_template_redirect',                10    );
add_action( 'wpmanual_template_redirect', '_wpmanual_maybe_load_template',              2    );
add_action( 'redirect_canonical',         '_wpmanual_maybe_remove_redirect_canonical', 10    );
add_action( 'widgets_init',               'wpmanual_widgets_init',                     10    );
add_filter( 'map_meta_cap',               'wpmanual_map_meta_caps',                    10, 4 );
add_action( 'admin_init',                 'wpmanual_admin_init',                       10    );
add_action( 'admin_head',                 'wpmanual_admin_head',                       10    );


add_action( 'wpmanual_admin_init', 		  'wpmanual_admin_register_settings',             11 );
add_action( 'wpmanual_after_theme_setup', 'wpmanual_add_manual_roles',                     1 );
add_action( 'wpmanual_setup_current_user','wpmanual_set_current_user_default_role'           );
add_action( 'wpmanual_setup_admin_bar',   'wpmanual_replace_edit_page_menu'                  );

add_action( 'wpmanual_widgets_init', array( 'WP_Manual_Widget', 'register_widget' ), 10 );
add_action( 'wpmanual_widgets_init', array( 'WP_Manual_Search_Widget', 'register_widget' ), 10 );

add_action( 'wpmanual_after_page_loop', 'wpmanual_reset_postdata', 1 );


function wpmanual_activation() {
	do_action( 'wpmanual_activation' );
}

function wpmanual_loaded(){
	do_action( 'wpmanual_loaded' );
}

function wpmanual_init(){
	do_action( 'wpmanual_init' );
}

function wpmanual_ready(){
	do_action( 'wpmanual_ready' );
}

function wpmanual_setup_current_user(){
	do_action( 'wpmanual_setup_current_user' );
}

function wpmanual_setup_theme(){
	do_action( 'wpmanual_setup_theme' );
}

function wpmanual_after_theme_setup(){
	do_action( 'wpmanual_after_theme_setup' );
}

function wpmanual_enqueue_scripts(){
	do_action( 'wpmanual_enqueue_scripts' );
}

function wpmanual_setup_admin_bar(){
	do_action( 'wpmanual_setup_admin_bar' );
}

function wpmanual_template_redirect(){
	do_action( 'wpmanual_template_redirect' );
}

function wpmanual_widgets_init() {
	do_action( 'wpmanual_widgets_init' );
}

function wpmanual_map_meta_caps( $caps, $cap, $user_id, $args ) {
	return apply_filters( 'wpmanual_map_meta_caps', $caps, $cap, $user_id, $args );
}

function wpmanual_admin_init() {
	do_action( 'wpmanual_admin_init' );
}

function wpmanual_admin_head() {
	do_action( 'wpmanual_admin_head' );
}

function wpmanual_admin_register_settings() {
	do_action( 'wpmanual_admin_register_settings' );
}
