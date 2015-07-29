<?php
/**
 * Plugin Name: CKAN Backend
 * Description: Plugin to create manual datasets, organisations and groups in CKAN via API.
 * Author: Team Jazz <juerg.hunziker@liip.ch>
 * Version: 1.0
 * Date: 17.06.2015
 *
 * @package CKAN\Backend
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'Ckan_Backend', false ) ) {

	/**
	 * Class Ckan_Backend
	 */
	class Ckan_Backend {

		/**
		 * Slug of this plugin.
		 * @var string
		 */
		public $plugin_slug = 'ckan-backend';

		/**
		 * Version number of this plugin.
		 * @var string
		 */
		public $version = '1.0.0';

		/**
		 * Single instance of the Ckan_Backend object
		 *
		 * @var Ckan_Backend
		 */
		public static $single_instance = null;

		/**
		 * Creates/returns the single instance Ckan_Backend object
		 *
		 * @return Ckan_Backend Single instance object
		 */
		public static function initiate() {
			if ( null === self::$single_instance ) {
				self::$single_instance = new self();
			}

			return self::$single_instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'bootstrap' ), 0 );
			add_action( 'admin_init', array( $this, 'add_scripts' ) );
			register_activation_hook( __FILE__, array( $this, 'activate' ) );
		}

		/**
		 * Bootstrap all post types.
		 *
		 * @return void
		 */
		public function bootstrap() {
			$this->load_dependencies();

			new Ckan_Backend_Local_Dataset();
			new Ckan_Backend_Local_Group();
			new Ckan_Backend_Local_Organisation();
			new Ckan_Backend_Local_Dataset_Import();
		}

		/**
		 * Add scripts and styles.
		 *
		 * @return void
		 */
		public function add_scripts() {
			wp_register_style( 'ckan-backend-base', plugins_url( 'assets/css/base.css', __FILE__ ) );
			wp_enqueue_style( 'ckan-backend-base' );
		}

		/**
		 * Activate the plugin.
		 *
		 * @return void
		 */
		public function activate() {
			// Add all new capabilities of plugin to administrator role (save in database).
			$new_capabilities = array(
				'disable_datasets'
			);

			$admin_role = get_role( 'administrator' );
			if ( is_object( $admin_role ) ) {
				$admin_role->add_cap( 'edit_datasets' );
				$admin_role->add_cap( 'edit_others_datasets' );
				$admin_role->add_cap( 'publish_datasets' );
				$admin_role->add_cap( 'read_private_datasets' );
				$admin_role->add_cap( 'delete_datasets' );
				$admin_role->add_cap( 'delete_private_datasets' );
				$admin_role->add_cap( 'delete_published_datasets' );
				$admin_role->add_cap( 'delete_others_datasets' );
				$admin_role->add_cap( 'edit_private_datasets' );
				$admin_role->add_cap( 'edit_published_datasets' );
				$admin_role->add_cap( 'create_datasets' );

				foreach ( $new_capabilities as $cap ) {
					$admin_role->add_cap( $cap );
				}
			}
		}

		/**
		 * Load all the dependencies.
		 *
		 * @return void
		 */
		protected function load_dependencies() {
			$depedency_file_paths = array(
				'helper/ckan-backend-helper.php',
				'post-types/ckan-backend-local-dataset.php',
				'post-types/ckan-backend-local-group.php',
				'post-types/ckan-backend-local-organisation.php',
				'post-types/ckan-backend-local-dataset-import.php',
				'sync/ckan-backend-sync-abstract.php',
				'sync/ckan-backend-sync-local-dataset.php',
				'sync/ckan-backend-sync-local-group.php',
				'sync/ckan-backend-sync-local-organisation.php',
			);

			foreach ( $depedency_file_paths as $file_path ) {
				require_once plugin_dir_path( __FILE__ ) . $file_path;
			}
		}

		/**
		 * Getter of the version number.
		 *
		 * @return string
		 */
		public function get_version() {
			return $this->version;
		}

	}

	Ckan_Backend::initiate();
}
