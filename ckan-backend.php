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
			add_filter( 'user_profile_update_errors', array( $this, 'validate_user_fields' ), 10, 1 );
			add_action( 'registration_errors', array( $this, 'validate_user_fields' ), 10, 1 );
			add_action( 'user_register', array( $this, 'save_custom_user_profile_fields' ), 10, 1 );
			add_action( 'edit_user_profile_update', array( $this, 'save_custom_user_profile_fields' ), 10, 1 );
			add_action( 'personal_options_update', array( $this, 'save_custom_user_profile_fields' ), 10, 1 );

			// show only own organisation to non admin users
			add_filter( 'parse_query', array( $this, 'show_own_organisation_only' ) );
		}

		/**
		 * Just show organisation which user is assigned
		 *
		 * @param WP_Query $query Current query.
		 */
		public function show_own_organisation_only( $query ) {
			$q_vars = &$query->query_vars;
			if( isset( $q_vars['post_type'] ) && $q_vars['post_type'] !== Ckan_Backend_Local_Organisation::POST_TYPE ) {
				return;
			}

			global $pagenow;
			if ( $pagenow === 'edit.php' ) {
				if( ! current_user_can( 'create_organisations' ) ) {
					// just show organisation which user is assigned
					$user_organisation = get_the_author_meta( self::$plugin_slug . '_organisation', wp_get_current_user()->ID );
					$q_vars['meta_key'] = Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'ckan_name';
					$q_vars['meta_value'] = $user_organisation;
				}
			}
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
			if ( ! current_user_can( 'edit_user_organisation' ) ) {
				return;
			}

			$organisation_field_name = self::$plugin_slug . '_organisation';
			?>
			<h3>Organisation</h3>

			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row">
						<label for="<?php echo esc_attr( $organisation_field_name ); ?>">Organisation</label>
						<span class="description">(required)</span>
					</th>
					<td>
						<select name="<?php echo esc_attr( $organisation_field_name ); ?>" id="<?php echo esc_attr( $organisation_field_name ); ?>" aria-required="true">
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
		 * Validates fields on user profile save
		 *
		 * @param WP_Error $errors Error object where possible errors can be added.
		 */
		public function validate_user_fields( $errors ) {
			if ( ! current_user_can( 'edit_user_organisation' ) ) {
				return;
			}

			if( ! isset( $_POST[ self::$plugin_slug . '_organisation' ] ) || '' === $_POST[ self::$plugin_slug . '_organisation' ] ) {
				$errors->add( 'organisation_required', __( 'Please choose an organisation for this user.' ) );
			}
		}

		/**
		 * Saves all custom user profile fields
		 *
		 * @param int $user_id ID of user being saved.
		 */
		public function save_custom_user_profile_fields( $user_id ) {
			if ( ! current_user_can( 'edit_user_organisation' ) ) {
				return;
			}

			update_user_meta( $user_id, self::$plugin_slug . '_organisation', $_POST[ self::$plugin_slug . '_organisation' ] );
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
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-publisher.php';
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
