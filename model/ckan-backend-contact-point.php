<?php
/**
 * Model for Contact Point
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_ContactPoint_Model
 */
class Ckan_Backend_ContactPoint_Model {
	/**
	 * Name of Contact Point
	 *
	 * @var string
	 */
	protected $name = '';
	/**
	 * Email of Contact Point
	 *
	 * @var string Email
	 */
	protected $email = '';

	/**
	 * Returns Name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Sets Name
	 *
	 * @param String $name Name.
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * Returns Email
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->email;
	}

	/**
	 * Sets Email
	 *
	 * @param String $email Email.
	 */
	public function set_email( $email ) {
		$this->email = $email;
	}

	/**
	 * Converts object to array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'name'  => $this->get_name(),
			'email' => $this->get_email(),
		);
	}
}
