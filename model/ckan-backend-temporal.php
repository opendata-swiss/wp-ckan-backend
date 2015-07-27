<?php

class Ckan_Backend_Temporal_Model {
	protected $startDate = '';
	protected $endDate = '';

	public function getStartDate() {
		return $this->startDate;
	}
	public function setStartDate($startDate) {
		$this->startDate = $startDate;
	}

	public function getEndDate() {
		return $this->endDate;
	}
	public function setEndDate($endDate) {
		$this->endDate = $endDate;
	}
}