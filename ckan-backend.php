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
		public static $plugin_slug = 'ckan-backend';

		/**
		 * Version number of this plugin.
		 * @var string
		 */
		public static $version = '1.0.0';

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
			// add custom user profile fields
			add_action( 'show_user_profile', array( $this, 'add_custom_user_profile_fields' ) );
			add_action( 'edit_user_profile', array( $this, 'add_custom_user_profile_fields' ) );
			add_action( 'user_new_form', array( $this, 'add_custom_user_profile_fields' ) );
			// save custom user profile fields
			add_action( 'user_register', array( $this, 'save_custom_user_profile_fields' ), 10, 1 );
			add_action( 'profile_update', array( $this, 'save_custom_user_profile_fields' ), 10, 1 );
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
		 * Adds custom user profile fields
		 *
		 * @param object $user User which is edited. Not available in 'user_new_form' action.
		 */
		public function add_custom_user_profile_fields( $user = null ) {
			// TODO do not show field is user can't manage_options
			$organisation_field_name = self::$plugin_slug . '_organisation';
			?>
			<h3>Organisation</h3>

			<table class="form-table">
				<tr>
					<th><label for="<?php echo esc_attr( $organisation_field_name ); ?>">Organisation</label></th>
					<td>
						<select name="<?php echo esc_attr( $organisation_field_name ); ?>" id="<?php echo esc_attr( $organisation_field_name ); ?>">
							<?php
							echo '<option value="">' . esc_attr( '- Please choose -', 'ogdch' ) . '</option>';
							$organisation_options = Ckan_Backend_Helper::get_organisation_form_field_options();
							foreach ( $organisation_options as $value => $title ) {
								echo '<option value="' . esc_attr( $value ) . '"';
								if ( is_object( $user ) ) {
									if ( get_the_author_meta( $organisation_field_name, $user->ID ) === $value ) {
										echo ' selected="selected"';
									}
								}

								echo '>' . esc_attr( $title ) . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Saves all custom user profile fields
		 *
		 * @param int $user_id ID of user being saved.
		 *
		 * @return bool|int
		 */
		public function save_custom_user_profile_fields( $user_id ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			return update_user_meta( $user_id, self::$plugin_slug . '_organisation', $_POST[ self::$plugin_slug . '_organisation' ] );
		}

		/**
		 * Activate the plugin.
		 *
		 * @return void
		 */
		public function activate() {
			$post_types = array(
				'datasets',
				'groups',
				'organisations',
			);
			// Add all capabilities of plugin to administrator role (save in database) to make them visible in backend.
			$admin_role = get_role( 'administrator' );
			if ( is_object( $admin_role ) ) {
				foreach ( $post_types as $post_type ) {
					$admin_role->add_cap( 'edit_' . $post_type );
					$admin_role->add_cap( 'edit_others_' . $post_type );
					$admin_role->add_cap( 'publish_' . $post_type );
					$admin_role->add_cap( 'read_private_' . $post_type );
					$admin_role->add_cap( 'delete_' . $post_type );
					$admin_role->add_cap( 'delete_private_' . $post_type );
					$admin_role->add_cap( 'delete_published_' . $post_type );
					$admin_role->add_cap( 'delete_others_' . $post_type );
					$admin_role->add_cap( 'edit_private_' . $post_type );
					$admin_role->add_cap( 'edit_published_' . $post_type );
					$admin_role->add_cap( 'create_' . $post_type );
				}
			}
		}

		/**
		 * Load all the dependencies.
		 *
		 * @return void
		 */
		protected function load_dependencies() {
			require_once plugin_dir_path( __FILE__ ) . 'helper/ckan-backend-helper.php';
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-contact-point.php';
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-distribution.php';
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-relation.php';
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-see-also.php';
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-temporal.php';
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-dataset.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-dataset.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-group.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-organisation.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-dataset-import.php';
			require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-abstract.php';
			require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-local-dataset.php';
			require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-local-group.php';
			require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-local-organisation.php';
		}

	}

	Ckan_Backend::initiate();
}
