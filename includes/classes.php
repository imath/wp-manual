<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
/**
 * WP_Manual_Page Class
 *
 * @package WP Manual
 * @subpackage WP Manual query class
 * @since 1.0
 *
 * Used to query manual pages
 */
class WP_Manual_Page {
	public $id;
	public $name;
	public $title;
	public $content;
	public $extra_data;
	public $query;


	/**
	 * Loads the constructor
	 *
	 * @since 1.0
	 * 
	 * @param  integer $id manual page id
	 * @uses WP_Manual_Page::__construct()
	 */
	public function wp_manual_page( $id = false ) {
		$this->__construct( $id, $name );
	}

	/**
	 * The constructor
	 *
	 * @since 1.0
	 * 
	 * @param  integer $id manual page id
	 * @uses WP_Manual_Page::populate()
	 */
	public function __construct( $id = false ) {
		if ( !empty( $id ) ) {
			$this->id = $id;
			$this->populate();
		}
	}

	/**
	 * Runs a WP Query for the desired manual page id
	 *
	 * @since 1.0
	 * 
	 * @uses wpmanual_get_post_type() to get the manual post type
	 * @uses WP_Query to run a posts query
	 */
	public function populate() {
		
		$query_args = array(
			'post_status'	 => 'publish',
			'post_type'	 => wpmanual_get_post_type(),
			'p' => intval( $this->id )
		);
		
		$this->query = new WP_Query( $query_args );

	}
	
	/**
	 * The main function to feed WP Manual loops
	 *
	 * @since 1.0
	 * 
	 * @param array $args the WP Manual query args
	 * @uses current_user_can() to check for user's capability
	 * @uses wp_parse_args() to merge custom args with default
	 * @uses wpmanual_get_post_type() to get WP Manual Post type
	 * @uses wpmannual_is_page_preview() to check if the previewed post is a WP Manual one
	 * @uses WP_Query to run a posts query
	 * @uses wp_cache_add() to add some queries to cache
	 * @uses wp_cache_set() to set some queries to cache
	 * @uses WP_Manual_Page::get_adjacent_page() to get previous and next manual page
	 * @uses paginate_links() to build the pagination
	 * @uses add_query_arg() to build the pagination links
	 */
	public function get( $args = array() ) {
		global $wpdb;
		
		// Only run the query once
		if ( empty( $this->query ) ) {

			// Setup possible post__not_in array
			$post_status[] = 'publish';

			// Check if user can read private forums
			if ( current_user_can( 'read_private_manuals' ) )
				$post_status[] = 'private';

			$defaults = array(
				'id'        => false,
				'name'      => false,
				'user_id'	=> false,
				'per_page'	=> -1,
				'paged'     => false,
				'search'    => false,
				'orderby'   => false,
				'order'     => 'ASC'
			);

			$r = wp_parse_args( $args, $defaults );
			extract( $r );
			
			if( !empty( $name ) ) {
				
				$query_args = array(
					'post_status'	 => implode( ',', $post_status ),
					'post_type'	 => wpmanual_get_post_type(),
					'name' => $name,
					'posts_per_page' => $per_page
				);
				
			} elseif( !empty( $id ) && wpmannual_is_page_preview() && current_user_can( 'publish_manuals' ) ) {

				$query_args = array(
					'post_status'	 => implode( ',', $post_status ) .',draft',
					'post_type'	 => wpmanual_get_post_type(),
					'p' => $id,
					'posts_per_page' => 1
				);

			} else {
				
				$query_args = array(
					'post_status'	 =>  implode( ',', $post_status ),
					'post_type'	 => wpmanual_get_post_type(),
					'posts_per_page' => $per_page,
					'paged'		 => $paged,
					'orderby'    => $orderby,
					'order'      => $order
				);
				
				if( !empty( $search ) ) {
					$query_args['s'] = $search;
				}
				
			}

			
			// Run the query, and store as an object property, so we can access from
			// other methods
			$this->query = new WP_Query( $query_args );

			if( !empty( $id ) ) {

				$this->query->is_preview = true;
				
				if( false === wp_cache_add( 'manual_page_preview', $this, 'wpmanual_preview_page' ) )
					wp_cache_set( 'manual_page_preview', $this, 'wpmanual_preview_page' );

			} else if( !empty( $name ) ) {

				if( empty( $this->query->post ) )
					return false;

				$this->query->is_singular = true;

				$this->query->adjacent_pages = $this->get_adjacent_page( $this->query->post->ID );
				
				if( false === wp_cache_add( 'manual_page_query', $this, 'wpmanual_single_page' ) )
					wp_cache_set( 'manual_page_query', $this, 'wpmanual_single_page' );

			} elseif ( empty( $search ) ) {
				
				if( false === wp_cache_add( 'manual_table_content', $this->query, 'wpmanual_all_page' ) )
					wp_cache_set( 'manual_table_content', $this->query, 'wpmanual_all_page' );
			}
			

			if( !empty( $per_page ) && !empty( $paged ) ) {
				// Let's also set up some pagination
				$this->pag_links = paginate_links( array(
					'base' => add_query_arg( 'mpage', '%#%' ),
					'format' => '',
					'total' => ceil( (int) $this->query->found_posts / (int) $this->query->query_vars['posts_per_page'] ),
					'current' => (int) $paged,
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
					'mid_size' => 1
				) );
			} 
			
		}
		
	}

