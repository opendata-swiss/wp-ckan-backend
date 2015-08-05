<?php
/**
 * Model for Relation
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Relation_Model
 */
class Ckan_Backend_Relation_Model {
	/**
	 * Url
	 *
	 * @var string
	 */
	protected $url = '';

	/**
	 * Label
	 *
	 * @var string
	 */
	protected $label = '';

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
	 * Returns label
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Sets label
	 *
	 * @param string $label Label.
	 */
	public function set_label($label) {
		$this->label = $label;
	}

	/**
	 * Converts object to array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'url'   => $this->get_url(),
			'label' => $this->get_label(),
		);
	}
}
