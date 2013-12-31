<?php
/* 
Plugin Name: WP Manual
Plugin URI: http://imathi.eu/tag/wp-manual
Description: Let's create a manual to help people use your app
Version: 1.0-beta2
Author: imath
Author URI: http://imathi.eu
License: GPLv2
Text Domain: wp-manual
Domain Path: /languages/
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if ( !class_exists( 'WP_Manual' ) ) :

/**
 * Main WP_Manual Class
 *
 * Inspired by bbpress 2.3
 */
class WP_Manual {
	
	private $data;

	public $current_user = false;

	private static $instance;

	/**
	 * Main WP Manual Instance
	 *
	 * Inspired by bbpress
	 *
	 * Avoids the use of a global
	 *
	 * @since 1.0
	 *
	 * @uses WP_Manual::setup_globals() to set the global needed
	 * @uses WP_Manual::includes() to include the required files
	 * @uses WP_Manual::setup_actions() to set up the hooks
	 * @return object the instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WP_Manual;
			self::$instance->setup_globals();
			self::$instance->includes();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	
	private function __construct() { /* Do nothing here */ }
	
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-manual' ), '1.0-beta2' ); }

	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-manual' ), '1.0-beta2' ); }

	public function __isset( $key ) { return isset( $this->data[$key] ); }

	public function __get( $key ) { return isset( $this->data[$key] ) ? $this->data[$key] : null; }

	public function __set( $key, $value ) { $this->data[$key] = $value; }

	public function __unset( $key ) { if ( isset( $this->data[$key] ) ) unset( $this->data[$key] ); }

	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }


	/**
	 * Some usefull vars
	 *
	 * @since 1.0
	 *
	 * @uses plugin_basename()
	 * @uses plugin_dir_path() to build WP Manual plugin path
	 * @uses plugin_dir_url() to build WP Manual plugin url
	 */
	private function setup_globals() {

		/** Version ***********************************************************/

		$this->version    = '1.0-beta2';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file       = __FILE__;
		$this->basename   = apply_filters( 'wpmanual_plugin_basename', plugin_basename( $this->file ) );
		$this->plugin_dir = apply_filters( 'wpmanual_plugin_dir_path', plugin_dir_path( $this->file ) );
		$this->plugin_url = apply_filters( 'wpmanual_plugin_dir_url',  plugin_dir_url ( $this->file ) );

		// Includes
		$this->includes_dir = apply_filters( 'wpmanual_includes_dir', trailingslashit( $this->plugin_dir . 'includes'  ) );
		$this->includes_url = apply_filters( 'wpmanual_includes_url', trailingslashit( $this->plugin_url . 'includes'  ) );
		$this->css_url      = apply_filters( 'wpmanual_images_url',   trailingslashit( $this->includes_url . 'css'     ) );

		// Languages
		$this->lang_dir     = apply_filters( 'wpmanual_lang_dir',     trailingslashit( $this->plugin_dir . 'languages' ) );
		
		// WP Manual slug and name
		$this->slug = apply_filters( 'wpmanual_slug', 'manual' );
		$this->name = apply_filters( 'wpmanual_name', 'WP Manual' );

		// Post type identifiers
		$this->post_type   = apply_filters( 'wpmanual_post_type', 'manual' );

		// templating
		$this->themes_dir   = apply_filters( 'wpmanual_themes_dir',   trailingslashit( $this->plugin_dir . 'templates' ) );
		$this->themes_url   = apply_filters( 'wpmanual_themes_url',   trailingslashit( $this->plugin_url . 'templates' ) );
		$this->theme_locations = array();

		/** Misc **************************************************************/

		$this->domain         = 'wp-manual';
		$this->filters        = new stdClass();
		$this->errors         = new WP_Error(); // Feedback

		/** catchuri ****/
		$this->root_page = new StdClass();
		$this->catchuri = new StdClass();
		
	}
	
	/**
	 * includes the needed files
	 *
	 * @since 1.0
	 *
	 * @uses is_admin() for the settings files
	 */
	private function includes() {
		require( $this->includes_dir . 'actions.php'       );
		require( $this->includes_dir . 'functions.php'     );
		require( $this->includes_dir . 'filters.php'       );
		require( $this->includes_dir . 'caps.php'          );
		require( $this->includes_dir . 'template.php'      );
		require( $this->includes_dir . 'classes.php'       );
		require( $this->includes_dir . 'template-tags.php' );
		require( $this->includes_dir . 'widget.php'        );

		if( is_admin() ){
			require( $this->includes_dir . 'admin/admin.php' );
		}
	}
	

	/**
	 * It's about hooks!
	 *
	 * @since 1.0
	 */
	private function setup_actions() {

		// Add actions to plugin activation and deactivation hooks
		add_action( 'activate_'   . $this->basename, 'wpmanual_activation', 1 );
		
		add_action( 'wpmanual_setup_current_user', array( $this, 'setup_current_user'       ), 1 );
		add_action( 'wpmanual_init',               array( $this, 'register_root_page'       ), 1 );
		add_action( 'wpmanual_init',               array( $this, 'register_post_type'       ), 2 );
		add_action( 'wpmanual_init',               array( $this, 'catch_uri'                ), 3 );
		add_action( 'wpmanual_loaded',             array( $this, 'register_theme_locations' )    );
		add_action( 'wpmanual_enqueue_scripts',    array( $this, 'enqueue_css'              )    );

		add_action( 'wpmanual_init',               array( $this, 'load_textdomain'          ), 0 );

		do_action_ref_array( 'wpmanual_after_setup_actions', array( &$this ) );
	}

	/**
	 * Adds current user datas to instance
	 *
	 * @since 1.0
	 */
	public function setup_current_user() {
		$this->current_user = wp_get_current_user();
	}

	/**
	 * Registers the slug and id of the root WordPress page
	 *
	 * @since 1.0
	 *
	 * @uses wpmanual_get_root_page_slug() to get the root page slug
	 * @uses wpmanual_get_root_page_id() to get the root page id
	 */
	public function register_root_page() {

		if( empty( $this->root_page->id ) ) {
			$this->root_page->slug = wpmanual_get_root_page_slug();
			$this->root_page->id   = wpmanual_get_root_page_id();
		}
	}
	
	/**
	 * Registers the manual hierarchical post type
	 *
	 * @since 1.0
	 *
	 * @uses wpmanual_get_root_slug() to get the root slug
	 * @uses register_post_type() to register the post type
	 * @uses wpmanual_get_post_type() to get the manual post type identifier
	 * @uses wpmanual_get_caps() to get the capabilities for post type
	 */
	public function register_post_type() {
		
		$post_type = array();

		// manual labels
		$post_type['labels'] = array(
			'name'               => __( 'Manual Pages',             'wp-manual' ),
			'menu_name'          => __( 'Manual Pages',             'wp-manual' ),
			'singular_name'      => __( 'Manual Page',              'wp-manual' ),
			'all_items'          => __( 'All Manual pages',         'wp-manual' ),
			'add_new'            => __( 'New Manual page',          'wp-manual' ),
			'add_new_item'       => __( 'Create New Manual page',   'wp-manual' ),
			'edit'               => __( 'Edit',                     'wp-manual' ),
			'edit_item'          => __( 'Edit Manual page',         'wp-manual' ),
			'new_item'           => __( 'New Manual page',          'wp-manual' ),
			'view'               => __( 'View Manual page',         'wp-manual' ),
			'view_item'          => __( 'View Manual page',         'wp-manual' ),
			'search_items'       => __( 'Search Manual pages',      'wp-manual' ),
			'not_found'          => __( 'No pages found',           'wp-manual' ),
			'not_found_in_trash' => __( 'No pages found in Trash',  'wp-manual' ),
			'parent_item_colon'  => __( 'Parent Manual Page:',      'wp-manual' )
		);

		// manual rewrite
		$post_type['rewrite'] = array(
			'slug'       => wpmanual_get_root_slug(),
			'with_front' => false
		);

		// manual supports
		$post_type['supports'] = array(
			'title',
			'editor',
			'revisions',
			'page-attributes'
		);

		// Register manual content type
		register_post_type(
			wpmanual_get_post_type(),
			apply_filters( 'wpmanual_manual_post_type', array(
				'labels'              => $post_type['labels'],
				'rewrite'             => $post_type['rewrite'],
				'supports'            => $post_type['supports'],
				'description'         => __( 'Manual Pages', 'wp-manual' ),
				'capabilities'        => wpmanual_get_caps(),
				'capability_type'     => array( 'manual', 'manuals' ),
				'menu_position'       => 20,
				'exclude_from_search' => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => current_user_can( 'admin_manuals' ),
				'public'              => true,
				'show_ui'             => current_user_can( 'admin_manuals' ),
				'can_export'          => true,
				'hierarchical'        => true,
				'query_var'           => true,
				'menu_icon'           => ''
			) )
		);
	}

	/**
	 * Catch the uri and parse it to load the right template
	 *
	 * @since 1.0
	 *
	 * @uses wpmanual_catch_uri() to uri datas organized in an array
	 */
	public function catch_uri() {
		
		$catchuri_datas = wpmanual_catch_uri();
		
		$this->catchuri->query       = $catchuri_datas['request'];
		$this->catchuri->query_chunk = $catchuri_datas['query_chunk'];
		$this->catchuri->is_manual   = $catchuri_datas['is_manual'];
		$this->catchuri->manual_page = $catchuri_datas['manual_page'];
		
		do_action_ref_array( 'wpmanual_after_catchuri', array( &$this->catchuri ) );
	}

	/**
	 * Adds the theme and child theme paths to allow template overriding
	 *
	 * @since 1.0
	 *
	 * @uses get_stylesheet_directory() to get theme directory
	 * @uses get_template_directory() to get parent theme directory
	 */
	public function register_theme_locations() {
		
		$this->theme_locations = array( 
			trailingslashit( get_stylesheet_directory() ) . $this->domain, 
			trailingslashit( get_template_directory() ) . $this->domain, 
			$this->themes_dir 
		);

	}

	/**
	 * Enqueues the css for the manual area
	 *
	 * themes or child themes can override the css by adding
	 * a css file named wpmanual.css in a subdirectory of their
	 * themes named css
	 * 
	 * @since 1.0
	 *
	 * @uses wpmanual_is_manual() to check we're on WP Manual area
	 * @uses get_stylesheet_directory() to get theme directory
	 * @uses get_stylesheet_directory_uri() to get theme url
	 * @uses get_template_directory() to get parent theme directory
	 * @uses get_template_directory_uri() to get parent theme url
	 * @uses wpmanual_get_css_url() to get css url of the plugin
	 * @uses wp_enqueue_style() to add the style to WordPress queue
	 * @uses wpmanual_get_manual_page_name() to check a manual page is displayed
	 * @uses wp_enqueue_script() to add a js script to WordPress queue
	 */
	public function enqueue_css() {

		if( wpmanual_is_manual() ) {
			$file = 'css/wpmanual.css';

			// Check child theme
			if ( file_exists( trailingslashit( get_stylesheet_directory() ) . $file ) ) {
				$location = trailingslashit( get_stylesheet_directory_uri() ) . $file ; 
				$handle   = 'wpmanual-child';

			// Check parent theme
			} elseif ( file_exists( trailingslashit( get_template_directory() ) . $file ) ) {
				$location = trailingslashit( get_template_directory_uri() ) . $file ;
				$handle   = 'wpmanual-parent';

			// use our style
			} else {
				$location = wpmanual_get_css_url() . 'wpmanual.css';
				$handle   = 'wpmanual';
			}

			wp_enqueue_style(  $handle, $location, false, $this->version );
		}

		if( wpmanual_get_manual_page_name() ) {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'thickbox' );
		}
	}

	/**
	 * Loads the translation
	 *
	 * @since 1.0
	 * 
	 * @uses get_locale()
	 * @uses load_textdomain()
	 */
	public function load_textdomain() {
		// try to get locale
		$locale = apply_filters( 'wpmanual_load_textdomain_get_locale', get_locale() );

		// if we found a locale, try to load .mo file
		if ( !empty( $locale ) ) {
			// default .mo file path
			$mofile_default = sprintf( '%s/languages/%s-%s.mo', $this->plugin_dir, $this->domain, $locale );
			// final filtered file path
			$mofile = apply_filters( 'wpmanual_textdomain_mofile', $mofile_default );
			// make sure file exists, and load it
			if ( file_exists( $mofile ) ) {
				load_textdomain( $this->domain, $mofile );
			}
		}
	}
	
}

/**
 * The WP Manual bootstrap function
 * 
 * @return object a unique instance of WP Manual
 */
function wpmanual() {
	return WP_Manual::instance();
}

wpmanual();


endif;
