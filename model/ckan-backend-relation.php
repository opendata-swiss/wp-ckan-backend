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
	 * Description
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Label
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Returns description
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Sets description
	 *
	 * @param string $description Description.
	 */
	public function set_description($description) {
		$this->description = $description;
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
			'description' => $this->get_description(),
			'label' => $this->get_label(),
		);
	}
}
