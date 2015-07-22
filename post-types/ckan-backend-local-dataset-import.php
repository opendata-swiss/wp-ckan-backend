<?php

add_action('admin_menu', 'register_ckan_local_dataset_import_submenu_page');

function register_ckan_local_dataset_import_submenu_page() {
	add_submenu_page(
		'edit.php?post_type=' . Ckan_Backend_Local_Dataset::POST_TYPE,
		__( 'Import CKAN Dataset', 'ogdch' ),
		__( 'Import', 'ogdch' ),
		'manage_options',
		'ckan-local-dataset-import-page',
		'ckan_local_dataset_import_page_callback'
	);
}

function ckan_local_dataset_import_page_callback() {
	// must check that the user has the required capability
	if (!current_user_can('manage_options'))
	{
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	$import_submit_hidden_field_name = 'ckan_local_dataset_import_submit';
	$file_path_field_name = 'ckan_local_dataset_import_file_path';

	// Handle import
	if( isset($_POST[ $import_submit_hidden_field_name ]) && $_POST[ $import_submit_hidden_field_name ] == 'Y' ) {
		// Read their posted value
		$file_path = $_POST[ $file_path_field_name ];

		// TODO import file

		?>
		<div class="updated"><p><strong><?php _e('Import successful', 'ogdch' ); ?></strong></p></div>
		<?php
	}

	echo '<div class="wrap">';
		echo '<h2>' . __( 'Import CKAN Dataset', 'ogdch' ) . '</h2>';
		?>

		<form name="form1" method="post" action="">
			<input type="hidden" name="<?php echo $import_submit_hidden_field_name; ?>" value="Y">

			<p><?php _e("File:", 'ogdch' ); ?>
			<input type="file" name="<?php echo $file_path_field_name; ?>" value="" size="20">
			</p><hr />

			<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Import') ?>" />
			</p>
		</form>
	</div>

	<?php
}