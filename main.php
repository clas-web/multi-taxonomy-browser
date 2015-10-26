<?php
/*
Plugin Name: Multi-Taxonomy Browser
Plugin URI: https://github.com/clas-web/multi-taxonomy-browser
Description: 
Version: 0.5.0
Author: Crystal Barton
Author URI: http://www.crystalbarton.com
*/

/**
 * URL arguments examples:
 * 
 * Show post of type "post" or "connection" with both categories and one of the tags: 
 * ?mt-archive&post_type=post,connection&category=Category1,Category3&post_tag=Tag1|Tag2
 * 
 * Show post of type "post" or "connection" with both categories and both tags:
 * ?mt-filtered-archive&post_type=post,connection&category=Category1,Category3&post_tag=Tag1,Tag2
 * 
 * Show post of type "post" or "connection" with one of the categories and one of the tags:
 * ?mt-combined-archive&post_type=post,connection&category=Category1,Category3&post_tag=Tag1,Tag2
 * 
 * Shortcode example:
 * 
 * [mt-link type="mt-filtered-archive" post-type="post,connection" taxonomies="category,post_tag" category="category1,category3" post_tag="tag1,tag3"]
 */


if( !defined('MT_PLUGIN_NAME') ):

/**
 * 
 * @var  string
 */
define( 'MT_PLUGIN_NAME', 'Multi-Taxonomy Browser' );

/**
 * 
 * @var  string
 */
define( 'MT_PLUGIN_VERSION', '1.0' );

/**
 * 
 * @var  string
 */
define( 'MT_PLUGIN_PATH', __DIR__ );

/**
 * 
 * @var  string
 */
define( 'MT_PLUGIN_URL', plugins_url('', __FILE__) );

/**
 * 
 * @var  string
 */
define( 'MT_COMBINED_ARCHIVE', 'mt-combined-archive' );

/**
 * 
 * @var  string
 */
define( 'MT_FILTERED_ARCHIVE', 'mt-filtered-archive' );

/**
 * 
 * @var  string
 */
define( 'MT_COMBINED_SEARCH', 'mt-combined-search' );

/**
 * 
 * @var  string
 */
define( 'MT_FILTERED_SEARCH', 'mt-filtered-search' );

/**
 * 
 * @var  string
 */
define( 'MT_DELIMITER', ',' );

endif;


require_once( __DIR__.'/functions.php' );
require_once( __DIR__.'/api.php' );
require_once( __DIR__.'/filter-widget.php' );


add_filter( 'query_vars', 'mt_query_vars' );
add_action( 'parse_request', 'mt_parse_request' );

add_filter( 'the_content', 'mt_process_content' );

foreach( array('archive','taxonomy','category','tag') as $archive )
	add_filter( $archive.'_template', 'mt_get_archive_template_file' );

add_filter( 'search_template',  'mt_get_search_template_file' );


$mt_page_type = MTType::None;
	
	
/**
 * Add filtering keys to the query vars.
 * @param  Array  $query_vars  The query vars.
 * @return  Array  The altered query vars.
 */
if( !function_exists('mt_query_vars') ):
function mt_query_vars( $query_vars )
{
	$query_vars[] = MT_COMBINED_ARCHIVE;
	$query_vars[] = MT_FILTERED_ARCHIVE;

	$query_vars[] = MT_COMBINED_SEARCH;
	$query_vars[] = MT_FILTERED_SEARCH;
	
	return $query_vars;
}
endif;


/**
 * Parse the request to search for filtering keys and setup the MTB API.
 * @param  WP  $wp  The WP object.
 */
