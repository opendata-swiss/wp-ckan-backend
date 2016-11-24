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

		// Sanitize keyword before saving it. Only allow 'a-z', '0-9' and '-'.
		add_filter( 'pre_insert_term', array( $this, 'sanatize_keyword' ), 10, 2 );
	}

	/**
	 * Registers taxonomy
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'          => sprintf( esc_html_x( 'Keywords (%s)', '%s contains the language of this keyword.', 'ogdch-backend' ), $this->get_language_suffix() ),
			'singular_name' => sprintf( esc_html_x( 'Keyword (%s)', '%s contains the language of this keyword.', 'ogdch-backend' ), $this->get_language_suffix() ),
			'all_items'     => sprintf( esc_html_x( 'All Keywords (%s)', '%s contains the language of this keyword.', 'ogdch-backend' ), $this->get_language_suffix() ),
			'edit_item'     => sprintf( esc_html_x( 'Edit Keywords (%s)', '%s contains the language of this keyword.', 'ogdch-backend' ), $this->get_language_suffix() ),
			'view_item'     => sprintf( esc_html_x( 'View Keyword (%s)', '%s contains the language of this keyword.', 'ogdch-backend' ), $this->get_language_suffix() ),
			'update_item'   => sprintf( esc_html_x( 'Update Keyword (%s)', '%s contains the language of this keyword.', 'ogdch-backend' ), $this->get_language_suffix() ),
			'add_new_item'  => sprintf( esc_html_x( 'Add New Keyword (%s)', '%s contains the language of this keyword.', 'ogdch-backend' ), $this->get_language_suffix() ),
			'new_item_name' => sprintf( esc_html_x( 'New Keyword Name (%s)', '%s contains the language of this keyword.', 'ogdch-backend' ), $this->get_language_suffix() ),
		);

		$capabilities = array(
			'manage_terms' => 'manage_keywords',
			'edit_terms'   => 'edit_keywords',
			'delete_terms' => 'delete_keywords',
			'assign_terms' => 'assign_keywords',
		);

		$args = array(
			'label'                 => sprintf( esc_html_x( 'Keywords (%s)', '%s contains the language of this keyword.', 'ogdch-backend' ), $this->get_language_suffix() ),
			'labels'                => $labels,
			'description'           => sprintf( esc_html_x( 'Keywords (%s) for CKAN datasets', '%s contains the language of this keyword.', 'ogdch-backend' ), $this->get_language_suffix() ),
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
	 * Sanitize keyword before saving it. Only allow 'a-z', '0-9' and '-'.
	 *
	 * @param string $term     The term to add or update.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return mixed
	 */
	public function sanatize_keyword( $term, $taxonomy ) {
		if ( $taxonomy === $this->get_taxonomy() ) {
			$term = $this->unicode_2_ascii( $term ); // replace unicode characters with ascii equivalent
			$term = trim( strtolower( $term ) ); // lowercase term and remove whitespaces around it
			$term = preg_replace( '/[^a-zA-Z0-9\- ]/', '', $term ); // remove all characters which doesn't fit the pattern
			$term = str_replace( ' ', '-', $term ); // replace whitespaces with dashes
		}

		return $term;
	}

	/**
	 * Converts unicode characters into ASCII equivalents.
	 * Source: http://stackoverflow.com/a/14815225/1328415
	 *
	 * @param string $unicode_string String with possible unicode characters.
	 *
	 * @return string
	 */
	public function unicode_2_ascii( $unicode_string ) {
		// @codingStandardsIgnoreStart
		return strtr( utf8_decode( $unicode_string ),
			utf8_decode(
				'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ' ),
				'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy' );
		// @codingStandardsIgnoreEnd
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
