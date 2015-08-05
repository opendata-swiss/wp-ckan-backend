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
	 * Url
	 *
	 * @var string
	 */
	protected $url = '';

	/**
	 * Format
	 *
	 * @var string
	 */
	protected $format = '';

	/**
	 * Returns url
	 *
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Sets url
	 *
	 * @param string $url Url.
	 */
	public function set_url($url) {
		$this->url = $url;
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
			'url'    => $this->get_url(),
			'format' => $this->get_format(),
		);
	}
}
