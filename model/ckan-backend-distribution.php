<?php

class Ckan_Backend_Distribution_Model {
	protected $identifier = '';
	protected $title = array();
	protected $description = array();
	protected $languages = array();
	protected $issued = '';
	protected $modified = '';
	protected $accessUrls = array();
	protected $rights = array();
	protected $license = '';
	protected $downloadUrls = array();
	protected $byteSize = 0;
	protected $mediaType = '';
	protected $format = '';
	protected $coverage = '';

	public function getIdentifier() {
		return $this->identifier;
	}
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	public function getTitle($lang = 'en') {
		return $this->title[$lang];
	}
	public function setTitle($title, $lang = 'en') {
		$this->title[$lang] = $title;
	}

	public function getDescription($lang = 'en') {
		return $this->description[$lang];
	}
	public function setDescription($description, $lang = 'en') {
		$this->description[$lang] = $description;
	}

	public function getLanguages() {
		return $this->languages;
	}
	public function addLanguage($language) {
		$this->languages[] = $language;
	}
	public function removeLanguage($language) {
		if(($key = array_search($language, $this->getLanguages())) !== false) {
			unset($this->languages[$key]);
		}
	}

	public function getIssued() {
		return $this->issued;
	}
	public function setIssued($issued) {
		$this->issued = $issued;
	}

	public function getModified() {
		return $this->modified;
	}
	public function setModified($modified) {
		$this->modified = $modified;
	}

	public function getAccessUrls() {
		return $this->accessUrls;
	}
	public function addAccessUrl($accessUrl) {
		$this->accessUrls[] = $accessUrl;
	}
	public function removeAccessUrl($accessUrl) {
		if(($key = array_search($accessUrl, $this->getAccessUrls())) !== false) {
			unset($this->accessUrls[$key]);
		}
	}

	public function getRights() {
		return $this->rights;
	}
	public function addRight($right) {
		$this->rights[] = $right;
	}
	public function removeRight($right) {
		if(($key = array_search($right, $this->getRights())) !== false) {
			unset($this->rights[$key]);
		}
	}

	public function getLicense() {
		return $this->license;
	}
	public function setLicense($license) {
		$this->license = $license;
	}

	public function getDownloadUrls() {
		return $this->downloadUrls;
	}
	public function addDownloadUrl($downloadUrl) {
		$this->downloadUrls[] = $downloadUrl;
	}
	public function removeDownloadUrl($downloadUrl) {
		if(($key = array_search($downloadUrl, $this->getDownloadUrls())) !== false) {
			unset($this->downloadUrls[$key]);
		}
	}

	public function getByteSize() {
		return $this->byteSize;
	}
	public function setByteSize($byteSize) {
		$this->byteSize = $byteSize;
	}

	public function getMediaType() {
		return $this->mediaType;
	}
	public function setMediaType($mediaType) {
		$this->mediaType = $mediaType;
	}

	public function getFormat() {
		return $this->format;
	}
	public function setFormat($format) {
		$this->format = $format;
	}

	public function getCoverage() {
		return $this->coverage;
	}
	public function setCoverage($coverage) {
		$this->coverage = $coverage;
	}

	public function toArray() {
		global $language_priority;

		$distribution = array(
			'identifier' => $this->getIdentifier(),
			'languages' => $this->getLanguages(),
			'issued' => $this->getIssued(),
			'modified' => $this->getModified(),
			'access_urls' => $this->getAccessUrls(),
			'download_urls' => $this->getDownloadUrls(),
			'rights' => $this->getRights(),
			'license' => $this->getLicense(),
			'byte_size' => $this->getByteSize(),
			'media_type' => $this->getMediaType(),
			'format' => $this->getFormat(),
			'coverage' => $this->getCoverage(),
		);

		foreach($language_priority as $lang) {
			$distribution['title_' . $lang] = $this->getTitle($lang);
			$distribution['description_' . $lang] = $this->getDescription($lang);
		}

		return $distribution;
	}
}