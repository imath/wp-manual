<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Gets plugin's version
 *
 * @since  1.0
 *
 * @uses wpmanual() to get wpmanual main instance
 * @return string the version
 */
function wpmanual_get_version() {
	return wpmanual()->version;
}

/**
 * Gets plugin's includes dir path
 *
 * @since  1.0
 *
 * @uses wpmanual() to get wpmanual main instance
 * @return string the plugin's includes dir path
 */
function wpmanual_get_includes_dir() {
	return wpmanual()->includes_dir;
}

/**
 * Gets plugin's css url
 *
 * @since  1.0
 *
 * @uses wpmanual() to get wpmanual main instance
 * @return string the plugin's css url
 */
function wpmanual_get_css_url() {
	return wpmanual()->css_url;
}

/**
 * Gets plugin's root WordPress page id
 *
 * @since  1.0
 *
 * @uses get_option() to get the plugin's stored option
 * @return integer the root page id
 */
function wpmanual_get_root_page_id() {
	$wpmanual_root_page_id = get_option( '_wpmanual_root_page_id', 0 );

	return intval( $wpmanual_root_page_id );
}

/**
 * Gets plugin's root WordPress page slug
 *
 * @since  1.0
 *
 * @uses wpmanual_get_root_page_id() to get the plugin's root page id
 * @uses get_post() to get the post datas
 * @uses wpmanual() to get wpmanual main instance
 * @return string the root page slug
 */
function wpmanual_get_root_page_slug() {
	$wpmanual_root_page_id = wpmanual_get_root_page_id();

	$wpmanual_root_page = get_post( $wpmanual_root_page_id );

	$root_page = !empty( $wpmanual_root_page->post_name ) ? $wpmanual_root_page->post_name : wpmanual()->slug ;
	
	return $root_page;
}

/**
 * Creates the root page for the plugin
 *
 * This page will display the table of content of the manual.
 *
 * @since 1.0
 * 
 * @param  string $title  the title for the manual root page
 * @return integer        the root page id.
 */
function wpmanual_create_root_page( $title = '' ) {

	$wpmanual_root_page_id = wpmanual_get_root_page_id();

	if( !empty( $wpmanual_root_page_id ) )
		return $wpmanual_root_page_id;

	if( empty( $title ) )
		$title = __( 'Manual', 'wp-manual' );

	$content = '<p>'. __( 'WP Manual needs this page to run. This content will not be displayed. Please leave this page as is.', 'wp-manual' ) .'</p>' ;
	$content .= '<p>'. __( 'Instead of this content, the page will display the detailled table of content for your manual.', 'wp-manual' ) .'</p>' ;

	$page_id = wp_insert_post( array( 
		'comment_status' => 'closed', 
		'ping_status'    => 'closed', 
		'post_title'     => $title,
		'post_content'   => $content, 
		'post_status'    => 'publish', 
		'post_type'      => 'page' 
	) );
	
	return $page_id;	
}

/**
 * Gets plugin's root slug
 *
 * @since  1.0
 *
 * @uses wpmanual() to get wpmanual main instance
 * @return string the root slug
 */
function wpmanual_get_root_slug() {
	return apply_filters( 'wpmanual_get_root_slug', wpmanual()->root_page->slug );
}

/**
 * Checks we're on WP Manual area
 *
 * @since  1.0
 *
 * @uses wpmanual() to get wpmanual main instance
 * @uses wpmanual_get_post_type() to get WP Manual post type
 * @return boolean true|false
 */
