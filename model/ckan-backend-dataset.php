<?php

class Ckan_Backend_Dataset_Model {
	protected $identifier = '';
	protected $title = array();
	protected $description = array();
	protected $issued = '';
	protected $modified = '';
	protected $publishers = array();
	protected $contactPoints = array();
	protected $themes = array();
	protected $languages = array();
	protected $keywords = array();
	protected $relations = array();
	protected $landingPage = '';
	protected $spatial = '';
	protected $coverage = '';
	protected $temporals = null;
	protected $accrualPeriodicy = '';
	protected $seeAlsos = array();
	protected $distributions = array();

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

	public function addPublisher($publisher) {
		if(! $publisher instanceof Ckan_Backend_Publisher_Model) {
			throw new RuntimeException( 'Publisher has to be a Ckan_Backend_Publisher_Model type' );
		}
		$this->publishers[spl_object_hash($publisher)] = $publisher;
	}
	public function removePublisher($publisher) {
		if(! $publisher instanceof Ckan_Backend_Publisher_Model) {
			throw new RuntimeException( 'Publisher has to be a Ckan_Backend_Publisher_Model type' );
		}
		unset($this->publishers[spl_object_hash($publisher)]);
	}
	public function getPublishers() {
		return $this->publishers;
	}

	public function addContactPoint($contactPoint) {
		if(! $contactPoint instanceof Ckan_Backend_ContactPoint_Model) {
			throw new RuntimeException( 'Contact Point has to be a Ckan_Backend_ContactPoint_Model type' );
		}
		$this->contactPoints[spl_object_hash($contactPoint)] = $contactPoint;
	}
	public function removeContactPoint($contactPoint) {
		if(! $contactPoint instanceof Ckan_Backend_ContactPoint_Model) {
			throw new RuntimeException( 'Contact Point has to be a Ckan_Backend_ContactPoint_Model type' );
		}
		unset($this->contactPoints[spl_object_hash($contactPoint)]);
	}
	public function getContactPoints() {
		return $this->contactPoints;
	}

	public function getThemes() {
		return $this->themes;
	}
	public function addTheme($theme) {
		$this->themes[] = $theme;
	}
	public function removeTheme($theme) {
		if(($key = array_search($theme, $this->getThemes())) !== false) {
			unset($this->themes[$key]);
		}
	}

	public function getLanguages() {
		return $this->language;
	}
	public function addLanguage($language) {
		$this->languages[] = $language;
	}
	public function removeLanguage($language) {
		if(($key = array_search($language, $this->getLanguages())) !== false) {
			unset($this->languages[$key]);
		}
	}

	public function getKeywords() {
		return $this->keywords;
	}
	public function addKeyword($keyword) {
		$this->keywords[] = $keyword;
	}
	public function removeKeyword($keyword) {
		if(($key = array_search($keyword, $this->getKeywords())) !== false) {
			unset($this->keywords[$key]);
		}
	}

	public function addRelation($relation) {
		if(! $relation instanceof Ckan_Backend_Relation_Model) {
			throw new RuntimeException( 'Relation has to be a Ckan_Backend_Relation_Model type' );
		}
		$this->relations[spl_object_hash($relation)] = $relation;
	}
	public function removeRelation($relation) {
		if(! $relation instanceof Ckan_Backend_Relation_Model) {
			throw new RuntimeException( 'Relation has to be a Ckan_Backend_Relation_Model type' );
		}
		unset($this->relations[spl_object_hash($relation)]);
	}
	public function getRelations() {
		return $this->relations;
	}

	public function getLandingPage() {
		return $this->landingPage;
	}
	public function setLandingPage($landingPage) {
		$this->landingPage = $landingPage;
	}

	public function getSpatial() {
		return $this->spatial;
	}
	public function setSpatial($spatial) {
		$this->spatial = $spatial;
	}

