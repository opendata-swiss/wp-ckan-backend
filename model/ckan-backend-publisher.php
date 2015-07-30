<?php

class Ckan_Backend_Publisher_Model {
	protected $name = '';
	protected $mbox = '';

	public function getName() {
		return $this->name;
	}
	public function setName($name) {
		$this->name = $name;
	}

	public function getMbox() {
		return $this->mbox;
	}
	public function setMbox($mbox) {
		$this->mbox = $mbox;
	}

	public function toArray() {
		return array(
			'name' => $this->getName(),
			'mbox' => $this->getMbox(),
		);
	}
}