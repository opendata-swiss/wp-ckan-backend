<?php
/**
 * Taxonomy ckan-tag
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Tag
 */
abstract class Ckan_Backend_Tag {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_taxonomy();
	}

	public function register_taxonomy() {
		$labels = array(
			'name' => __( 'Tags ' . $this->get_language_suffix() ),
			'singular_name' => __( 'Tag ' . $this->get_language_suffix() ),
			'all_items' => __( 'All Tags ' . $this->get_language_suffix() ),
			'edit_item' => __( 'Edit Tags ' . $this->get_language_suffix() ),
			'view_item' => __( 'View Tag ' . $this->get_language_suffix() ),
			'update_item' => __( 'Update Tag ' . $this->get_language_suffix() ),
			'add_new_item' => __( 'Add New Tag ' . $this->get_language_suffix() ),
			'new_item_name' => __( 'New Tag Name ' . $this->get_language_suffix() ),
		);

		$capabilities = array(
			'manage_terms' => 'manage_tags',
			'edit_terms' => 'edit_tags',
			'delete_terms' => 'delete_tags',
			'assign_terms' => 'assign_tags',
		);

		$args = array(
			'label' => __( 'Tags ' . $this->get_language_suffix() ),
			'labels' => $labels,
			'description' => __( 'Tags for CKAN datasets', 'ogdch' ),
			'hierarchical'          => false,
			'update_count_callback' => '_update_post_term_count',
			'capabilities' => $capabilities,
		);

		register_taxonomy(
			$this->get_taxonomy(),
			Ckan_Backend_Local_Dataset::POST_TYPE,
			$args
		);

		register_taxonomy_for_object_type( $this->get_taxonomy(), Ckan_Backend_Local_Dataset::POST_TYPE );
	}

	public abstract function get_language_suffix();
	public abstract function get_taxonomy();
}
