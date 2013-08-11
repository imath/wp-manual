<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
/**
 * WP_Manual_Widget
 *
 * @since 1.0
 *
 * @package WP_Manual
 * @subpackage WP_Manual_Admin
 */
class WP_Manual_Admin {
	
	/**
	 * @var string Path to the WP Manual admin directory
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * @var string URL to the WP Manual admin directory
	 */
	public $admin_url = '';

	/**
	 * @var string Path to the WP Manual admin directory
	 */
	public $images_url = '';

	/**
	 * @var string URL to the WP Manual admin directory
	 */
	public $post_type = '';

	/**
	 * The constructor
	 *
	 * @since  1.0
	 *
	 * @uses WP_Manual_Admin::setup_globals() to register some globals
	 * @uses WP_Manual_Admin::includes() to include the needed files
	 * @uses WP_Manual_Admin::setup_actions() to add hooks at key points
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Admin globals
	 *
	 * @since 1.0
	 * 
	 * @access private
	 * @uses wpmanual() to get plugin's main instance
	 * @uses wpmanual_get_post_type() to get the WP Manual post type
	 */
	private function setup_globals() {
		$wpmanual = wpmanual();
		$this->post_type = wpmanual_get_post_type();
		$this->admin_dir  = trailingslashit( $wpmanual->includes_dir . 'admin'  ); // Admin path
		$this->admin_url  = trailingslashit( $wpmanual->includes_url . 'admin'  ); // Admin url
		$this->images_url = trailingslashit( $this->admin_url   . 'images' ); // Admin images URL
	}

	/**
	 * Includes needed files
	 *
	 * @since 1.0
	 * 
	 * @access private
	 */
	private function includes(){
		require( $this->admin_dir . 'chapters.php' );
		require( $this->admin_dir . 'settings.php' );
	}
	
	/**
	 * Add hooks at key points
	 *
	 * @since 1.0
	 * 
	 * @access private
	 */
	private function setup_actions() {
		
		add_action( 'admin_menu',                       array( $this, 'create_menu'              )        );
		add_action( 'admin_notices',                    array( $this, 'maybe_update_permastruct' )        );
		add_action( 'wpmanual_admin_head',              array( $this, 'admin_head'               )        );
		add_action( 'wpmanual_admin_register_settings', array( $this, 'register_settings'        )        );
		add_action( 'save_post',                        array( $this, 'update_toc'               ), 10, 2 );
		add_filter( 'wp_insert_post_parent',            array( $this , 'reset_parent'            ), 10, 4 );
		add_action( 'before_delete_post',               array( $this, 'remove_from_toc'          ), 10, 1 );
		
		do_action_ref_array( 'wpmanual_admin_loaded', array( &$this ) );
		
	}
	
	/**
	 * At plugin's activation, creates the need WordPress page
	 *
	 * @since 1.0
	 * 
	 * @uses get_option() to check if no page has been set
	 * @uses wp_insert_post() to create a page
	 * @uses update_option() to store some init settings
	 */
	public function new_install() {
		
		$wpmanual_page_id = get_option( '_wpmanual_root_page_id', 0 );
		
		if( empty( $wpmanual_page_id ) ) {
			$id = wpmanual_create_root_page();

			update_option( '_wpmanual_root_page_id', $id );

			// sets some options on activation..
			update_option( '_wpmanual_backtotop', 1 );
			update_option( '_wpmanual_thickbox', 1 );
		}

	}

	/**
	 * Creates the settings menu and eventually update the plugin's db version
	 *
	 * @since 1.0
	 * 
	 * @uses add_options_page() to add a submenu to WordPress settings
	 * @uses get_option() to check for plugin's db version
	 * @uses wpmanual_get_version() to get plugin's version
	 * @uses update_option() to store plugin's version
	 */
	public function create_menu(){

		add_options_page(
				__( 'Manual Options',  'wp-manual' ),
				__( 'Manual Options',  'wp-manual' ),
				'manage_manual',
				'wp-manual',
				'wpmanual_admin_settings'
		);

		if( get_option( 'wpmanual_version' ) != wpmanual_get_version() ) {

			do_action( 'wpmanual_upgrade', get_option( 'wpmanual_version' ) );

			update_option( 'wpmanual_version', wpmanual_get_version() );
		}

	}

