<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Let's start the WP Manual loop
 *
 * @since 1.0
 * 
 * @global object $wpmanual_template
 * @param  array $args the query arguments
 * @uses wpmanual_is_search() to check if we need a src query
 * @uses wpmanual_sanitize_search() to sanitize the search terms
 * @uses wp_parse_args() to merge custom with default args
 * @uses wpmannual_is_page_preview() to check if user's previewing a page
 * @uses wp_cache_get() to eventually use the cached queries
 * @uses wp_cache_delete() to free some cache
 * @uses WP_Manual_Page to launch a WP Manual query
 * @return array the list of pages
 */
function wpmanual_has_pages( $args = '' ) {
	global $wpmanual_template;
	
	$manual_page_in_cache = false;

	// This keeps us from firing the query more than once
	if ( empty( $wpmanual_template ) ) {
		
		/***
		 * Set the defaults for the parameters you are accepting via the "bp_checkins_has_places()"
		 * function call
		 */
		$page = $src = false;
		$ppage = -1;


		if( wpmanual_is_search() ) {
			$ppage = 10;
			$page = !empty( $_GET['mpage'] ) ? $_GET['mpage'] : 1;
			$src = wpmanual_sanitize_search( 'input' );
		}

		$defaults = array(
			'id'        => false,
			'name'      => false,
			'per_page'	=> $ppage,
			'paged'     => $page,
			'search'    => $src,
			'orderby'   => false,
			'order'     => 'ASC'
		);
		
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
		
		if( wpmannual_is_page_preview() ) {

			$preview_page = wp_cache_get( 'manual_page_preview', 'wpmanual_preview_page' );
			
			if( false !== $preview_page )
				$manual_page_in_cache = $preview_page;

		} else if( $manual_page = wpmanual_get_manual_page_name() ) {
				
			$name = $manual_page;
			$page = wp_cache_get( 'manual_page_query', 'wpmanual_single_page' );
			
			if( false !== $page && $page->query->post->post_name == $manual_page )
				$manual_page_in_cache = $page;

		} else {
			if( false !== wp_cache_get( 'manual_page_query', 'wpmanual_single_page' ) )
				wp_cache_delete( 'manual_page_query', 'wpmanual_single_page' );
		}
		
		if( empty( $manual_page_in_cache ) ) {
			
			$all_page = wp_cache_get( 'manual_table_content', 'wpmanual_all_page' );

			if( false !== $all_page && empty( $search ) && -1 == $per_page && empty( $paged ) && 'menu_order' == $orderby && 'ASC' == $order ) {
				$wpmanual_template = new stdClass();
				$wpmanual_template->query = $all_page;
			} else {

				$wpmanual_template = new WP_Manual_Page();

				if( !empty($search) )
					$wpmanual_template->get( array( 'paged' => $paged, 'per_page' => $per_page, 'search' => $search, 'order' => $order ) );
				else
					$wpmanual_template->get( array( 'id' => $id, 'name' => $name, 'per_page' => $per_page, 'paged' => $paged, 'orderby' => $orderby, 'order' => $order ) );
				
			}
	
		} else {
			$wpmanual_template = $manual_page_in_cache;
		}
	
	}

	return apply_filters( 'wpmanual_has_pages', $wpmanual_template->have_posts() );
}

/**
 * Prints the pagination links
 *
 * @since 1.0
 * 
 * @uses wpmanual_get_pagination_links() to get it
 * @return string html for the pagination
 */
function wpmanual_pagination_links() {
	echo wpmanual_get_pagination_links();
}

	/**
	 * Gets the html for the pagination
	 *
	 * @since 1.0
	 *
	 * @global object $wpmanual_template
	 * @return string the pagination
	 */
	function wpmanual_get_pagination_links() {
		global $wpmanual_template;

		return apply_filters( 'wpmanual_get_pagination_links', $wpmanual_template->pag_links );
	}

/**
 * Prints the next or previous navigation links
 *
 * @since 1.0
 *
 * @param integer $page_id the id of the displayed manual page
 * @param string $when previous or next link ?
 * @uses wpmanual_the_title() to get the title of the previous or next page
 * @return string html for the pagination
 */
function wpmanual_adjacent_page_link( $page_id = false, $when = 'Next' ) {
	if( empty( $page_id ) )
		return false;

	?>
	<a href="<?php wpmanual_the_permalink( $page_id );?>" title="<?php printf( __('%s manual page', 'wp-manual' ), $when );?>">
		<?php wpmanual_the_title( $page_id );?>
	</a>
	<?php
}

/**
 * Prints the navigation
 *
 * @since 1.0
 *
 * @global object $wpmanual_template
 * @uses wpmannual_is_page_preview() to check if we're previewing a manual page
 * @uses wpmanual_adjacent_page_link() to print prev or next link
 * @return string html the navigation
 */
