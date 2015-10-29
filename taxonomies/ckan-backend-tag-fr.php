<?php
/**
 * Taxonomy ckan-tag-fr
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Tag_Fr
 */
class Ckan_Backend_Tag_Fr extends Ckan_Backend_Tag {

	const TAXONOMY = 'ckan-tag-fr';

	const LANGUAGE_SUFFIX = 'FR';

	public function get_taxonomy() {
		return self::TAXONOMY;
	}
	public function get_language_suffix() {
		return self::LANGUAGE_SUFFIX;
	}

}