	/**
	 * Checks permalinks and asks for pretty urls
	 *
	 * @since 1.0
	 *
	 * @global object $wp_rewrite
	 * @uses admin_url() to build the admin link to the needed settings page
	 * @uses wpmanual_get_root_page_id() to get the plugin's root page id
	 */
	public function maybe_update_permastruct() {
		global $wp_rewrite;

		if ( isset( $_POST['permalink_structure'] ) )
			return;

		/**
	 	* Are pretty permalinks enabled?
	 	*/
		if ( empty( $wp_rewrite->permalink_structure ) ) {
			?>
			<div id="message" class="updated fade">
			
				<p><?php printf( __( '<strong>WP Manual is almost ready</strong>. You need to <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'wp-manual' ), admin_url( 'options-permalink.php' ) );?></p>

			</div>
			<?php
		}

		/**
		 * Do we have a WordPress root page
		 */
		$wpmanual_page_id = wpmanual_get_root_page_id();
		
		if( empty( $wpmanual_page_id ) ) {
			?>
			<div id="message" class="error fade">
			
				<p><?php printf( __( '<strong>Oh no ! WP Manual is broken</strong>. It requires a WordPress page to run, please go to <a href="%s">plugin settings</a> to fix this.', 'wp-manual' ), add_query_arg( array( 'page' => 'wp-manual' ), admin_url( 'admin.php' ) ) );?></p>

			</div>
			<?php
		}
	}

	/**
	 * Checks current post type is a manual one
	 *
	 * @since 1.0
	 * 
	 * @uses get_current_screen() to get current screen object
	 */
	private function bail() {
		if ( !isset( get_current_screen()->post_type ) || ( $this->post_type != get_current_screen()->post_type ) )
			return true;

		return false;
	}

	/**
	 * Updates the table of content once a manual page has been saved
	 *
	 * @since 1.0
	 *
	 * @param integer $page_id the page id
	 * @param object $page the page object
	 * @uses WP_Manual_Admin::bail() to stop the process in not a manual page
	 * @uses current_user_can() to check for user's capability
	 * @uses wp_is_post_revision() to get the parent post id
	 * @uses get_option() to load the previously saved table of content
	 * @uses WP_Manual_Build_Chapters to parse the page content and create the TOC
	 * @uses update_option() to save the table of content
	 */
	public function update_toc( $page_id, $page ) {

		if ( $this->bail() ) return $page_id;

		// Bail if doing an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $page_id;

		// Bail if not a post request
		if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) )
			return $page_id;

		// Bail if current user cannot edit this topic
		if ( !current_user_can( 'edit_manual', $page_id ) )
			return $page_id;

		$current_id = $page_id;

		if ( $the_page = wp_is_post_revision( $page ) )
			$page_id = $the_page;

		if( $page->post_status == 'inherit' )
			$page_id = $page->post_parent;

		if( !empty( $page_id ) && !empty( $page->post_content ) && !empty( $page->post_title ) ) {

			$toc_option = get_option( '_wpmanual_toc', array() );
			$toc_element = new stdClass();

			$headings = new WP_Manual_Build_Chapters( $page->post_content ); 

			$toc_element->title = $page->post_title;
			$toc_element->permalink = get_permalink( $page_id );
			$toc_element->name = $page->post_name;
			$toc_element->headings = $headings->toc;

			$toc_option[$page_id] = $toc_element;

			update_option( '_wpmanual_toc', $toc_option );

			do_action( 'wpmanual_toc_updated', $page_id, $page );
		}

		return $current_id;

	}

	/**
	 * When a manual page is removed, updates the TOC
	 *
	 * @since 1.0
	 * 
	 * @param  integer $page_id the page id
	 * @uses get_post_type() to check a manual page is being removed
	 * @uses get_option() to load the previously saved table of content
	 * @uses update_option() to save the table of content
	 */
	public function remove_from_toc( $page_id ) {

		/* we need to check if the admin just deleted the root page !! */
		if( wpmanual_get_root_page_id() == $page_id )
			delete_option( '_wpmanual_root_page_id' );

		if( $this->post_type != get_post_type( $page_id ) )
			return;

		if( !empty( $page_id ) ) {

			$toc_option = get_option( '_wpmanual_toc', array() );
			
			if( !empty( $toc_option[$page_id] ) ) {
				unset( $toc_option[$page_id] );
				update_option( '_wpmanual_toc', $toc_option );
			}

			do_action( 'wpmanual_toc_deleted', $page_id );
		}
	}

