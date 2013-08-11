<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
/**
 * Builds the Table of content and parse manual pages to add anchors 
 *
 * This part is using @mattyza's WP Section Index scripts for content parsing
 * @see http://wordpress.org/plugins/wp-section-index/
 *
 * @since 1.0
 *
 * @package WP_Manual
 * @subpackage WP_Manual_Build_Chapters
 */
class WP_Manual_Build_Chapters {

	public $headings;
	public $content;
	public $toc;
	public $increment;
	public $backtotop_counter;

	/**
	 * Loads the constructor
	 *
	 * @since  1.0
	 * 
	 * @param  string $content the content to parse
	 * @uses WP_Manual_Build_Chapters::__construct() to begin the process
	 */
	public function wp_manual_build_chapters( $content = '' ) {
		$this->__construct( $content );
	} 

	/**
	 * The Constructor
	 *
	 * @since 1.0
	 * 
	 * @param string $content the content to parse and to build the toc for
	 * @uses get_option() to get the admin specific settings
	 * @uses is_admin() to check if we are in WordPress backend
	 * @uses WP_Manual_Build_Chapters::create_toc() to build the TOC
	 * @uses WP_Manual_Build_Chapters::create_content_anchors() to create the heading anchors
	 * @uses wpmanual_backtotop_activated() to check back to top links are activated
	 * @uses WP_Manual_Build_Chapters::create_back_to_tops() to create the back to tops links
	 * @uses wpmanual_thickbox_activated() to check thickbox is activated
	 * @uses WP_Manual_Build_Chapters::create_thickbox_classes() to add thickbox classes to image links
	 */
	public function __construct( $content = '' ) {
		if( empty( $content) )
			return false;
		else
			$this->content = $content;

		$this->headings = get_option( '_wpmanual_headings', array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ) );
		
		if( is_admin() )
			$this->create_toc();

