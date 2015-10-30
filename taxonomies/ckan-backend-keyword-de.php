<?php
/**
 * Taxonomy ckan-keyword-de
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Keyword_De
 */
class Ckan_Backend_Keyword_De extends Ckan_Backend_Keyword {

	/**
	 * Taxonomy name
	 */
	const TAXONOMY = 'ckan-keyword-de';

	/**
	 * Language suffix of taxonomy
	 */
	const LANGUAGE_SUFFIX = 'DE';

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
