<?php
/**
 * Model for SeeAlso
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_SeeAlso_Model
 */
class Ckan_Backend_SeeAlso_Model {
	/**
	 * About
	 *
	 * @var string
	 */
	protected $about = '';

	/**
	 * Format
	 *
	 * @var string
	 */
	protected $format = '';

	/**
	 * Returns about
	 *
	 * @return string
	 */
	public function get_about() {
		return $this->about;
	}

	/**
	 * Sets about
	 *
	 * @param string $about About.
	 */
	public function set_about($about) {
		$this->about = $about;
	}

	/**
	 * Returns format
	 *
	 * @return string
	 */
	public function get_format() {
		return $this->format;
	}

	/**
	 * Sets format
	 *
	 * @param string $format Format.
	 */
	public function set_format($format) {
		$this->format = $format;
	}

	/**
	 * Converts object to array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'about' => $this->get_about(),
			'format' => $this->get_format(),
		);
	}
}
