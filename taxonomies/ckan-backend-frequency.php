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
	const TAXONOMY = 'ckan-frequency';
	const FIELD_PREFIX = '_ckan_frequency_';

	/**
	 * Constructor of this class.
	 */
	public function __construct() {
		$this->register_taxonomy();
		add_filter( 'cmb2-taxonomy_meta_boxes', array( $this, 'add_fields' ) );
	}

	/**
	 * Registers the taxonomy in WordPress
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'                       => __( 'Frequencies', 'ogdch' ),
			'singular_name'              => __( 'Frequency', 'ogdch' ),
			'menu_name'                  => __( 'Frequencies', 'ogdch' ),
			'all_items'                  => __( 'All Frequencies', 'ogdch' ),
			'edit_item'                  => __( 'Edit Frequency', 'ogdch' ),
			'view_item'                  => __( 'View Frequency', 'ogdch' ),
			'update_item'                => __( 'Update Frequency', 'ogdch' ),
			'add_new_item'               => __( 'Add New Frequency', 'ogdch' ),
			'parent_item'                => __( 'Parent Frequency', 'ogdch' ),
			'parent_item_colon'          => __( 'Parent Frequency:', 'ogdch' ),
			'search_items'               => __( 'Search Frequency', 'ogdch' ),
			'popular_items'              => __( 'Popular Frequencies', 'ogdch' ),
			'separate_items_with_commas' => __( 'Separate frequencies with commas', 'ogdch' ),
			'add_or_remove_items'        => __( 'Add or remove frequencies', 'ogdch' ),
			'choose_from_most_used'      => __( 'Choose from the most used frequencies', 'ogdch' ),
			'not_found'                  => __( 'Not found', 'ogdch' ),
		);

		$args = array(
			'label'        => __( 'Frequencies', 'ogdch' ),
			'labels'       => $labels,
			'public'       => true,
			'show_ui'      => true,
			'meta_box_cb'  => false, // Disable metabox in post edit
			'hierarchical' => false,
			'capabilities' => array(
				'manage_terms' => 'manage_frequencies',
				'edit_terms'   => 'edit_frequencies',
				'delete_terms' => 'delete_frequencies',
				'assign_terms' => 'assign_frequencies',
			),
		);
		register_taxonomy( self::TAXONOMY, Ckan_Backend_Local_Dataset::POST_TYPE, $args );
	}


	/**
	 * Define the metabox and field configurations.
	 *
	 * @param  array $meta_boxes Array where meta boxes can be added.
	 *
	 * @return array
	 */
	public function add_fields( array $meta_boxes ) {
		global $language_priority;
		/**
		 * Sample metabox to demonstrate each field type included
		 */
		$meta_boxes[ Ckan_Backend_Frequency::TAXONOMY . '-box' ] = array(
			'id'           => Ckan_Backend_Frequency::TAXONOMY . '-box',
			'title'        => __( 'Test Metabox', 'cmb2' ),
			'object_types' => array( Ckan_Backend_Frequency::TAXONOMY ), // Taxonomy
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left
			'fields'       => array(),
		);

		foreach ( $language_priority as $lang ) {
			$meta_boxes[ Ckan_Backend_Frequency::TAXONOMY . '-box' ]['fields'][] = array(
				'name' => __( 'Title', 'ogdch' ) . ' (' . strtoupper( $lang ) . ')',
				'id'   => self::FIELD_PREFIX . 'title_' . $lang,
				'type' => 'text',
			);
		}
		$meta_boxes[ Ckan_Backend_Frequency::TAXONOMY . '-box' ]['fields'][] = array(
			'name' => __( 'URL', 'ogdch' ),
			'id'   => self::FIELD_PREFIX . 'url',
			'type' => 'text_url',
		);

		return $meta_boxes;
	}

}
