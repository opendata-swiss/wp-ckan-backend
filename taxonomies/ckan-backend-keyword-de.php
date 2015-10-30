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

	const TAXONOMY = 'ckan-keyword-de';

	const LANGUAGE_SUFFIX = 'DE';

	public function get_taxonomy() {
		return self::TAXONOMY;
	}
	public function get_language_suffix() {
		return self::LANGUAGE_SUFFIX;
	}

}
