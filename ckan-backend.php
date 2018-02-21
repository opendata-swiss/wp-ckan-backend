<?php
/**
 * Plugin Name: CKAN Backend
 * Description: Manages datasets, organizations, groups and harvesters in CKAN via its API. It is required to install the <strong>CMB2</strong> plugin as well to use all the features of this plugin.
 * Author: Liip - Team Jazz <jazz@liip.ch>
 * Author URI:   https://liip.ch
 * Plugin URI:   https://github.com/opendata-swiss/wp-ckan-backend
 * Version: 1.0.0
 * Date: 01.12.2016
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
		 * Mapping between language and taxonomy name for keywords
		 *
		 * @var array
		 */
		public static $keywords_tax_mapping = array();

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
			$this->load_dependencies();

			register_activation_hook( __FILE__,  array( $this, 'activate_plugin' ) );
			add_action( 'init', array( $this, 'bootstrap' ), 0 );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ), 999, 1 );
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
			add_filter( 'restrict_manage_users', array( $this, 'add_user_organisation_filter' ), 10 , 1 );

			// Add body class to uses which can edit data of all organizations
			add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );

			// hide specific row actions
			add_filter( 'post_row_actions', array( $this, 'hide_row_actions' ), 10, 2 );

			// add custom CMB2 field type ckan_synced
			add_action( 'cmb2_render_ckan_synced', array( $this, 'cmb2_render_callback_ckan_synced' ), 10, 5 );

			// Add ckan sync status to submitbox
			add_action( 'post_submitbox_start', array( $this, 'add_ckan_sync_status_to_submitbox' ), 10, 1 );

			// order custom post types alphabetically in admin list
			add_action( 'pre_get_posts', array( $this, 'set_post_order_in_admin' ) );

			// filter slug generation
			add_filter( 'sanitize_title', array( $this, 'slug_must_be_string' ) );

			// use custom mailer when debugging
			add_action( 'phpmailer_init', array( $this, 'mailer_config' ) );
		}

		/**
		 * Configures the PHPMailer of WordPress
		 *
		 * This function is used for debugging purposes on local setups
		 *
		 * @param PHPMailer $mailer The mailer instance.
		 */
		public function mailer_config(PHPMailer $mailer) {
			if ( defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ) {
				$mailer->IsSMTP();
				$mailer->Host = 'localhost'; // your SMTP server
				$mailer->Port = 1025;
				$mailer->SMTPDebug = 0; // write 2 if you want to see client/server communication in page
				$mailer->CharSet = 'utf-8';
			}
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
			$requested_cap = '';
			if ( count( $args ) > 0 ) {
				$requested_cap = $args[0];
			}
			$required_cap = '';
			if ( count( $cap ) > 0 ) {
				$required_cap = $cap[0];
			}

			// Bail out users who are already allowed to edit other datasets / organisations)
			if ( isset( $allcaps['edit_others_organisations'] ) || isset( $allcaps['edit_others_datasets'] ) ) {
				return $allcaps;
			}

			// On the list view there is a call without a post id
			if ( in_array( $requested_cap, array( 'edit_others_datasets', 'edit_others_organisations' ) ) && empty( $args[2] ) ) {
				$allcaps[ $requested_cap ] = true;
				return $allcaps;
			}

			if ( 'edit_others_organisations' === $required_cap ) {
				$current_user_id   = $args[1];
				$post_id           = $args[2];
				$organisation      = get_post_meta( $post_id, Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'ckan_name', true );
				// if the assigned organisation matches the organisation of the current entity
				if ( Ckan_Backend_Helper::is_own_organization( $organisation, $current_user_id ) ) {
					$allcaps[ $required_cap ] = true;
				}
			}

			if ( 'edit_others_datasets' === $required_cap ) {
				$current_user_id   = $args[1];
				$post_id           = $args[2];
				$identifier        = get_post_meta( $post_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'identifier', true );
				if ( Ckan_Backend_Helper::is_own_organization( $identifier['organisation'], $current_user_id ) ) {
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
			$required_cap = '';
			if ( count( $cap ) > 0 ) {
				$required_cap = $cap[0];
			}

			if ( 'edit_users' !== $required_cap ) {
				return $allcaps;
			}
			$current_user_id = $args[1];

			// only remove capability if user is a non-admin user
			if ( ! Ckan_Backend_Helper::current_user_has_role( 'administrator' ) ) {
				if ( ! empty( $args[2] ) ) {
					$other_user_id = $args[2];
					$other_user_organisation = get_user_meta( $other_user_id, self::$plugin_slug . '_organisation', true );
					if ( ! Ckan_Backend_Helper::is_own_organization( $other_user_organisation, $current_user_id ) || Ckan_Backend_Helper::user_has_role( $other_user_id, 'administrator' ) || Ckan_Backend_Helper::user_has_role( $other_user_id, 'content_manager' ) ) {
						// remove edit_users capability if other user isn't in same organisation, an admin user or a content manager
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
			if ( ! Ckan_Backend_Helper::current_user_has_role( 'administrator' ) ) {
				if ( isset( $roles['administrator'] ) ) {
					unset( $roles['administrator'] );
				}
				if ( isset( $roles['content_manager'] ) ) {
					unset( $roles['content_manager'] );
				}
			}

			return $roles;
		}

		/**
		 * Add organisation filter dropdown to user admin list
		 *
		 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
		 */
		public function add_user_organisation_filter( $which ) {
			$field_name = 'organisation_filter_' . $which;
			Ckan_Backend_Helper::print_organisation_filter( true, $field_name );
			$submit_id = 'post-query-submit-' . $which;
			$submit_name = 'filter_action_' . $which;
			submit_button( __( 'Filter' ), 'button', $submit_name, false, array( 'id' => $submit_id ) );
		}

		/**
		 * Applies organisation filter to user query
		 *
		 * @param WP_Query $query The current query.
		 */
		function filter_user_organisation( $query ) {
			global $pagenow;
			if ( is_admin() && 'users.php' === $pagenow ) {
				$organisation_filter   = '';
				if ( isset( $_GET['organisation_filter_top'] ) && ! empty( $_GET['filter_action_top'] ) ) {
					$organisation_filter = sanitize_text_field( $_GET['organisation_filter_top'] );
				} elseif ( isset( $_GET['organisation_filter_bottom'] ) && ! empty( $_GET['filter_action_bottom'] ) ) {
					$organisation_filter = sanitize_text_field( $_GET['organisation_filter_bottom'] );
				} elseif ( ! Ckan_Backend_Helper::current_user_has_role( 'administrator' ) ) {
					// set filter on first page load if user is not an administrator
					$organisation_filter = get_the_author_meta( Ckan_Backend::$plugin_slug . '_organisation', get_current_user_id() );
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
		 * Activation hook, check if we can actually use this plugin
		 *
		 * @return void
		 */
		public function activate_plugin() {
			//check if constants are defined in wp config
			if ( is_admin() && ! defined( 'CKAN_API_ENDPOINT' ) ) {
				wp_die( 'Please define CKAN_API_ENDPOINT in your WP config.' );
				return;
			}
			if ( is_admin() && ! defined( 'CKAN_API_KEY' ) ) {
				wp_die( 'Please define CKAN_API_KEY in your WP config.' );
				return;
			}
			// Require CMB2 plugin
			if ( ! is_plugin_active( 'cmb2/init.php' ) && current_user_can( 'activate_plugins' ) ) {
				// Stop activation redirect and show error
				wp_die( 'Sorry, but this plugin requires CMB2 to be installed and active.' );
			}
			// initialize capabilities
			$this->init_caps();
		}

		/**
		 * Initializes capabilities used in this plugin
		 */
		public function init_caps() {
			$post_types = array(
				'datasets',
				'groups',
				'organisations',
				'harvesters',
			);
			$taxonomies = array(
				'keywords',
				'mediatypes',
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
				foreach ( $taxonomies as $taxonomy ) {
					$admin_role->add_cap( 'manage_' . $taxonomy );
					$admin_role->add_cap( 'edit_' . $taxonomy );
					$admin_role->add_cap( 'delete_' . $taxonomy );
					$admin_role->add_cap( 'assign_' . $taxonomy );
				}

				$admin_role->add_cap( 'edit_data_of_all_organisations' );
				$admin_role->add_cap( 'edit_organisation_users' );
				$admin_role->add_cap( 'harvester_dashboard' );
			}
		}

		/**
		 * Bootstraps the plugin
		 *
		 * @return void
		 */
		public function bootstrap() {
			// Initialize text domain
			load_plugin_textdomain( 'ogdch-backend', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			self::$keywords_tax_mapping = array(
				'en' => Ckan_Backend_Keyword_En::TAXONOMY,
				'de' => Ckan_Backend_Keyword_De::TAXONOMY,
				'fr' => Ckan_Backend_Keyword_Fr::TAXONOMY,
				'it' => Ckan_Backend_Keyword_It::TAXONOMY,
			);

			Ckan_Backend_Frequency::init();
			Ckan_Backend_Rights::init();
			Ckan_Backend_PoliticalLevel::init();
			new Ckan_Backend_Keyword_De();
			new Ckan_Backend_Keyword_En();
			new Ckan_Backend_Keyword_Fr();
			new Ckan_Backend_Keyword_It();
			new Ckan_Backend_MediaType();
			new Ckan_Backend_Local_Dataset();
			new Ckan_Backend_Local_Group();
			new Ckan_Backend_Local_Organisation();
			new Ckan_Backend_Local_Dataset_Import();
			new Ckan_Backend_Local_Dataset_Export();
			new Ckan_Backend_Local_Harvester();
			new Ckan_Backend_Local_Harvester_Dashboard();
			new Ckan_Backend_User_Admin();
		}

		/**
		 * Add scripts and styles.
		 *
		 * @param string $suffix Suffix of current admin page.
		 */
		public function add_scripts( $suffix ) {
			wp_register_style( 'ckan-backend-base', plugins_url( 'assets/css/base.css', __FILE__ ) );
			wp_enqueue_style( 'ckan-backend-base' );

			wp_register_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js' );
			wp_enqueue_script( 'select2' );

			wp_register_script( 'ckan-backend-base', plugins_url( 'assets/javascript/base.js', __FILE__ ), array( 'select2' ), null, false );
			wp_enqueue_script( 'ckan-backend-base' );
			wp_localize_script( 'ckan-backend-base', 'baseConfig',
				array(
					'datasetSearch' => array(
						'CKAN_API_ENDPOINT' => CKAN_API_ENDPOINT,
						'currentLanguage'   => Ckan_Backend_Helper::get_current_language(),
						'placeholder'       => __( 'Search dataset...', 'ogdch-backend' ),
					),
					'mediatypeSearch' => array(
						'placeholder' => __( 'No media type', 'ogdch-backend' ),
					),
				)
			);

			wp_register_script( 'select2-i18n', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/i18n/' . Ckan_Backend_Helper::get_current_language() . '.js', array( 'select2' ), null, false );
			wp_enqueue_script( 'select2-i18n' );

			wp_register_style( 'select2-style', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css' );
			wp_enqueue_style( 'select2-style' );
		}

		/**
		 * Adds custom user profile fields
		 *
		 * @param object $user User which is edited. Not available in 'user_new_form' action.
		 */
		public function add_custom_user_profile_fields( $user = null ) {
			if ( ! current_user_can( 'edit_data_of_all_organisations' ) ) {
				return;
			}

			$organisation_field_name = self::$plugin_slug . '_organisation';
			?>
			<h3><?php esc_attr_e( 'Organization', 'ogdch-backend' ); ?></h3>

			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row">
						<label for="<?php echo esc_attr( $organisation_field_name ); ?>"><?php esc_attr_e( 'Organization', 'ogdch-backend' ); ?></label>
						<span class="description">(required)</span>
					</th>
					<td>
						<select name="<?php echo esc_attr( $organisation_field_name ); ?>"
						        id="<?php echo esc_attr( $organisation_field_name ); ?>" aria-required="true">
							<?php
							echo '<option value="">' . esc_attr( '- Please choose -', 'ogdch-backend' ) . '</option>';
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
			if ( ! current_user_can( 'edit_data_of_all_organisations' ) ) {
				return;
			}

			if ( ! isset( $_POST[ self::$plugin_slug . '_organisation' ] ) || '' === $_POST[ self::$plugin_slug . '_organisation' ] ) {
				$errors->add( 'organization_required', __( 'Please choose an organization for this user.' ) );
			}
		}

		/**
		 * Saves all custom user profile fields
		 *
		 * @param int $user_id ID of user being saved.
		 */
		public function save_custom_user_profile_fields( $user_id ) {
			if ( ! current_user_can( 'edit_data_of_all_organisations' ) ) {
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
				'user_organisation' => __( 'Organization', 'ogdch-backend' ),
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

				return esc_attr( Ckan_Backend_Helper::get_organization_title( $user_organisation ) );
			}
		}

		/**
		 * Add body class to uses which can edit data of all organizations
		 *
		 * @param string $classes Current classes on body element.
		 *
		 * @return string
		 */
		public function add_admin_body_class( $classes ) {
			if ( current_user_can( 'edit_data_of_all_organisations' ) ) {
				$classes .= ' can_edit_data_of_all_organisations';
			}
			return $classes;
		}

		/**
		 * Hides specific row actions on custom post type
		 *
		 * @param array   $actions An array of row action links. Defaults are 'Edit', 'Quick Edit', 'Restore, 'Trash', 'Delete Permanently', 'Preview', and 'View'.
		 * @param WP_Post $post The post object.
		 *
		 * @return array
		 */
		public function hide_row_actions( $actions, $post ) {
			$remove_quick_edit_on_cpt = array(
				Ckan_Backend_Local_Dataset::POST_TYPE,
				Ckan_Backend_Local_Group::POST_TYPE,
				Ckan_Backend_Local_Organisation::POST_TYPE,
				Ckan_Backend_Local_Harvester::POST_TYPE,
			);
			if ( in_array( $post->post_type, $remove_quick_edit_on_cpt ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
			// Hide 'View' link in harvest list
			if ( $post->post_type === Ckan_Backend_Local_Harvester::POST_TYPE ) {
				unset( $actions['view'] );
			}
			return $actions;
		}

		/**
		 * Renders CMB2 field of type ckan_synced. Field ID must be equal to synced meta key in database
		 *
		 * @param CMB2_Field $field The passed in `CMB2_Field` object.
		 * @param mixed      $escaped_value The value of this field escaped. It defaults to `sanitize_text_field`.
		 * @param int        $object_id The ID of the current object.
		 * @param string     $object_type The type of object you are working with.
		 * @param CMB2_Types $field_type_object This `CMB2_Types` object.
		 */
		public function cmb2_render_callback_ckan_synced( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
			$meta_key = $field->id();
			$synced = get_post_meta( $object_id, $meta_key, true );
			if ( $synced ) {
				echo '<p class="ckan-synced success"><span class="dashicons dashicons-yes"></span>' . esc_attr__( 'All good!', 'ogdch-backend' ) . '</p>';
			} else {
				echo '<p class="ckan-synced error"><span class="dashicons dashicons-no"></span>' . esc_attr__( 'Not synchronized! Please fix data and save the element again.', 'ogdch-backend' ) . '</p>';
			}

			// add link to dashboard if we're on the harvster detail page
			if ( Ckan_Backend_Local_Harvester::POST_TYPE === get_post_type( $object_id ) ) {
				$ckan_id = get_post_meta( $object_id, Ckan_Backend_Local_Harvester::FIELD_PREFIX . 'ckan_id', true );
				// @codingStandardsIgnoreStart
				echo '<a class="ckan-dashboard-link" href="edit.php?post_type=' . esc_attr(Ckan_Backend_Local_Harvester::POST_TYPE) . '&page=ckan-local-harvester-dashboard-page&harvester_id=' . esc_attr($ckan_id) . '""><span class="dashicons dashicons-dashboard"></span> ' . esc_html__('Dashboard', 'ogdch-backend') . '</a>';
				// @codingStandardsIgnoreEnd
			}
		}

		/**
		 * Adds ckan sync status to submitbox (Only works with WP >=4.9)
		 *
		 * @param WP_Post|null $post WP_Post object for the current post on Edit Post screen,
		 *                           null on Edit Link screen.
		 */
		public function add_ckan_sync_status_to_submitbox( $post = null ) {
			if ( is_null( $post ) ) {
				return;
			}

			$post_type = get_post_type( $post );
			$synced = false;

			$supported_post_types = array(
				Ckan_Backend_Local_Dataset::POST_TYPE,
				Ckan_Backend_Local_Group::POST_TYPE,
				Ckan_Backend_Local_Organisation::POST_TYPE,
				Ckan_Backend_Local_Harvester::POST_TYPE,
			);
			if ( ! in_array( $post_type, $supported_post_types, true ) ) {
				return;
			}

			if ( Ckan_Backend_Local_Dataset::POST_TYPE === $post_type ) {
				$synced = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'ckan_synced', true );
			} elseif( Ckan_Backend_Local_Group::POST_TYPE === $post_type ) {
				$synced = get_post_meta( $post->ID, Ckan_Backend_Local_Group::FIELD_PREFIX . 'ckan_synced', true );
			} elseif( Ckan_Backend_Local_Organisation::POST_TYPE === $post_type ) {
				$synced = get_post_meta( $post->ID, Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'ckan_synced', true );
			} elseif( Ckan_Backend_Local_Harvester::POST_TYPE === $post_type ) {
				$synced = get_post_meta( $post->ID, Ckan_Backend_Local_Harvester::FIELD_PREFIX . 'ckan_synced', true );
			}
			?>
			<div class="ckan-sync-status">
				<?php esc_html_e( 'CKAN Sync Status:', 'ogdch-backend' ) ?>
				<?php
				// If status is auto-draft the post is not saved yet.
				$post_status = get_post_status( $post->ID );
				if ( 'auto-draft' === $post_status ) {
					echo '<p class="ckan-synced warning"><span class="dashicons dashicons-edit"></span>' . esc_html__( 'Not saved yet.', 'ogdch-backend' ) . '</p>';
				} else {
					if ( $synced ) {
						echo '<p class="ckan-synced success"><span class="dashicons dashicons-yes"></span>' . esc_html__( 'All good!', 'ogdch-backend' ) . '</p>';
					} else {
						echo '<p class="ckan-synced error"><span class="dashicons dashicons-no"></span>' . esc_html__( 'Not synchronized! Please fix data and save the element again.', 'ogdch-backend' ) . '</p>';
					}
				}
				?>
			</div>
			<?php
		}

		/**
		 * Order custom post types alphabetically in admin list
		 *
		 * @param WP_Query $wp_query The current query as reference.
		 */
		public function set_post_order_in_admin( $wp_query ) {
			if ( ! is_admin() ) {
				return;
			}

			$post_types = array(
				Ckan_Backend_Local_Dataset::POST_TYPE,
				Ckan_Backend_Local_Organisation::POST_TYPE,
				Ckan_Backend_Local_Group::POST_TYPE,
				Ckan_Backend_Local_Harvester::POST_TYPE,
			);
			$current_post_type = $wp_query->get( 'post_type' );
			if ( in_array( $current_post_type, $post_types ) ) {
				if ( '' === $wp_query->get( 'orderby' ) ) {
					$wp_query->set( 'orderby', 'title' );
				}
				if ( '' === $wp_query->get( 'order' ) ) {
					$wp_query->set( 'order', 'ASC' );
				}
			}
		}

		/**
		 * Make sure all slugs are strings.
		 *
		 * CKAN currently can't handle nummeric dataset slugs
		 *
		 * @param string $title The string to be sanitized.
		 *
		 * @return string
		 */
		public function slug_must_be_string( $title ) {
			if ( is_numeric( $title ) ) {
				$title = '_' . $title;
			}
			return $title;
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
			require_once plugin_dir_path( __FILE__ ) . 'taxonomies/ckan-backend-rights.php';
			require_once plugin_dir_path( __FILE__ ) . 'taxonomies/ckan-backend-keyword.php';
			require_once plugin_dir_path( __FILE__ ) . 'taxonomies/ckan-backend-keyword-de.php';
			require_once plugin_dir_path( __FILE__ ) . 'taxonomies/ckan-backend-keyword-en.php';
			require_once plugin_dir_path( __FILE__ ) . 'taxonomies/ckan-backend-keyword-fr.php';
			require_once plugin_dir_path( __FILE__ ) . 'taxonomies/ckan-backend-keyword-it.php';
			require_once plugin_dir_path( __FILE__ ) . 'taxonomies/ckan-backend-mediatype.php';
			require_once plugin_dir_path( __FILE__ ) . 'taxonomies/ckan-backend-politicallevel.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-dataset.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-group.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-organisation.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-dataset-import.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-dataset-export.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-harvester.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-local-harvester-dashboard.php';
			require_once plugin_dir_path( __FILE__ ) . 'post-types/ckan-backend-user-admin.php';
			require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-abstract.php';
			require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-local-dataset.php';
			require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-local-group.php';
			require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-local-organisation.php';
			require_once plugin_dir_path( __FILE__ ) . 'sync/ckan-backend-sync-local-harvester.php';
		}

	}

	Ckan_Backend::initiate();
}
