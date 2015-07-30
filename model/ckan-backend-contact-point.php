<?php

class Ckan_Backend_ContactPoint_Model {
	protected $name = '';
	protected $email = '';

	public function getName() {
		return $this->name;
	}
	public function setName($name) {
		$this->name = $name;
	}

	public function getEmail() {
		return $this->email;
	}
	public function setEmail($email) {
		$this->email = $email;
	}

	public function toArray() {
		return array(
			'name' => $this->getName(),
			'email' => $this->getEmail(),
		);
	}
}