<?php
/**
 * The main functions for the Multi-Taxonomy Browser plugin.
 * 
 * @package    multi-taxonomy-browser
 * @author     Crystal Barton <atrus1701@gmail.com>
 */


/**
 * Struct for the type of the MultiTaxonomy Browser.
 */
if( !class_exists('MTType') ):
abstract class MTType
{
	const None = -1;
	const FilteredArchive = 0;
	const CombinedArchive = 1;
	const FilteredSearch = 2;
	const CombinedSearch = 3;
}
endif;


/**
 * Print a variable in pretty, easy-to-read format.
 * @param  mixed  $var  The variable to print.
 * @param  string  $label  The label for the variable output.
 */
if( !function_exists('mt_print') ):
function mt_print( $var, $label = null )
{
	echo '<pre>';
	
	if( $label !== null )
	{
		$label = print_r( $label, true );
		echo "<strong>$label:</strong><br/>";
	}
	
	var_dump($var);
	
	echo '</pre>';
}
endif;


/**
 * Determines if the type of the parameter or global $mt_page_type is an archive type.
 * @param  MTType|null  $mt_type  The type to compare, or null if the global $mt_page_type is used.
 * @return  bool  True if the type is an archive type.
 */
if( !function_exists('mt_is_archive') ):
function mt_is_archive( $mt_type = null )
{
	global $mt_page_type;
	if( $mt_type == null ) $mt_type = $mt_page_type;
	return ( ($mt_type == MTType::FilteredArchive) || ($mt_type == MTType::CombinedArchive) );
}
endif;


/**
 * Determines if the type of the parameter or global $mt_page_type is a search type.
 * @param  MTType|null  $mt_type  The type to compare, or null if the global $mt_page_type is used.
 * @return  bool  True if the type is a search type.
 */
if( !function_exists('mt_is_search') ):
function mt_is_search( $mt_type = null )
{
	global $mt_page_type;
	if( $mt_type == null ) $mt_type = $mt_page_type;
	return ( ($mt_type == MTType::FilteredSearch) || ($mt_type == MTType::CombinedSearch) );
}
endif;


/**
 * Determines if the type of the parameter or global $mt_page_type is a filtered type.
 * @param  MTType|null  $mt_type  The type to compare, or null if the global $mt_page_type is used.
 * @return  bool  True if the type is a filtered type.
 */
if( !function_exists('mt_is_filtered') ):
function mt_is_filtered( $mt_type = null )
{
	global $mt_page_type;
	if( $mt_type == null ) $mt_type = $mt_page_type;
	return ( ($mt_type == MTType::FilteredArchive) || ($mt_type == MTType::FilteredSearch) );
}
endif;


/**
 * Determines if the type of the parameter or global $mt_page_type is a combined type.
 * @param  MTType|null  $mt_type  The type to compare, or null if the global $mt_page_type is used.
 * @return  bool  True if the type is a combined type.
 */
if( !function_exists('mt_is_combined') ):
function mt_is_combined( $mt_type = null )
{
	global $mt_page_type;
	if( $mt_type == null ) $mt_type = $mt_page_type;
	return ( ($mt_type == MTType::CombinedArchive) || ($mt_type == MTType::CombinedSearch) );
}
endif;


/**
 * Determines if the type of the parameter or global $mt_page_type is a filtered archive type.
 * @param  MTType|null  $mt_type  The type to compare, or null if the global $mt_page_type is used.
 * @return  bool  True if the type is a filtered archive type.
 */
if( !function_exists('mt_is_filtered_archive') ):
function mt_is_filtered_archive( $mt_type = null )
{
	global $mt_page_type;
	if( $mt_type == null ) $mt_type = $mt_page_type;
	return $mt_type == MTType::FilteredArchive;
}
endif;


/**
 * Determines if the type of the parameter or global $mt_page_type is a combined archive type.
 * @param  MTType|null  $mt_type  The type to compare, or null if the global $mt_page_type is used.
 * @return  bool  True if the type is a combined archive type.
 */
if( !function_exists('mt_is_combined_archive') ):
function mt_is_combined_archive( $mt_type = null )
{
	global $mt_page_type;
	if( $mt_type == null ) $mt_type = $mt_page_type;
	return $mt_type == MTType::CombinedArchive;
}
endif;


/**
 * Determines if the type of the parameter or global $mt_page_type is a filtered search type.
 * @param  MTType|null  $mt_type  The type to compare, or null if the global $mt_page_type is used.
 * @return  bool  True if the type is a filtered search type.
 */
