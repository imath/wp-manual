<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if ( !class_exists( 'WP_Manual_Widget' ) ) :
/**
 * WP_Manual_Widget
 *
 * @since 1.0
 *
 * @package WP_Manual
 * @subpackage WP_Manual_Widget
 */
class WP_Manual_Widget extends WP_Widget {
	
	/**
	 * The constructor
	 *
	 * @since  1.0
	 */
	function __construct() {
		$widget_ops = array( 'classname' => 'wpmanual-widget', 'description' => __( 'Displays the table of content when on a manual page', 'wp-manual' ) );
		parent::__construct( false, _x( 'WP Manual TOC', 'widget name', 'wp-manual' ), $widget_ops );
	}
	
	/**
	 * Registers the widget
	 *
	 * @since  1.0
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'WP_Manual_Widget' );
	}

	/**
	 * Displays the table of content of the manual
	 *
	 * @since  1.0
	 *
	 * @param array $args 
	 * @param array $instance 
	 * @uses wpmanual_get_manual_page_name() to check we're on a manual page
	 * @uses wp_cache_get() to get the cached all manual pages query
	 * @uses wpmanual_the_title() to display the title of the page
	 * @uses wpmanual_the_toc() to display the Table of content
	 * @return string html the content of the widget
	 */
	function widget( $args, $instance ) {
		extract( $args );

		$manual_page = wpmanual_get_manual_page_name();

		if( empty( $manual_page ) )
			return false;

		$all_page = wp_cache_get( 'manual_table_content', 'wpmanual_all_page' );

		if( empty( $all_page->posts ) || !is_array( $all_page->posts ) || count( $all_page->posts ) < 1 )
			return false;
		
		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Table of Content', 'wp-manual' ) : $instance['title'], $instance, $this->id_base);
		
		echo $before_widget;
				
		if ( $title )
			echo $before_title . $title . $after_title;?>

		<ul class="manual-list">

			<?php foreach( $all_page->posts as $page ):?>

				<li id="toc-page-<?php echo $page->ID;?>" <?php if( $manual_page == $page->post_name ) echo 'class="current_manual_page"';?>>
					<h4><a href="<?php wpmanual_the_permalink( $page->ID ) ?>"><?php wpmanual_the_title( $page->ID ) ?></a></h4>

					<?php if( $manual_page == $page->post_name ) :?>
						<div class="manual-toc">
							<?php wpmanual_the_toc( $page->ID );?>
						</div>
					<?php endif;?>
					
				</li>
		
			<?php endforeach;?>

		</ul>
		
		<?php
		echo $after_widget;
	}
	
	/**
	 * Updates the title of the widget
	 *
	 * @since  1.0
	 *
	 * @param array $new_instance 
	 * @param array $old_instance 
	 * @return array the instance
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Displays the form in the admin of Widgets
	 *
	 * @since  1.0
	 *
	 * @param array $instance 
	 * @uses wp_parse_args() to merge args with defaults
	 * @uses esc_attr() to sanitize the title
	 * @return string html the form
	 */
	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = esc_attr( $instance['title'] );
	?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'wp-manual'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
<?php
	}

}

endif;

if ( !class_exists( 'WP_Manual_Search_Widget' ) ) :
/**
 * WP_Manual_Search_Widget
 *
 * @since 1.0
 *
 * @package WP_Manual
 * @subpackage WP_Manual_Search_Widget
 */
class WP_Manual_Search_Widget extends WP_Widget {
	
	/**
	 * The constructor
	 * 
	 * @since  1.0
	 */
	function __construct() {
		$widget_ops = array( 'classname' => 'wpmanual-search-widget', 'description' => __( 'Displays a form to search in the manual', 'wp-manual' ) );
		parent::__construct( false, _x( 'WP Manual Search', 'widget name', 'wp-manual' ), $widget_ops );
	}
	
	/**
	 * Registers the widget
	 *
	 * @since  1.0
	 *
	 * @uses register_widget()
	 */
	public static function register_widget() {
		register_widget( 'WP_Manual_Search_Widget' );
	}

	/**
	 * Displays the content of the widget
	 *
	 * @since  1.0
	 *
	 * @param array $args 
	 * @param array $instance 
	 * @uses wpmanual_is_manual() to only load the widget in WP Manual area
	 * @uses wpmanual_url() to get WP Manual root url
	 * @uses esc_attr_x() to retrieve the translation and to escape it for safe use in an attribute
	 * @uses wpmanual_sanitize_search() to sanitize the search terms
	 * @return string html the content of the widget
	 */
	function widget( $args, $instance ) {
		extract( $args );

		if( !wpmanual_is_manual() )
			return false;
		
		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'Manual Search', 'wp-manual' ) : $instance['title'], $instance, $this->id_base);
		
		echo $before_widget;
				
		if ( $title )
			echo $before_title . $title . $after_title;?>

		<form role="search" method="get" class="search-form" action="<?php wpmanual_url();?>">	
			<input type="search" class="search-field" placeholder="<?php echo esc_attr_x( 'Search Manual &hellip;', 'placeholder', 'wp-manual' ) ;?>" value="<?php echo wpmanual_sanitize_search( 'input' );?>" name="manual-search" title="<?php  _e( 'Search for:', 'wp-manual' );?>" />
			<input type="submit" class="search-submit" value="<?php echo esc_attr_x( 'Search', 'submit button', 'wp-manual' );?>" />
		</form>
		
		<?php
		echo $after_widget;
	}
	
	/**
	 * Updates the title of the widget
	 *
	 * @since  1.0
	 *
	 * @param array $new_instance 
	 * @param array $old_instance 
	 * @return array the instance
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	/**
	 * Displays the form in the admin of Widgets
	 *
	 * @since  1.0
	 *
	 * @param array $instance 
	 * @uses wp_parse_args() to merge args with defaults
	 * @uses esc_attr() to sanitize the title
	 * @return string html the form
	 */
	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$title = esc_attr( $instance['title'] );
	?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'wp-manual'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
<?php
	}

}

endif;