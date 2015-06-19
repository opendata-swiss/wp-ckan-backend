<?php

class Ckan_Backend_Local_Group {

	// Be careful max. 20 characters allowed!
	const POST_TYPE = 'ckan-local-group';
	const FIELD_PREFIX = '_ckan_local_group_';

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ), 0 );
		add_action( 'cmb2_init', array( $this, 'define_fields' ) );

		// initialize local group sync
		$ckan_backend_sync_local_group = new Ckan_Backend_Sync_Local_Group();
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( 'CKAN local Groups', 'ogdch' ),
			'singular_name'      => __( 'CKAN local Group', 'ogdch' ),
			'menu_name'          => __( 'CKAN local Group', 'ogdch' ),
			'name_admin_bar'     => __( 'CKAN local Group', 'ogdch' ),
			'parent_item_colon'  => __( 'Parent Group:', 'ogdch' ),
			'all_items'          => __( 'All local Groups', 'ogdch' ),
			'add_new_item'       => __( 'Add New Group', 'ogdch' ),
			'add_new'            => __( 'Add New', 'ogdch' ),
			'new_item'           => __( 'New local Group', 'ogdch' ),
			'edit_item'          => __( 'Edit local Group', 'ogdch' ),
			'update_item'        => __( 'Update local Group', 'ogdch' ),
			'view_item'          => __( 'View Group', 'ogdch' ),
			'search_items'       => __( 'Search Group', 'ogdch' ),
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
				'placeholder' => 'my-group',
				'readonly'    => 'readonly',
			),
		) );

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

		/* Title */
		$cmb->add_field( array(
			'name' => __( 'Group Title', 'ogdch' ),
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
			'name' => __( 'Group Description', 'ogdch' ),
			'type' => 'title',
			'id'   => 'description_title',
			'desc' => __( 'Markdown Syntax can be used to format the description.', 'ogdch' ),
		) );

		foreach ( $language_priority as $lang ) {
			$cmb->add_field( array(
				'name'       => __( 'Description', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => self::FIELD_PREFIX . 'description_' . $lang,
				'type'       => 'textarea',
				'attributes' => array( 'rows' => 3 ),
			) );
		}

		/* Image */
		$cmb->add_field( array(
			'name' => __( 'Group Image', 'ogdch' ),
			'type' => 'title',
			'id'   => 'image_title',
		) );

		$cmb->add_field( array(
			'name'       => __( 'Image', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'image',
			'type'    => 'file'
		) );
	}

}