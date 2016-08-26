<?php
/**
 * Post type ckan-local-org
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Local_Organisation
 */
class Ckan_Backend_Local_Organisation {

	// Be careful max. 20 characters allowed!
	const POST_TYPE = 'ckan-local-org';
	const FIELD_PREFIX = '_ckan_local_org_';

	/**
	 * Constructor of this class
	 */
	public function __construct() {
		$this->register_post_type();
		add_action( 'cmb2_init', array( $this, 'define_fields' ) );

		// render additional field after main cmb2 form is rendered
		add_action( 'cmb2_after_post_form_' . self::POST_TYPE . '-box', array( $this, 'render_addition_fields' ) );

		// initialize local organisation sync
		new Ckan_Backend_Sync_Local_Organisation( self::POST_TYPE, self::FIELD_PREFIX );
	}

	/**
	 * Renders additional fields which aren't saved in database.
	 */
	public function render_addition_fields() {
		// Field shows that the metadata is not yet saved in database -> get values from $_POST array
		echo '<input type="hidden" id="metadata_not_in_db" name="metadata_not_in_db" value="1" />';
	}

	/**
	 * Registers a new post type
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Organizations', 'ogdch' ),
			'singular_name'      => __( 'Organization', 'ogdch' ),
			'menu_name'          => __( 'Organizations', 'ogdch' ),
			'name_admin_bar'     => __( 'Organizations', 'ogdch' ),
			'all_items'          => __( 'All Organizations', 'ogdch' ),
			'add_new_item'       => __( 'Add New Organization', 'ogdch' ),
			'add_new'            => __( 'Add New', 'ogdch' ),
			'new_item'           => __( 'New Organization', 'ogdch' ),
			'edit_item'          => __( 'Edit Organization', 'ogdch' ),
			'update_item'        => __( 'Update Organization', 'ogdch' ),
			'view_item'          => __( 'View Organization', 'ogdch' ),
			'search_items'       => __( 'Search Organizations', 'ogdch' ),
			'not_found'          => __( 'No Organizations found', 'ogdch' ),
			'not_found_in_trash' => __( 'No Organizations found in Trash', 'ogdch' ),
		);

		$args = array(
			'label'               => __( 'Organizations', 'ogdch' ),
			'description'         => __( 'Organizations which get synced with CKAN', 'ogdch' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 22,
			'menu_icon'           => 'dashicons-building',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => array( 'slug' => 'organization' ),
			'map_meta_cap'        => true,
			'capability_type'     => array( 'organisation', 'organisations' ),
			'capabilities'        => array(
				'edit_posts'             => 'edit_organisations',
				'edit_others_posts'      => 'edit_others_organisations',
				'publish_posts'          => 'publish_organisations',
				'read_private_posts'     => 'read_private_organisations',
				'delete_posts'           => 'delete_organisations',
				'delete_private_posts'   => 'delete_private_organisations',
				'delete_published_posts' => 'delete_published_organisations',
				'delete_others_posts'    => 'delete_others_organisations',
				'edit_private_posts'     => 'edit_private_organisations',
				'edit_published_posts'   => 'edit_published_organisations',
				'create_posts'           => 'create_organisations',
				// Meta capabilites assigned by WordPress. Do not give to any role.
				'edit_post'              => 'edit_organisation',
				'read_post'              => 'read_organisation',
				'delete_post'            => 'delete_organisation',
			),
		);
		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Define all custom fields of this post type
	 *
	 * @return void
	 */
	public function define_fields() {
		global $language_priority;

		$cmb = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-box',
			'title'        => __( 'Organization', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		/* Organization Information */
		$cmb->add_field( array(
			'name' => __( 'Organization Information', 'ogdch' ),
			'type' => 'title',
			'id'   => 'organization_information_title',
		) );

		foreach ( $language_priority as $lang ) {
			$cmb->add_field( array(
				'name'       => __( 'Title', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => self::FIELD_PREFIX . 'title_' . $lang,
				'type'       => 'text',
			) );

			$cmb->add_field( array(
				'name'       => __( 'Description', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => self::FIELD_PREFIX . 'description_' . $lang,
				'type'       => 'textarea_code',
				'attributes' => array( 'rows' => 3 ),
			) );
		}

		if ( current_user_can( 'edit_data_of_all_organisations' ) ) {
			/* Parent */
			$cmb->add_field( array(
				'name'             => __( 'Parent Organization', 'ogdch' ),
				'id'               => self::FIELD_PREFIX . 'parent',
				'type'             => 'select',
				'show_option_none' => __( 'None - top level', 'ogdch' ),
				'options'          => array( $this, 'get_parent_options' ),
			) );
		}

		/* Type */
		$cmb->add_field( array(
			'name' => __( 'Federal level', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'federal_level',
			'type' => 'select',
			'show_option_none' => false,
			'options' => array( 'Ckan_Backend_FederalLevel', 'get_federal_levels' ),
		) );

		/* URL */
		$cmb->add_field( array(
			'name' => __( 'URL', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'url',
			'type' => 'text_url',
		) );

		/* Image */
		$cmb->add_field( array(
			'name' => __( 'Image', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'image',
			'type' => 'file',
		) );

		$cmb_side_ckan = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox',
			'title'        => __( 'CKAN Data', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		/* Ckan id (If Set -> update. Set on first save) */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'ID', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'ckan_id',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		/* Ckan name */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'Name (Slug)', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'ckan_name',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		$cmb_side_ckan->add_field( array(
			'name' => __( 'Sync Status', 'ogdch' ),
			'type' => 'ckan_synced',
			'id'   => self::FIELD_PREFIX . 'ckan_synced',
		) );
	}

	/**
	 * Gets all possible parent organisations from CKAN and returns them in an array.
	 *
	 * @return array All possbile parent organisations
	 */
	public function get_parent_options() {
		$organisations = Ckan_Backend_Helper::get_organisation_form_field_options();
		// remove current organisation from result (current organisation can't be its on parent)
		if ( isset( $_GET['post'] ) ) {
			$current_organisation_name = get_post_meta( $_GET['post'], self::FIELD_PREFIX . 'ckan_name', true );
			if ( array_key_exists( $current_organisation_name, $organisations ) ) {
				unset( $organisations[ $current_organisation_name ] );
			}
		}

		return $organisations;
	}
}
