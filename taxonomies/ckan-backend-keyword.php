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

	/**
	 * Registers taxonomy
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'          => sprintf( __( 'Keywords (%s)', 'ogdch' ), $this->get_language_suffix() ),
			'singular_name' => sprintf( __( 'Keyword (%s)', 'ogdch' ), $this->get_language_suffix() ),
			'all_items'     => sprintf( __( 'All Keywords (%s)', 'ogdch' ), $this->get_language_suffix() ),
			'edit_item'     => sprintf( __( 'Edit Keywords (%s)', 'ogdch' ), $this->get_language_suffix() ),
			'view_item'     => sprintf( __( 'View Keyword (%s)', 'ogdch' ), $this->get_language_suffix() ),
			'update_item'   => sprintf( __( 'Update Keyword (%s)', 'ogdch' ), $this->get_language_suffix() ),
			'add_new_item'  => sprintf( __( 'Add New Keyword (%s)', 'ogdch' ), $this->get_language_suffix() ),
			'new_item_name' => sprintf( __( 'New Keyword Name (%s)', 'ogdch' ), $this->get_language_suffix() ),
		);

		$capabilities = array(
			'manage_terms' => 'manage_keywords',
			'edit_terms'   => 'edit_keywords',
			'delete_terms' => 'delete_keywords',
			'assign_terms' => 'assign_keywords',
		);

		$args = array(
			'label'                 => sprintf( __( 'Keywords (%s)', 'ogdch' ), $this->get_language_suffix() ),
			'labels'                => $labels,
			'description'           => sprintf( __( 'Keywords (%s) for CKAN datasets', 'ogdch' ), $this->get_language_suffix() ),
			'show_ui'               => true,
			'show_in_menu'          => false,
			'show_in_nav_menus'     => false,
			'hierarchical'          => false,
			'update_count_callback' => '_update_post_term_count',
			'capabilities'          => $capabilities,
		);

		register_taxonomy(
			$this->get_taxonomy(),
			Ckan_Backend_Local_Dataset::POST_TYPE,
			$args
		);

		register_taxonomy_for_object_type( $this->get_taxonomy(), Ckan_Backend_Local_Dataset::POST_TYPE );
	}

	/**
	 * Returns taxonomy name
	 */
	public abstract function get_taxonomy();

	/**
	 * Returns language suffix of taxonomy
	 */
	public abstract function get_language_suffix();
}
