<?php 

class acf_settings_export {
	
	/*
	*  __construct
	*
	*  Initialize filters, action, variables and includes
	*
	*  @type	function
	*  @date	23/06/12
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function __construct() {
	
		// actions
		add_action( 'admin_menu', 				array( $this, 'admin_menu' ) );
	}
	
	
	/*
	*  admin_menu
	*
	*  This function will add the ACF menu item to the WP admin
	*
	*  @type	action (admin_menu)
	*  @date	28/09/13
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function admin_menu() {
		
		// bail early if no show_admin
		if( !acf_get_setting('show_admin') )
		{
			return;
		}
		
		
		// add page
		$page = add_submenu_page('edit.php?post_type=acf-field-group', __('Import / Export','acf'), __('Import/Export','acf'), 'manage_options','acf-settings-export', array($this,'html') );
		
		
		// actions
		add_action('load-' . $page, array($this,'load'));
		
	}
	
	
	/*
	*  html
	*
	*  description
	*
	*  @type	function
	*  @date	7/01/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function html() {
		
		// disable JSON to avoid conflicts between DB and JSON
		acf_update_setting('json', false);
		
		
		// vars
		$view = array();
		
		
		// load view
		acf_get_view('settings-export', $view);
		
	}
	
	
	/*
	*  load
	*
	*  description
	*
	*  @type	function
	*  @date	7/01/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function load() {
		
		if( acf_verify_nonce('import') )
		{
			$this->import();
		}
		elseif( acf_verify_nonce('export') )
		{
			$this->export();
		}
		
	}
	
	function export() {
		
		// validate
		if( empty($_POST['acf_export_keys']) )
		{
			return;
		}
		
		
		// vars
		$id_ref = array();
		$json = array();
		
		
		// construct JSON
		foreach( $_POST['acf_export_keys'] as $key )
		{
			// load field group
			$field_group = acf_get_field_group( $key );
			
			
			// validate field group
			if( empty($field_group) )
			{
				continue;
			}
			
			
			// load fields
			$fields = acf_get_fields( $field_group );
			
			
			// extract field group ID and add to ref
			$id = acf_extract_var( $field_group, 'ID' );
			$id_ref[ $id ] = $field_group['key'];
			
			
			// load fields from DB
			if( !empty($fields) )
			{
				foreach( $fields as $field )
				{
					// extract some args
					$extract = acf_extract_vars($field, array(
						'ID',
						'value',
						'menu_order',
						'id',
						'class',
						'ancestors',
						'field_group'
					));
	
					
					// extract field ID and add to ref
					$id_ref[ $extract['ID'] ] = $field['key'];
					
					
					// update parent ID to parent key
					if( isset($id_ref[ $field['parent'] ]) )
					{
						$field['parent'] = $id_ref[ $field['parent'] ];
					}					
					
					
					// append field
					$field_group['fields'][] = $field;
				}
			}
			
			
			// add to json array
			$json[] = $field_group;
			
		}
		// end foreach
		
		
		// set headers
		$file_name = 'acf-export-' . date('Y-m-d') . '.json';
		
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename={$file_name}" );
		header( "Content-Type: application/json" );
		
		echo acf_json_encode( $json );
		die;
		
	}
	
	
	function import() {
		
		// validate
		if( empty($_FILES['acf_import_file']) )
		{
			return;
		}
		
		
		// vars
		$file = $_FILES['acf_import_file'];
		
		
		// validate error
		if( $file['error'] )
		{
			acf_add_admin_notice(__('Error uploading file. Please try again', 'acf'), 'error');
			return;
		}
		
		
		// validate type
		if( $file['type'] !== 'application/json' )
		{
			acf_add_admin_notice(__('Incorrect file type', 'acf'), 'error');
			return;
		}
		
		
		// read file
		$json = file_get_contents( $file['tmp_name'] );
		
		
		// decode json
		$json = json_decode($json, true);
		
		
		// validate json
    	if( empty($json) )
    	{
    		acf_add_admin_notice(__('Import file empty', 'acf'), 'error');
	    	return;
    	}
    	
    	
    	// vars
    	$added = array();
    	$ignored = array();
    	
    	
    	foreach( $json as $field_group )
    	{
	    	// check if field group exists
	    	if( acf_get_field_group($field_group['key']) )
	    	{
	    		// append to ignored
	    		$ignored[] = $field_group['title'];
	    		continue;
	    	}
	    	
	    	
	    	// extract fields
	    	$fields = acf_extract_var($field_group, 'fields');
	    	
	
	    	// save field group
			$field_group = acf_update_field_group( $field_group );
			
	    	
	    	// save fields
	    	if( !empty($fields) )
			{
				foreach( $fields as $field )
				{
					// add args
					$field['parent'] = $field_group['ID'];
					
					
					// save field
					acf_update_field( $field );
				}
			}
			
			
			// append to added
	    	$added[] = $field_group['title'];
			
    	}
    	
    	
    	// messages
    	if( !empty($added) )
    	{
    		$message = __('<b>Success</b>. Import tool added %s field groups: %s', 'acf');
    		$message = sprintf( $message, count($added), implode(', ', $added) );
    		
	    	acf_add_admin_notice( $mesasge );
    	}
    	
    	if( !empty($ignored) )
    	{
    		$message = __('<b>Warning</b>. Import tool detected %s field groups already exist and have been ignored: %s', 'acf');
    		$message = sprintf( $message, count($ignored), implode(', ', $ignored) );
    		
	    	acf_add_admin_notice( $message, 'error' );
    	}
    	
		
	}
	
}


// initialize
new acf_settings_export();

?>