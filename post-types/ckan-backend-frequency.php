<?php
/**
 * Post type ckan-frequency
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Frequency
 */
class Ckan_Backend_Frequency {

	// Be careful max. 20 characters allowed!
	const POST_TYPE    = 'ckan-frequency';
	const FIELD_PREFIX = '_ckan_frequency_';

	/**
	 * Constructor of this class.
	 */
	public function __construct() {
		$this->register_post_type();
		add_action( 'cmb2_init', array( $this, 'define_fields' ) );
	}

	/**
	 * Registers the post type in WordPress
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Frequencies', 'ogdch' ),
			'singular_name'      => __( 'Frequency', 'ogdch' ),
			'menu_name'          => __( 'Frequencies', 'ogdch' ),
			'name_admin_bar'     => __( 'Frequencies', 'ogdch' ),
			'parent_item_colon'  => __( 'Parent Frequency:', 'ogdch' ),
			'all_items'          => __( 'All Frequencies', 'ogdch' ),
			'add_new_item'       => __( 'Add New Frequency', 'ogdch' ),
			'add_new'            => __( 'Add New', 'ogdch' ),
			'new_item'           => __( 'New Frequency', 'ogdch' ),
			'edit_item'          => __( 'Edit Frequency', 'ogdch' ),
			'update_item'        => __( 'Update Frequency', 'ogdch' ),
			'view_item'          => __( 'View Frequency', 'ogdch' ),
			'search_items'       => __( 'Search Frequencies', 'ogdch' ),
			'not_found'          => __( 'Not found', 'ogdch' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'ogdch' ),
		);

		$args = array(
			'label'               => __( 'Frequencies', 'ogdch' ),
			'description'         => __( 'Frequency mapping', 'ogdch' ),
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
			'map_meta_cap'        => true,
			'capability_type'     => array( 'frequency', 'frequencies' ),
			'capabilities'        => array(
				'edit_posts'             => 'edit_frequencies',
				'edit_others_posts'      => 'edit_others_frequencies',
				'publish_posts'          => 'publish_frequencies',
				'read_private_posts'     => 'read_private_frequencies',
				'delete_posts'           => 'delete_frequencies',
				'delete_private_posts'   => 'delete_private_frequencies',
				'delete_published_posts' => 'delete_published_frequencies',
				'delete_others_posts'    => 'delete_others_frequencies',
				'edit_private_posts'     => 'edit_private_frequencies',
				'edit_published_posts'   => 'edit_published_frequencies',
				'create_posts'           => 'create_frequencies',
				// Meta capabilites assigned by WordPress. Do not give to any role.
				'edit_post'              => 'edit_frequency',
				'read_post'              => 'read_frequency',
				'delete_post'            => 'delete_frequency',
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
			'title'        => __( 'Frequency Data', 'ogdch' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		/* Title */
		$cmb->add_field( array(
			'name' => __( 'Frequency Title', 'ogdch' ),
			'type' => 'title',
			'id'   => 'title_title',
		) );

		foreach ( $language_priority as $lang ) {
			$cmb->add_field( array(
				'name'       => __( 'Title', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'         => self::FIELD_PREFIX . 'title_' . $lang,
				'type'       => 'text',
				'attributes' => array(
					'placeholder' => __( 'e.g. Awesome frequency', 'ogdch' ),
				),
			) );
		}

		$cmb->add_field( array(
			'name'       => __( 'URL', 'ogdch' ),
			'id'         => self::FIELD_PREFIX . 'url',
			'type'       => 'text_url',
			'attributes' => array(
				'placeholder' => 'http://purl.org/cld/freq/frequency',
			),
		) );
	}

}
