<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Analyses the url and checks if we're on a WP Manual Area
 *
 * @since 1.0
 *
 * @global object $wp_rewrite
 * @param  array $args the arguments
 * @uses wp_parse_args() to merge custom with default args
 * @uses bp_get_referer_path() to get the referer in case BuddyPress is activated
 * @uses wp_get_referer() to get the referer
 * @uses home_url() to get the site url
 * @return array the url parsed
 */
function wpmanual_catch_uri( $args = '' ) {
	global $wp_rewrite;
	
	$is_manual = $req_uri = false;
	
	$defaults = array( 'root_page' => wpmanual_get_root_slug() );
					
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	if ( isset( $_SERVER['PATH_INFO'] ) )
		$pathinfo = $_SERVER['PATH_INFO'];
	else
		$pathinfo = '';
		
	$pathinfo_array = explode( '?', $pathinfo );
	$pathinfo = str_replace( "%", "%25", $pathinfo_array[0] );

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX || strpos( $_SERVER['REQUEST_URI'], 'wp-load.php' ) ){
		$req_uri = function_exists('bp_get_referer_path') ? bp_get_referer_path() : wp_get_referer() ;
			
		$home_path_ajax = trailingslashit( home_url() );
		$req_uri = str_replace( $home_path_ajax, '',$req_uri );
	} else {
		$req_uri = $_SERVER['REQUEST_URI'];
		$req_uri_array = explode( '?', $req_uri );
		$req_uri = $req_uri_array[0];
		$self = $_SERVER['PHP_SELF'];
		$home_path = parse_url( home_url() );
		
		if ( isset( $home_path['path'] ) )
			$home_path = $home_path['path'];
		else
			$home_path = '';
			
		$home_path = trim( $home_path, '/' );

		// Trim path info from the end and the leading home path from the
		// front. For path info requests, this leaves us with the requesting
		// filename, if any. For 404 requests, this leaves us with the
		// requested permalink.
		$req_uri = str_replace( $pathinfo, '', $req_uri );
		$req_uri = trim( $req_uri, '/' );
		$req_uri = preg_replace( "|^$home_path|i", '', $req_uri );
		$req_uri = trim( $req_uri, '/' );
		$pathinfo = trim( $pathinfo, '/' );
		$pathinfo = preg_replace( "|^$home_path|i", '', $pathinfo );
		$pathinfo = trim( $pathinfo, '/' );
		$self = trim( $self, '/' );
		$self = preg_replace( "|^$home_path|i", '', $self );
		$self = trim( $self, '/' );

		// The requested permalink is in $pathinfo for path info requests and
		//  $req_uri for other requests.
		if ( ! empty($pathinfo) && !preg_match('|^.*' . $wp_rewrite->index . '$|', $pathinfo) ) {
			$request = $pathinfo;
		} else {
			// If the request uri is the index, blank it out so that we don't try to match it against a rule.
			if ( $req_uri == $wp_rewrite->index )
				$req_uri = '';
		}
	}

	$request = $req_uri;
	$query_chunk = explode('/', $request );
	$uri_offset = 0;

	$uri_parse = array(
		'request'        => $request,
		'query_chunk'    => $query_chunk,
		'is_manual'      => false,
		'manual_page'    => false
	);
	
	if( $query_chunk[$uri_offset] == $root_page ) {
		$uri_parse['is_manual'] = true;

		if( count( $query_chunk ) > 1 ) 
			$uri_parse['manual_page'] = $query_chunk[$uri_offset +1];
	}

	do_action( 'wpmanual_catch_uri' );
	
	return $uri_parse;
}

/**
 * Locates the right templates for a WP Manual page
 *
 * @since 1.0
 * 
 * @param  array  $template_names the templates
 * @param  boolean $load           should we load or simply return
 * @param  boolean $require_once   should we load more than once the template
 * @uses wpmanual_get_theme_locations() to check a theme has templates to override plugin's ones
 * @uses load_template() to load the template
 * @return string the located template
 */
function wpmanual_locate_template( $template_names, $load = false, $require_once = true ) {

	// No file found yet
	$located            = false;
	$template_locations = wpmanual_get_theme_locations();

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name  = ltrim( $template_name, '/' );

		// Loop through template stack
		foreach ( (array) $template_locations as $template_location ) {

			// Continue if $template_location is empty
			if ( empty( $template_location ) )
				continue;

			// Check child theme first
			if ( file_exists( trailingslashit( $template_location ) . $template_name ) ) {
				$located = trailingslashit( $template_location ) . $template_name;
				break 2;
			}
		}
	}

	// Maybe load the template if one was located
	if ( ( true == $load ) && !empty( $located ) )
		load_template( $located, $require_once );
		
	return $located;
}

