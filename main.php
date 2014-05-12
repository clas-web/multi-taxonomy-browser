<?php
/*
Plugin Name: Multiple Taxonomy Archives
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




add_filter( 'query_vars', array('MultiTags_Main', 'query_vars') );
add_action( 'parse_request', array('MultiTags_Main', 'parse_request') );




class MultiTags_Main
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
			MultiTags_Api::ProcessCombinedArchive();
			add_action( 'pre_get_posts', array('MultiTags_Api', 'alter_wp_query') );
		}
		elseif( array_key_exists(MT_FILTERED_ARCHIVE, $wp->query_vars) )
		{
			self::$page_type = MTType::FilteredArchive;
			MultiTags_Api::ProcessFilteredArchive();
			add_action( 'pre_get_posts', array('MultiTags_Api', 'alter_wp_query') );
		}

		if( array_key_exists(MT_COMBINED_SEARCH, $wp->query_vars) )
		{
			self::$page_type = MTType::CombinedSearch;
			MultiTags_Api::ProcessCombinedSearch();
			add_action( 'pre_get_posts', array('MultiTags_Api', 'alter_wp_query') );
		}
		elseif( array_key_exists(MT_FILTERED_SEARCH, $wp->query_vars) )
		{
			self::$page_type = MTType::FilteredSearch;
			MultiTags_Api::ProcessFilteredSearch();
			add_action( 'pre_get_posts', array('MultiTags_Api', 'alter_wp_query') );
		}
	}
	
	
	
	public static function get_current_filter_data()
	{
		$data = array(
			'post-types' => array(),
			'taxonomies' => array(),
		);
		
		if( !is_archive() && !mt_is_archive() && !is_search() ) return $data;
		
		//----------------------------------------
		
		$qo = get_queried_object();
		if( $qo != null )
		{
			if( is_category() || is_tag() || is_tax() )
			{
				$data['taxonomies'][$qo->taxonomy] = array( $qo->slug );
			}
			
// 			print_r($qo);
		}
		
		//----------------------------------------
		
		if( mt_is_archive() )
		{
			$mt_post_types = MultiTags_Api::GetPostTypes();
			$mt_taxonomies = MultiTags_Api::GetTaxonomies();
			
// 			mt_print( $mt_post_types, 'MT Post Types' );
// 			mt_print( $mt_taxonomies, 'MT Taxonomies' );
			
			$data['post-types'] = array_merge( $mt_post_types, $data['post-types'] );
			foreach( $mt_taxonomies as $taxname => $terms )
			{
				if( array_key_exists( $taxname, $data['taxonomies'] ) )
					$data['taxonomies'][$taxname] = array_merge(
						$mt_taxonomies[$taxname],
						$data['taxonomies'][$taxname]
					);
				else
					$data['taxonomies'][$taxname] = $mt_taxonomies[$taxname];
			}
		}
		
		foreach( $data['taxonomies'] as $taxname => &$terms )
		{
			$terms = array_unique($terms);
		}
		
		//----------------------------------------
		
// 		mt_print( $data, 'Current Filter Data' );
		return $data;
	}
	
	
	
}


