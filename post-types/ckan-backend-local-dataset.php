<?php

class Ckan_Backend_Local_Dataset {

	// Be careful max. 20 characters allowed!
	const POST_TYPE = 'ckan-local-dataset';
	const FIELD_PREFIX = '_ckan_local_dataset_';

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ), 0 );
		add_action( 'cmb2_init', array( $this, 'define_fields' ) );

		// initialize local dataset sync
		$ckan_backend_sync_local_dataset = new Ckan_Backend_Sync_Local_Dataset();
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
			'capability_type'     => 'page',
		);
		register_post_type( self::POST_TYPE, $args );
	}

	public function define_fields() {
		global $language_priority;

		$cmb = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-box',
			'title'        => __( 'Ressource Data', 'ogdch' ),
			'object_types' => array( self::POST_TYPE, ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		/* CKAN Ref ID (If Set.. update.. set on first save) */
		$cmb->add_field( array(
			'name'       => __( 'CKAN Ref. ID', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'reference',
			'type'       => 'text',
			'desc'       => __( 'Ref. ID from CKAN', 'ogdch' ),
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		/* Permalink */
		$cmb->add_field( array(
			'name'       => __( 'Name (Slug)', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'name',
			'type'       => 'text',
			'desc'       => __( 'Permalink Name', 'ogdch' ),
			'attributes' => array(
				'placeholder' => 'my-dataset-about-paper',
				'readonly'    => 'readonly',
			),
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

		/* Visibility */
		$cmb->add_field( array(
			'name' => __( 'Sichtbarkeit', 'ogdch' ),
			'type' => 'title',
			'id'   => 'visibility_title',
		) );

		$cmb->add_field( array(
			'name'    => __( 'Visibility', 'ogdch' ),
			'desc'    => __( 'Select the visibility of the Dataset', 'ogdch' ),
			'id'      => self::FIELD_PREFIX . 'visibility',
			'type'    => 'radio',
			'default' => 'active',
			'options' => array(
				'active'  => __( 'Active', 'ogdch' ),
				'deleted' => __( 'Deleted', 'ogdch' ),
			),
		) );

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

		/* Resource */
		$cmb->add_field( array(
			'name' => __( 'Resources', 'ogdch' ),
			'type' => 'title',
			'id'   => 'resource_title'
		) );

		$cmb->add_field( array(
			'name' => 'Add Resource',
			'desc' => '',
			'id'   => self::FIELD_PREFIX . 'resources',
			'type' => 'file_list',
		) );


		/* Custom Fields */
		$cmb->add_field( array(
			'name' => __( 'Custom Fields', 'ogdch' ),
			'type' => 'title',
			'id'   => 'customfields_title',
		) );

		$custom_fields_id = $cmb->add_field( array(
			'id'      => self::FIELD_PREFIX . 'custom_fields',
			'type'    => 'group',
			'options' => array(
				'group_title'   => __( 'Custom Field {#}', 'ogdch' ),
				'add_button'    => __( 'Add another Field', 'ogdch' ),
				'remove_button' => __( 'Remove Field', 'ogdch' ),
			),
		) );

		$cmb->add_group_field( $custom_fields_id, array(
			'name' => __( 'Key', 'ogdch' ),
			'id'   => 'key',
			'type' => 'text',
		) );

		$cmb->add_group_field( $custom_fields_id, array(
			'name' => __( 'Value', 'ogdch' ),
			'id'   => 'value',
			'type' => 'text',
		) );

	}

}