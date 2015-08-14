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
	 * Start date timestamp
	 *
	 * @var int
	 */
	protected $start_date = 0;

	/**
	 * End date timestamp
	 *
	 * @var int
	 */
	protected $end_date = 0;

	/**
	 * Returns start date timestamp
	 *
	 * @return int
	 */
	public function get_start_date() {
		return $this->start_date;
	}

	/**
	 * Sets start date timestamp
	 *
	 * @param int $start_date Start date.
	 */
	public function set_start_date($start_date) {
		$this->start_date = $start_date;
	}

	/**
	 * Returns end date timestamp
	 *
	 * @return int
	 */
	public function get_end_date() {
		return $this->end_date;
	}

	/**
	 * Sets end date timestamp
	 *
	 * @param int $end_date End date.
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
