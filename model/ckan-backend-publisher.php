<?php
/**
 * Model for Publisher
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Publisher_Model
 */
class Ckan_Backend_Publisher_Model {
	/**
	 * Reference to TERMDAT
	 *
	 * @var string
	 */
	protected $termdat_reference = '';
	/**
	 * Label
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Returns TERMDAT reference
	 *
	 * @return string
	 */
	public function get_termdat_reference() {
		return $this->termdat_reference;
	}

	/**
	 * Sets TERMDAT reference
	 *
	 * @param String $termdat_reference TERMDAT reference.
	 */
	public function set_termdat_reference( $termdat_reference ) {
		$this->termdat_reference = $termdat_reference;
	}

	/**
	 * Returns Label
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Sets Label
	 *
	 * @param String $label Label.
	 */
	public function set_label( $label ) {
		$this->label = $label;
	}

	/**
	 * Converts object to array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'termdat_reference'  => $this->get_termdat_reference(),
			'label'              => $this->get_label(),
		);
	}
}
