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

	const TAXONOMY = 'ckan-keyword-fr';

	const LANGUAGE_SUFFIX = 'FR';

	public function get_taxonomy() {
		return self::TAXONOMY;
	}
	public function get_language_suffix() {
		return self::LANGUAGE_SUFFIX;
	}

}
