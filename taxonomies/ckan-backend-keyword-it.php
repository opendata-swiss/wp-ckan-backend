<?php
/**
 * Taxonomy ckan-keyword-it
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Keyword_It
 */
class Ckan_Backend_Keyword_It extends Ckan_Backend_Keyword {

	/**
	 * Taxonomy name
	 */
	const TAXONOMY = 'ckan-keyword-it';

	/**
	 * Language suffix of taxonomy
	 */
	const LANGUAGE_SUFFIX = 'IT';

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
