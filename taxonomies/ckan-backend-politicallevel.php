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
			'confederation' => __( 'Confederation', 'ogdch-backend' ),
			'canton' => __( 'Canton', 'ogdch-backend' ),
			'commune' => __( 'Commune', 'ogdch-backend' ),
			'other' => __( 'Other', 'ogdch-backend' ),
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
