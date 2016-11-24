<?php
/**
 * Post type ckan-local-group
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Local_Group
 */
class Ckan_Backend_Local_Group {

	// Be careful max. 20 characters allowed!
	const POST_TYPE = 'ckan-local-group';
	const FIELD_PREFIX = '_ckan_local_group_';

	/**
	 * Constructor of this class.
	 */
	public function __construct() {
		$this->register_post_type();
		add_action( 'cmb2_init', array( $this, 'define_fields' ) );

		// render additional field after main cmb2 form is rendered
		add_action( 'cmb2_after_post_form_' . self::POST_TYPE . '-box', array( $this, 'render_addition_fields' ) );

		// initialize local group sync
		new Ckan_Backend_Sync_Local_Group( self::POST_TYPE, self::FIELD_PREFIX );
	}

	/**
	 * Renders additional fields which aren't saved in database.
	 */
	public function render_addition_fields() {
		// Field shows that the metadata is not yet saved in database -> get values from $_POST array
		echo '<input type="hidden" id="metadata_not_in_db" name="metadata_not_in_db" value="1" />';
	}

	/**
	 * Registers the post type in WordPress
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Categories', 'ogdch-backend' ),
			'singular_name'      => __( 'Category', 'ogdch-backend' ),
			'menu_name'          => __( 'Categories', 'ogdch-backend' ),
			'name_admin_bar'     => __( 'Categories', 'ogdch-backend' ),
			'all_items'          => __( 'All Categories', 'ogdch-backend' ),
			'add_new_item'       => __( 'Add New Category', 'ogdch-backend' ),
			'add_new'            => __( 'Add New', 'ogdch-backend' ),
			'new_item'           => __( 'New Category', 'ogdch-backend' ),
			'edit_item'          => __( 'Edit Category', 'ogdch-backend' ),
			'update_item'        => __( 'Update Category', 'ogdch-backend' ),
			'view_item'          => __( 'View Category', 'ogdch-backend' ),
			'search_items'       => __( 'Search Categories', 'ogdch-backend' ),
			'not_found'          => __( 'No Categories found', 'ogdch-backend' ),
			'not_found_in_trash' => __( 'No Categories found in Trash', 'ogdch-backend' ),
		);

		$args = array(
			'label'               => __( 'Categories', 'ogdch-backend' ),
			'description'         => __( 'Categories which get synced with CKAN', 'ogdch-backend' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 21,
			'menu_icon'           => 'dashicons-category',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => array( 'slug' => 'group' ),
			'map_meta_cap'        => true,
			'capability_type'     => array( 'group', 'groups' ),
			'capabilities'        => array(
				'edit_posts'             => 'edit_groups',
				'edit_others_posts'      => 'edit_others_groups',
				'publish_posts'          => 'publish_groups',
				'read_private_posts'     => 'read_private_groups',
				'delete_posts'           => 'delete_groups',
				'delete_private_posts'   => 'delete_private_groups',
				'delete_published_posts' => 'delete_published_groups',
				'delete_others_posts'    => 'delete_others_groups',
				'edit_private_posts'     => 'edit_private_groups',
				'edit_published_posts'   => 'edit_published_groups',
				'create_posts'           => 'create_groups',
				// Meta capabilites assigned by WordPress. Do not give to any role.
				'edit_post'              => 'edit_group',
				'read_post'              => 'read_group',
				'delete_post'            => 'delete_group',
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
			'title'        => __( 'Category', 'ogdch-backend' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		/* Category Information */
		$cmb->add_field( array(
			'name' => __( 'Category Information', 'ogdch-backend' ),
			'type' => 'title',
			'id'   => 'category_information_title',
		) );

		foreach ( $language_priority as $lang ) {
			$cmb->add_field( array(
				'name'       => __( 'Title', 'ogdch-backend' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => self::FIELD_PREFIX . 'title_' . $lang,
				'type'       => 'text',
			) );

			$cmb->add_field( array(
				'name'       => __( 'Description', 'ogdch-backend' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => self::FIELD_PREFIX . 'description_' . $lang,
				'type'       => 'textarea_code',
				'attributes' => array( 'rows' => 3 ),
			) );
		}

		/* Image */
		$cmb->add_field( array(
			'name'       => __( 'Image', 'ogdch-backend' ),
			'id'         => self::FIELD_PREFIX . 'image',
			'type'    => 'file',
		) );

		$cmb_side_ckan = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox',
			'title'        => __( 'CKAN Data', 'ogdch-backend' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		/* Ckan id (If Set -> update. Set on first save) */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'ID', 'ogdch-backend' ),
			'id'         => self::FIELD_PREFIX . 'ckan_id',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		/* Ckan name */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'Name (Slug)', 'ogdch-backend' ),
			'id'         => self::FIELD_PREFIX . 'ckan_name',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		$cmb_side_ckan->add_field( array(
			'name' => __( 'Sync Status', 'ogdch-backend' ),
			'type' => 'ckan_synced',
			'id'   => self::FIELD_PREFIX . 'ckan_synced',
		) );

		/* CMB Sidebox for RDF Reference */
		$cmb_side_ckan = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox-rdf',
			'title'        => __( 'RDF Reference', 'ogdch-backend' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		/* RDF URI */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'RDF URI', 'ogdch-backend' ),
			'id'         => self::FIELD_PREFIX . 'rdf_uri',
			'type'       => 'text',
		) );
	}
}
