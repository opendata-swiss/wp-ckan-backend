<?php
/**
 * Model for Temporal
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Temporal_Model
 */
class Ckan_Backend_Temporal_Model {
	/**
	 * Start date
	 *
	 * @var string
	 */
	protected $start_date = '';

	/**
	 * End date
	 *
	 * @var string
	 */
	protected $end_date = '';

	/**
	 * Returns start date
	 *
	 * @return string
	 */
	public function get_start_date() {
		return $this->start_date;
	}

	/**
	 * Sets start date
	 *
	 * @param string $start_date Start date.
	 */
	public function set_start_date($start_date) {
		$this->start_date = $start_date;
	}

	/**
	 * Returns end date
	 *
	 * @return string
	 */
	public function get_end_date() {
		return $this->end_date;
	}

	/**
	 * Sets end date
	 *
	 * @param string $end_date End date.
	 */
	public function set_end_date($end_date) {
		$this->end_date = $end_date;
	}

	/**
	 * Converts object to array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'start_date' => $this->get_start_date(),
			'end_date' => $this->get_end_date(),
		);
	}
}
