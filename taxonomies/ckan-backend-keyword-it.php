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

	const TAXONOMY = 'ckan-keyword-it';

	const LANGUAGE_SUFFIX = 'IT';

	public function get_taxonomy() {
		return self::TAXONOMY;
	}
	public function get_language_suffix() {
		return self::LANGUAGE_SUFFIX;
	}

}
