<?php

class Ckan_Backend_Relation_Model {
	protected $description = '';
	protected $label = '';

	public function getDescription() {
		return $this->description;
	}
	public function setDescription($description) {
		$this->description = $description;
	}

	public function getLabel() {
		return $this->label;
	}
	public function setLabel($label) {
		$this->label = $label;
	}

	public function toArray() {
		return array(
			'description' => $this->getDescription(),
			'label' => $this->getLabel(),
		);
	}
}