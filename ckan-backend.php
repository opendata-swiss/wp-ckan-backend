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

			// Allow users to edit all entities of their organisation (override edit_others_entities capability)
			add_filter( 'user_has_cap', array( $this, 'allow_edit_own_organisation' ), 10, 3 );

			// Disallow non-administrators to edit users from other organisation
			add_filter( 'user_has_cap', array( $this, 'allow_edit_users_of_same_organisation' ), 10, 3 );
			// Disable possibility to assign admin or content manager role for non-admin users
			add_filter( 'editable_roles', array( $this, 'disable_roles_for_non_admins' ) );

			// Add organisation to user list
			add_filter( 'manage_users_columns', array( $this, 'add_user_organisation_column' ) );
			add_filter( 'manage_users_custom_column', array( $this, 'manage_user_organisation_column' ), 10, 3 );

			// Add organisation filter dropdown to user admin list
			add_filter( 'pre_user_query', array( $this, 'filter_user_organisation' ) );
			add_filter( 'restrict_manage_users', array( $this, 'add_user_organisation_filter' ) );
		}

		/**
		 * Allow users to edit all entities of their organisation (override edit_others_entities capability)
		 *
		 * @param array $allcaps All the capabilities of the user.
		 * @param array $cap [0] Required capability.
		 * @param array $args [0] Requested capability, [1] User ID, [2] Associated object ID.
		 *
		 * @return array
		 */
		public function allow_edit_own_organisation( $allcaps, $cap, $args ) {
			$requested_cap = $args[0];
			$required_cap  = $cap[0];

			// Bail out users who are already allowed to edit other datasets / organisations)
			if ( isset( $allcaps['edit_others_organisations'] ) || isset( $allcaps['edit_others_datasets'] ) ) {
				return $allcaps;
			}

			// TODO on save there is a call without a post id. Why?
			if ( in_array( $requested_cap, array( 'edit_others_datasets', 'edit_others_organisations' ) ) && empty( $args[2] ) ) {
				$allcaps[ $requested_cap ] = true;
			}

			if ( 'edit_others_organisations' === $required_cap ) {
				$current_user_id   = $args[1];
				$post_id           = $args[2];
				$organisation      = get_post_meta( $post_id, Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'ckan_name', true );
				$user_organisation = get_the_author_meta( self::$plugin_slug . '_organisation', $current_user_id );
				// if the assigned organisation matches the organisation of the current entity
				if ( $organisation === $user_organisation ) {
					$allcaps[ $required_cap ] = true;
				}
			}

			if ( 'edit_others_datasets' === $required_cap ) {
				$current_user_id   = $args[1];
				$post_id           = $args[2];
				$identifier        = get_post_meta( $post_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'identifier', true );
				$user_organisation = get_the_author_meta( self::$plugin_slug . '_organisation', $current_user_id );
				if ( $identifier['organisation'] === $user_organisation ) {
					$allcaps[ $required_cap ] = true;
				}
			}

			return $allcaps;
		}

		/**
		 * Disallow non-administrators to edit users from other organisation
		 *
		 * @param array $allcaps All the capabilities of the user.
		 * @param array $cap [0] Required capability.
		 * @param array $args [0] Requested capability, [1] User ID, [2] Associated object ID.
		 *
		 * @return array
		 */
		public function allow_edit_users_of_same_organisation( $allcaps, $cap, $args ) {
			$required_cap = $cap[0];

			if ( 'edit_users' !== $required_cap ) {
				return $allcaps;
			}
			$current_user_id = $args[1];
			$user_info       = get_userdata( $current_user_id );

			if ( ! in_array( 'administrator', $user_info->roles ) ) {
				$other_user_id = $args[2];

				if ( ! empty( $other_user_id ) ) {
					$user_organisation       = get_the_author_meta( self::$plugin_slug . '_organisation', $current_user_id );
					$other_user_organisation = get_user_meta( $other_user_id, self::$plugin_slug . '_organisation', true );
					if ( $user_organisation !== $other_user_organisation ) {
						// remove edit_users capability if other user isn't in same organisation
						$allcaps[ $required_cap ] = false;
					}
				}
			}

			return $allcaps;
		}

		/**
		 * Disable possibility to assign admin or content manager role for non-admin users
		 *
		 * @param array $roles Available roles.
		 *
		 * @return array
		 */
		public function disable_roles_for_non_admins( $roles ) {
			$user_info = get_userdata( get_current_user_id() );

			if ( ! in_array( 'administrator', $user_info->roles ) ) {
				if ( isset( $roles['administrator'] ) ) {
					unset( $roles['administrator'] );
				}
				if ( isset( $roles['content-manager'] ) ) {
					unset( $roles['content-manager'] );
				}
			}

			return $roles;
		}

		/**
		 * Add organisation filter dropdown to user admin list
		 */
		public function add_user_organisation_filter() {
			Ckan_Backend_Helper::print_organisation_filter( true );
			submit_button( __( 'Filter' ), 'button', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
		}

		/**
		 * Applies organisation filter to user query
		 *
		 * @param WP_Query $query The current query.
		 */
		function filter_user_organisation( $query ) {
			global $pagenow;
			if ( is_admin() && 'users.php' === $pagenow ) {
				$current_user          = wp_get_current_user();
				$current_user_is_admin = in_array( 'administrator', $current_user->roles );
				$organisation_filter   = '';
				if ( isset( $_GET['organisation_filter'] ) ) {
					$organisation_filter = sanitize_text_field( $_GET['organisation_filter'] );
				} elseif ( ! $current_user_is_admin ) {
					// set filter on first page load if user is not an administrator
					$organisation_filter = get_the_author_meta( Ckan_Backend::$plugin_slug . '_organisation', $current_user->ID );
				}

				if ( ! empty( $organisation_filter ) ) {
					global $wpdb;
					// @codingStandardsIgnoreStart
					$query->query_from .= " INNER JOIN {$wpdb->usermeta} ON " .
					                      "{$wpdb->users}.ID={$wpdb->usermeta}.user_id AND " .
					                      "{$wpdb->usermeta}.meta_key='" . Ckan_Backend::$plugin_slug . '_organisation' . "' AND {$wpdb->usermeta}.meta_value = '{$organisation_filter}' ";
					// @codingStandardsIgnoreEnd
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

			Ckan_Backend_Frequency::init();
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
						<select name="<?php echo esc_attr( $organisation_field_name ); ?>"
						        id="<?php echo esc_attr( $organisation_field_name ); ?>" aria-required="true">
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

			if ( ! isset( $_POST[ self::$plugin_slug . '_organisation' ] ) || '' === $_POST[ self::$plugin_slug . '_organisation' ] ) {
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
		 * Add organisation column to user list
		 *
		 * @param array $columns Array with all current columns.
		 *
		 * @return array
		 */
		public function add_user_organisation_column( $columns ) {
			$new_columns = array(
				'user_organisation' => __( 'Organization', 'ogdch' ),
			);

			return array_merge( $columns, $new_columns );
		}

		/**
		 * Returns data for organisation column
		 *
		 * @param string $output Custom column output. Default empty.
		 * @param string $column_name Column name.
		 * @param int    $user_id ID of the currently-listed user.
		 *
		 * @return mixed
		 */
		public function manage_user_organisation_column( $output = '', $column_name, $user_id ) {
			if ( 'user_organisation' === $column_name ) {
				$user_organisation = get_user_meta( $user_id, self::$plugin_slug . '_organisation', true );

				// TODO use readable organisation name instead of slug
				return $user_organisation;
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
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-publisher.php';
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-relation.php';
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-temporal.php';
			require_once plugin_dir_path( __FILE__ ) . 'model/ckan-backend-dataset.php';
			require_once plugin_dir_path( __FILE__ ) . 'taxonomies/ckan-backend-frequency.php';
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
