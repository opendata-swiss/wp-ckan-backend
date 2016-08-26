<?php
/**
 * Hardcoded taxonomy ckan-federal-level
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_FederalLevel
 */
class Ckan_Backend_FederalLevel {

	/**
	 * Federal levels.
	 *
	 * @var array
	 */
	public static $federal_levels = array();

	/**
	 * Constructor.
	 */
	public static function init() {
		self::$federal_levels = array(
			'federation' => __( 'Federation', 'ogdch' ),
			'canton' => __( 'Canton', 'ogdch' ),
			'municipality' => __( 'Municipality', 'ogdch' ),
			'federal_organization' => __( 'Federal organization', 'ogdch' ),
			'inter_federal_organization' => __( 'Inter federal organization', 'ogdch' ),
		);
	}

	/**
	 * Returns federal levels.
	 *
	 * @return array
	 */
	public static function get_federal_levels() {
		return self::$federal_levels;
	}

}
