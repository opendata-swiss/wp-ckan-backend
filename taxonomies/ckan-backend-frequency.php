<?php
/**
 * Taxonomy ckan-frequency
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Frequency
 */
class Ckan_Backend_Frequency {

	/**
	 * Frequencies
	 *
	 * @var array
	 */
	public static $frequencies = array();

	/**
	 * Constructor of this class.
	 */
	public static function init() {
		self::$frequencies = array(
			'http://purl.org/cld/freq/completelyIrregular' => __( 'Irregular', 'ogdch-backend' ),
			'http://purl.org/cld/freq/continuous'          => __( 'Continuous', 'ogdch-backend' ),
			'http://purl.org/cld/freq/daily'               => __( 'Daily', 'ogdch-backend' ),
			'http://purl.org/cld/freq/threeTimesAWeek'     => __( 'Three times a week', 'ogdch-backend' ),
			'http://purl.org/cld/freq/semiweekly'          => __( 'Semi weekly', 'ogdch-backend' ),
			'http://purl.org/cld/freq/weekly'              => __( 'Weekly', 'ogdch-backend' ),
			'http://purl.org/cld/freq/threeTimesAMonth'    => __( 'Three times a month', 'ogdch-backend' ),
			'http://purl.org/cld/freq/biweekly'            => __( 'Biweekly', 'ogdch-backend' ),
			'http://purl.org/cld/freq/semimonthly'         => __( 'Semimonthly', 'ogdch-backend' ),
			'http://purl.org/cld/freq/monthly'             => __( 'Monthly', 'ogdch-backend' ),
			'http://purl.org/cld/freq/bimonthly'           => __( 'Bimonthly', 'ogdch-backend' ),
			'http://purl.org/cld/freq/quarterly'           => __( 'Quarterly', 'ogdch-backend' ),
			'http://purl.org/cld/freq/threeTimesAYear'     => __( 'Three times a year', 'ogdch-backend' ),
			'http://purl.org/cld/freq/semiannual'          => __( 'Semi Annual', 'ogdch-backend' ),
			'http://purl.org/cld/freq/annual'              => __( 'Annual', 'ogdch-backend' ),
			'http://purl.org/cld/freq/biennial'            => __( 'Biennial', 'ogdch-backend' ),
			'http://purl.org/cld/freq/triennial'           => __( 'Triennial', 'ogdch-backend' ),
		);
	}

	/**
	 * Returns frequencies.
	 *
	 * @return array
	 */
	public static function get_frequencies() {
		return self::$frequencies;
	}

}
