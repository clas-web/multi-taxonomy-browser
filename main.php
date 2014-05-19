<?php
/*
Plugin Name: Multi-Taxonomy Browser
Plugin URI: 
Description: 
Version: 0.1.0
Author: Crystal Barton
Author URI: http://www.crystalbarton.com
*/

// 
// Show post of type "post" or "connection" with both categories and one of the tags: 
// ?mt-archive&post_type=post,connection&category=Category1,Category3&post_tag=Tag1|Tag2
// 
// Show post of type "post" or "connection" with both categories and both tags:
// ?mt-filtered-archive&post_type=post,connection&category=Category1,Category3&post_tag=Tag1,Tag2
// 
// Show post of type "post" or "connection" with one of the categories and one of the tags:
// ?mt-combined-archive&post_type=post,connection&category=Category1,Category3&post_tag=Tag1,Tag2
// 


require_once( dirname(__FILE__).'/config.php' );
require_once( dirname(__FILE__).'/functions.php' );
require_once( dirname(__FILE__).'/api.php' );
require_once( dirname(__FILE__).'/filter-widget.php' );




add_filter( 'query_vars', array('MultiTaxonomyBrowser', 'query_vars') );
add_action( 'parse_request', array('MultiTaxonomyBrowser', 'parse_request') );

add_filter( 'the_content', array('MultiTaxonomyBrowser', 'process_content') );

foreach( array('archive','taxonomy','category','tag') as $archive )
	add_filter( $archive.'_template',  array('MultiTaxonomyBrowser', 'get_archive_template_file') );
add_filter( 'search_template',  array('MultiTaxonomyBrowser', 'get_search_template_file') );




class MultiTaxonomyBrowser
{
	
	public static $page_type = MTType::None;
	
	
	
	/**
	 * Adds plugin's tag to the list of parseable query variables.
	 */
	public static function query_vars( $query_vars )
	{
		$query_vars[] = MT_COMBINED_ARCHIVE;
		$query_vars[] = MT_FILTERED_ARCHIVE;

		$query_vars[] = MT_COMBINED_SEARCH;
		$query_vars[] = MT_FILTERED_SEARCH;
		
		return $query_vars;
	}


	/**
	 * Check for the plugin's tag and if found, then process the mobile post data
	 * from the Android device.
	 */
	public static function parse_request( &$wp )
	{
		global $wp;

		if( array_key_exists(MT_COMBINED_ARCHIVE, $wp->query_vars) )
		{
			self::$page_type = MTType::CombinedArchive;
			MultiTaxonomyBrowser_Api::ProcessCombinedArchive();
			add_action( 'pre_get_posts', array('MultiTaxonomyBrowser_Api', 'alter_wp_query') );
		}
		elseif( array_key_exists(MT_FILTERED_ARCHIVE, $wp->query_vars) )
		{
			self::$page_type = MTType::FilteredArchive;
			MultiTaxonomyBrowser_Api::ProcessFilteredArchive();
			add_action( 'pre_get_posts', array('MultiTaxonomyBrowser_Api', 'alter_wp_query') );
		}

		if( array_key_exists(MT_COMBINED_SEARCH, $wp->query_vars) )
		{
			self::$page_type = MTType::CombinedSearch;
			MultiTaxonomyBrowser_Api::ProcessCombinedSearch();
			add_action( 'pre_get_posts', array('MultiTaxonomyBrowser_Api', 'alter_wp_query') );
		}
		elseif( array_key_exists(MT_FILTERED_SEARCH, $wp->query_vars) )
		{
			self::$page_type = MTType::FilteredSearch;
			MultiTaxonomyBrowser_Api::ProcessFilteredSearch();
			add_action( 'pre_get_posts', array('MultiTaxonomyBrowser_Api', 'alter_wp_query') );
		}
	}
	
	
	
	public static function process_content( $content )
	{
		$matches = NULL;
		$num_matches = preg_match_all("/\[mt-link(.+)\]/", $content, $matches, PREG_SET_ORDER);

		if( ($num_matches !== FALSE) && ($num_matches > 0) )
		{
			for( $i = 0; $i < $num_matches; $i++ )
			{
				$content = str_replace($matches[$i][0], self::get_shortcode_link( $matches[$i][0] ), $content);
			}
		}
		
		return $content;
	}
	
	
	/*
	[mt-link type="mt-filtered-archive"
	         post-type="post,connection"
	         taxonomies="category,post_tag"
	         category="category1,category3"
	         post_tag="tag1,tag3"
	*/
	
	public static function get_shortcode_link( $shortcode )
	{
		$mt_type = MT_FILTERED_ARCHIVE;
		$post_types = array( 'post' );
		$taxnames = array();
		$taxonomies = array();

		$matches = NULL;
		if( preg_match("/type=\"([^\"]+)\"/", $shortcode, $matches) )
			$mt_type = trim($matches[1]);
			
		$matches = NULL;
		if( preg_match("/post-types=\"([^\"]+)\"/", $shortcode, $matches) )
			$post_types = explode( ',', trim($matches[1]) );

		$matches = NULL;
		if( preg_match("/taxonomies=\"([^\"]+)\"/", $shortcode, $matches) )
			$taxnames = explode( ',', trim($matches[1]) );
		
		foreach( $taxnames as $taxname )
		{
			$matches = NULL;
			if( preg_match("/".$taxname."=\"([^\"]+)\"/", $shortcode, $matches) )
				$taxonomies[$taxname] = explode( ',', trim($matches[1]) );
			else
				$taxonomies[$taxname] = array();
		}
		
		$url = get_home_url().'/?'.$mt_type.'&post-types='.implode(',',$post_types);
		foreach( $taxonomies as $taxname => $terms )
		{
			$url .= '&'.$taxname.'='.implode(',',$terms);
		}
		
		return $url;
	}
	
	
	
	public static function get_archive_template_file( $template )
	{
		if( !mt_is_archive() ) return $template;
	
		$template_filenames = array();
	
		if( mt_is_filtered() )
			array_push( $template_filenames, 'mt-filtered-archive' );
		else if( mt_is_combined() )
			array_push( $template_filenames, 'mt-combined-archive' );
	
		array_push( $template_filenames, 'mt-archive' );
	
		return self::find_template_file( $template_filenames, $template );
	}
	
	public static function get_search_template_file( $template )
	{
		if( !mt_is_search() ) return $template;
	
		$template_filenames = array();
	
		if( mt_is_filtered() )
			array_push( $template_filenames, 'mt-filtered-search' );
		else if( mt_is_combined() )
			array_push( $template_filenames, 'mt-combined-search' );
	
		array_push( $template_filenames, 'mt-search' );
	
		return self::find_template_file( $template_filenames, $template );	
	}
	
	
	private static function find_template_file( $template_filenames, $template )
	{
		foreach( $template_filenames as $filename )
		{
			if( file_exists( get_stylesheet_directory().'/'.$filename.'.php' ) )
			{
				$template = get_stylesheet_directory().'/'.$filename.'.php';
				break;
			}
			if( file_exists( get_template_directory().'/'.$filename.'.php' ) )
			{
				$template = get_template_directory().'/'.$filename.'.php';
				break;
			}
		}
		return $template;
	}
}


