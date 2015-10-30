<?php
/**
 * Taxonomy ckan-keyword
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Keyword
 */
abstract class Ckan_Backend_Keyword {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register_taxonomy();
	}

	public function register_taxonomy() {
		$labels = array(
			'name' => __( 'Keywords ' . $this->get_language_suffix() ),
			'singular_name' => __( 'Keyword ' . $this->get_language_suffix() ),
			'all_items' => __( 'All Keywords ' . $this->get_language_suffix() ),
			'edit_item' => __( 'Edit Keywords ' . $this->get_language_suffix() ),
			'view_item' => __( 'View Keyword ' . $this->get_language_suffix() ),
			'update_item' => __( 'Update Keyword ' . $this->get_language_suffix() ),
			'add_new_item' => __( 'Add New Keyword ' . $this->get_language_suffix() ),
			'new_item_name' => __( 'New Keyword Name ' . $this->get_language_suffix() ),
		);

		$capabilities = array(
			'manage_terms' => 'manage_keywords',
			'edit_terms' => 'edit_keywords',
			'delete_terms' => 'delete_keywords',
			'assign_terms' => 'assign_keywords',
		);

		$args = array(
			'label' => __( 'Keywords ' . $this->get_language_suffix() ),
			'labels' => $labels,
			'description' => __( 'Keywords ' . $this->get_language_suffix() . ' for CKAN datasets', 'ogdch' ),
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
