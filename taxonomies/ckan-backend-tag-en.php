<?php
/**
 * Taxonomy ckan-tag-en
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Tag_En
 */
class Ckan_Backend_Tag_En extends Ckan_Backend_Tag {

	const TAXONOMY = 'ckan-tag-en';

	const LANGUAGE_SUFFIX = 'EN';

	public function get_taxonomy() {
		return self::TAXONOMY;
	}
	public function get_language_suffix() {
		return self::LANGUAGE_SUFFIX;
	}

}
