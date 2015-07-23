<?php

class Ckan_Backend_Local_Dataset {

	// Be careful max. 20 characters allowed!
	const POST_TYPE = 'ckan-local-dataset';
	const FIELD_PREFIX = '_ckan_local_dataset_';

	public function __construct() {
		$this->register_post_type();
		add_action( 'cmb2_init', array( $this, 'define_fields' ) );

		// initialize local dataset sync
		$ckan_backend_sync_local_dataset = new Ckan_Backend_Sync_Local_Dataset( self::POST_TYPE, self::FIELD_PREFIX );
	}

	public function show_message_if_disabled( $field_args, $field ) {
		if ( isset( $_GET['post'] ) ) {
			$post_id = $_GET['post'];
		} elseif ( isset( $_POST['post_ID'] ) ) {
			$post_id = $_POST['post_ID'];
		}

		// see if dataset is disabled
		$value = get_post_meta( $post_id, self::FIELD_PREFIX . 'disabled', true );
		if($value == 'on') {
			echo '<div class="error"><p>' . __( 'This dataset is disabled. Please contact an adimistrator if this seems to be wrong.', 'ogdch' ) . '</p></div>';
		}
	}

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
			'map_meta_cap' => true,
			'capability_type' => 'dataset',
			'capabilities' => array(
				'edit_posts' => 'edit_datasets',
				'edit_others_posts' => 'edit_others_datasets',
				'publish_posts' => 'publish_datasets',
				'read_private_posts' => 'read_private_datasets',
				'delete_posts' => 'delete_datasets',
				'delete_private_posts' => 'delete_private_datasets',
				'delete_published_posts' => 'delete_published_datasets',
				'delete_others_posts' => 'delete_others_datasets',
				'edit_private_posts' => 'edit_private_datasets',
				'edit_published_posts' => 'edit_published_datasets',
				'create_posts' => 'create_datasets',
				// Meta capabilites assigned by WordPress. Do not give to any role.
				'edit_post' => 'edit_dataset',
				'read_post' => 'read_dataset',
				'delete_post' => 'delete_dataset',
			),
		);
		register_post_type( self::POST_TYPE, $args );
	}

	public function define_fields() {
		global $language_priority;

		/* CMB Mainbox */
		$cmb = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-box',
			'title'        => __( 'Ressource Data', 'ogdch' ),
			'object_types' => array( self::POST_TYPE, ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		/* Title */
		$cmb->add_field( array(
			'name' => __( 'Dataset Title', 'ogdch' ),
			'type' => 'title',
			'id'   => 'title_title'
		) );

		foreach ( $language_priority as $lang ) {
			$cmb->add_field( array(
				'name'       => __( 'Title', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => self::FIELD_PREFIX . 'name_' . $lang,
				'type'       => 'text',
				'attributes' => array(
					'placeholder' => __( 'e.g. Awesome dataset', 'ogdch' )
				)
			) );
		}

		/* Description */
		$cmb->add_field( array(
			'name' => __( 'Dataset Description', 'ogdch' ),
			'type' => 'title',
			'id'   => 'description_title',
			'desc' => __( 'Markdown Syntax can be used to format the description.', 'ogdch' ),
		) );

		foreach ( $language_priority as $lang ) {
			$cmb->add_field( array(
				'name'       => 'Description (' . strtoupper( $lang ) . ')',
				'id'         => self::FIELD_PREFIX . 'description_' . $lang,
				'type'       => 'textarea',
				'attributes' => array( 'rows' => 3 ),
			) );
		}

		/* Source */
		$cmb->add_field( array(
			'name' => __( 'Source', 'ogdch' ),
			'type' => 'title',
			'id'   => 'source_title',
		) );

		$cmb->add_field( array(
			'name'       => __( 'Source', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'source',
			'type'       => 'text',
			'attributes' => array(
				'placeholder' => 'http://example.com/dataset.json',
			),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Version', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'version',
			'type'       => 'text',
			'attributes' => array(
				'placeholder' => '1.0'
			)
		) );


		/* Author */
		$cmb->add_field( array(
			'name' => __( 'Dataset Author', 'ogdch' ),
			'type' => 'title',
			'id'   => 'author_title'
		) );

		$cmb->add_field( array(
			'name'       => __( 'Author Name', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'author',
			'type'       => 'text',
			'attributes' => array(
				'placeholder' => __( 'Hans Musterman', 'ogdch' ),
			)
		) );

		$cmb->add_field( array(
			'name'       => __( 'Author Email', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'author_email',
			'type'       => 'text',
			'attributes' => array(
				'placeholder' => __( 'hans@musterman.ch', 'ogdch' ),
			)
		) );

		/* Maintainer */
		$cmb->add_field( array(
			'name' => __( 'Dataset Maintainer', 'ogdch' ),
			'type' => 'title',
			'id'   => 'maintainer_title'
		) );

		$cmb->add_field( array(
			'name'       => __( 'Maintainer Name', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'maintainer',
			'type'       => 'text',
			'attributes' => array(
				'placeholder' => __( 'Peter Müller', 'ogdch' ),
			)
		) );

		$cmb->add_field( array(
			'name'       => __( 'Maintainer Email', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'maintainer_email',
			'type'       => 'text',
			'attributes' => array(
				'placeholder' => __( 'peter@mueller.ch', 'ogdch' ),
			)
		) );

		/* Organisation */
		$cmb->add_field( array(
			'name' => __( 'Organisation', 'ogdch' ),
			'type' => 'title',
			'id'   => 'organisation_title',
		) );

		$cmb->add_field( array(
			'name'             => __( 'Organisation', 'ogdch' ),
			'id'               => self::FIELD_PREFIX . 'organisation',
			'type'             => 'select',
			'show_option_none' => __( 'No Organisation', 'ogdch' ),
			'options'          => array( $this, 'get_organisation_options' ),
		) );

		/* Groups */
		$cmb->add_field( array(
			'name' => __( 'Groups', 'ogdch' ),
			'type' => 'title',
			'id'   => 'groups_title',
		) );

		$cmb->add_field( array(
			'name'              => __( 'Groups', 'ogdch' ),
			'id'                => self::FIELD_PREFIX . 'groups',
			'type'              => 'multicheck',
			'select_all_button' => false,
			'options'           => array( $this, 'get_group_options' ),
		) );

		/* Resources */
		$cmb->add_field( array(
			'name' => __( 'Resources', 'ogdch' ),
			'type' => 'title',
			'id'   => 'resource_title'
		) );

		$resources_group = $cmb->add_field( array(
			'id'      => self::FIELD_PREFIX . 'resources',
			'type'    => 'group',
			'options' => array(
				'group_title'   => __( 'Resource {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Resource', 'ogdch' ),
				'remove_button' => __( 'Remove Resource', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $resources_group, array(
			'name' => __( 'Resource URL', 'ogdch' ),
			'id'   => 'url',
			'type' => 'text_url',
		) );

		$cmb->add_group_field( $resources_group, array(
			'name' => __( 'Title', 'ogdch' ),
			'id'   => 'title',
			'type' => 'text',
		) );

		foreach ( $language_priority as $lang ) {
			$cmb->add_group_field( $resources_group, array(
				'name'       => __( 'Description', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => 'description_' . $lang,
				'type' => 'text',
			) );
		}


		/* Custom Fields */
		$cmb->add_field( array(
			'name' => __( 'Custom Fields', 'ogdch' ),
			'type' => 'title',
			'id'   => 'customfields_title',
		) );

		$custom_fields_group = $cmb->add_field( array(
			'id'      => self::FIELD_PREFIX . 'custom_fields',
			'type'    => 'group',
			'options' => array(
				'group_title'   => __( 'Custom Field {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Field', 'ogdch' ),
				'remove_button' => __( 'Remove Field', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $custom_fields_group, array(
			'name' => __( 'Key', 'ogdch' ),
			'id'   => 'key',
			'type' => 'text',
		) );

		$cmb->add_group_field( $custom_fields_group, array(
			'name' => __( 'Value', 'ogdch' ),
			'id'   => 'value',
			'type' => 'text',
		) );

		/* CMB Sidebox */
		$cmb_side = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox',
			'title'        => __( 'CKAN Data', 'ogdch' ),
			'object_types' => array( self::POST_TYPE, ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		/* CKAN Ref ID (If Set.. update.. set on first save) */
		$cmb_side->add_field( array(
			'name'       => __( 'Reference ID', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'reference',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		/* Permalink */
		$cmb_side->add_field( array(
			'name'       => __( 'Name (Slug)', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'name',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		/* CMB Sidebox to disable dataset */
		$cmb_side_disabled = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox-disabled',
			'title'        => __( 'Disable Dataset', 'ogdch' ),
			'object_types' => array( self::POST_TYPE, ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		$disabled_checkbox_args = array(
			'desc' => __( 'Disable Dataset', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'disabled',
			'type' => 'checkbox',
			'before_row'   => array( $this, 'show_message_if_disabled' ),
		);
		if( ! current_user_can( 'disable_datasets' )) {
			$disabled_checkbox_args['attributes'] = array(
				'disabled' => 'disabled'
			);
		}
		$cmb_side_disabled->add_field( $disabled_checkbox_args );

	}

	public function get_group_options() {
		return Ckan_Backend_Helper::get_form_field_options( 'group' );
	}

	public function get_organisation_options() {
		return Ckan_Backend_Helper::get_form_field_options( 'organization' );
	}
}