function wpmanual_is_manual() {

	$is_manual = wpmanual()->catchuri->is_manual;

	if( empty( $is_manual ) && !empty( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == wpmanual_get_post_type() )
		$is_manual = true;

	return apply_filters( 'wpmanual_is_manual', $is_manual );
}

/**
 * Checks we're previewing a page
 *
 * @since  1.0
 *
 * @uses current_user_can() to check for user's capability
 * @return integer the page id
 */
function wpmannual_is_page_preview() {
	$is_preview = 0;
	
	if( !empty( $_REQUEST[ 'preview' ] ) && !empty( $_REQUEST[ 'p' ] ) && current_user_can( 'publish_manuals' ) )
		$is_preview = intval( $_REQUEST[ 'p' ] );
	
	return $is_preview;
}

/**
 * Checks/Gets the manual page slug
 *
 * @since  1.0
 *
 * @uses wpmanual() to get wpmanual main instance
 * @return string the manual page slug
 */
function wpmanual_get_manual_page_name() {
	return apply_filters( 'wpmanual_get_manual_page_name', wpmanual()->catchuri->manual_page );
}

/**
 * Checks a content can be parsed to build the TOC
 *
 * @since  1.0
 *
 * @uses wpmanual_get_manual_page_name() to get manual page slug
 * @uses wpmannual_is_page_preview() to check we're previewing a page
 * @return mixed string the manual page slug | integer the manual page id
 */
function wpmanual_is_tocable() {

	$retval = wpmanual_get_manual_page_name();

	if( empty( $retval ) )
		$retval = wpmannual_is_page_preview();
	
	return $retval;
}

/**
 * Prints the manual post type
 *
 * @since  1.0
 * 
 * @uses wpmanual_get_post_type() to get the manual page post type
 */
function wpmanual_post_type() {
	echo wpmanual_get_post_type();
}
	
	/**
	 * Gets the manual post type
	 *
	 * @since  1.0
	 * 
	 * @uses wpmanual() to get wpmanual main instance
	 * @return string the manual post type
	 */
	function wpmanual_get_post_type() {
		return apply_filters( 'wpmanual_get_post_type', wpmanual()->post_type );
	}

/**
 * Gets the theme locations
 *
 * @since  1.0
 * 
 * @uses wpmanual() to get wpmanual main instance
 * @return array the theme locations (parent & child theme)
 */
function wpmanual_get_theme_locations() {
	return apply_filters( 'wpmanual_get_theme_locations', wpmanual()->theme_locations );
}

/**
 * Gets the current user infos
 *
 * @since  1.0
 * 
 * @uses wpmanual() to get wpmanual main instance
 * @return object the current user datas
 */
function wpmanual_current_user() {
	return apply_filters( 'wpmanual_current_user', wpmanual()->current_user );
}

/**
 * Gets the current user id
 *
 * @since  1.0
 * 
 * @uses wpmanual_current_user() to get current user's datas
 * @return integer the current user id
 */
function wpmanual_current_user_id() {
	$current_user = wpmanual_current_user();
	
	return apply_filters( 'wpmanual_current_user_id', $current_user->data->ID );
}

/**
 * Prints the manual root url
 *
 * @since  1.0
 * 
 * @uses wpmanual_get_url() to get the manual root url
 */
function wpmanual_url() {
	echo wpmanual_get_url();
}

	/**
	 * Gets the manual root url
	 *
	 * @since  1.0
	 * 
	 * @uses site_url() to build the site url
	 * @uses wpmanual_get_root_slug() to get WP Manual slug
	 * @return string the WP Manual root url
	 */
	function wpmanual_get_url() {
		return trailingslashit( site_url( wpmanual_get_root_slug() ) );
	}

/**
 * Checks if user's performing a search in manual pages
 *
 * @since 1.0
 * @return boolean true|false
 */
function wpmanual_is_search() {
	if( !empty( $_GET['manual-search'] ) )
		return true;
	else
		return false;
}

/**
 * Sanitizes the search terms
 *
 * @since 1.0
 * 
 * @param  string $context to maybe esc html
 * @uses esc_html() to sanitize any html tags
 * @uses wpmanual_unslash() a wp_unslash() wrapper as it had been introduced in 3.6
 * @return string the sanitized search
 */
function wpmanual_sanitize_search( $context = false ) {
	$search = false;

	if( !empty( $_GET['manual-search'] ) ) {

		if( $context == 'input' )
			$search = esc_html( wpmanual_unslash( $_GET['manual-search'] ) );
		else
			$search = apply_filters( 'single_post_title', wpmanual_unslash( $_GET['manual-search'] ) );
		
	}

	return $search;
}

/**
 * Uses wp_unslash or default to stripslashes_deep
 *
 * @since 1.0
 * 
 * @param  string $var the string to strip slashes to
 * @uses wp_unslash() 3.6 and after
 * @uses stripslashes_deep() 3.5.2 and before
 * @return string the sanitized string
 */
function wpmanual_unslash( $var ) {
	if( function_exists( 'wp_unslash' ) )
		return wp_unslash( $var );
	else
		return stripslashes_deep( $var );
}

/**
 * Adds a WP Admin Menu to edit the current menu page
 *
 * @since 1.0
 *
 * @global $wp_admin_bar
 * @uses wp_cache_get() to get the cached query for single manual page
 */
function wpmanual_admin_bar_edit_menu() {
	global $wp_admin_bar;

	$page = wp_cache_get( 'manual_page_query', 'wpmanual_single_page' );

	if( empty( $page->query->post->ID ) )
		return false;

	if( !current_user_can( 'edit_manuals' ) )
		return false;

	$wp_admin_bar->add_menu( array(
		'id' => 'edit',
		'title' => __( 'Edit Manual Page', 'wp-manual'),
		'href' => get_edit_post_link( $page->query->post->ID )
	) );
}

/**
 * Eventually replaces the WP edit menu by WP Manual one
 *
 * @since 1.0
 *
 * @uses wpmanual_get_manual_page_name() to check we're on a WP Manual Page
 */
function wpmanual_replace_edit_page_menu() {
	if ( wpmanual_get_manual_page_name() ) {
		remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
		add_action( 'admin_bar_menu', 'wpmanual_admin_bar_edit_menu', 80 );
	}
}

/**
 * Check for a selected value in an array
 *
 * @since 1.0
 * 
 * @param  array   $current the complete array
 * @param  mixes $tocheck the value to check in the array
 * @param  boolean $echo    wether to display or return
 * @uses checked() to build the checked attribute
 * @return string html checked attribute
 */
function wpmanual_array_checked( $current = array(), $tocheck = false , $echo = true ) {
	$checked = false;

	if( empty( $current ) || empty( $tocheck ) )
		return false;

	if( !is_array( $current ) )
		$current = explode( ',', $current );

	if( is_array( $current ) && in_array( $tocheck, $current ) )
		$checked = checked(  $tocheck,  $tocheck, false );
	else
		$checked = checked( $current, $tocheck, false );

	if( empty( $echo ) )
		return $checked;

	else
		echo $checked;
}

/**
 * Should we add back to top anchors ?
 *
 * @since 1.0
 *
 * @uses get_option() to get Admin settings
 * @return integer 1 or 0
 */
function wpmanual_backtotop_activated() {
	return intval( get_option( '_wpmanual_backtotop', 0 ) );
}

/**
 * Should we use thickbox to zoom images ?
 *
 * @since 1.0
 *
 * @uses get_option() to get Admin settings
 * @return integer 1 or 0
 */
function wpmanual_thickbox_activated() {
	return intval( get_option( '_wpmanual_thickbox', 0 ) );
}

/**
 * Makes sure the listed page or the menu items has the correct classes
 *
 * @since 1.0
 * 
 * @param  string $output wp_page_menu or wp_nav_menu output
 * @param  array $args   wp_page_menu or wp_nav_menu args
 * @uses wpmanual_is_manual() to stop the process if not in WP Manual area
 * @uses wpmanual_get_root_page_id() to get the plugin's root page ID
 * @uses wp_parse_id_list() to eventually build an array with comma separated list of excluded pages
 * @uses wpmanual_get_manual_page_name() to check we're on a manual page
 * @return string $output the output with correct classes
 */
function wpmanual_current_classes( $output, $args ) {

	if( !wpmanual_is_manual() )
		return $output;

	$manual_root_page = wpmanual_get_root_page_id();
	$match_index = 0;

	if( !is_object( $args ) ) {

		$exclude_array = wp_parse_id_list( $args['exclude'] );
	
		if( in_array( $manual_root_page, $exclude_array ) )
			return $ouput;

		$pattern = '/<li class=\"(.+?)\">/i';
		$match_index = 1;

	} else {
		$pattern = '/<li id=\"(.+?)\" class=\"(.+?)\">/i'; 
		$match_index = 2;
	}

	preg_match_all( $pattern, $output, $matches );
	
	if( empty( $matches[$match_index] ) )
		return $output;

	$li_classes = array();
	$found_index = false;

	foreach( $matches[$match_index] as $key => $classes ){

		$li_id = false;
		$classes_array = explode( ' ', $classes );

		foreach( $classes_array as $kclass => $vclass ) {

			if( strpos( $vclass, 'page-item-' ) !== false ) {
				$li_id = str_replace( 'page-item-', '', $vclass );
				
				if( intval( $li_id ) == $manual_root_page )
					$found_index = $key;
			}

			if( in_array( $vclass, array( 'current_page_ancestor', 'current_page_item', 'current_page_parent' ) ) )
				unset( $classes_array[$kclass] );
		}

		$li_classes[$key] = implode( ' ', $classes_array );
	}

	if( false !== $found_index ) {
		$li_classes[$found_index] = wpmanual_get_manual_page_name() ? $li_classes[$found_index] .' current_page_parent current_page_ancestor' : $li_classes[$found_index] .' current_page_item';

		foreach( $matches[$match_index] as $kreplace => $to_replace ) {
			$output = str_replace( $to_replace, $li_classes[$kreplace], $output );
		}
	}

	return $output;
}

/**
 * Filters wp_title to add WP Manual info to it
 *
 * @since 1.0
 * 
 * @param  string $title
 * @param  string $sep
 * @param  string $seplocation
 * @uses wpmanual_is_manual() to stop the process if not in WP Manual area
 * @uses wpmanual_get_manual_page_name() to check we're not on a manual page
 * @uses wp_cache_get() to get wp manual single page cached query
 * @return string the title
 */
function wpmanual_filter_wp_title( $title, $sep, $seplocation) {
	global $wp_query;

	$manual_page_title = false;

	if( wpmanual_is_manual() ) {
		if( !wpmanual_get_manual_page_name() )
			$manual_page_title = $wp_query->post->post_title;
		else {
			$page = wp_cache_get( 'manual_page_query', 'wpmanual_single_page' );
			$manual_page_title = !empty( $page ) ? $page->query->post->post_title : false;
		}
			
	}

	if( !empty( $manual_page_title ) )
		$title = apply_filters( 'single_post_title', $manual_page_title ) .' '. $sep .' ' .$title ;

	return $title;
}

/**
 * Checks for a protected content before displaying it
 *
 * @since 1.0
 * 
 * @param  string $new_content the content to be displayed
 * @param  string $content     the regular WP content
 * @uses post_password_required() to check if the user has provided the password
 * @uses current_user_can() to check for current user's capability
 * @uses get_the_password_form() to return the password form
 * @return string html the content or the password form
 */
function wpmanual_check_for_password( $new_content, $content ) {
	global $wp_query;

	if( !empty( $wp_query->post->post_password ) && post_password_required( $wp_query->post ) && !current_user_can( 'read_private_manuals' ) )
		$new_content = get_the_password_form(); 

	return $new_content;
}

/**
 * Filters the content to add anchors to it
 *
 * @since  1.0
 * 
 * @param  string $content the manual page content
 * @uses wpmanual_is_tocable() to check if we need to parse the content
 * @uses wpmanual_get_includes_dir() to get plugins include dir
 * @uses WP_Manual_Build_Chapters to parse the content and add the anchors
 * @return string the manual page with anchors
 */
function wpmanual_render_toc( $content = '' ) {

	if( !wpmanual_is_tocable() )
		return $content;
	
	require( wpmanual_get_includes_dir() . 'admin/chapters.php' );

	if( !class_exists( 'WP_Manual_Build_Chapters' ) )
		return $content;

	$parsing = new WP_Manual_Build_Chapters( $content );

	$content = $parsing->content;

	return $content;
}

/**
 * Resets WordPress post datas
 * 
 * @since 1.0
 * 
 * @uses wp_reset_postdata() to reset the post datas
 */
function wpmanual_reset_postdata() {
	wp_reset_postdata();
}

/**
 * We never know what an admin can do!
 *
 * In case the root page has been deleted, filters the
 * plugin's settings to add one to create a new root page
 *
 * @since 1.0
 * 
 * @param  array  $settings the plugin's setting fields
 * @uses  wpmanual_get_root_page_id() to get plugin's root page id
 * @return array           the plugin's setting fields
 */
function wpmanual_maybe_add_page_setting( $settings = array() ) {

	$wpmanual_page_id = wpmanual_get_root_page_id();

	if( empty( $wpmanual_page_id ) ) {
		$settings['wpmanual_settings_main']['_wpmanual_root_page_id'] = array(
			'title'             => __( 'Title of your Manual root page', 'wp-manual' ),
			'callback'          => 'wpmanual_admin_setting_callback_root_page',
			'sanitize_callback' => 'wpmanual_sanitize_root_page',
			'args'              => array()
		);
	}

	return $settings;
}