if( !function_exists('mt_is_filtered_search') ):
function mt_is_filtered_search( $mt_type = null )
{
	global $mt_page_type;
	if( $mt_type == null ) $mt_type = $mt_page_type;
	return $mt_type == MTType::FilteredSearch;
}
endif;


/**
 * Determines if the type of the parameter or global $mt_page_type is a combined search type.
 * @param  MTType|null  $mt_type  The type to compare, or null if the global $mt_page_type is used.
 * @return  bool  True if the type is a combined search type.
 */
if( !function_exists('mt_is_combined_search') ):
function mt_is_combined_search( $mt_type = null )
{
	global $mt_page_type;
	if( $mt_type == null ) $mt_type = $mt_page_type;
	return $mt_type == MTType::CombinedSearch;
}
endif;


/**
 * Get currently filtered taxonomies and post types.
 * @return  Array  The filtered taxonomies and post types.
 */
if( !function_exists('mt_get_current_filter_data') ):
function mt_get_current_filter_data()
{
		$data = array(
			'post_types' => array(),
			'taxonomies' => array(),
		);
		
		if( (!is_archive() && !mt_is_archive()) && (!is_search() && !mt_is_search()) ) return $data;
		
		
		$qo = get_queried_object();
		if( $qo != null )
		{
			if( is_category() || is_tag() || is_tax() )
			{
				$data['taxonomies'][$qo->taxonomy] = array( $qo->slug );
			}
		}
		
		
		if( mt_is_archive() || mt_is_search() )
		{
			$mt_post_types = MultiTaxonomyBrowser_Api::GetPostTypes();
			$mt_taxonomies = MultiTaxonomyBrowser_Api::GetTaxonomies();
			
			$data['post_types'] = array_merge( $mt_post_types, $data['post_types'] );
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
		
		
		return $data;
}
endif;


/**
 * Print the Multi-Taxonomy Browser related terms filtering interface.
 * @param  MTType  $mt_type  The type of interface to create (combined or filtered)
 * @param  Array  $post_types  The post types that can be filtered.
 * @param  Array  $taxonomies  The taxonomies that can be filtered.
 * @param  int  $related_level  The level of ralated terms that should be filtered by.
 *                              0 - Only include related that match posts.
 *                              1 - Only include posts from one level down (children of taxonomies).
 *                              2 - Match all children posts
 * @param  String  $sort  The method to use when sorting the terms.
 * @param  Int  $max_terms  The maximum number of terms to include for each taxonomy.
 * @param  Array  $current  The currently filtered post types and taxonomy terms.
 */
if( !function_exists('mt_print_interface') ):
function mt_print_interface( $mt_type, $post_types, $taxonomies, $related_level, $sort, $max_terms, $current )
{
	$relation = 'AND'; if( mt_is_combined($mt_type) ) $relation = 'OR';
	
	// Get matching posts.
	$query_args = array( 'posts_per_page' => -1 );
	
	if( count($post_types) > 0  )
	{
		$query_args['post_type'] = $post_types;
	}
	
	// Generate the tax_query for the WP_Query.
	$query_args['tax_query'] = mt_get_tax_query( $current['taxonomies'], $relation, $related_level );
	
	// Get matching taxonomies from posts.
	$matching_taxonomies = array();
	foreach( $taxonomies as $taxonomy )
	{
		$matching_taxonomies[$taxonomy] = array();
	}

	$matching_query = new WP_Query( $query_args );
	
	while( $matching_query->have_posts() )
	{
		$post = $matching_query->the_post();
		
		foreach( $taxonomies as $taxname )
		{
			$matching_taxonomies[$taxname] = array_merge(
				$matching_taxonomies[$taxname],
				wp_get_post_terms( get_the_ID(), $taxname, array( 'fields' => 'slugs' ) )
			);
		}
	}
	
	wp_reset_query();

	foreach( $matching_taxonomies as $taxname => &$terms )
	{
		$terms = array_unique( $terms );
		$terms = array_diff( $terms, $current['taxonomies'][$taxname] );

		// Change terms to term objects for sorting.
		foreach( $terms as &$term )
		{
			$term = get_term_by( 'slug', $term, $taxname );
		}

		// Sort taxonomies.
		switch( $sort )
		{
			case 'name-reverse':
				usort( $terms,
					function( $a, $b )
					{
						return ( strcasecmp($a->name, $b->name) * -1 );
					});
				break;

			case 'count':
				usort( $terms,
					function( $a, $b )
					{
						return ( $a->count - $b->count );
					});
				break;
			
			case 'count-reverse':
				usort( $terms,
					function( $a, $b )
					{
						return ( $b->count - $a->count );
					});
				break;

			case 'name':
			default:
				usort( $terms,
					function( $a, $b )
					{
						return ( strcasecmp($a->name, $b->name) );
					});
				break;
		}
	}


	// Get taxonomy labels.
	$labels = array();
	foreach( $taxonomies as $taxname )
	{
		$labels[$taxname] = $taxname;
		$tax = get_taxonomy($taxname);
		if( $tax )
		{
			if( $tax->labels->name == "Categories" ) 
			{
				$taxonomy_label = get_option('category_base');
				if( !$taxonomy_label ) $taxonomy_label = $tax->labels->name;
			}
			else if( $tax->labels->name == "Tags" ) 
			{
				$taxonomy_label = get_option('tag_base');
				if( !$taxonomy_label ) $taxonomy_label = $tax->labels->name;		
			}
			else
			{
				$taxonomy_label = $tax->labels->name;
			}
		
			$labels[$taxname] = $taxonomy_label;
		}
	}
	
	sort($taxonomies);
	
	// Display current taxonomies.
	echo '<div class="current-taxonomies">';
	
	foreach( $taxonomies as $taxname )
	{
		$class = 'results';
		if( count($current['taxonomies'][$taxname]) == 0 )
			$class = 'no-results';
			
		echo '<div class="'.$taxname.' '.$class.'">';
		echo '<span class="title"> Current '.$labels[$taxname].':</span>';
	
		if( count($current['taxonomies'][$taxname]) > 0 )
		{
			foreach( $current['taxonomies'][$taxname] as $term )
			{
				$link = mt_get_url(
					$mt_type,
					$post_types,
					$taxonomies,
					$related_level,
					$current['taxonomies'],
					array( $taxname => array( $term->slug ) ),
					true
				);
				
				$t = get_term_by( 'slug', $term, $taxname );
				echo '<a href="'.$link.'" class='.$t->slug.'>'.$t->name.'</a>';
			}
			
		}
		else
		{
			echo '<span>None</span>';
		}
		
		echo '</div>';
	}
	
	echo '</div>';
	
	
	// Display related taxonomies.
	echo '<div class="related-taxonomies">';
	
	foreach( $taxonomies as $taxname )
	{
		$class = 'results';
		if( count($matching_taxonomies[$taxname]) == 0 )
			$class = 'no-results';
			
		echo '<div class="'.$taxname.' '.$class.'">';
		echo '<span class="title"> Related '.$labels[$taxname].':</span>';
		
		if( count($matching_taxonomies[$taxname]) > 0 )
		{

			foreach( $matching_taxonomies[$taxname] as $term )
			{
				$link = mt_get_url(
					$mt_type,
					$post_types,
					$taxonomies,
					$related_level,
					$current['taxonomies'],
					array( $taxname => array( $term->slug ) ),
					false
				);
				
				echo '<a href="'.$link.'" class='.$term->slug.'>'.$term->name.'</a>';
			}
			
		}
		else
		{
			echo '<span>None</span>';
		}
		
		echo '</div>';
	}
	
	echo '</div>';
}
endif;


/**
 * 
 * @param  MTType  $mt_type  The type of interface to create (combined or filtered)
 * @param  Array  $post_types  The post types that can be filtered.
 * @param  Array  $taxonomies  The taxonomies that can be filtered.
 * @param  int  $related_level  The level of ralated terms that should be filtered by.
 *                              0 - Only include related that match posts.
 *                              1 - Only include posts from one level down (children of taxonomies).
 *                              2 - Match all children posts
 * @param  Array  $current_taxonomies  The currently filtered taxonomy terms.
 * @param  Array|null  $new_taxonomies  
 * @param  bool  $remove_new_taxonomies  
 * @return  string  
 */
if( !function_exists('mt_get_url') ):
function mt_get_url( $mt_type, $post_types, $taxonomies, $related_level,
                               $current_taxonomies, $new_taxonomies = null, 
                               $remove_new_taxonomies = false )
{
	$url = get_home_url().'/?';
	switch( $mt_type )
	{
		case( MTType::FilteredArchive ): $url .= MT_FILTERED_ARCHIVE; break;
		case( MTType::CombinedArchive ): $url .= MT_COMBINED_ARCHIVE; break;
		case( MTType::FilteredSearch ): $url .= MT_FILTERED_SEARCH; break;
		case( MTType::CombinedSearch ): $url .= MT_COMBINED_SEARCH; break;
	}
	
	if( !empty($post_types) )
	{
		$url .= '&post-type='.implode( ',', $post_types );
	}
	
	foreach( $current_taxonomies as $taxname => $terms )
	{
		$t = $terms;
		if( ($new_taxonomies !== null) && (array_key_exists($taxname, $new_taxonomies)) )
		{
			if( !$remove_new_taxonomies ) $t = array_merge( $t, $new_taxonomies[$taxname] );
			else $t = array_diff( $t, $new_taxonomies[$taxname] );
		}
		
		if( count($t) > 0 )
		{
			asort( $t );
			$url .= '&'.$taxname.'='.implode( ',', $t );
		}
		else
		{
			$url .= '&'.$taxname.'=';
		}
	}
	
	foreach( $taxonomies as $taxname )
	{
		if( !array_key_exists($taxname, $current_taxonomies) )
		{
			$url .= '&'.$taxname.'=';
		}
	}
	
	if( $related_level > 0 )
	{
		$url .= '&rl='.$related_level;
	}
	
	return $url;
}
endif;


/**
 * 
 * @param  
 * @param  
 * @param  
 * @param  
 */
if( !function_exists('mt_create_interface') ):
function mt_create_interface( $mt_type, $post_types, $taxonomies, $related_level = 0, $sort = 'name', $max_terms = -1 )
{
	$current_filtered_data = mt_get_current_filter_data();
	
	if( !mt_is_archive() && !mt_is_search() )
	{
		$current_filtered_data['post_types'] = $post_types;
		foreach( $taxonomies as $taxname )
		{
			if( !array_key_exists($taxname, $current_filtered_data['taxonomies']) )
				$current_filtered_data['taxonomies'][$taxname] = array();
		}
	}	
	
	mt_print_interface( $mt_type, $post_types, $taxonomies, $related_level, $sort, $max_terms, $current_filtered_data );
}
endif;


/**
 * 
 * @param  
 * @param  
 * @param  
 * @return  
 */
if( !function_exists('mt_get_tax_query') ):
function mt_get_tax_query( $taxonomies, $relation, $related_level = 2 )
{
	if( count($taxonomies) === 0 ) return null;
	
	
	$query_taxonomies = array();
	foreach( $taxonomies as $taxname => $terms )
	{
		if( count($terms) === 0 ) continue;
		
		if( $related_level === 0 || !is_taxonomy_hierarchical($taxname) )
		{
			$query_taxonomies[$taxname] = $terms;
			continue;
		}
		
		$query_taxonomies[$taxname] = array();
		
		foreach( $terms as $term )
		{
			$t = get_term_by( 'slug', $term, $taxname );
			$children_args = array(
				'taxonomy'	=> $taxname,
				'child_of'	=> $t->term_id,
			);
			
			if( $related_level === 1 )
			{
				$children_args['parent'] = $t->term_id;
			}
			
			$all_terms = array_merge(
				array( $t ),
				get_categories( $children_args )
			);
			
			$all_terms = array_map(
				function( $t ) { return $t->term_id; },
				$all_terms
			);
			
			$query_taxonomies[$taxname][] = $all_terms;
		}
	}
	

	if( count($query_taxonomies) === 0 ) return null;
	
	
	$count = 0;
	$tax_query = array();
	foreach( $query_taxonomies as $taxname => $terms )
	{
		if( count($terms) > 0 )
		{
			if( $related_level === 0 || !is_taxonomy_hierarchical($taxname) )
			{
				array_push(
					$tax_query,
					array(
						'taxonomy'			=> $taxname,
						'field'				=> 'slug',
						'terms'				=> $terms,
						'operator'			=> ( $relation === 'AND' ? 'AND' : 'IN' ),
						'include_children'	=> false,
					)
				);

				$count++;
				continue;
			}
			
			foreach( $terms as $term_list )
			{
				array_push(
					$tax_query,
					array(
						'taxonomy' 			=> $taxname,
						'field' 			=> 'id',
						'terms' 			=> $term_list,
						'operator' 			=> 'IN',
						'include_children' 	=> false,
					)
				);

				$count++;
			}
		}
	}
	if( $count > 1 )
	{
		$tax_query['relation'] = $relation;
	}
	

	return $tax_query;
}
endif;

