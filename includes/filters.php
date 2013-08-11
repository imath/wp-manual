<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// some filters to apply at key points
add_filter( 'editable_roles', 'wpmanual_blog_editable_roles', 10, 1 );
add_filter( 'views_users',    'wpmanual_blog_editable_roles', 10, 1 );
add_filter( 'wp_list_pages',  'wpmanual_current_classes',     10, 2 );
add_filter( 'wp_nav_menu',    'wpmanual_current_classes',     10, 2 );
add_filter( 'wp_title',       'wpmanual_filter_wp_title',     20, 3 );

add_filter( 'wpmanual_get_the_title', 'wptexturize'   );
add_filter( 'wpmanual_get_the_title', 'convert_chars' );
add_filter( 'wpmanual_get_the_title', 'trim'          );

add_filter( 'wpmanual_get_the_content',     'wpmanual_render_toc', 11, 1         );
add_filter( 'wpmanual_get_the_content',     'wptexturize'                        );
add_filter( 'wpmanual_get_the_content',     'convert_smilies'                    );
add_filter( 'wpmanual_get_the_content',     'convert_chars'                      );
add_filter( 'wpmanual_get_the_content',     'wpautop'                            );
add_filter( 'wpmanual_get_the_content',     'shortcode_unautop'                  );
add_filter( 'wpmanual_get_the_content',     'prepend_attachment'                 );
add_filter( 'wpmanual_get_the_content',     'do_shortcode',                12    );
add_filter( 'wpmanual_replace_the_content', 'wpmanual_check_for_password', 10, 2 );


add_filter( 'wpmanual_admin_get_settings_fields', 'wpmanual_maybe_add_page_setting', 1, 1 );
