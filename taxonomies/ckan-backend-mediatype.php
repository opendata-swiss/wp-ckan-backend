<?php
/**
 * Taxonomy ckan-mediatype
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_MediaType
 */
class Ckan_Backend_MediaType {

	/**
	 * Taxonomy name
	 */
	const TAXONOMY = 'ckan-mediatype';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_taxonomy();
	}

	/**
	 * Registers taxonomy
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'          => __( 'Formats', 'ogdch' ),
			'singular_name' => __( 'Format', 'ogdch' ),
			'all_items'     => __( 'All Formats', 'ogdch' ),
			'edit_item'     => __( 'Edit Formats', 'ogdch' ),
			'view_item'     => __( 'View Format', 'ogdch' ),
			'update_item'   => __( 'Update Format', 'ogdch' ),
			'add_new_item'  => __( 'Add New Format', 'ogdch' ),
			'new_item_name' => __( 'New Format Name', 'ogdch' ),
		);

		$capabilities = array(
			'manage_terms' => 'manage_mediatypes',
			'edit_terms'   => 'edit_mediatypes',
			'delete_terms' => 'delete_mediatypes',
			'assign_terms' => 'assign_mediatypes',
		);

		$args = array(
			'label'                 => __( 'Formats', 'ogdch' ),
			'labels'                => $labels,
			'description'           => __( 'Formats for CKAN datasets', 'ogdch' ),
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => false,
			'show_tagcloud'         => false,
			'meta_box_cb'           => false, // disable meta box in post type
			'hierarchical'          => false,
			'update_count_callback' => '_update_post_term_count',
			'capabilities'          => $capabilities,
		);

		register_taxonomy(
			self::TAXONOMY,
			Ckan_Backend_Local_Dataset::POST_TYPE,
			$args
		);

		register_taxonomy_for_object_type( self::TAXONOMY, Ckan_Backend_Local_Dataset::POST_TYPE );
	}
}
