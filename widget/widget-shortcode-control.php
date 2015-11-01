<?php
/**
 * Base class for all widget and shortcode controls.
 * 
 * @package    WidgetShortcodeControl
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('WidgetShortcodeControl') ):
class WidgetShortcodeControl extends WP_Widget
{
	/**
	 * The index for the current widget / shortcode object.
	 * @var  int
	 */
	protected static $index = 0;
	
	/**
	 * The control type (widget or shortcode).
	 * @var  string
	 */
	public $control_type;

	/**
	 * The arguments for the widget or shortcode.
	 * @var  Array
	 */
	private $args;
	
	
	/**
	 * Constructor.
	 * Setup the shortcode or widget default properties and actions.
	 * @param  string  $id_base  Optional Base ID for the widget, lowercase and unique. If left empty,
	 *                           a portion of the widget's class name will be used Has to be unique.
	 * @param  string  $name  Name for the widget displayed on the configuration page.
	 * @param  array  $widget_options  Optional. Widget options. See {@see wp_register_sidebar_widget()} for
	 *                                 information on accepted arguments. Default empty array.
	 * @param  array  $control_options  Optional. Widget control options. See {@see 
	 *                                  wp_register_widget_control()} for information on accepted 
	 *                                  arguments. Default empty array.
     */
	public function __construct( $id_base, $name, $widget_ops = null, $control_ops = null )
	{
		parent::__construct( $id_base, $name, $widget_ops, $control_ops );
		
		$this->control_type = 'widget';
		$this->args = array(
			'before_widget'	=> '<div id="%1$s" class="widget %2$s">',
			'after_widget'	=> "</div>\n",
			'before_title'	=> '<h2 class="title">',
			'after_title'	=> "</h2>\n",
		);
		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}
	
	
	/**
	 * Registers the Widget Shortcode Control as a WordPress widget.
	 */
	public static function register_widget()
	{
		add_action(
			'widgets_init',
			create_function('', 'return register_widget("'.get_called_class().'");')
		);
	}
	
	
	/**
	 * Registers the Widget Shortcode Control as a WordPress shortcode.
	 */
	public static function register_shortcode()
	{
		$obj = new static();
		$obj->control_type = 'shortcode';
		add_shortcode( str_replace('-','_',$obj->id_base), array($obj, 'shortcode') );
	}
	
	
	/**
	 * Enqueues the scripts or styles needed for the control in the admin backend.
	 */
	public function admin_enqueue_scripts() { }
	
	
	/**
	 * Enqueues the scripts or styles needed for the control in the site frontend.
	 */
	public function enqueue_scripts() { }
	
	
	/**
	 * Echo the widget content.
	 * Override function from WP_Widget parent class.
	 * @param  array  $args  Display arguments for the widget.
	 * @param  array  $options  The settings for this instance of the widget.
	 */
	public function widget( $args, $options )
	{
		static::$index++;
		$options = $this->process_options( $this->merge_options($options) );
		$this->print_control( $options, $args );
	}
	
	
	/**
	 * Echo the shortcode content.
	 * Called by Shortcode API.
	 * @param  array  $options  The settings for this shortcode.
	 */
	public function shortcode( $options )
	{
		static::$index++;
		if( !is_array($options) ) $options = array();
		$options = $this->process_options( $this->merge_options($options) );
		
		ob_start();
		$this->print_control( $options, $this->get_args() );
		return ob_get_clean();
	}
	
	
	/**
	 * Output the widget form in the admin.
	 * Replacing with print_widget_form.
	 * Do not override. Override print_widget_form instead.
	 * Override function from WP_Widget parent class.
	 * @param  array  $options  The current settings for the widget.
	 */
	public function form( $options )
	{
		$this->print_widget_form( $options );
	}
	
	
	/**
	 * Output the widget form in the admin.
	 * Use this function instead of form.
	 * @param  array  $options  The current settings for the widget.
	 */
	public function print_widget_form( $options )
	{
		die('function WidgetShortcodeControl::print_widget_form() must be over-ridden in a sub-class.');
	}
	
	
	/**
	 * Update a particular instance.
	 * Override function from WP_Widget parent class.
	 * @param  array  $new_options  New options set in the widget form by the user.
	 * @param  array  $old_options  Old options from the database.
	 * @return  array|bool  The settings to save, or false to cancel saving.
	 */
	public function update( $new_options, $old_options )
	{
		return $new_options;
	}
	
	
	/**
	 * Process options from the database or shortcode.
	 * Designed to convert options from strings or sanitize output.
	 * @param  array  $options  The current settings for the widget or shortcode.
	 * @return  array  The processed settings.
	 */
	public function process_options( $options )
	{
		return $options;
	}
	
	
	/**
	 * Get the default settings for the widget or shortcode.
	 * @return  array  The default settings.
	 */
	public function get_default_options()
	{
		return array();
	}
	
	
	/**
	 * Gets the default display arguments for the widget or shortcode.
	 * @return  array  The default display arguments.
	 */
	public function get_args()
	{
		$args = $this->args;
		
		foreach( $args as $k => &$v )
		{
			$v = sprintf(
				$v,
				$this->control_type.'_'.$this->id_base.'-'.static::$index,
				$this->id_base
			);
		}
		
		return $args;
	}
	
	
	/**
	 * Merges the default options with the user-entered options.
	 * @param  array  $options  The user-entered options.
	 * @return  array  The complete options.
	 */
	public function merge_options( $options )
	{
		return array_merge( $this->get_default_options(), $options );
	}
	
	
	/**
	 * Echo the widget or shortcode contents.
	 * @param  array  $options  The current settings for the control.
	 * @param  array  $args  The display arguments.
	 */
	public function print_control( $options, $args )
	{
		die('function WidgetShortcodeControl::print_control() must be over-ridden in a sub-class.');
	}
}
endif;

