<?php

class Ckan_Backend_SeeAlso_Model {
	protected $about = '';
	protected $format = '';

	public function getAbout() {
		return $this->about;
	}
	public function setAbout($about) {
		$this->about = $about;
	}

	public function getFormat() {
		return $this->format;
	}
	public function setFormat($format) {
		$this->format = $format;
	}

	public function toArray() {
		return array(
			'about' => $this->getAbout(),
			'format' => $this->getFormat(),
		);
	}
}