<?php

require_once( __DIR__.'/widget-shortcode-control.php' );


/**
 * The MultiTaxFilter_WidgetShortcodeControl class for the "Multi-Taxonomy Browser" plugin.
 * Derived from the official WP RSS widget.
 * 
 * Shortcode Example:
 * [multi_tax_filter title="My Multi Tax Filter" post_types="post,connection" taxonomies="post_tag,categories" related_level="0" sort="name" max_terms="-1"]
 * 
 * @package    multi-taxonomy-browser
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('MultiTaxFilter_WidgetShortcodeControl') ):
class MultiTaxFilter_WidgetShortcodeControl extends WidgetShortcodeControl
{
	private static $SORT_TYPES = array(
		'name'          => 'Name, A-Z',
		'name-reverse'  => 'Name, Z-A',
		'count'         => 'Count, Lowest to Highest',
		'count-reverse' => 'Count, Highest to Lowest',
	);


	/**
	 * Constructor.
	 * Setup the properties and actions.
	 */
	public function __construct()
	{
		$widget_ops = array(
			'description'	=> 'Creates a Multi-Taxonomy Browser interface.',
		);
		
		parent::__construct( 'multi-tax-browser', 'Multi-Taxonomy Browser', $widget_ops );
	}
	
	
	/**
	 * Output the widget form in the admin.
	 * Use this function instead of form.
	 * @param   array   $options  The current settings for the widget.
	 */
	public function print_widget_form( $options )
	{
		$options = $this->process_options( $options );
		$options = $this->merge_options( $options );
		extract( $options );
		
		?>
		
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<br/>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		<br/>
		</p>
		
		<p>
		<label for="<?php echo $this->get_field_id( 'post_types' ); ?>"><?php _e( 'Post Types:' ); ?></label> 
		<br/>
		<?php foreach( $all_post_types as $pt ): ?>
			<?php if( in_array($pt->name, $exclude_post_types) ) continue; ?>
			<input type="checkbox" name="<?php echo $this->get_field_name( 'post_types' ); ?>[]" value="<?php echo esc_attr( $pt->name ); ?>" <?php echo ( in_array($pt->name, $post_types) ? 'checked' : '' ); ?> />
			<?php echo $pt->label; ?>
			<br/>
		<?php endforeach; ?>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'taxonomies' ); ?>"><?php _e( 'Taxonomies:' ); ?></label>
		<br/>
		<?php foreach( $all_taxonomies as $tax ): ?>
			<?php if( in_array($tax->name, $exclude_taxonomies) ) continue; ?>
			<input type="checkbox" name="<?php echo $this->get_field_name( 'taxonomies' ); ?>[]" value="<?php echo esc_attr( $tax->name ); ?>" <?php echo ( in_array($tax->name, $taxonomies) ? 'checked' : '' ); ?> />
			<?php echo $tax->label; ?>
			<br/>
		<?php endforeach; ?>
		</p>
		
		<p>
		<label for="<?php echo $this->get_field_id( 'related_level' ); ?>"><?php _e( 'Related Level:' ); ?></label>
		<br/>
		<input type="radio" name="<?php echo $this->get_field_name( 'related_level' ); ?>" value="0" <?php echo checked( $related_level, 0 ); ?> />
		Include siblings<br/>
		<input type="radio" name="<?php echo $this->get_field_name( 'related_level' ); ?>" value="1" <?php echo checked( $related_level, 1 ); ?> />
		Include siblings and first descendents<br/>
		<input type="radio" name="<?php echo $this->get_field_name( 'related_level' ); ?>" value="2" <?php echo checked( $related_level, 2 ); ?> />		
		Include siblings and all descendents<br/>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'sort' ); ?>"><?php _e( 'Sort:' ); ?></label>
		<br/>
		<?php foreach( self::$SORT_TYPES as $k => $v ): ?>
			<input type="radio" name="<?php echo $this->get_field_name( 'sort' ); ?>" value="<?php echo $k; ?>" <?php echo checked( $sort, $k ); ?> /><?php echo $v; ?><br/>
		<?php endforeach; ?>
		</p>
		
		<p>
		<label for="<?php echo $this->get_field_id( 'max_terms' ); ?>"><?php _e( 'Max Terms Per Taxonomy:' ); ?></label>
		<br/>
		<select id="<?php echo $this->get_field_id( 'max_terms' ); ?>" name="<?php echo $this->get_field_name( 'max_terms' ); ?>" class="widefat">
			<option value="-1" <?php selected(-1, $max_terms); ?>>No Limit</option>
			<?php for( $i = 10; $i <= 100; $i += 10 ): ?>
				<option value="<?php echo $i; ?>" <?php selected($i, $max_terms); ?>><?php echo $i; ?></option>
			<?php endfor; ?>
		</select>
		</p>

		<p>
		<input type="hidden" name="<?php echo $this->get_field_name( 'show_count' ); ?>" value="false" />
		<input type="checkbox" id="<?php echo $this->get_field_name( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" value="true" <?php echo ( $show_count ? 'checked' : '' ); ?> />
		<label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php _e( 'Show Count' ); ?></label>
		</p>

		<p>
		<input type="hidden" name="<?php echo $this->get_field_name( 'hide' ); ?>" value="false" />
		<input type="checkbox" id="<?php echo $this->get_field_name( 'hide' ); ?>" name="<?php echo $this->get_field_name( 'hide' ); ?>" value="true" <?php echo ( $hide ? 'checked' : '' ); ?> />
		<label for="<?php echo $this->get_field_id( 'hide' ); ?>"><?php _e( 'Hide on Non-Multi-Taxonomy Pages' ); ?></label>
		</p>
		
		<?php
	}
	
	
	/**
	 * Get the default settings for the widget or shortcode.
	 * @return  array  The default settings.
	 */
	public function get_default_options()
	{
		$defaults = array();

		// title
		$defaults['title'] = '';

		// post types
		$defaults['all_post_types'] = get_post_types( array(), 'objects' );
		$defaults['exclude_post_types'] = array( 'attachment', 'revision', 'nav_menu_item' );
		$defaults['post_types'] = array( 'post' );

		// taxonomy types
		$defaults['all_taxonomies'] = get_taxonomies( array(), 'objects' );
		$defaults['exclude_taxonomies'] = array( 'nav_menu', 'link_category', 'post_format' );
		$defaults['taxonomies'] = array( 'post_tag' );
		
		// related level
		$defaults['related_level'] = 0;

		// sort and max_terms
		$defaults['sort'] = 'alpha-desc';
		$defaults['max_terms'] = -1;
		
		$defaults['show_count'] = false;
		$defaults['hide'] = false;
		
		return $defaults;
	}
	
	
	/**
	 * Process options from the database or shortcode.
	 * Designed to convert options from strings or sanitize output.
	 * @param   array   $options  The current settings for the widget or shortcode.
	 * @return  array   The processed settings.
	 */
	public function process_options( $options )
	{
		if( array_key_exists('post_types', $options) && is_string($options['post_types']) ) 
			$options['post_types'] = explode( ',', $options['post_types'] );
		
		if( array_key_exists('taxonomies', $options) && is_string($options['taxonomies']) ) 
			$options['taxonomies'] = explode( ',', $options['taxonomies'] );

		$options['related_level'] = intval( $options['related_level'] );

		$options['max_terms'] = intval( $options['max_terms'] );
		if( $options['max_terms'] < 0 ) $options['max_terms'] = -1;
		
		if( ! is_bool( $options['show_count'] ) ) {
			if( is_string( $options['show_count'] ) ) {
				$options['show_count'] = ( $options['show_count'] == 'true' ? true : false );
			} else {
				$options['show_count'] = false;
			}
		}
		
		if( ! is_bool( $options['hide'] ) ) {
			if( is_string( $options['hide'] ) ) {
				$options['hide'] = ( $options['hide'] == 'true' ? true : false );
			} else {
				$options['hide'] = false;
			}
		}
		
		return $options;
	}
	
	
	/**
	 * Echo the widget or shortcode contents.
	 * @param   array  $options  The current settings for the control.
	 * @param   array  $args     The display arguments.
	 */
	public function print_control( $options, $args = null )
	{
		extract( $options );
		$current_filtered_data = mt_get_current_filter_data();
		
		if( $hide )
		{
			$display_filter_widget = true;
			if( mt_is_archive() || mt_is_search() )
			{
				if( count($post_types) != count($current_filtered_data['post_types']) )
					$display_filter_widget = false;
				if( count($taxonomies) != count($current_filtered_data['taxonomies']) )
					$display_filter_widget = false;
				
				if( $display_filter_widget )
				{
					foreach( $post_types as $pt )
					{
						if( !in_array($pt, $current_filtered_data['post_types']) )
						{
							$display_filter_widget = false; break;
						}
					}
				}
				
				if( $display_filter_widget )
				{
					foreach( $taxonomies as $tx )
					{
						if( !array_key_exists($tx, $current_filtered_data['taxonomies']) )
						{
							$display_filter_widget = false; break;
						}
					}
				}
			}
			else
			{
				$display_filter_widget = false;
				
				foreach( $taxonomies as $tx )
				{
					if( array_key_exists($tx, $current_filtered_data['taxonomies']) )
					{
						$display_filter_widget = true; break;
					}
				}
			}

			if( !$display_filter_widget ) return;
		}
		
		if( !mt_is_archive() && !mt_is_search() )
		{
			$current_filtered_data['post_types'] = $post_types;
			foreach( $taxonomies as $taxname )
			{
				if( !array_key_exists($taxname, $current_filtered_data['taxonomies']) )
					$current_filtered_data['taxonomies'][$taxname] = array();
			}
		}
		
		echo $args['before_widget'];
		echo '<div id="multi-tax-browser-control-'.self::$index.'" class="wscontrol multi-tax-browser-control">';
		
		if( !empty($title) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		
		mt_print_interface( MTType::FilteredArchive, $post_types, $taxonomies, $related_level, $sort, $max_terms, $current_filtered_data, $show_count );
		
		echo '</div>';
		echo $args['after_widget'];
	}
}
endif;

