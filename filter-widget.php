<?php

add_action('widgets_init',
     create_function('', 'return register_widget("MultiTax_Filter_Widget");')
);

class MultiTax_Filter_Widget extends WP_Widget
{

//	private static $id = 0;

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct()
	{
		parent::__construct(
			'multitax_filter_widget',
			'MultiTax Filter Widget',
			array( 
				'description' => '', 
			)
		);
	}


	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance )
	{
		if( !is_archive() ) return;
		
		$options = $this->get_instance_variables($instance);
		$options['title'] = ( !empty($options['title']) ? $args['before_title'].$options['title'].$args['after_title'] : '' );

		extract($options);
// 		mt_print($options);
		
		$current_filtered_data = mt_get_current_filter_data();
// 		mt_print($current_filtered_data);
		
		//------------------------------

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
		
		//----------------------------------------
		
		if( !mt_is_archive() && !mt_is_search() )
		{
			$current_filtered_data['post_types'] = $post_types;
			foreach( $taxonomies as $taxname )
			{
				if( !array_key_exists($taxname, $current_filtered_data['taxonomies']) )
					$current_filtered_data['taxonomies'][$taxname] = array();
			}
		}
		
		//----------------------------------------
		
		echo $args['before_widget'];
		
		echo $title;
		mt_print_interface( MTType::FilteredArchive, $post_types, $taxonomies, $current_filtered_data );
		
		echo $args['after_widget'];
		
		//----------------------------------------
		
	}


	/**
	 * Ouputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance )
	{
		extract( $this->get_instance_variables($instance) );
		?>
		
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<br/>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		<br/>
		</p>
		
		<p>
		<label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Post Type:' ); ?></label> 
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

		<?php
	}


	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance )
	{
		$instance = $new_instance;
		return $instance;		
	}
	
	
	/**
	 * 
	 */
	private function get_instance_variables( $instance )
	{
		$options = array();

		// title
		$options['title'] = '';

		// post types
		$options['all_post_types'] = get_post_types( array(), 'objects' );
		$options['exclude_post_types'] = array( 'attachment', 'revision', 'nav_menu_item' );
		$options['post_types'] = array( 'post' );

		// taxonomy types
		$options['all_taxonomies'] = get_taxonomies( array(), 'objects' );
		$options['exclude_taxonomies'] = array( 'nav_menu', 'link_category', 'post_format' );
		$options['taxonomies'] = array('post_tag');
		
		foreach( $instance as $k => $v )
		{
			$options[$k] = $v;
		}
		
		return $options;
	}

}

