<?php
/**
 * Taxonomy ckan-rights
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
			'NonCommercialAllowed-CommercialAllowed-ReferenceNotRequired'           => __( '* Non-commercial Allowed / Commercial Allowed / Reference Not Required', 'ogdch-backend' ),
			'NonCommercialAllowed-CommercialAllowed-ReferenceRequired'              => __( '* Non-commercial Allowed / Commercial Allowed / Reference Required', 'ogdch-backend' ),
			'NonCommercialAllowed-CommercialWithPermission-ReferenceNotRequired'    => __( '* Non-commercial Allowed / Commercial With Permission Allowed / Reference Not Required', 'ogdch-backend' ),
			'NonCommercialAllowed-CommercialWithPermission-ReferenceRequired'       => __( '* Non-commercial Allowed / Commercial With Permission Allowed / Reference Required', 'ogdch-backend' ),
			'NonCommercialAllowed-CommercialNotAllowed-ReferenceNotRequired'        => __( 'Non-commercial Allowed / Commercial Not Allowed / Reference Not Required', 'ogdch-backend' ),
			'NonCommercialAllowed-CommercialNotAllowed-ReferenceRequired'           => __( 'Non-commercial Allowed / Commercial Not Allowed / Reference Required', 'ogdch-backend' ),
			'NonCommercialNotAllowed-CommercialNotAllowed-ReferenceNotRequired'     => __( 'Non-commercial Not Allowed / Commercial Not Allowed / Reference Not Required', 'ogdch-backend' ),
			'NonCommercialNotAllowed-CommercialNotAllowed-ReferenceRequired'        => __( 'Non-commercial Not Allowed / Commercial Not Allowed / Reference Required', 'ogdch-backend' ),
			'NonCommercialNotAllowed-CommercialAllowed-ReferenceNotRequired'        => __( 'Non-commercial Not Allowed / Commercial Allowed / Reference Not Required', 'ogdch-backend' ),
			'NonCommercialNotAllowed-CommercialAllowed-ReferenceRequired'           => __( 'Non-commercial Not Allowed / Commercial Allowed / Reference Required', 'ogdch-backend' ),
			'NonCommercialNotAllowed-CommercialWithPermission-ReferenceNotRequired' => __( 'Non-commercial Not Allowed / Commercial With Permission Allowed / Reference Not Required', 'ogdch-backend' ),
			'NonCommercialNotAllowed-CommercialWithPermission-ReferenceRequired'    => __( 'Non-commercial Not Allowed / Commercial With Permission Allowed / Reference Required', 'ogdch-backend' ),
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
