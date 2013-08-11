<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * The main settings arguments
 * 
 * @return array
 */
function wpmanual_admin_get_settings_sections() {
	return (array) apply_filters( 'wpmanual_admin_get_settings_sections', array(
		'wpmanual_settings_main' => array(
			'title'    => __( 'Manual Options', 'wp-manual' ),
			'callback' => 'wpmanual_setting_callback_main_section',
			'page'     => 'wp-manual',
		)
	) );
}


/**
 * The different fields for the main settings
 * 
 * @return array
 */
function wpmanual_admin_get_settings_fields() {
	return (array) apply_filters( 'wpmanual_admin_get_settings_fields', array(

		/** Main Section ******************************************************/

		'wpmanual_settings_main' => array(

			// headings settings
			'_wpmanual_headings' => array(
				'title'             => __( 'Select the headings you wish to include in the table of content', 'wp-manual' ),
				'callback'          => 'wpmanual_admin_setting_callback_headings',
				'sanitize_callback' => 'wpmanual_sanitize_headings',
				'args'              => array()
			),

			// Back to top settings
			'_wpmanual_backtotop' => array(
				'title'             => __( 'Activate the back to top links', 'wp-manual' ),
				'callback'          => 'wpmanual_admin_setting_callback_backtotop',
				'sanitize_callback' => 'intval',
				'args'              => array()
			),
			// Thickbox settings
			'_wpmanual_thickbox' => array(
				'title'             => __( 'Activate Thickbox to zoom the images', 'wp-manual' ),
				'callback'          => 'wpmanual_admin_setting_callback_thickbox',
				'sanitize_callback' => 'intval',
				'args'              => array()
			)
		)
	) );
}

/**
 * Gives the setting fields for section
 * 
 * @param  string $section_id 
 * @return array  the fields
 */
function wpmanual_admin_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) )
		return false;

	$fields = wpmanual_admin_get_settings_fields();
	$retval = isset( $fields[$section_id] ) ? $fields[$section_id] : false;

	return (array) apply_filters( 'wpmanual_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Main section callback
 * 
 * @return string html section introduction
 */
function wpmanual_setting_callback_main_section() {
?>

	<p><?php _e( 'A few options to set..', 'wp-manual' ); ?></p>

<?php
}

/**
 * Let the admin customize headings to build the toc for
 *
 * @since 1.0
 * 
 * @uses get_option() to get the activated headings
 * @uses wpmanual_array_checked() to activate the previously selected headings
 * @return string html
 */
function wpmanual_admin_setting_callback_headings() {
	$defaults = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
	$headings = get_option( '_wpmanual_headings', $defaults );

	if( empty( $headings ) )
		$headings = array();
	?>
		
	<ul class="admin-checkbox">

		<?php foreach( $defaults as $heading ) :?>

			<li id="checkbox-<?php echo $heading;?>"><input type="checkbox" value="<?php echo $heading;?>" name="_wpmanual_headings[]" <?php wpmanual_array_checked( $headings, $heading, true );?>/> <?php echo $heading ;?></li>
		
		<?php endforeach;?>

	</ul>

	<?php
}

/**
 * Sanitize headings option
 *
 * @since 1.0
 *
 * @param array $option 
 * @return array the sanitized headings
 */
function wpmanual_sanitize_headings( $option ) {

	if( !is_array( $option ) )
		return false;

	if( count( $option ) == 0 )
		return false;

	$option =  array_map( 'sanitize_key', $option );
	
	return $option;
}

/**
 * Let the admin activate or not the back to top links
 *
 * @since 1.0
 * 
 * @uses get_option() to get the backtotop setting
 * @uses checked() to activate the checkbox if needed
 * @return string html
 */
function wpmanual_admin_setting_callback_backtotop() {
	$backtotop = get_option( '_wpmanual_backtotop', 0 );
	$backtotop =intval( $backtotop );
	?>
	<input id="_wpmanual_backtotop" name="_wpmanual_backtotop" type="checkbox" value="1" <?php checked( 1, $backtotop ); ?> />
	<?php
}

/**
 * Let the admin activate or not thickbox
 *
 * @since 1.0
 * 
 * @uses get_option() to get the thickbox setting
 * @uses checked() to activate the checkbox if needed
 * @return string html
 */
function wpmanual_admin_setting_callback_thickbox() {
	$thickbox = get_option( '_wpmanual_thickbox', 0 );
	$thickbox =intval( $thickbox );
	?>
	<input id="_wpmanual_thickbox" name="_wpmanual_thickbox" type="checkbox" value="1" <?php checked( 1, $thickbox ); ?> />
	<?php
}

/**
 * Displays the callback function for the root page id setting
 *
 * If the admin deleted the root WordPress page...
 *
 * @since 1.0
 * 
 * @return string html
 */
function wpmanual_admin_setting_callback_root_page() {
	?>
	<input name="_wpmanual_root_page_id" type="text" id="_wpmanual_root_page_id" class="regular-text code" />
	<label for="_wpmanual_root_page_id"><?php _e( 'Title of the root WordPress page', 'wp-manual' ); ?></label>
	<p class="description"><?php _e( 'Important the plugin needs a WordPress page to run, make sure to fill the field above with the wanted title for this page.', 'wp-manual' );?></p>
	<?php
}

/**
 * Sanitize WordPress root page title, creates the page and returns the page id
 *
 * @since 1.0
 *
 * @param string $option
 * @uses sanitize_text_field() to sanitize the title
 * @uses wpmanual_create_root_page() to create the root page
 * @return integer the root page_id
 */
function wpmanual_sanitize_root_page( $option ) {

	if( empty( $option ) )
		return 0;

	$title = sanitize_text_field( $option );

	if( !is_numeric( $option ) )
		$option = wpmanual_create_root_page( $title );

	return $option;
}

/**
 * Displays the settings form
 *
 * @since 1.0
 * 
 * @uses screen_icon() to add the settings icon
 * @uses settings_fields() to call the wp manual settings fields
 * @uses do_settings_sections() to display the settings section
 * @return string html
 */
function wpmanual_admin_settings() {
	?>
	<div class="wrap">
		<?php screen_icon(); ?>

		<h2><?php _e( 'WP Manual settings', 'wp-manual' );?></h2>

		<form action="options.php" method="post">

			<?php settings_fields( 'wp-manual' ); ?>

			<?php do_settings_sections( 'wp-manual' ); ?>

			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save settings', 'wp-manual' ); ?>" />
			</p>
		</form>
	</div>
	<?php
}