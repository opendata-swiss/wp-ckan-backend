<?php
/**
 * Post type ckan-frequency
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
			'http://purl.org/cld/freq/completelyIrregular' => __( 'Irregular', 'ogdch' ),
			'http://purl.org/cld/freq/continuous'          => __( 'Continuous', 'ogdch' ),
			'http://purl.org/cld/freq/daily'               => __( 'Daily', 'ogdch' ),
			'http://purl.org/cld/freq/threeTimesAWeek'     => __( 'Three times a week', 'ogdch' ),
			'http://purl.org/cld/freq/semiweekly'          => __( 'Semi weekly', 'ogdch' ),
			'http://purl.org/cld/freq/weekly'              => __( 'Weekly', 'ogdch' ),
			'http://purl.org/cld/freq/threeTimesAMonth'    => __( 'Three times a month', 'ogdch' ),
			'http://purl.org/cld/freq/biweekly'            => __( 'Biweekly', 'ogdch' ),
			'http://purl.org/cld/freq/semimonthly'         => __( 'Semimonthly', 'ogdch' ),
			'http://purl.org/cld/freq/monthly'             => __( 'Monthly', 'ogdch' ),
			'http://purl.org/cld/freq/bimonthly'           => __( 'Bimonthly', 'ogdch' ),
			'http://purl.org/cld/freq/quarterly'           => __( 'Quarterly', 'ogdch' ),
			'http://purl.org/cld/freq/threeTimesAYear'     => __( 'Three times a year', 'ogdch' ),
			'http://purl.org/cld/freq/semiannual'          => __( 'Semi Annual', 'ogdch' ),
			'http://purl.org/cld/freq/annual'              => __( 'Annual', 'ogdch' ),
			'http://purl.org/cld/freq/biennial'            => __( 'Biennial', 'ogdch' ),
			'http://purl.org/cld/freq/triennial'           => __( 'Triennial', 'ogdch' ),
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
