<?php

class acf_field_color_picker extends acf_field
{
	
	/*
	*  __construct
	*
	*  Set name / label needed for actions / filters
	*
	*  @since	3.6
	*  @date	23/01/13
	*/
	
	function __construct()
	{
		// vars
		$this->name = 'color_picker';
		$this->label = __("Color Picker",'acf');
		$this->category = __("jQuery",'acf');
		$this->defaults = array(
			'default_value'	=>	'',
		);
		
		
		// do not delete!
    	parent::__construct();
    	
	}
	
	
	/*
	*  input_admin_enqueue_scripts
	*
	*  description
	*
	*  @type	function
	*  @date	3/03/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function input_admin_enqueue_scripts() {
		
		// globals
		global $wp_scripts;
		
		
		// bail early if already set
		if( isset($wp_scripts->registered['iris']) )
		{
			return;
		}
		
		
		// thanks to http://wordpress.stackexchange.com/questions/82718/how-do-i-implement-the-wordpress-iris-picker-into-my-plugin-on-the-front-end
		wp_enqueue_style( 'wp-color-picker' );
	    wp_enqueue_script(
	        'iris',
	        admin_url( 'js/iris.min.js' ),
	        array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ),
	        false,
	        1
	    );
	    wp_enqueue_script(
	        'wp-color-picker',
	        admin_url( 'js/color-picker.min.js' ),
	        array( 'iris' ),
	        false,
	        1
	    );
	    $colorpicker_l10n = array(
	        'clear' => __( 'Clear' ),
	        'defaultString' => __( 'Default' ),
	        'pick' => __( 'Select Color' )
	    );
	    wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n ); 
			
	}
	
	
	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field - an array holding all the field's data
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*/
	
	function render_field( $field ) {
		
		// vars
		$atts = array();
		$e = '';
		
		
		// populate atts
		foreach( array( 'id', 'class', 'name', 'value' ) as $k )
		{
			$atts[ $k ] = $field[ $k ];
		}
		
		
		// render
		$e .= '<div class="acf-color_picker">';
		$e .= '<input type="text" ' . acf_esc_attr($atts) . ' />';
		$e .= '</div>';
		
		
		// return
		echo $e;
	}
	
	
	/*
	*  render_field_options()
	*
	*  Create extra options for your field. This is rendered when editing a field.
	*  The value of $field['name'] can be used (like bellow) to save extra data to the $field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field	- an array holding all the field's data
	*/
	
	function render_field_options( $field ) {
		
		// display_format
		acf_render_field_option( $this->name, array(
			'label'			=> __('Default Value','acf'),
			'instructions'	=> '',
			'type'			=> 'text',
			'name'			=> 'default_value',
			'prefix'		=> $field['prefix'],
			'value'			=> $field['default_value'],
			'placeholder'	=> '#FFFFFF'
		));
		
	}
	
}

new acf_field_color_picker();

?>