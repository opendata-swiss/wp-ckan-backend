<?php

class Ckan_Backend_Local_Dataset_Import {

	public $menu_slug = 'ckan-local-dataset-import-page';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );
	}


	public function register_submenu_page() {
		add_submenu_page(
			'edit.php?post_type=' . Ckan_Backend_Local_Dataset::POST_TYPE,
			__( 'Import CKAN Dataset', 'ogdch' ),
			__( 'Import', 'ogdch' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'import_page_callback' )
		);
	}

	public function import_page_callback() {
		// must check that the user has the required capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$import_submit_hidden_field_name = 'ckan_local_dataset_import_submit';
		$file_field_name                 = 'ckan_local_dataset_import_file';

		// Handle import
		if ( isset( $_POST[ $import_submit_hidden_field_name ] ) && $_POST[ $import_submit_hidden_field_name ] == 'Y' ) {
			// TODO import file
			if(isset($_FILES[$file_field_name])) {
				$this->handle_file_import($_FILES[$file_field_name]);
			}
			?>
			<div class="updated"><p><strong><?php _e( 'Import successful', 'ogdch' ); ?></strong></p></div>
		<?php } ?>
		<div class="wrap">
			<h2><?php _e( 'Import CKAN Dataset', 'ogdch' ); ?></h2>

			<form enctype="multipart/form-data" action="" method="POST">
				<input type="hidden" name="<?php echo $import_submit_hidden_field_name; ?>" value="Y">

				<p><?php _e( "File:", 'ogdch' ); ?>
					<input type="file" name="<?php echo $file_field_name; ?>" value="" size="20">
				</p>
				<hr/>

				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Import' ) ?>"/>
				</p>
			</form>
		</div>

		<?php
	}

	public function handle_file_import($file) {
		try {
			// Undefined | Multiple Files | $_FILES Corruption Attack
			// If this request falls under any of them, treat it invalid.
			if (
				! isset( $file['error'] ) ||
				is_array( $file['error'] )
			) {
				throw new RuntimeException( 'Invalid parameters.' );
			}

			// Check $file['error'] value.
			switch ( $file['error'] ) {
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_NO_FILE:
					throw new RuntimeException( 'No file sent.' );
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					throw new RuntimeException( 'Exceeded filesize limit.' );
				default:
					throw new RuntimeException( 'Unknown errors.' );
			}

			$xml = simplexml_load_file($file['tmp_name']);
			if( ! $xml ) {
				throw new RuntimeException( 'Uploaded file is not a vaild XML file' );
			}
			print_r($xml);
		} catch ( RuntimeException $e ) {
			echo $e->getMessage();
		}
	}
}