	/**
	 * On single Manual pages, runs a new query to get previous and next pages
	 *
	 * @since 1.0
	 * 
	 * @param  integer $post_id the manual page we need to get previous and next
	 * @uses current_user_can() to check for user's capability
	 * @uses wpmanual_get_post_type() to get WP Manual Post type
	 * @uses WP_Query to run a posts query
	 * @uses wp_cache_add() to add some queries to cache
	 * @uses wp_cache_set() to set some queries to cache
	 * @return object          the adjacent pages
	 */
	public function get_adjacent_page( $post_id = 0 ) {

		if( empty( $post_id ) )
			return false;

		// not great but let's be consistent and have the same order than toc..
		$adjacent_pages = new stdClass();

		// Setup possible post__not_in array
		$post_status[] = 'publish';

		// Check if user can read private forums
		if ( current_user_can( 'read_private_manuals' ) )
			$post_status[] = 'private';
	
		$query_args = array(
			'post_status'	 => implode( ',', $post_status ),
			'post_type'	 => wpmanual_get_post_type(),
			'posts_per_page' => -1,
			'paged'		 => false,
			'orderby'    => 'menu_order',
			'order'      => 'ASC'
		);

		$all = new WP_Query( $query_args );

		if( false === wp_cache_add( 'manual_table_content', $all, 'wpmanual_all_page' ) )
			wp_cache_set( 'manual_table_content', $all, 'wpmanual_all_page' );

		$adjacent_pages->prev = $adjacent_pages->next = false;

		foreach( $all->posts as $post ) {
			$order[] = $post->ID;
		}

		$key = array_search( $post_id, $order );

		if( false !== $key ) {
			$adjacent_pages->prev = !empty( $order[$key-1] ) ? $order[$key-1] : false;
			$adjacent_pages->next = !empty( $order[$key+1] ) ? $order[$key+1] : false;
		}

		return $adjacent_pages;
	}

	/**
	 * Gets the queried manual pages
	 *
	 * @since 1.0
	 * 
	 * @uses WP_Query->have_posts() to get the queried manual pages
	 */
	public function have_posts() {
		return $this->query->have_posts();
	}

	/**
	 * Gets the manual page
	 *
	 * @since 1.0
	 * 
	 * @uses WP_Query->the_post() to get the manual page
	 */
	public function the_post() {
		return $this->query->the_post();
	}

}