	public function getCoverage() {
		return $this->coverage;
	}
	public function setCoverage($coverage) {
		$this->coverage = $coverage;
	}

	public function addTemporal($temporal) {
		if(! $temporal instanceof Ckan_Backend_Temporal_Model) {
			throw new RuntimeException( 'Relation has to be a Ckan_Backend_Temporal_Model type' );
		}
		$this->temporals[spl_object_hash($temporal)] = $temporal;
	}
	public function removeTemporal($temporal) {
		if(! $temporal instanceof Ckan_Backend_Temporal_Model) {
			throw new RuntimeException( 'Relation has to be a Ckan_Backend_Temporal_Model type' );
		}
		unset($this->temporals[spl_object_hash($temporal)]);
	}
	public function getTemporals() {
		return $this->temporals;
	}

	public function getAccrualPeriodicy() {
		return $this->accrualPeriodicy;
	}
	public function setAccrualPeriodicy($accrualPeriodicy) {
		$this->accrualPeriodicy = $accrualPeriodicy;
	}

	public function addSeeAlso($seeAlso) {
		if(! $seeAlso instanceof Ckan_Backend_SeeAlso_Model) {
			throw new RuntimeException( 'Relation has to be a Ckan_Backend_SeeAlso_Model type' );
		}
		$this->seeAlsos[spl_object_hash($seeAlso)] = $seeAlso;
	}
	public function removeSeeAlso($seeAlso) {
		if(! $seeAlso instanceof Ckan_Backend_SeeAlso_Model) {
			throw new RuntimeException( 'Relation has to be a Ckan_Backend_SeeAlso_Model type' );
		}
		unset($this->seeAlsos[spl_object_hash($seeAlso)]);
	}
	public function getSeeAlsos() {
		return $this->seeAlsos;
	}

	public function addDistribution($distribution) {
		if(! $distribution instanceof Ckan_Backend_Distribution_Model) {
			throw new RuntimeException( 'Relation has to be a Ckan_Backend_Distribution_Model type' );
		}
		$this->distributions[spl_object_hash($distribution)] = $distribution;
	}
	public function removeDistribution($distribution) {
		if(! $distribution instanceof Ckan_Backend_Distribution_Model) {
			throw new RuntimeException( 'Relation has to be a Ckan_Backend_Distribution_Model type' );
		}
		unset($this->distributions[spl_object_hash($distribution)]);
	}
	public function getDistributions() {
		return $this->distributions;
	}

	public function toArray() {
		global $langauge_priority;

		$dataset = array(
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'identifier' => $this->getIdentifier(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'issued' => $this->getIssued(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'modified' => $this->getModified(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'themes' => $this->getThemes(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'languages' => $this->getLanguages(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'keywords' => $this->getKeywords(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'landing_page' => $this->getLandingPage(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'spatial' => $this->getSpatial(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'coverage' => $this->getCoverage(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'accrual_periodicy' => $this->getAccrualPeriodicy()
		);

		foreach($langauge_priority as $lang) {
			$dataset[Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'title_' . $lang] = $this->getTitle($lang);
			$dataset[Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'description_' . $lang] = $this->getDescription($lang);
		}

		foreach($this->getPublishers() as $publisher) {
			$dataset[Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'publishers'][] = $publisher->toArray();
		}
		foreach($this->getContactPoints() as $contact_point) {
			$dataset[Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'contact_points'][] = $contact_point->toArray();
		}
		foreach($this->getRelations() as $relation) {
			$dataset[Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'relations'][] = $relation->toArray();
		}
		foreach($this->getTemporals() as $temporal) {
			$dataset[Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'temporals'][] = $temporal->toArray();
		}
		foreach($this->getSeeAlsos() as $see_also) {
			$dataset[Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'see_alsos'][] = $see_also->toArray();
		}
		foreach($this->getDistributions() as $distribution) {
			$dataset[Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'distributions'][] = $distribution->toArray();
		}

		return $dataset;
	}
}