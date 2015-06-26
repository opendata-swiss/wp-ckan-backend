<?php
class Ckan_Backend_Main {

	protected $loader;

	protected $plugin_slug;

	protected $version;

	public function __construct() {
		$this->plugin_slug = 'ckan-backend';
		$this->version = '1.0.0';

		$this->load_dependencies();

		$local_dataset = new Ckan_Backend_Local_Dataset();
		$local_group = new Ckan_Backend_Local_Group();
		$local_organisation = new Ckan_Backend_Local_Organisation();
	}

	protected function load_dependencies() {
		$depedency_file_paths = array(
			'helper/ckan-backend-helper.php',
			'post-types/ckan-backend-local-dataset.php',
			'post-types/ckan-backend-local-group.php',
			'post-types/ckan-backend-local-organisation.php',
			'sync/ckan-backend-sync-abstract.php',
			'sync/ckan-backend-sync-local-dataset.php',
			'sync/ckan-backend-sync-local-group.php',
			'sync/ckan-backend-sync-local-organisation.php'
		);

		foreach($depedency_file_paths as $file_path) {
			require_once plugin_dir_path( __FILE__ ) . $file_path;
		}
	}

	public function get_version() {
		return $this->version;
	}

}