<?php
/**
 * Taxonomy ckan-tag-de
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Tag_De
 */
class Ckan_Backend_Tag_De extends Ckan_Backend_Tag {

	const TAXONOMY = 'ckan-tag-de';

	const LANGUAGE_SUFFIX = 'DE';

	public function get_taxonomy() {
		return self::TAXONOMY;
	}
	public function get_language_suffix() {
		return self::LANGUAGE_SUFFIX;
	}

}