		else {
			$this->create_content_anchors();

			if( wpmanual_backtotop_activated() )
				$this->create_back_to_tops();

			if( wpmanual_thickbox_activated() )
				$this->create_thickbox_classes();
		}
	
	}

	/**
	 * Get anchors name for a match
	 *
	 * @since 1.0
	 * 
	 * @param string $match the matched content
	 * @uses  remove_accents() to avoid accents trouble in url
	 * @uses  wptexturize() to be sure quotes are encoded the WordPress way
	 */
	private function get_anchors( $match ) {

		if( empty( $match ) )
			return;

		// Setup the text to be used as the anchor.
		$anchor_text = '';
		$anchor_text = strtolower( $match );
		
		// Limit anchor text to only 4 words.
		$anchor_text_bits = explode(' ', $anchor_text);
		if ( count( $anchor_text_bits ) > 4 ) { $limited_anchor_text_bits = 4; } else { $limited_anchor_text_bits = count( $anchor_text_bits ); }
		$anchor_text = '';
		for ( $i = 0; $i < $limited_anchor_text_bits; $i++ ) {
			$anchor_text .= $anchor_text_bits[$i];
				
				if ( $i == $limited_anchor_text_bits - 1 ) {} else {	$anchor_text .= ' '; }

		} // End FOR Loop
		
		// Setup and clean up anchor text.
		$anchor_text = remove_accents( $anchor_text );
		$anchor_text = wptexturize( $anchor_text );
		$anchor_text = str_replace( ' ', '_', $anchor_text );
		$anchor_text = str_replace( '.', '', $anchor_text );
		$anchor_text = rawurlencode( $anchor_text );
		$anchor_text = $anchor_text .'_'. $this->increment;

		$this->increment += 1;

		return $anchor_text;
	}

	/**
	 * Add anchors to content that matched
	 *
	 * @since 1.0
	 * 
	 * @param array $matches the matched array
	 * @uses WP_Manual_Build_Chapters::get_anchors() to build the anchor name and id for each match
	 * @return string the heading anchors to add
	 */
	private function get_section_headings( $matches ) {
		
		$anchor_text = $this->get_anchors( $matches[1] );
		
		$anchor = '';

		$anchor = '<a name="' . $anchor_text . '" id="' . $anchor_text . '" class="manuel_anchor">&nbsp;</a>';
			
		// Construct the filtered heading tag with the newly created anchor.
		$filtered_heading = '';
		$filtered_heading = "\n" . $anchor . "\n" . $matches[0];
			
		$filtered_heading = trim( $filtered_heading );
		
		
		return $filtered_heading;
		
	}

	/**
	 * Loops the activated headings and creates the anchors
	 *
	 * @since 1.0
	 * 
	 * @uses WP_Manual_Build_Chapters::get_section_headings() to do the job
	 */
	public function create_content_anchors() {

		$this->increment = 0;

		foreach( $this->headings as $heading ) {

			$pattern = '/<' . $heading . '[^>]*>(.*?)<\/' . $heading . '>/i';
		
			$this->content = preg_replace_callback( $pattern, array( &$this, 'get_section_headings' ), $this->content );

		}
		
	}

	/**
	 * Gets back to top links
	 *
	 * @since 1.0
	 * 
	 * @param array $matches the matched content
	 * @return  string the back to top link
	 */
	public function get_back_to_tops( $matches ) {

		$backtotop_anchor = '';
		
		if ( $this->backtotop_counter == 0 ) {} else {
			
			// Setup the text to be used as the anchor.
			$backtotop_anchor = apply_filters( 'wpmanual_backtotop_anchor', '<a href="#wpmanual" class="wpmanual_to_top">' . __( 'Back to top', 'wp-manual' ) . ' &uarr;</a>' );
		
		} // End IF Statement		
		
		$this->backtotop_counter++;
		
		// Construct the filtered heading tag with the newly created anchor.
		$hyperlink = '';
		$hyperlink = "\n" . $backtotop_anchor . "\n" . $matches[0];
		
		$hyperlink = trim( $hyperlink );
		
		return $hyperlink;

	}

	/**
	 * Loop activated headings to add back to top anchors to content
	 *
	 * @since 1.0
	 *
	 * @uses WP_Manual_Build_Chapters::get_back_to_tops() to do the job
	 */
	public function create_back_to_tops() {

		foreach( $this->headings as $heading ) {

			$pattern = '/<' . $heading . '[^>]*>(.*?)<\/' . $heading . '>/i';
		
			$this->content = preg_replace_callback( $pattern, array( &$this, 'get_back_to_tops' ), $this->content );

		}

		// Let's finally add one more back to top anchor at the end of the content
		$this->content .= "\n" . apply_filters( 'wpmanual_backtotop_anchor', '<a href="#wpmanual" class="wpmanual_to_top">' . __( 'Back to top', 'wp-manual' ) . ' &uarr;</a>' ) . "\n";
	
	}

	/**
	 * Gets thickbox attributes for each image links
	 *
	 * @since 1.0
	 * 
	 * @param array $matches the matched content
	 * @return  string the thickbox needed attributes
	 */
	public function get_thickbox_classes( $matches ) {
		$to_replace = $matches[0];

		if( is_array( $matches ) && !empty( $matches[3] ) && in_array( $matches[3], array( 'png', 'jpg', 'jpeg', 'gif' ) ) )
			$to_replace = str_replace( '>', 'class="thickbox" title="'. __( 'Click to zoom', 'wp-manual' ) .'">', $to_replace );

		return $to_replace;
	}

	/**
	 * Gets thickbox attributes for each image links
	 *
	 * @since 1.0
	 * 
	 * @uses  WP_Manual_Build_Chapters::get_thickbox_classes to add the needed attributes
	 */
	public function create_thickbox_classes() {

		$pattern = '/<a(.+?)href=\"(.+?).(jpe?g|png|gif)\"(.*?)>/i';

		$this->content = preg_replace_callback( $pattern, array( &$this, 'get_thickbox_classes' ), $this->content );
	}

	/**
	 * Creates the table of content
	 *
	 * @since 1.0
	 * 
	 * @uses WP_Manual_Build_Chapters::get_anchors() to build the anchor name and id for each match
	 */
	public function create_toc() {

		$found_headings = $this->toc = array();
		$this->increment = 0;
		

		foreach( $this->headings as $heading ) {

			$pattern = '/<' . $heading . '[^>]*>(.*?)<\/' . $heading . '>/i';

			preg_match_all( $pattern, $this->content, $matches, PREG_OFFSET_CAPTURE );

			foreach( $matches[1] as $key => $ids ) {
				$found_headings[$ids[1]]= array( 'type' => $heading, 'anchor' => $this->get_anchors( $ids[0] ), 'title' => $ids[0] );
			}
			
		}

		ksort( $found_headings );

		$this->toc = $found_headings;
	}
}