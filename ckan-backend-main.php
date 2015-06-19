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

	private function load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-dataset.php';
		require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-group.php';
		require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-organisation.php';
		require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-abstract.php';
		require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-local-dataset.php';
		require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-local-group.php';
		require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-local-organisation.php';
	}

	public function get_version() {
		return $this->version;
	}

}