/**
 * Gets the requested template and load it
 *
 * @since 1.0
 * 
 * @param  string $slug
 * @param  string $name
 * @uses wpmanual_locate_template() to locate/load the template
 * @return the located template
 */
function wpmanual_get_template_part( $slug, $name = null ) {

	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template parst to be filtered
	$templates = apply_filters( 'wpmanual_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return wpmanual_locate_template( $templates, true, false );
}

/**
 * Selects the template to load depending on the context
 *
 * @since 1.0
 *
 * @global  object $post
 * @global  object $wp_query
 * @uses wpmanual_get_root_page_id() to get root page id
 * @uses locate_template() to locate a template file
 * @uses wpmanual_get_manual_page_name() to check we're on a single manual page
 * @uses WP_Manual_Page::get() to run a manual page query
 * @uses get_permalink() to build the root page permalink
 * @uses wpmannual_is_page_preview() to check if the manual page is being previewed
 * @uses wpmanual_is_search() to check if a search is performed
 * @uses wpmanual_reset_post() to reset post datas on the same model than bbPress
 * @uses wpmanual_is_manual() to check we're in WP Manual area
 * @uses wpmanual_remove_all_filters() to temporarly remove all filters added to the content
 */
function wpmanual_load_template() {
	global $post, $wp_query;
	
	$wpmanual_root_page_id = intval( wpmanual_get_root_page_id() );

	if( empty( $wpmanual_root_page_id ) )
		return false;
		
	if( !empty( $wpmanual_root_page_id ) ) {
		$wp_query->queried_object    = @get_post( $wpmanual_root_page_id );
		$wp_query->queried_object_id = $wpmanual_root_page_id;
		$post                        = $wp_query->queried_object;
	}
	
	$located_template = locate_template( wp_manual_get_template_from_stack(), false );

	status_header( 200 );
	$wp_query->is_page = $wp_query->is_singular = true;
	$wp_query->is_404  = false;
	
	$reset_post_args = array(
		'ID'             => 0,
		'post_title'     => $post->post_title,
		'post_author'    => 0,
		'post_date'      => 0,
		'post_content'   => '',
		'post_type'      => 'manual_home',
		'post_status'    => 'publish',
		'is_archive'     => true,
		'comment_status' => 'closed'
	);
	
	if( $manual_page = wpmanual_get_manual_page_name() ) {

		$manual_page_data = new WP_Manual_Page();

		$manual_page_data->get( array( 'name' => $manual_page ) );

		if( !empty( $manual_page_data->query->post->ID ) ) {
			$reset_post_args['post_title'] = '<a href="'. get_permalink( $post->ID ) .'">'. $post->post_title. '</a> &rarr; ' . $manual_page_data->query->post->post_title;
			$reset_post_args['post_type'] = 'manual_page';

			if( !empty( $manual_page_data->query->post->post_password ) )
				$reset_post_args['post_password'] = $manual_page_data->query->post->post_password;
		}
		

		add_filter( 'wpmanual_template_part', create_function( '', 'return array("manual", "page");' ) );

	} elseif( $manual_page_id = wpmannual_is_page_preview() ) {

		$manual_preview_data = new WP_Manual_Page();

		$manual_preview_data->get( array( 'id' => $manual_page_id ) );
		$reset_post_args['post_title'] = $manual_preview_data->query->post->post_title;
		$reset_post_args['post_type'] = 'manual_page_preview';

		add_filter( 'wpmanual_template_part', create_function( '', 'return array("manual", "page");' ) );

	} elseif( wpmanual_is_search() ) {

		$reset_post_args['post_title'] = sprintf( __( 'Search results for : %s', 'wp-manual' ), wpmanual_sanitize_search() );
		$reset_post_args['post_type'] = 'manual_search';

		add_filter( 'wpmanual_template_part', create_function( '', 'return array("manual", "search");' ) );

	} else {

		add_filter( 'wpmanual_template_part', create_function( '', 'return array("manual", "home");' ) );
		
	}
	
	
	wpmanual_reset_post( $reset_post_args );
	
	if ( wpmanual_is_manual() ) {
		
		wpmanual_remove_all_filters( 'the_content' );

		// Add a filter on the_content late, which we will later remove
		add_filter( 'the_content', 'wpmanual_replace_the_content' );
		
	}
	
	do_action( 'wpmanual_template_loaded' );

	load_template( apply_filters( 'wpmanual_load_template', $located_template ) );

	// Kill any other output after this.
	exit();
}

/**
 * Resets the post vars
 *
 * @since 1.0
 *
 * @global  object $post
 * @global  object $wp_query
 * @uses wp_parse_args() to merge custom with default args
 */
function wpmanual_reset_post( $args = array() ) {
	global $wp_query, $post;

	global $wp_query, $post;

	// Switch defaults if post is set
	if ( isset( $wp_query->post ) ) {
		$dummy = wp_parse_args( $args, array(
			'ID'                    => $wp_query->post->ID,
			'post_status'           => $wp_query->post->post_status,
			'post_author'           => $wp_query->post->post_author,
			'post_parent'           => $wp_query->post->post_parent,
			'post_type'             => $wp_query->post->post_type,
			'post_date'             => $wp_query->post->post_date,
			'post_date_gmt'         => $wp_query->post->post_date_gmt,
			'post_modified'         => $wp_query->post->post_modified,
			'post_modified_gmt'     => $wp_query->post->post_modified_gmt,
			'post_content'          => $wp_query->post->post_content,
			'post_title'            => $wp_query->post->post_title,
			'post_excerpt'          => $wp_query->post->post_excerpt,
			'post_content_filtered' => $wp_query->post->post_content_filtered,
			'post_mime_type'        => $wp_query->post->post_mime_type,
			'post_password'         => $wp_query->post->post_password,
			'post_name'             => $wp_query->post->post_name,
			'guid'                  => $wp_query->post->guid,
			'menu_order'            => $wp_query->post->menu_order,
			'pinged'                => $wp_query->post->pinged,
			'to_ping'               => $wp_query->post->to_ping,
			'ping_status'           => $wp_query->post->ping_status,
			'comment_status'        => $wp_query->post->comment_status,
			'comment_count'         => $wp_query->post->comment_count,
			'filter'                => $wp_query->post->filter,

			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false,
		) );
	} else {
		$dummy = wp_parse_args( $args, array(
			'ID'                    => -9999,
			'post_status'           => 'publish',
			'post_author'           => 0,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => 0,
			'post_date_gmt'         => 0,
			'post_modified'         => 0,
			'post_modified_gmt'     => 0,
			'post_content'          => '',
			'post_title'            => '',
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => '',
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',

			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false,
		) );
	}

	// Bail if dummy post is empty
	if ( empty( $dummy ) ) {
		return;
	}

	// Set the $post global
	$post = new WP_Post( (object) $dummy );

	// Copy the new post global into the main $wp_query
	$wp_query->post       = $post;
	$wp_query->posts      = array( $post );

	// Prevent comments form from appearing
	$wp_query->post_count = 1;
	$wp_query->is_404     = $dummy['is_404'];
	$wp_query->is_page    = $dummy['is_page'];
	$wp_query->is_single  = $dummy['is_single'];
	$wp_query->is_archive = $dummy['is_archive'];
	$wp_query->is_tax     = $dummy['is_tax'];

	// Clean up the dummy post
	unset( $dummy );

	/**
	 * Force the header back to 200 status if not a deliberate 404
	 */
	if ( ! $wp_query->is_404() ) {
		status_header( 200 );
	}
}

/**
 * Replace the content with the WP Manual page one
 *
 * @since 1.0
 *
 * @param string $content the content
 * @uses in_the_loop() to check we're in a loop
 * @uses wpmanual_restore_all_filters() to put back the filters on content
 * @uses wpmanual_get_template_part() to get the template to display
 */
function wpmanual_replace_the_content( $content = '' ) {

	// Bail if not inside the query loop
	if ( ! in_the_loop() )
		return $content;

	// Define local variable(s)
	$new_content = '';
	
	$template_part = apply_filters( 'wpmanual_template_part', array() ); 

	if( count( $template_part ) == 0 ) {
		wpmanual_restore_all_filters( 'the_content' );
		return $content;
	}

	ob_start();

	wpmanual_get_template_part( $template_part[0], $template_part[1] );

	$new_content = ob_get_contents();

	ob_end_clean();

	wp_reset_postdata();
	
	$content = apply_filters( 'wpmanual_replace_the_content', $new_content, $content );

	// Return possibly hi-jacked content
	return $content;
}

/**
 * Removes temporarly filters and add it to wpmanual instance
 *
 * @since 1.0
 * 
 * @global object $wp_filter
 * @global array $merged_filters
 * @param string $tag
 * @param int $priority
 * @uses wpmanual() to get WP Manual main instance
 * @return bool
 */
function wpmanual_remove_all_filters( $tag, $priority = false ) {
	global $wp_filter, $merged_filters;

	$wpmanual = wpmanual();

	// Filters exist
	if ( isset( $wp_filter[$tag] ) ) {

		// Filters exist in this priority
		if ( !empty( $priority ) && isset( $wp_filter[$tag][$priority] ) ) {

			// Store filters in a backup
			$wpmanual->filters->wp_filter[$tag][$priority] = $wp_filter[$tag][$priority];

			// Unset the filters
			unset( $wp_filter[$tag][$priority] );

		// Priority is empty
		} else {

			// Store filters in a backup
			$wpmanual->filters->wp_filter[$tag] = $wp_filter[$tag];

			// Unset the filters
			unset( $wp_filter[$tag] );
		}
	}

	// Check merged filters
	if ( isset( $merged_filters[$tag] ) ) {

		// Store filters in a backup
		$wpmanual->filters->merged_filters[$tag] = $merged_filters[$tag];

		// Unset the filters
		unset( $merged_filters[$tag] );
	}

	return true;
}

/**
 * Restores filters from the wpmanual instance
 *
 * @since 1.0
 * 
 * @global object $wp_filter
 * @global array $merged_filters
 * @param string $tag
 * @param int $priority
 * @uses wpmanual() to get WP Manual main instance
 * @return bool
 */
function wpmanual_restore_all_filters( $tag, $priority = false ) {
	global $wp_filter, $merged_filters;

	$wpmanual = wpmanual();

	// Filters exist
	if ( isset( $wpmanual->filters->wp_filter[$tag] ) ) {

		// Filters exist in this priority
		if ( !empty( $priority ) && isset( $wpmanual->filters->wp_filter[$tag][$priority] ) ) {

			// Store filters in a backup
			$wp_filter[$tag][$priority] = $wpmanual->filters->wp_filter[$tag][$priority];

			// Unset the filters
			unset( $wpmanual->filters->wp_filter[$tag][$priority] );

		// Priority is empty
		} else {

			// Store filters in a backup
			$wp_filter[$tag] = $wpmanual->filters->wp_filter[$tag];

			// Unset the filters
			unset( $wpmanual->filters->wp_filter[$tag] );
		}
	}

	// Check merged filters
	if ( isset( $wpmanual->filters->merged_filters[$tag] ) ) {

		// Store filters in a backup
		$merged_filters[$tag] = $wpmanual->filters->merged_filters[$tag];

		// Unset the filters
		unset( $wpmanual->filters->merged_filters[$tag] );
	}

	return true;
}

/**
 * Forces the WP Manual pages to have no comments
 *
 * @since 1.0
 * 
 * @param  boolean  $open
 * @param  integer $post_id
 * @uses wpmanual_is_manual() to check we're on WP Manual area
 * @return boolean true|false
 */
function wpmanual_comments_open( $open, $post_id = 0 ) {

	$retval = wpmanual_is_manual() ? false : $open;

	// Allow override of the override
	return apply_filters( 'wpmanual_comments_open', $retval, $open, $post_id );
}

/**
 * Removes the redirect_canonical if in WP Manual area
 *
 * @since 1.0
 *
 * @uses wpmanual_is_manual() to check we're on WP Manual area
 */
function _wpmanual_maybe_remove_redirect_canonical() {
	if ( wpmanual_is_manual() )
		remove_action( 'template_redirect', 'redirect_canonical' );
}

/**
 * Bootstrap point to WP Manual template loading
 *
 * @since 1.0
 *
 * @uses wpmanual_is_manual() to check we're on WP Manual area
 * @uses wpmanual_load_template() to choose what template to display
 */
function _wpmanual_maybe_load_template() {
	if ( wpmanual_is_manual() )
		wpmanual_load_template();
}


/**
 * Tries to load the best template file to rely on
 *
 * @since 1.0
 *
 * @uses get_stylesheet_directory() to get the child theme directory
 * @uses get_template_directory() to get the parent theme directory
 */
function wp_manual_get_template_from_stack() {

	$template_names = array( 
		'wpmanual.php',
		'page.php',
		'single.php',
		'index.php'
	);

	$template_locations = array(
		get_stylesheet_directory(), 
		get_template_directory()
	);

	$template = 'page.php';

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Loop through template stack
		foreach ( (array) $template_locations as $template_location ) {

			// Continue if $template_location is empty
			if ( empty( $template_location ) )
				continue;

			// Check child theme first
			if ( file_exists( trailingslashit( $template_location ) . $template_name ) ) {
				$template = $template_name;
				break 2;
			}
		}
	}

	return $template;
}
