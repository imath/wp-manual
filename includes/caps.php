<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/** This part is inspired by bbPress way of checking capabilities and creating roles **/

/**
 * List the manual post type capabilities
 *
 * @since 1.0
 * 
 * @return array the capabilities
 */
function wpmanual_get_caps() {
	return apply_filters( 'wpmanual_get_caps', array (
		'edit_posts'          => 'edit_manuals',
		'edit_others_posts'   => 'edit_others_manuals',
		'publish_posts'       => 'publish_manuals',
		'read_private_posts'  => 'read_private_manuals',
		'delete_posts'        => 'delete_manuals',
		'delete_others_posts' => 'delete_others_manuals'
	) );
}

/**
 * Maps the capabilities for the manual post type
 *
 * @since 1.0
 *
 * @uses get_post() to get the current post
 * @uses get_post_type_object() to get caps for post type object
 * @uses user_can() to check for cap
 * @return array the capability mapped
 */
function wpmanual_map_cap( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// What capability is being checked?
	switch ( $cap ) {

		case 'edit_manual' :
			
			$_post = get_post( $args[0] );
			if ( !empty( $_post ) ) {

				$post_type = get_post_type_object( $_post->post_type );
				$caps      = array();

				if ( (int) $user_id == (int) $_post->post_author ) {
					$caps[] = $post_type->cap->edit_posts;
				} elseif ( user_can( $user_id, 'edit_others_manuals' ) ) {
					$caps = array( 'edit_others_manuals' );
				} else {
					$caps[] = $post_type->cap->edit_others_posts;
				}
			} else {
				$caps = array( 'edit_pages' );
			}

			break;

		/** Deleting **********************************************************/

		case 'delete_manual' :

			$_post = get_post( $args[0] );
			if ( !empty( $_post ) ) {

				$post_type = get_post_type_object( $_post->post_type );
				$caps      = array();

				if ( user_can( $user_id, 'manage_manual' ) ) {
					$caps[] = 'manage_manual';
				} else {
					$caps[] = $post_type->cap->delete_others_posts;
				}
			}

			break;

		case 'admin_manuals'    :
				$caps = array( 'manage_manual' );
			break;
	}

	return apply_filters( 'wpmanual_map_cap', $caps, $cap, $user_id, $args );
}

add_filter( 'wpmanual_map_meta_caps', 'wpmanual_map_cap', 10, 4 );

/**
 * Adds the WP Manual roles to WordPress ones
 *
 * @since  1.0
 * 
 * @uses wpmanual_get_wp_roles() to get WordPress roles
 * @uses wpmanual_get_dynamic_roles() to get the WP Manual roles
 * @return object WP Roles
 */
function wpmanual_add_manual_roles() {
	$wp_roles = wpmanual_get_wp_roles();

	foreach( wpmanual_get_dynamic_roles() as $role_id => $details ) {
		$wp_roles->roles[$role_id]        = $details;
		$wp_roles->role_objects[$role_id] = new WP_Role( $role_id, $details['capabilities'] );
		$wp_roles->role_names[$role_id]   = $details['name'];
	}

	return $wp_roles;
}

/**
 * The Manual Editor role
 *
 * @since 1.0
 * 
 * @return string
 */
function wpmanual_get_primary_role() {
	return apply_filters( 'wpmanual_get_primary_role', 'wpmanual_editor' );
}

/**
 * The Manual default role
 *
 * @since 1.0
 * 
 * @return string
 */
function wpmanual_get_default_role() {
	return apply_filters( 'wpmanual_get_default_role', 'wpmanual_reader' );
}

/**
 * List the caps for the WP Manual role requested
 *
 * @since 1.0
 * 
 * @param  string $role the role to get the needed cap
 * @return array the role capabilities
 */
function wpmanual_get_caps_for_role( $role = '' ) {

	// Which role are we looking for?
	switch ( $role ) {

		// Manual Editor
		case wpmanual_get_primary_role() :
			$caps = array(
				// Primary caps
				'manage_manual'          => true,
				'read_manual'            => true,
				'upload_files'           => true,

				// Editing caps
				'publish_manuals'        => true,
				'edit_manuals'           => true,
				'edit_others_manuals'    => true,
				'delete_manuals'         => true,
				'delete_others_manuals'  => true,
				'read_private_manuals'   => true,
			);

			break;


		// Manual Reader
		case wpmanual_get_default_role() :
		default :
			$caps = array(
				// Primary caps
				'manage_manual'          => false,
				'read_manual'            => true,
				'upload_files'           => false,

				// Editing caps
				'publish_manuals'        => false,
				'edit_manuals'           => false,
				'edit_others_manuals'    => false,
				'delete_manuals'         => false,
				'delete_others_manuals'  => false,
				'read_private_manuals'   => false,
			);

			break;
	}

	return apply_filters( 'wpmanual_get_caps_for_role', $caps, $role );
}

/**
 * The Roles with caps to loop through
 * 
 * @since  1.0
 *
 * @uses wpmanual_get_primary_role() to get the full cap role
 * @uses wpmanual_get_caps_for_role() to get the caps for the role
 * @uses wpmanual_get_default_role() to get the default role
 * @return array associtative array of roles with caps
 */
