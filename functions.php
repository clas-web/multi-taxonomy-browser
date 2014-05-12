<?php


function mt_print( $text, $title = '' )
{
	echo '<pre>';
	if( !empty($title) ) echo $title.":\n";
	print_r($text);
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
	if( $mt_type == null ) $mt_type = MultiTags_Main::$page_type;
	return ( ($mt_type == MTType::FilteredArchive) || ($mt_type == MTType::CombinedArchive) );
}

function mt_is_search( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTags_Main::$page_type;
	return ( ($mt_type == MTType::FilteredSearch) || ($mt_type == MTType::CombinedSearch) );
}

function mt_is_filtered( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTags_Main::$page_type;
	return ( ($mt_type == MTType::FilteredArchive) || ($mt_type == MTType::FilteredSearch) );
}

function mt_is_combined( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTags_Main::$page_type;
	return ( ($mt_type == MTType::CombinedArchive) || ($mt_type == MTType::CombinedSearch) );
}

function mt_is_filtered_archive( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTags_Main::$page_type;
	return $mt_type == MTType::FilteredArchive;
}

function mt_is_combined_archive( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTags_Main::$page_type;
	return $mt_type == MTType::CombinedArchive;
}

function mt_is_filtered_search( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTags_Main::$page_type;
	return $mt_type == MTType::FilteredSearch;
}

function mt_is_combined_search( $mt_type = null )
{
	if( $mt_type == null ) $mt_type = MultiTags_Main::$page_type;
	return $mt_type == MTType::CombinedSearch;
}



//----------------------------------------------------------------------------------------

/**
 * Get currently filtered taxonomies and post types.
 **/
function mt_get_current_filter_data()
{
		$data = array(
			'post-types' => array(),
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
			$mt_post_types = MultiTags_Api::GetPostTypes();
			$mt_taxonomies = MultiTags_Api::GetTaxonomies();
			
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
		
		//------------------------------------------
		
		return $data;
}



//----------------------------------------------------------------------------------------

/**
 * 
 **/
function mt_print_interface( $mt_type, $post_types, $taxonomies, $current )
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
	
	$count = 0;
	$query_args['tax_query'] = array();
	foreach( $current['taxonomies'] as $taxname => $terms )
	{
		if( count($terms) > 0 )
		{
			$count++;
			array_push(
				$query_args['tax_query'],
				array(
					'taxonomy' => $taxname,
					'field' => 'slug',
					'terms' => $terms,
					'operator' => ( $relation == 'AND' ? $relation : 'OR' ),
				)
			);
		}
	}
	if( $count > 1 )
	{
		$query_args['tax_query']['relation'] = $relation;
	}
	
	$query = new WP_Query( $query_args );
	
	
	// 
	// Get matching taxonomies from posts.
	//
	
	$matching_taxonomies = array();
	foreach( $taxonomies as $taxonomy )
	{
		$matching_taxonomies[$taxonomy] = array();
	}

	
	global $post;
	while( $query->have_posts() )
	{
		$query->the_post();
		
		foreach( $taxonomies as $taxname )
		{
			$matching_taxonomies[$taxname] = array_merge(
				$matching_taxonomies[$taxname],
				wp_get_post_terms( $post->ID, $taxname, array("fields" => "slugs") )
			);
		}
	}	
	
	
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
			$labels[$taxname] = $tax->labels->name;
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
					$current['taxonomies'],
					array( $taxname => array( $term ) ),
					true
				);
				
				$t = get_term_by( 'slug', $term, $taxname ); 
				echo '<a href="'.$link.'">'.$t->name.'</a>';
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
					$current['taxonomies'],
					array( $taxname => array( $term ) ),
					false
				);
				
				$t = get_term_by( 'slug', $term, $taxname ); 
				echo '<a href="'.$link.'">'.$t->name.'</a>';
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
function mt_get_url( $mt_type, $post_types, $taxonomies, 
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
		if( array_key_exists($taxname, $new_taxonomies) )
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
	
	return $url;
}



//----------------------------------------------------------------------------------------

/**
 * 
 **/
function mt_create_interface( $mt_type, $post_types, $taxonomies )
{
	$current_filtered_data = mt_get_current_filter_data();
	
	
	if( !mt_is_archive() && !mt_is_search() )
	{
		$current_filtered_data['post-types'] = $post_types;
		foreach( $taxonomies as $taxname )
		{
			if( !array_key_exists($taxname, $current_filtered_data['taxonomies']) )
				$current_filtered_data['taxonomies'][$taxname] = array();
		}
	}	
	
	
	mt_print_interface( $mt_type, $post_types, $taxonomies, $current_filtered_data );
}





































