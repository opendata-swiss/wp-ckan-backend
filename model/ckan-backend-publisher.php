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
	 * Name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Mbox
	 *
	 * @var string
	 */
	protected $mbox = '';

	/**
	 * Returns name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Sets name
	 *
	 * @param string $name Name.
	 */
	public function set_name($name) {
		$this->name = $name;
	}

	/**
	 * Returns mbox
	 *
	 * @return string
	 */
	public function get_mbox() {
		return $this->mbox;
	}

	/**
	 * Sets mbox
	 *
	 * @param string $mbox Mbox.
	 */
	public function set_mbox($mbox) {
		$this->mbox = $mbox;
	}

	/**
	 * Converts object to array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'name' => $this->get_name(),
			'mbox' => $this->get_mbox(),
		);
	}
}
