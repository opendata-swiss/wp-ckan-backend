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
			'http://purl.org/cld/freq/weekly'              => __( 'Weekly', 'ogdch' ),
			'http://purl.org/cld/freq/monthly'             => __( 'Monthly', 'ogdch' ),
			'http://purl.org/cld/freq/semiannual'          => __( 'Semi Annual', 'ogdch' ),
			'http://purl.org/cld/freq/annual'              => __( 'Annual', 'ogdch' ),
		);
	}

}
