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
		if ( ! current_user_can( 'create_datasets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$import_submit_hidden_field_name = 'ckan_local_dataset_import_submit';
		$file_field_name                 = 'ckan_local_dataset_import_file';

		// Handle import
		if ( isset( $_POST[ $import_submit_hidden_field_name ] ) && $_POST[ $import_submit_hidden_field_name ] == 'Y' ) {
			$success = false;
			if(isset($_FILES[$file_field_name])) {
				$success = $this->handle_file_import($_FILES[$file_field_name]);
			}

			if ( $success ) {
				echo '<div class="updated"><p><strong>' . __( 'Import successful', 'ogdch' ) . '</strong></p></div>';
			}
		} ?>
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

			$xml_object = simplexml_load_file($file['tmp_name']);
			$json_string = json_encode( $xml_object );
			$xml = json_decode($json_string, TRUE);
			if( ! $xml ) {
				throw new RuntimeException( 'Uploaded file is not a vaild XML file' );
			}

			return $this->create_local_dataset($xml);
		} catch ( RuntimeException $e ) {
			echo $e->getMessage();
		}
	}

	public function create_local_dataset($xml) {
		foreach($xml['groups']['group'] as $group) {
			if ( ! Ckan_Backend_Helper::group_exists( $group['name'] ) ) {
				echo '<div class="error"><p>';
				printf( __( 'Group %1$s does not exist! Import aborted.', 'ogdch' ), $group['name'] );
				echo '</p></div>';
				return false;
			}
		}

		if ( ! Ckan_Backend_Helper::organisation_exists( $xml['owner_org'] ) ) {
			echo '<div class="error"><p>';
			printf( __( 'Organisation %1$s does not exist! Import aborted.', 'ogdch' ), $xml['owner_org'] );
			echo '</p></div>';
			return false;
		}

		// simulate $_POST data to make post_save hook work correctly
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'custom_fields' ] = array();
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'resources' ] = array();
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'groups' ] = array();
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'name' ] = $xml['name'];
		$_POST['post_title'] = $xml['title'];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'maintainer' ] = $xml['maintainer'];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'maintainer_email' ] = $xml['maintainer_email'];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'author' ] = $xml['author'];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'author_email' ] = $xml['author_email'];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'description_de' ] = $xml['description_de'];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'version' ] = $xml['version'];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'organisation' ] = $xml['owner_org'];

		// TODO check if it's an update or an insert action
		$dataset_search_args = array(
			'meta_key' => Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'masterid',
			'meta_value' => $xml['masterid'],
			'post_type' => Ckan_Backend_Local_Dataset::POST_TYPE,
			'post_status' => 'any',
		);
		$datasets = get_posts( $dataset_search_args );

		if(count($datasets) > 0) {
			// Dataset already exists -> update
			$dataset_id = $datasets[0]->ID;
			$this->update($dataset_id, $xml);
		} else {
			// Create new dataset
			$dataset_id = $this->insert($xml);
		}

		return $dataset_id;
	}

	protected function update( $dataset_id, $xml ) {
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'disabled' ] = get_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'disabled', true );
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'reference' ] = get_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'reference', true );

		$dataset_args = array(
			'ID'             => $dataset_id,
			'post_name'      => $xml['name'],
			'post_title'     => $xml['title'],
		);

		wp_update_post( $dataset_args );

		// manually update all dataset metafields
		update_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'name_de', $xml['title'] );
	}

	protected function insert( $xml ) {
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'disabled' ] = '';

		$dataset_args = array(
			'post_name'      => $xml['name'],
			'post_title'     => $xml['title'],
			'post_status'    => 'publish',
			'post_type'      => Ckan_Backend_Local_Dataset::POST_TYPE,
			'post_excerpt'   => '',
		);

		$dataset_id = wp_insert_post( $dataset_args );

		// manually insert all dataset metafields
		add_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'name_de', $xml['title'], true );

		return $dataset_id;
	}
}