	/**
	 * WP Manual doesn't allow page nesting so far...
	 *
	 * @since 1.0
	 * 
	 * @param  integer $parent the parent page id or current one
	 * @param  integer $page_id the current page id
	 * @param  array $keys the keys of $postarr in an array
	 * @param  array $postarr array of post vars
	 * @uses WP_Manual_Admin::bail() to stop the process in not a manual page
	 * @return  integer 0
	 */
	public function reset_parent( $parent, $page_id = 0, $keys = array(), $postarr = array()  ) {

		if ( !defined( 'DOING_AJAX' ) && $this->bail() ) return $parent;

		// Bail if doing an autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $parent;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && $postarr['post_type'] != $this->post_type )
			return $parent;

		return 0;
	}

	/**
	 * WP Manual settings
	 *
	 * @since 1.0
	 * 
	 * @uses wpmanual_admin_get_settings_sections() to get settings sections
	 * @uses current_user_can() to check for user's capability
	 * @uses wpmanual_admin_get_settings_fields_for_section() to list the settings fields for section
	 * @uses add_settings_section() to add a settings section
	 * @uses add_settings_field() to add the setting fields
	 * @uses register_setting() to register the settings
	 */
	public static function register_settings() {
		// Bail if no sections available
		$sections = wpmanual_admin_get_settings_sections();

		if ( empty( $sections ) )
			return false;

		// Loop through sections
		foreach ( (array) $sections as $section_id => $section ) {

			// Only proceed if current user can see this section
			if ( ! current_user_can( 'manage_manual' ) )
				continue;

			// Only add section and fields if section has fields
			$fields = wpmanual_admin_get_settings_fields_for_section( $section_id );
			if ( empty( $fields ) )
				continue;

			// Add the section
			add_settings_section( $section_id, $section['title'], $section['callback'], $section['page'] );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {

				// Add the field
				add_settings_field( $field_id, $field['title'], $field['callback'], $section['page'], $section_id, $field['args'] );

				// Register the setting
				register_setting( $section['page'], $field_id, $field['sanitize_callback'] );
			}
		}
	}

	/**
	 * Loads some css rules to add some icon to menus ans WP Manual Admin area
	 *
	 * @since 1.0
	 * 
	 * @uses wpmanual_get_version() to get plugin's version
	 * @uses sanitize_html_class() to sanitize the manual class
	 * @uses wpmanual_get_post_type() to get WP Manual post type
	 * @return string css rules
	 */
	public function admin_head() {
		$version          = wpmanual_get_version();
		$menu_icon_url    = $this->images_url . 'menu.png?ver='       . $version;
		$icon32_url       = $this->images_url . 'icon-32.png?ver='    . $version;

		$manual_class = sanitize_html_class( wpmanual_get_post_type() );
		?>

		<style type="text/css" media="screen">
		/*<![CDATA[*/

			/* Icon 32 */
			#icon-edit.icon32-posts-<?php echo $manual_class; ?> {
				background: url('<?php echo $icon32_url; ?>');
				background-repeat: no-repeat;
			}

			/* Icon Positions */
			#icon-edit.icon32-posts-<?php echo $manual_class; ?> {
				background-position: 0px 0px;
			}

			/* Menu */
			#menu-posts-<?php echo $manual_class; ?> .wp-menu-image,
			#menu-posts-<?php echo $manual_class; ?>:hover .wp-menu-image,

			#menu-posts-<?php echo $manual_class; ?>.wp-has-current-submenu .wp-menu-image{
				background: url('<?php echo $menu_icon_url; ?>');
				background-repeat: no-repeat;
			}

			/* Menu Positions */
			#menu-posts-<?php echo $manual_class; ?> .wp-menu-image {
				background-position: 0px -32px;
			}
			#menu-posts-<?php echo $manual_class; ?>:hover .wp-menu-image,
			#menu-posts-<?php echo $manual_class; ?>.wp-has-current-submenu .wp-menu-image {
				background-position: 0px 0px;
			}

		/*]]>*/
		</style>

		<?php
	}

}

/**
 * Attaches the Admin to plugin's main instance
 *
 * @since 1.0
 * 
 * @uses wpmanual() to get plugin's main instance
 * @uses WP_Manual_Admin to load the admin part of the plugin
 */
function wpmanual_admin() {
	wpmanual()->admin = new WP_Manual_Admin();
}

// WordPress administration
add_action( 'wpmanual_loaded', 'wpmanual_admin', 10 );

// Plugin's activation
add_action( 'wpmanual_activation', array( 'WP_Manual_Admin', 'new_install') );