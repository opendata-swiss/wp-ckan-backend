<?php
/**
 * Hardcoded taxonomy ckan-political-level
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_PoliticalLevel
 */
class Ckan_Backend_PoliticalLevel {

	/**
	 * Political levels.
	 *
	 * @var array
	 */
	public static $political_levels = array();

	/**
	 * Constructor.
	 */
	public static function init() {
		self::$political_levels = array(
			'federation' => __( 'Federation', 'ogdch' ),
			'canton' => __( 'Canton', 'ogdch' ),
			'municipality' => __( 'Municipality', 'ogdch' ),
			'federal_organization' => __( 'Federal organization', 'ogdch' ),
			'inter_federal_organization' => __( 'Inter federal organization', 'ogdch' ),
		);
	}

	/**
	 * Returns political levels.
	 *
	 * @return array
	 */
	public static function get_political_levels() {
		return self::$political_levels;
	}

}
