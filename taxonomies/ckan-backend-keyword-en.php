<?php
/**
 * Taxonomy ckan-keyword-en
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Keyword_En
 */
class Ckan_Backend_Keyword_En extends Ckan_Backend_Keyword {

	const TAXONOMY = 'ckan-keyword-en';

	const LANGUAGE_SUFFIX = 'EN';

	public function get_taxonomy() {
		return self::TAXONOMY;
	}
	public function get_language_suffix() {
		return self::LANGUAGE_SUFFIX;
	}

}
