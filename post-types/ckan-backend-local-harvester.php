<?php
/**
 * Post type ckan-local-harvester
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Local_Group
 */
class Ckan_Backend_Local_Harvester {

	// Be careful: POST_TYPE max. 20 characters allowed!
	const POST_TYPE = 'ckan-local-harvester';
	const FIELD_PREFIX = '_ckan_local_harvester_';

	/**
	 * Constructor of this class.
	 */
	public function __construct() {
		$this->register_post_type();
		add_action( 'cmb2_init', array( $this, 'define_fields' ) );

		// initialize local group sync
		new Ckan_Backend_Sync_Local_Harvester( self::POST_TYPE, self::FIELD_PREFIX );
	}

	/**
	 * Registers the post type in WordPress
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Harvesters', 'ogdch' ),
			'singular_name'      => __( 'Harvester', 'ogdch' ),
			'menu_name'          => __( 'Harvesters', 'ogdch' ),
			'name_admin_bar'     => __( 'Harvesters', 'ogdch' ),
			'all_items'          => __( 'All Harvesters', 'ogdch' ),
			'add_new_item'       => __( 'Add New Harvester', 'ogdch' ),
			'add_new'            => __( 'Add New', 'ogdch' ),
			'new_item'           => __( 'New Harvester', 'ogdch' ),
			'edit_item'          => __( 'Edit Harvester', 'ogdch' ),
			'update_item'        => __( 'Update Harvester', 'ogdch' ),
			'view_item'          => __( 'View Harvester', 'ogdch' ),
			'search_items'       => __( 'Search Harvesters', 'ogdch' ),
			'not_found'          => __( 'No Harvesters found', 'ogdch' ),
			'not_found_in_trash' => __( 'No Harvesters found in Trash', 'ogdch' ),
		);

		$args = array(
			'label'               => __( 'Harvesters', 'ogdch' ),
			'description'         => __( 'Harvesters which get synced with CKAN', 'ogdch' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 23,
			'menu_icon'           => 'dashicons-download',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'map_meta_cap'        => true,
			'capability_type'     => array( 'harvester', 'harvesters' ),
			'capabilities'        => array(
				'edit_posts'             => 'edit_harvesters',
				'edit_others_posts'      => 'edit_others_harvesters',
				'publish_posts'          => 'publish_harvesters',
				'read_private_posts'     => 'read_private_harvesters',
				'delete_posts'           => 'delete_harvesters',
				'delete_private_posts'   => 'delete_private_harvesters',
				'delete_published_posts' => 'delete_published_harvesters',
				'delete_others_posts'    => 'delete_others_harvesters',
				'edit_private_posts'     => 'edit_private_harvesters',
				'edit_published_posts'   => 'edit_published_harvesters',
				'create_posts'           => 'create_groups',
				// Meta capabilites assigned by WordPress. Do not give to any role.
				'edit_post'              => 'edit_harvester',
				'read_post'              => 'read_harvester',
				'delete_post'            => 'delete_harvester',
			),
		);
		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Add custom fields.
	 *
	 * @return void
	 */
	public function define_fields() {
		global $language_priority;

		$cmb = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-box',
			'title'        => __( 'Harvester', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		/* Harvester Information */
		$cmb->add_field( array(
			'name' => __( 'Harvester Information', 'ogdch' ),
			'type' => 'title',
			'id'   => 'harvester_information_title',
		) );

		$cmb->add_field( array(
			'name' => __( 'URL', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'url',
			'type' => 'text_url',
		) );

		$cmb->add_field( array(
			'name'       => __( 'Description', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'description',
			'type'       => 'textarea_code',
			'attributes' => array( 'rows' => 3 ),
		) );

		$cmb->add_field( array(
			'name'    => __( 'Source type', 'ogdch' ),
			'id'      => self::FIELD_PREFIX . 'source_type',
			'type'    => 'select',
			'options' => array( $this, 'get_source_type_form_field_options' ),
		) );

		$cmb->add_field( array(
			'name'             => __( 'Organisation', 'ogdch' ),
			'id'               => self::FIELD_PREFIX . 'organisation',
			'type'             => 'select',
			'options'          => array( 'Ckan_Backend_Helper', 'get_organisation_form_field_options' ),
		) );

		$cmb->add_field( array(
			'name'    => __( 'Update frequency', 'ogdch' ),
			'id'      => self::FIELD_PREFIX . 'update_frequency',
			'type'    => 'select',
			'options' => array(
				'manual'   => __( 'Manual', 'ogdch' ),
				'monthly'  => __( 'Monthly', 'ogdch' ),
				'weekly'   => __( 'Weekly', 'ogdch' ),
				'biweekly' => __( 'Biweekly', 'ogdch' ),
				'daily'    => __( 'Daily', 'ogdch' ),
				'always'   => __( 'Always', 'ogdch' ),
			),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Configuration', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'configuration',
			'type'       => 'textarea_code',
			'attributes' => array( 'rows' => 3 ),
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
			'name'       => __( 'CKAN ID', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'ckan_id',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		/* Ckan name */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'CKAN Name (Slug)', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'ckan_name',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );
	}

	/**
	 * Returns all available source types as an options array.
	 *
	 * @return array
	 */
	public static function get_source_type_form_field_options() {
		$source_type_options = array();

		$transient_name = Ckan_Backend::$plugin_slug . '_source_types';
		if ( false === ( $source_types = get_transient( $transient_name ) ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'harvesters_info_show';
			$response = Ckan_Backend_Helper::do_api_request( $endpoint );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				$source_types = $response['result'];

				// save result in transient
				set_transient( $transient_name, $source_types, 1 * HOUR_IN_SECONDS );
			} else {
				Ckan_Backend_Helper::print_error_messages( $errors );
			}
		}

		foreach( $source_types as $source_type ) {
			$source_type_options[ $source_type['name'] ] = $source_type['title'];
		}

		return $source_type_options;
	}
}
