<?php


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



//----------------------------------------------------------------------------------------
//------------------------------------------------------------------------------  -----

abstract class MTType
{
	const None = -1;
	const FilteredArchive = 0;
	const CombinedArchive = 1;
	const FilteredSearch = 2;
	const CombinedSearch = 3;
}

//----------------------------------------------------------------------------------------

function mt_is_archive( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTaxonomyBrowser::$page_type;
	return ( ($mt_type == MTType::FilteredArchive) || ($mt_type == MTType::CombinedArchive) );
}

function mt_is_search( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTaxonomyBrowser::$page_type;
	return ( ($mt_type == MTType::FilteredSearch) || ($mt_type == MTType::CombinedSearch) );
}

function mt_is_filtered( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTaxonomyBrowser::$page_type;
	return ( ($mt_type == MTType::FilteredArchive) || ($mt_type == MTType::FilteredSearch) );
}

function mt_is_combined( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTaxonomyBrowser::$page_type;
	return ( ($mt_type == MTType::CombinedArchive) || ($mt_type == MTType::CombinedSearch) );
}

function mt_is_filtered_archive( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTaxonomyBrowser::$page_type;
	return $mt_type == MTType::FilteredArchive;
}

function mt_is_combined_archive( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTaxonomyBrowser::$page_type;
	return $mt_type == MTType::CombinedArchive;
}

function mt_is_filtered_search( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTaxonomyBrowser::$page_type;
	return $mt_type == MTType::FilteredSearch;
}

function mt_is_combined_search( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTaxonomyBrowser::$page_type;
	return $mt_type == MTType::CombinedSearch;
}



//----------------------------------------------------------------------------------------

/**
 * Get currently filtered taxonomies and post types.
 **/
function mt_get_current_filter_data()
{
		$data = array(
			'post_types' => array(),
			'taxonomies' => array(),
		);
		
		if( (!is_archive() && !mt_is_archive()) && (!is_search() && !mt_is_search()) ) return $data;
		
		//------------------------------------------
		
		$qo = get_queried_object();
		if( $qo != null )
		{
			if( is_category() || is_tag() || is_tax() )
			{
				$data['taxonomies'][$qo->taxonomy] = array( $qo->slug );
			}
		}
		
		//------------------------------------------
		
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
		
		//------------------------------------------
		
		return $data;
}



//----------------------------------------------------------------------------------------

/**
 * 
 **/
function mt_print_interface( $mt_type, $post_types, $taxonomies, $related_level, $current )
{
	$relation = 'AND'; if( mt_is_combined($mt_type) ) $relation = 'OR';
	
	
	// 
	// Get matching posts.
	//
	
	$query_args = array( 'posts_per_page' => -1 );
	
	if( count($post_types) > 0  )
	{
		$query_args['post_type'] = $post_types;
	}
	
	//
	// Three options:
	// 0 - Only include related that match posts.
	// 1 - Only include posts from one level down (children of taxonomies).
	// 2 - Match all children posts
	//
	
	$query_args['tax_query'] = mt_get_tax_query( $current['taxonomies'], $relation, $related_level );
// 	mt_print( $query_args['tax_query'] );
	
	
	// 
	// Get matching taxonomies from posts.
	//
	
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
				wp_get_post_terms( get_the_ID(), $taxname, array("fields" => "slugs") )
			);
		}
	}
	
	wp_reset_query();
	
	// 
	// Sort taxonomy lists.
	//

	ksort( $matching_taxonomies );
	foreach( $matching_taxonomies as $taxname => &$terms )
	{
		$terms = array_unique( $terms, SORT_STRING );
		$terms = array_diff( $terms, $current['taxonomies'][$taxname] );
		asort( $terms );
	}
	
	ksort( $current['taxonomies'] );
	foreach( $current['taxonomies'] as $taxname => &$terms )
	{
		asort( $terms );
	}	
	
	// 
	// Get taxonomy labels.
	//
	
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
	
	
	// 
	// Display current taxonomies.
	//

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
					array( $taxname => array( $term ) ),
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
	
	
	// 
	// Display related taxonomies.
	//

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
					array( $taxname => array( $term ) ),
					false
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
}



//----------------------------------------------------------------------------------------

/**
 * 
 **/
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



//----------------------------------------------------------------------------------------

/**
 * 
 **/
function mt_create_interface( $mt_type, $post_types, $taxonomies, $related_level )
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
	
	mt_print_interface( $mt_type, $post_types, $taxonomies, $related_level, $current_filtered_data );
}



function mt_get_tax_query( $taxonomies, $relation, $related_level = 2 )
{
// 	mt_print( $taxonomies, $relation.' : '.$related_level );	
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
	
// 	mt_print($query_taxonomies);
// 	mt_print(count($query_taxonomies));
	
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
	
// 	mt_print( $tax_query );	
	return $tax_query;
}





