function wpmanual_content_nav() {
	global $wpmanual_template;

	if( wpmannual_is_page_preview() || empty( $wpmanual_template->query->adjacent_pages ) )
		return false;

	$next_id = $wpmanual_template->query->adjacent_pages->next;
	$prev_id = $wpmanual_template->query->adjacent_pages->prev;

	if ( !empty( $next_id ) || !empty( $prev_id ) ) : ?>
		<nav id="nav-below" class="navigation" role="navigation">

			<?php if( !empty( $prev_id ) ):?>
				<div class="nav-previous alignleft"><span class="meta-nav">&larr;</span> <?php wpmanual_adjacent_page_link( $prev_id, __( 'Previous', 'wp-manual' ) ); ?></div>
			<?php endif;?>

			<?php if( !empty( $next_id ) ):?>
				<div class="nav-next alignright"><?php wpmanual_adjacent_page_link( $next_id, __( 'Next', 'wp-manual' ) ); ?> <span class="meta-nav">&rarr;</span></div>
			<?php endif;?>

		</nav><!-- #nav-below .navigation -->
	<?php endif;
}

/**
 * The Manual Page loop > the page
 *
 * @since 1.0
 *
 * @return object the current manual page
 */
function wpmanual_the_page() {
	global $wpmanual_template;
	return $wpmanual_template->query->the_post();
}

/**
 * Displays the manual page id
 *
 * @since 1.0
 *
 * @uses wpmanual_get_the_id() to get id
 * @return string the manual page id
 */
function wpmanual_the_id(){
	echo wpmanual_get_the_id();
}

	/**
	 * Gets the manual page id
	 *
	 * @since 1.0
	 *
	 * @global object $wpmanual_template
	 * @return string the manual page id
	 */
	function wpmanual_get_the_id() {
		global $wpmanual_template;

		$page_id = isset( $wpmanual_template ) ? $wpmanual_template->query->post->ID : false;
		
		return apply_filters( 'wpmanual_get_the_id', $page_id );
	}

/**
 * Displays the manual page title
 *
 * @since 1.0
 *
 * @uses wpmanual_get_the_title() to get title
 * @return string the manual page title
 */
function wpmanual_the_title( $page_id = false ){
	echo wpmanual_get_the_title( $page_id );
}
	
	/**
	 * Gets the manual page title
	 *
	 * @since 1.0
	 *
	 * @global object $wpmanual_template
	 * @uses get_the_title() to have a fallback to get the title of the page
	 * @return string the manual page title
	 */
	function wpmanual_get_the_title( $page_id = false ) {
		global $wpmanual_template;

		$title = '';

		if( !empty( $page_id ) )
			$title = get_the_title( $page_id );

		if( empty( $title) && !empty( $wpmanual_template ) ) {

			$title = $wpmanual_template->query->post->post_title;

			if ( ! is_admin() ) {
				if ( ! empty( $wpmanual_template->query->post->post_password ) ) {
					$protected_title_format = apply_filters( 'protected_title_format', __( 'Protected: %s', 'wp-manual' ) );
					$title = sprintf( $protected_title_format, $title );
				} else if ( isset( $wpmanual_template->query->post->post_status ) && 'private' == $wpmanual_template->query->post->post_status ) {
					$private_title_format = apply_filters( 'private_title_format', __( 'Private: %s', 'wp-manual' ) );
					$title = sprintf( $private_title_format, $title );
				}
			}

		}
			
		
		return apply_filters( 'wpmanual_get_the_title', $title );
	}

/**
 * Displays the manual page date
 *
 * @since 1.0
 *
 * @uses wpmanual_get_the_date to get date
 * @return string the manual page date
 */
function wpmanual_the_date() {
	echo wpmanual_get_the_date();
}
	
	/**
	 * Gets the manual page date
	 *
	 * @since 1.0
	 *
	 * @global object $wpmanual_template
	 * @uses mysql2date() to build the date
	 * @uses get_option() to get format admin settings
	 * @return string the manual page date
	 */
	function wpmanual_get_the_date() {
		global $wpmanual_template;

		$date = $wpmanual_template->query->post->post_modified_gmt;
		
		$dateformatted = mysql2date(get_option('date_format'), $date );
		
		return apply_filters( 'wpmanual_get_the_date', $dateformatted );
	}

/**
 * Displays the manual page permalink
 *
 * @since 1.0
 *
 * @uses wpmanual_get_the_permalink() to get it
 * @return string the manual page permalink
 */