if( !function_exists('mt_parse_request') ):
function mt_parse_request( &$wp )
{
	global $mt_page_type;

	if( array_key_exists(MT_COMBINED_ARCHIVE, $wp->query_vars) )
	{
		$mt_page_type = MTType::CombinedArchive;
		MultiTaxonomyBrowser_Api::ProcessCombinedArchive();
	}
	elseif( array_key_exists(MT_FILTERED_ARCHIVE, $wp->query_vars) )
	{
		$mt_page_type = MTType::FilteredArchive;
		MultiTaxonomyBrowser_Api::ProcessFilteredArchive();
	}

	if( array_key_exists(MT_COMBINED_SEARCH, $wp->query_vars) )
	{
		$mt_page_type = MTType::CombinedSearch;
		MultiTaxonomyBrowser_Api::ProcessCombinedSearch();
	}
	elseif( array_key_exists(MT_FILTERED_SEARCH, $wp->query_vars) )
	{
		$mt_page_type = MTType::FilteredSearch;
		MultiTaxonomyBrowser_Api::ProcessFilteredSearch();
	}
	
	if( mt_is_archive() || mt_is_search() )
	{
		add_action( 'pre_get_posts', array('MultiTaxonomyBrowser_Api', 'alter_wp_query') );
		add_filter( 'body_class', 'mt_alter_body_class' );
	}
}
endif;


/**
 * Add classes to the body tag, if using the MultiTaxonomy Browser.
 * @param  Array  $classes  The body classes.
 * @return  Array  The altered list of body classes.
 */
if( !function_exists('mt_alter_body_class') ):
function mt_alter_body_class( $classes )
{
	if( mt_is_archive() )
		$classes[] = 'mt-archive';
	
	if( mt_is_search() )
		$classes[] = 'mt-search';
	
	return $classes;
}
endif;


/**
 * Process the shortcode in the content.
 * @param  string  $content  The content.
 * @return  string  The altered content.
 */
if( !function_exists('mt_process_content') ):
function mt_process_content( $content )
{
	$matches = NULL;
	$num_matches = preg_match_all("/\[mt-link(.+)\]/", $content, $matches, PREG_SET_ORDER);

	if( ($num_matches !== FALSE) && ($num_matches > 0) )
	{
		for( $i = 0; $i < $num_matches; $i++ )
		{
			$content = str_replace($matches[$i][0], mt_get_shortcode_link( $matches[$i][0] ), $content);
		}
	}
	
	return $content;
}
endif;


/**
 * Convert shortcode to a MultiTaxonomy URL.
 * @param  string  $shortcode  The shortcode to convert.
 * @return  string  The generated url.
 */
if( !function_exists('mt_get_shortcode_link') ):
function mt_get_shortcode_link( $shortcode )
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
endif;


/**
 * Get the path to the MultiTaxonomy archive theme file.
 * @param  string  $template  The current template file.
 * @return  string  The MT archive file, if needed and found.
 */
if( !function_exists('mt_get_archive_template_file') ):
function mt_get_archive_template_file( $template )
{
	if( !mt_is_archive() ) return $template;

	$template_filenames = array();

	if( mt_is_filtered() )
		array_push( $template_filenames, 'mt-filtered-archive' );
	else if( mt_is_combined() )
		array_push( $template_filenames, 'mt-combined-archive' );

	array_push( $template_filenames, 'mt-archive' );

	return mt_find_template_file( $template_filenames, $template );
}
endif;


/**
 * Get the path to the MultiTaxonomy search theme file.
 * @param  string  $template  The current template file.
 * @return  string  The MT search file, if needed and found.
 */
if( !function_exists('mt_get_search_template_file') ):
function mt_get_search_template_file( $template )
{
	if( !mt_is_search() ) return $template;

	$template_filenames = array();

	if( mt_is_filtered() )
		array_push( $template_filenames, 'mt-filtered-search' );
	else if( mt_is_combined() )
		array_push( $template_filenames, 'mt-combined-search' );

	array_push( $template_filenames, 'mt-search' );

	return mt_find_template_file( $template_filenames, $template );	
}
endif;


/**
 * Get the path to the MultiTaxonomy theme file in the child and parent theme.
 * @param  Array  $template_filenames  Template filenames to search for.
 * @param  string  $template  The current template file.
 * @return  string  The found template file, or the current template file if not found.
 */
if( !function_exists('mt_find_template_file') ):
function mt_find_template_file( $template_filenames, $template )
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
endif;

