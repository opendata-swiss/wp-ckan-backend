<?php
/**
 * Taxonomy ckan-tag-it
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Tag_It
 */
class Ckan_Backend_Tag_It extends Ckan_Backend_Tag {

	const TAXONOMY = 'ckan-tag-it';

	const LANGUAGE_SUFFIX = 'IT';

	public function get_taxonomy() {
		return self::TAXONOMY;
	}
	public function get_language_suffix() {
		return self::LANGUAGE_SUFFIX;
	}

}
