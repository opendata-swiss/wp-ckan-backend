<?php
/**
 * Post type ckan-local-dataset
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Local_Dataset
 */
class Ckan_Backend_Local_Dataset {

	// Be careful max. 20 characters allowed!
	const POST_TYPE = 'ckan-local-dataset';
	const FIELD_PREFIX = '_ckan_local_dataset_';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_post_type();
		add_action( 'cmb2_init', array( $this, 'define_fields' ) );

		// initialize local dataset sync
		new Ckan_Backend_Sync_Local_Dataset( self::POST_TYPE, self::FIELD_PREFIX );
	}

	/**
	 * Shows an error message if the dataset is disbaled
	 *
	 * This function is a callback function for CMB2
	 *
	 * @param array  $field_args Array of field arguments.
	 * @param object $field CMB field.
	 *
	 * @return void
	 */
	public function show_message_if_disabled( $field_args, $field ) {
		$post_id = 0;
		if ( isset( $_GET['post'] ) ) {
			$post_id = $_GET['post'];
		} elseif ( isset( $_POST['post_ID'] ) ) {
			$post_id = $_POST['post_ID'];
		}

		// see if dataset is disabled
		$value = get_post_meta( $post_id, self::FIELD_PREFIX . 'disabled', true );
		if ( 'on' === $value ) {
			// @codingStandardsIgnoreStart
			echo '<div class="error"><p>' . __( 'This dataset is disabled. Please contact an adimistrator if this seems to be wrong.', 'ogdch' ) . '</p></div>';
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Registers the post type.
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'CKAN local Datasets', 'ogdch' ),
			'singular_name'      => __( 'CKAN local Dataset', 'ogdch' ),
			'menu_name'          => __( 'CKAN local Data', 'ogdch' ),
			'name_admin_bar'     => __( 'CKAN local Data', 'ogdch' ),
			'parent_item_colon'  => __( 'Parent Dataset:', 'ogdch' ),
			'all_items'          => __( 'All local Datasets', 'ogdch' ),
			'add_new_item'       => __( 'Add New Dataset', 'ogdch' ),
			'add_new'            => __( 'Add New', 'ogdch' ),
			'new_item'           => __( 'New local Dataset', 'ogdch' ),
			'edit_item'          => __( 'Edit local Dataset', 'ogdch' ),
			'update_item'        => __( 'Update local Dataset', 'ogdch' ),
			'view_item'          => __( 'View Dataset', 'ogdch' ),
			'search_items'       => __( 'Search Dataset', 'ogdch' ),
			'not_found'          => __( 'Not found', 'ogdch' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'ogdch' ),
		);

		$args = array(
			'label'               => __( 'CKAN', 'ogdch' ),
			'description'         => __( 'Contains Data from the CKAN Instance', 'ogdch' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'taxonomies'          => array( 'post_tag' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-category',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => false,
			'map_meta_cap'        => true,
			'capability_type'     => 'dataset',
			'capabilities'        => array(
				'edit_posts'             => 'edit_datasets',
				'edit_others_posts'      => 'edit_others_datasets',
				'publish_posts'          => 'publish_datasets',
				'read_private_posts'     => 'read_private_datasets',
				'delete_posts'           => 'delete_datasets',
				'delete_private_posts'   => 'delete_private_datasets',
				'delete_published_posts' => 'delete_published_datasets',
				'delete_others_posts'    => 'delete_others_datasets',
				'edit_private_posts'     => 'edit_private_datasets',
				'edit_published_posts'   => 'edit_published_datasets',
				'create_posts'           => 'create_datasets',
				// Meta capabilites assigned by WordPress. Do not give to any role.
				'edit_post'              => 'edit_dataset',
				'read_post'              => 'read_dataset',
				'delete_post'            => 'delete_dataset',
			),
		);
		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Define the custom fields of this post type
	 *
	 * @return void
	 */
	public function define_fields() {
		global $language_priority;

		/* CMB Mainbox */
		$cmb = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-box',
			'title'        => __( 'Ressource Data', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		$cmb->add_field( array(
			'name'              => __( 'Language', 'ogdch' ),
			'id'                => self::FIELD_PREFIX . 'languages',
			'type'              => 'multicheck_inline',
			'select_all_button' => false,
			'options'           => array(
				'en' => __( 'English', 'ogdch' ),
				'de' => __( 'German', 'ogdch' ),
				'fr' => __( 'French', 'ogdch' ),
				'it' => __( 'Italian', 'ogdch' ),
			),
		) );

		/* Title */
		$cmb->add_field( array(
			'name' => __( 'Dataset Information', 'ogdch' ),
			'type' => 'title',
			'id'   => 'title_title',
		) );

		foreach ( $language_priority as $lang ) {
			/* Title */
			$cmb->add_field( array(
				'name'       => __( 'Title', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => self::FIELD_PREFIX . 'title_' . $lang,
				'type'       => 'text',
				'attributes' => array(
					'placeholder' => __( 'e.g. Awesome dataset', 'ogdch' ),
				),
			) );

			/* Description */
			$cmb->add_field( array(
				'name'       => 'Description (' . strtoupper( $lang ) . ')',
				'id'         => self::FIELD_PREFIX . 'description_' . $lang,
				'type'       => 'textarea',
				'attributes' => array( 'rows' => 3 ),
			) );
		}

		/* Dates */
		$cmb->add_field( array(
			'name' => __( 'Dates', 'ogdch' ),
			'type' => 'title',
			'id'   => 'dates_title',
		) );

		$cmb->add_field( array(
			'name' => __( 'Issued', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'issued',
			'desc' => __( 'Date and time when dataset was issued.', 'ogdch' ),
			'type' => 'text_datetime_timestamp',
		) );

		$cmb->add_field( array(
			'name' => __( 'Modified', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'modified',
			'desc' => __( 'Date and time when dataset was last modified.', 'ogdch' ),
			'type' => 'text_datetime_timestamp',
		) );

		/* Publisher */
		$cmb->add_field( array(
			'name' => __( 'Publisher', 'ogdch' ),
			'type' => 'title',
			'id'   => 'publisher_title',
		) );

		$cmb->add_field( array(
			'name'             => __( 'Publisher', 'ogdch' ),
			'id'               => self::FIELD_PREFIX . 'publisher',
			'type'             => 'select',
			'show_option_none' => __( 'Not defined', 'ogdch' ),
			'options'          => array( 'Ckan_Backend_Helper', 'get_organisation_form_field_options' ),
		) );

		$contact_points_group = $cmb->add_field( array(
			'id'      => self::FIELD_PREFIX . 'contact_points',
			'type'    => 'group',
			'options' => array(
				'group_title'   => __( 'Contact Point {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Contact Point', 'ogdch' ),
				'remove_button' => __( 'Remove Contact Point', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $contact_points_group, array(
			'name' => __( 'Name', 'ogdch' ),
			'id'   => 'name',
			'type' => 'text',
		) );

		$cmb->add_group_field( $contact_points_group, array(
			'name' => __( 'Email', 'ogdch' ),
			'id'   => 'email',
			'type' => 'text_email',
		) );

		$cmb->add_field( array(
			'name' => __( 'Other', 'ogdch' ),
			'type' => 'title',
			'id'   => 'other_title',
		) );

		/* Theme */
		$cmb->add_field( array(
			'name'              => __( 'Theme', 'ogdch' ),
			'id'                => self::FIELD_PREFIX . 'themes',
			'type'              => 'multicheck',
			'select_all_button' => false,
			'options'           => array( 'Ckan_Backend_Helper', 'get_group_form_field_options' ),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Landing Page', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'landing_page',
			'type'       => 'text_url',
			'attributes' => array(
				'placeholder' => 'http://example.com/',
			),
		) );

		$cmb->add_field( array(
			'name' => __( 'Relation', 'ogdch' ),
			'type' => 'title',
			'id'   => 'relation_title',
		) );

		$relations_group = $cmb->add_field( array(
			'id'      => self::FIELD_PREFIX . 'relations',
			'type'    => 'group',
			'options' => array(
				'group_title'   => __( 'Relation {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Relation', 'ogdch' ),
				'remove_button' => __( 'Remove Relation', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $relations_group, array(
			'name' => __( 'URL', 'ogdch' ),
			'id'   => 'url',
			'type' => 'text_url',
		) );

		$cmb->add_group_field( $relations_group, array(
			'name' => __( 'Label', 'ogdch' ),
			'id'   => 'label',
			'type' => 'text',
		) );

		$cmb->add_field( array(
			'name'       => __( 'Spatial', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'spatial',
			'type'       => 'text',
			'attributes' => array(
				'placeholder' => __( 'Geographical assignment of this dataset', 'ogdch' ),
			),
		) );

		$cmb->add_field( array(
			'name' => __( 'Coverage', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'coverage',
			'type' => 'text',
		) );

		$cmb->add_field( array(
			'name' => __( 'Accrual Periodicy', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'accrual_periodicy',
			'type' => 'text',
		) );

		$temporals_group = $cmb->add_field( array(
			'id'      => self::FIELD_PREFIX . 'temporals',
			'type'    => 'group',
			'options' => array(
				'group_title'   => __( 'Temporal {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Temporal', 'ogdch' ),
				'remove_button' => __( 'Remove Temporal', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $temporals_group, array(
			'name' => __( 'Start Date', 'ogdch' ),
			'id'   => 'start_date',
			'type' => 'text_date_timestamp',
		) );

		$cmb->add_group_field( $temporals_group, array(
			'name' => __( 'End Date', 'ogdch' ),
			'id'   => 'end_date',
			'type' => 'text_date_timestamp',
		) );

		$see_alsos_group = $cmb->add_field( array(
			'id'      => self::FIELD_PREFIX . 'see_alsos',
			'type'    => 'group',
			'options' => array(
				'group_title'   => __( 'See Also {#}', 'ogdch' ),
				'add_button'    => __( 'Add another See Also', 'ogdch' ),
				'remove_button' => __( 'Remove See Also', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $see_alsos_group, array(
			'name' => __( 'URL', 'ogdch' ),
			'id'   => 'url',
			'type' => 'text_url',
		) );

		/* Resources */
		$cmb->add_field( array(
			'name' => __( 'Distributions', 'ogdch' ),
			'type' => 'title',
			'id'   => 'distributions_title',
		) );

		$distributions_group = $cmb->add_field( array(
			'id'      => self::FIELD_PREFIX . 'distributions',
			'type'    => 'group',
			'options' => array(
				'group_title'   => __( 'Distribution {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Distribution', 'ogdch' ),
				'remove_button' => __( 'Remove Distribution', 'ogdch' ),
			),
		) );

		foreach ( $language_priority as $lang ) {
			$cmb->add_group_field( $distributions_group, array(
				'name' => __( 'Title', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'   => 'title_' . $lang,
				'type' => 'text',
			) );

			$cmb->add_group_field( $distributions_group, array(
				'name'       => __( 'Description', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => 'description_' . $lang,
				'type'       => 'textarea',
				'attributes' => array( 'rows' => 3 ),
			) );
		}

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Issued', 'ogdch' ),
			'id'   => 'issued',
			'desc' => __( 'Date and time when dataset was issued.', 'ogdch' ),
			'type' => 'text_datetime_timestamp',
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Modified', 'ogdch' ),
			'id'   => 'modified',
			'desc' => __( 'Date and time when dataset was last modified.', 'ogdch' ),
			'type' => 'text_datetime_timestamp',
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name'    => __( 'Quellenangabe', 'ogdch' ),
			'id'      => 'right_reference',
			'type'    => 'radio',
			'options' => array(
				'reference_required'     => __( 'Erforderlich', 'ogdch' ),
				'reference_not-required' => __( 'Nicht erforderlich', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name'    => __( 'Nicht kommerzielle Nutzung', 'ogdch' ),
			'id'      => 'right_non_commercial',
			'type'    => 'radio',
			'options' => array(
				'non-commercial_allowed'     => __( 'Erlaubt', 'ogdch' ),
				'non-commercial_not-allowed' => __( 'Nicht erlaubt', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name'    => __( 'Kommerzielle Nutzung', 'ogdch' ),
			'id'      => 'right_commercial',
			'type'    => 'radio',
			'options' => array(
				'commercial_allowed'            => __( 'Erlaubt', 'ogdch' ),
				'commercial_not-allowed'        => __( 'Nicht erlaubt', 'ogdch' ),
				'commercial_with-approval-only' => __( 'Nur mit Bewilligung', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Access URL', 'ogdch' ),
			'id'   => 'access_url',
			'type' => 'text_url',
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Download URL', 'ogdch' ),
			'id'   => 'download_url',
			'type' => 'text_url',
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Bytesize', 'ogdch' ),
			'id'   => 'byte_size',
			'type' => 'text',
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Mediatype', 'ogdch' ),
			'id'   => 'media_type',
			'type' => 'text',
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Format', 'ogdch' ),
			'id'   => 'format',
			'type' => 'text',
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Coverage', 'ogdch' ),
			'id'   => 'coverage',
			'type' => 'text',
		) );

		$cmb->add_group_field( $distributions_group, array(
			'name' => __( 'Identifier', 'ogdch' ),
			'id'   => 'identifier',
			'type' => 'text',
		) );

		/* CMB Sidebox to disable dataset */
		$cmb_side_disabled = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox-disabled',
			'title'        => __( 'Disable Dataset', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		$disabled_checkbox_args = array(
			'desc'       => __( 'Disable Dataset', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'disabled',
			'type'       => 'checkbox',
			'before_row' => array( $this, 'show_message_if_disabled' ),
		);
		if ( ! current_user_can( 'disable_datasets' ) ) {
			$disabled_checkbox_args['attributes'] = array(
				'disabled' => 'disabled',
			);
		}
		$cmb_side_disabled->add_field( $disabled_checkbox_args );

		/* CMB Sidebox for CKAN data */
		$cmb_side_ckan = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox-ckan',
			'title'        => __( 'CKAN Data', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		/* CKAN Ref ID (If Set.. update.. set on first save) */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'Reference ID', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'reference',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		/* Permalink */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'Name (Slug)', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'name',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		/* CMB Sidebox for other data */
		$cmb_side_other = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox-other',
			'title'        => __( 'Other Data', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		$cmb_side_other->add_field( array(
			'name' => __( 'Identifier', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'identifier',
			'type' => 'text',
		) );

	}
}
