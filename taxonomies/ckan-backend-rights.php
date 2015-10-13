<?php
/**
 * Post type ckan-rights
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Rights
 */
class Ckan_Backend_Rights {

	/**
	 * Rights
	 *
	 * @var array
	 */
	public static $rights = array();

	/**
	 * Constructor of this class.
	 */
	public static function init() {
		self::$rights = array(
			'NonCommercialAllowed-CommercialAllowed-ReferenceNotRequired'           => __( '* Non-commercial Allowed / Commercial Allowed / Reference Not Required', 'ogdch' ),
			'NonCommercialAllowed-CommercialAllowed-ReferenceRequired'              => __( '* Non-commercial Allowed / Commercial Allowed / Reference Required', 'ogdch' ),
			'NonCommercialAllowed-CommercialWithPermission-ReferenceNotRequired'    => __( '* Non-commercial Allowed / Commercial With Permission Allowed / Reference Not Required', 'ogdch' ),
			'NonCommercialAllowed-CommercialWithPermission-ReferenceRequired'       => __( '* Non-commercial Allowed / Commercial With Permission Allowed / Reference Required', 'ogdch' ),
			'NonCommercialAllowed-CommercialNotAllowed-ReferenceNotRequired'        => __( 'Non-commercial Allowed / Commercial Not Allowed / Reference Not Required', 'ogdch' ),
			'NonCommercialAllowed-CommercialNotAllowed-ReferenceRequired'           => __( 'Non-commercial Allowed / Commercial Not Allowed / Reference Required', 'ogdch' ),
			'NonCommercialNotAllowed-CommercialNotAllowed-ReferenceNotRequired'     => __( 'Non-commercial Not Allowed / Commercial Not Allowed / Reference Not Required', 'ogdch' ),
			'NonCommercialNotAllowed-CommercialNotAllowed-ReferenceRequired'        => __( 'Non-commercial Not Allowed / Commercial Not Allowed / Reference Required', 'ogdch' ),
			'NonCommercialNotAllowed-CommercialAllowed-ReferenceNotRequired'        => __( 'Non-commercial Not Allowed / Commercial Allowed / Reference Not Required', 'ogdch' ),
			'NonCommercialNotAllowed-CommercialAllowed-ReferenceRequired'           => __( 'Non-commercial Not Allowed / Commercial Allowed / Reference Required', 'ogdch' ),
			'NonCommercialNotAllowed-CommercialWithPermission-ReferenceNotRequired' => __( 'Non-commercial Not Allowed / Commercial With Permission Allowed / Reference Not Required', 'ogdch' ),
			'NonCommercialNotAllowed-CommercialWithPermission-ReferenceRequired'    => __( 'Non-commercial Not Allowed / Commercial With Permission Allowed / Reference Required', 'ogdch' ),
		);
	}

	/**
	 * Returns rights.
	 *
	 * @return array
	 */
	public static function get_rights() {
		return self::$rights;
	}

}