function wpmanual_get_dynamic_roles() {
	return (array) apply_filters( 'wpmanual_get_dynamic_roles', array(

		// Keymaster
		wpmanual_get_primary_role() => array(
			'name'         => __( 'manual-editor', 'wp-manual' ),
			'capabilities' => wpmanual_get_caps_for_role( wpmanual_get_primary_role() )
		),

		// Moderator
		wpmanual_get_default_role() => array(
			'name'         => __( 'manual-reader', 'wp-manual' ),
			'capabilities' => wpmanual_get_caps_for_role( wpmanual_get_default_role() )
		),

	) );
}

/**
 * Maps WordPress Roles to WP Manual ones
 *
 * @since 1.0
 *
 * @uses wpmanual_get_default_role() to get the default role
 * @uses wpmanual_get_primary_role() to get the full cap role
 * @return array the mapped roles
 */
function wpmanual_get_user_role_map() {

	// Get the role once here
	$default_role = wpmanual_get_default_role();
	$full_role = wpmanual_get_primary_role();

	// Return filtered results, forcing admins and editors to WP Manual Editors
	return (array) apply_filters( 'wpmanual_get_user_role_map', array (
		'administrator' => $full_role,
		'editor'        => $full_role,
		'author'        => $default_role,
		'contributor'   => $default_role,
		'subscriber'    => $default_role
	) );
}


/**
 * Gets WordPress roles
 *
 * @since 1.0
 *
 * @global $wp_roles
 * @return object WP Roles
 */
function wpmanual_get_wp_roles() {
	global $wp_roles;

	// Load roles if not set
	if ( ! isset( $wp_roles ) )
		$wp_roles = new WP_Roles();

	return $wp_roles;
}

/**
 * Gets the current user role
 *
 * @since  1.0
 * 
 * @param  integer $user_id the logged in user id
 * @uses wpmanual_add_manual_roles() to add WP Manual roles
 * @uses get_userdata() to get user's data
 * @return array the role
 */
function wpmanual_get_user_blog_role( $user_id = 0 ) {
	
	$wp_roles  = wpmanual_add_manual_roles();

	$user      = get_userdata( $user_id );
	$role      = false;
	$all_roles = apply_filters( 'editable_roles', $wp_roles->roles );

	if ( ! empty( $user->roles ) ) {
		$roles = array_intersect( array_values( $user->roles ), array_keys( $all_roles ) );

		// If there's a role in the array, use the first one
		if ( !empty( $roles ) ) {
			@$role = array_shift( array_values( $roles ) );
		}
	}

	return apply_filters( 'wpmanual_get_user_blog_role', $role, $user_id, $user );
}

/**
 * Sets a WP Manual Role to the current user without adding it to db
 *
 * @since 1.0
 * 
 * @uses is_user_logged_in() to check we have someone to set a role to
 * @uses wpmanual_current_user_id() to get current user id
 * @uses wpmanual_get_user_blog_role() to get the user's blog role
 * @uses wpmanual_get_user_role_map() to get the mapped roles
 * @uses wpmanual_get_default_role() to get the reader role
 * @uses wpmanual_add_manual_roles() to reference our roles
 * @uses wpmanual() to get main plugin instance
 */
function wpmanual_set_current_user_default_role() {

	// Catch all, to prevent premature user initialization
	if ( ! did_action( 'set_current_user' ) )
		return;

	// Bail if not logged in or already a member of this site
	if ( ! is_user_logged_in() )
		return;

	// Get the current user ID
	$user_id = wpmanual_current_user_id();

	// Get the current user's WordPress role. Set to empty string if none found.
	$user_role   = wpmanual_get_user_blog_role( $user_id );

	// Get the role map
	$role_map    = wpmanual_get_user_role_map();

	// Use a mapped role
	if ( isset( $role_map[$user_role] ) ) {
		$new_role = $role_map[$user_role];

	// Use the default role
	} else {
		$new_role = wpmanual_get_default_role();
	}

	wpmanual_add_manual_roles();
	$wpmanual = wpmanual();
	$wpmanual->current_user->add_role( $new_role );

	$wpmanual->current_user->caps[$new_role] = true;
	$wpmanual->current_user->get_role_caps();

}

/**
 * Strips WP Manual roles to avoid being in the profile edit/ user add role select box 
 *
 * @since 1.0
 * 
 * @param  array  $all_roles
 * @uses wpmanual_get_dynamic_roles() to get the WP Manual roles
 * @return array the roles - WP Manual ones
 */
function wpmanual_blog_editable_roles( $all_roles = array() ) {

	// Loop through bbPress roles
	foreach ( array_keys( wpmanual_get_dynamic_roles() ) as $wpmanual_role ) {

		// Loop through WordPress roles
		foreach ( array_keys( $all_roles ) as $wp_role ) {

			// If keys match, unset
			if ( $wp_role == $wpmanual_role ) {
				unset( $all_roles[$wp_role] );
			}
		}
	}

	return $all_roles;
}
