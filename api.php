<?php


class MultiTaxonomyBrowser_Api
{
	private static $initialized = false;
	private static $post_type;
	private static $taxonomies;
	private static $relation;
	private static $search = false;
	
	
	public static function Init()
	{
// 		echo 'Init';
		
		if( self::$initialized ) return;
		
		self::$post_type = array( 'post' );
		self::$taxonomies = array();
		
		self::$initialized = true;
	}
	
	
	public static function ProcessCombinedArchive()
	{
// 		echo 'ProcessCombinedArchive';
		
		self::$relation = 'OR';
		self::$search = false;
		self::Process();
	}
	
	
	public static function ProcessFilteredArchive()
	{
// 		echo 'ProcessFilteredArchive';
		
		self::$relation = 'AND';
		self::$search = false;
		self::Process();
	}
	
	
	public static function ProcessCombinedSearch()
	{
// 		echo 'ProcessCombinedSearch';
		
		self::$relation = 'OR';
		self::$search = true;
		self::Process();
	}
	
	
	public static function ProcessFilteredSearch()
	{
// 		echo 'ProcessFilteredSearch';
		
		self::$relation = 'AND';
		self::$search = true;
		self::Process();
	}
	
	
	private static function Process()
	{
// 		echo 'Process';
		
		if( !isset($_GET) ) return;
		if( !self::$initialized ) self::Init();	
		
		foreach( $_GET as $key => $value )
		{
			switch( $key )
			{
				case MT_COMBINED_ARCHIVE:
				case MT_FILTERED_ARCHIVE:
					break;
				
				case 'post-type':
					self::$post_type = explode(MT_DELIMITER, $value);
					break;
				
				default:
// 					self::AddTaxonomy( $key, self::$relation, explode(MT_DELIMITER, $value) );
					if( !taxonomy_exists($key) ) break;
					if( trim($value) == '' ) { self::$taxonomies[$key] = array(); break; }
					self::$taxonomies[$key] = explode(MT_DELIMITER, $value);
					break;
			}
		}
	}
		
	
	private static function AddTaxonomy( $taxonomy, $relation, $terms )
	{
// 		echo 'AddTaxonomy';
		
		if( !taxonomy_exists($taxonomy) ) return;
		self::$taxonomies[$taxonomy] = array(
			'taxonomy' => $taxonomy,
			'terms'    => $terms,
			'relation' => ( $relation == 'AND' ? 'AND' : 'IN' ),
		);
	}
	
	
	public static function alter_wp_query( $query )
	{
		if( is_admin() ) return $query;
		if( !$query->is_main_query() ) return $query;
		if( !self::$initialized ) return $query;
		
		$query->query_vars['post_type'] = self::$post_type;
		
		$count = 0;
		$tax_query = array();
		if( count(self::$taxonomies) > 0 )
		{
			foreach( self::$taxonomies as $taxname => $terms )
			{
				if( count($terms) > 0 )
				{
// 					mt_print($terms);
					array_push(
						$tax_query,
						array(
							'taxonomy' => $taxname,
							'field' => 'slug',
							'terms' => $terms,
							'operator' => ( self::$relation === 'AND' ? 'AND' : 'IN' ),
						)
					);
				
					$count++;
				}
			}
			if( $count > 1 )
			{
				$tax_query['relation'] = self::$relation;
			}
			$query->query_vars['tax_query'] = $tax_query;
			
// 			mt_print($query->query_vars['tax_query'], 'Tax_Query');
		}
		
		if( self::$search )
		{
			$query->is_search = true;
		}
		else
		{
			$query->is_archive = true;
			$query->is_home = false;
		}
		
// 		mt_print( self::$taxonomies, 'MT Taxs' );
// 		mt_print( $query, 'Query' );

		return $query;
	}
	
	
	public static function create_url( $relation, $post_type = null, $taxonomy = null, $use_current = false )
	{
// 		echo 'create_url';

		if( !self::$initialized ) self::Init();
		
		global $wp;
		$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
		$url_parts = array();
		
		
		//
		// MultiTag Query Var
		//
		
		switch( $relation )
		{
			case 'OR': array_push( $url_parts, MT_COMBINED_ARCHIVE ); break;
			case 'AND': array_push( $url_parts, MT_FILTERED_ARCHIVE ); break;
		}
		
		
		//
		// Post Type
		//
		
		if( $use_current )
		{
			if( $post_type !== null )
				$post_type = array_unique( array_merge(self::$post_type, $post_type), SORT_REGULAR );
			else
				$post_type = self::$post_type;
		}
			
		if( (count($post_type) > 0) && ($post_type[0] == 'post') )
		{
			array_push( $url_parts, 'post-type='.implode(MT_DELIMITER, $post_type) );
		}
		
		
		//
		// Taxonomies
		//
		
		$tax_name = '';
		if( $taxonomy === null ) 
		{
			$taxonomy = array();
		}
		
		if( $use_current )
		{
			$is_new_taxonomy = true;
			foreach( self::$taxonomies as $t )
			{
				// check if taxonomy already exists.
				$create_new = true;
				foreach( $taxonomy as $k => $v )
				{
					if( $t['taxonomy'] == $taxonomy[$k]['taxonomy'] )
					{
						$create_new = false;
						$taxonomy[$k]['terms'] = array_unique( array_merge($t['terms'], $taxonomy[$k]['terms']), SORT_REGULAR );
						break;
					}
				}
				if( $create_new )
				{
					array_push( $taxonomy, $t );
				}
			}
		}
		
		foreach( $taxonomy as $t )
		{
			array_push( $url_parts, $t['taxonomy'].'='.implode(MT_DELIMITER, $t['terms']) );
		}
		
		
// 		echo '<pre>';
// 		print_r( 'URL: [[['.$current_url.'/?'.implode('&', $url_parts) . ']]]' );
// 		echo '</pre>';
		
		
		return $current_url.'/?'.implode('&', $url_parts);
	}
	
	public static function GetPostTypes()
	{
		return self::$post_type;
	}
	
	public static function GetTaxonomies()
	{
		return self::$taxonomies;
	}
	
}