function wpmanual_the_permalink( $page_id = false ) {
	echo wpmanual_get_the_permalink( $page_id );
}

	/**
	 * Gets the manual page permalink
	 *
	 * @since 1.0
	 *
	 * @global object $wpmanual_template
	 * @uses get_permalink() to build the permalink
	 * @uses wpmanual_get_url() to get root page url
	 * @return string the manual page permalink
	 */
	function wpmanual_get_the_permalink( $page_id = false ) {
		global $wpmanual_template;

		$link = '';

		if( !empty( $page_id ) )
			$link = get_permalink( $page_id );

		if( empty( $link ) && !empty( $wpmanual_template ) )
			$link = trailingslashit( wpmanual_get_url() . $wpmanual_template->query->post->post_name );

		return apply_filters('wpmanual_get_the_permalink', $link );
	}

/**
 * Displays the manual page content
 *
 * @since 1.0
 *
 * @uses wpmanual_get_the_content() to get it
 * @return string the manual page content
 */
function wpmanual_the_content() {
	echo wpmanual_get_the_content();
}

	/**
	 * Gets the manual page permalink
	 *
	 * @since 1.0
	 *
	 * @global object $wpmanual_template
	 * @return string the manual page content
	 */
	function wpmanual_get_the_content() {
		global $wpmanual_template;
		
		return apply_filters('wpmanual_get_the_content', $wpmanual_template->query->post->post_content );
	}

/**
 * Displays the manual page excerpt
 *
 * @since 1.0
 *
 * @uses wpmanual_get_the_excerpt() to get it
 * @return string the manual page excerpt
 */
function wpmanual_the_excerpt() {
	echo wpmanual_get_the_excerpt();
}
	
	/**
	 * Gets the manual page excerpt
	 *
	 * @since 1.0
	 *
	 * @global object $wpmanual_template
	 * @uses post_password_required() to check if user has provided the password for a protected page
	 * @uses current_user_can() to check for current user's capability
	 * @uses strip_shortcodes() to get rid of shorcodes
	 * @uses wp_trim_words() to truncate the content
	 * @return string the manual page excerpt
	 */
	function wpmanual_get_the_excerpt() {
		global $wpmanual_template;

		if( post_password_required( $wpmanual_template->query->post ) && !current_user_can( 'read_private_manuals' ) )
			return false;

		$text = strip_shortcodes( $wpmanual_template->query->post->post_content );

		$text = str_replace(']]>', ']]&gt;', $text);
		$excerpt_length = apply_filters('excerpt_length', 55);
		$excerpt_more = apply_filters('excerpt_more', ' ' . '[&hellip;]');
		$text = wp_trim_words( $text, $excerpt_length, $excerpt_more );

		return apply_filters( 'wpmanual_get_the_excerpt', $text );
	}


/* Table of Content : toc */

/**
 * Displays the WP Manual Table of content
 *
 * @since 1.0
 * 
 * @param  integer $page_id the manual page id
 * @return string html for the TOC
 */
function wpmanual_the_toc( $page_id = false ) {
	echo wpmanual_get_the_toc( $page_id );
}

	/**
	 * Gets the WP Manual Table of content
	 *
	 * @since 1.0
	 *
	 * @global object $wpmanual_template
	 * @param  integer $page_id the manual page id
	 * @uses wpmanual_get_the_id() as a fallback if no page_id provided
	 * @uses get_option() to get the stored Table of content
	 * @uses post_password_required() to check if user has provided the password for a protected page
	 * @uses current_user_can() to check for current user's capability
	 * @uses sanitize_key() to sanitize the key of the toc headings
	 * @uses wpmanual_get_the_permalink() to get the permalink of the manual page
	 * @return string the html for the toc
	 */
	function wpmanual_get_the_toc( $page_id = false ) {
		global $wpmanual_template;

		$output = '';

		if( empty( $page_id ) )
			$page_id = wpmanual_get_the_id();

		$toc = get_option( '_wpmanual_toc' );

		if( empty( $toc ) )
			return false;

		if( empty( $toc[$page_id]) )
			return false;

		if( !is_array( $toc[$page_id]->headings ) || count( $toc[$page_id]->headings ) < 1 )
			return false;

		if( post_password_required( $wpmanual_template->query->post ) && !current_user_can( 'read_private_manuals' ) )
			return false;

		$output = '<dl class="wpmanual-page-toc">';

		foreach( $toc[$page_id]->headings as $heading ) {

			$type = sanitize_key( $heading['type'] );
			$title = apply_filters( 'wpmanual_get_the_title', $heading['title'] );
			$link = wpmanual_get_the_permalink( $toc[$page_id] ) .'#'. $heading['anchor'];

			$output .= '<dt class="wpmanual-toc-element dt-'. $type .'">';
			$output .= '<a href="'. $link .'" title="'. $title .'">'. $title .'</a></dt>'; 
		}

		$output .= '</dl>';

		return apply_filters( 'wpmanual_get_the_toc', $output, $toc[$page_id] );
	}