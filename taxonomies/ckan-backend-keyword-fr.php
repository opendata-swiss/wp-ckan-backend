<?php
/**
 * Taxonomy ckan-keyword-fr
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Keyword_Fr
 */
class Ckan_Backend_Keyword_Fr extends Ckan_Backend_Keyword {

	/**
	 * Taxonomy name
	 */
	const TAXONOMY = 'ckan-keyword-fr';

	/**
	 * Language suffix of taxonomy
	 */
	const LANGUAGE_SUFFIX = 'FR';

	/**
	 * Returns taxonomy name
	 */
	public function get_taxonomy() {
		return self::TAXONOMY;
	}

	/**
	 * Returns language suffix of taxonomy
	 */
	public function get_language_suffix() {
		return self::LANGUAGE_SUFFIX;
	}

}
