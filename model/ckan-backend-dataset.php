<?php
/**
 * Model for Dataset
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Dataset_Model
 */
class Ckan_Backend_Dataset_Model {
	/**
	 * Identifier
	 *
	 * @var string
	 */
	protected $identifier = '';

	/**
	 * Title in all languages
	 *
	 * @var string[]
	 */
	protected $title = array();

	/**
	 * Description in all languages
	 *
	 * @var string[]
	 */
	protected $description = array();

	/**
	 * Issued
	 *
	 * @var string
	 */
	protected $issued = '';

	/**
	 * Modified
	 *
	 * @var string
	 */
	protected $modified = '';

	/**
	 * Publisher
	 *
	 * @var string
	 */
	protected $publisher = '';

	/**
	 * Contact Points
	 *
	 * @var Ckan_Backend_ContactPoint_Model[]
	 */
	protected $contact_points = array();

	/**
	 * Themes
	 *
	 * @var string[]
	 */
	protected $themes = array();

	/**
	 * Keywords
	 *
	 * @var string[]
	 */
	protected $keywords = array();

	/**
	 * Relations
	 *
	 * @var Ckan_Backend_Relation_Model[]
	 */
	protected $relations = array();

	/**
	 * Landing Page
	 *
	 * @var string
	 */
	protected $landing_page = '';

	/**
	 * Spatial
	 *
	 * @var string
	 */
	protected $spatial = '';

	/**
	 * Coverage
	 *
	 * @var string
	 */
	protected $coverage = '';

	/**
	 * Temporals
	 *
	 * @var Ckan_Backend_Temporal_Model[]
	 */
	protected $temporals = array();

	/**
	 * Accrual Periodicity
	 *
	 * @var string
	 */
	protected $accrual_periodicity = '';

	/**
	 * See Alsos
	 *
	 * @var Ckan_Backend_SeeAlso_Model[]
	 */
	protected $see_alsos = array();

	/**
	 * Distributions
	 *
	 * @var Ckan_Backend_Distribution_Model[]
	 */
	protected $distributions = array();

	/**
	 * Returns Identifier
	 *
	 * @return string
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * Sets Identifier
	 *
	 * @param string $identifier identifier.
	 */
	public function set_identifier( $identifier ) {
		$this->identifier = $identifier;
	}

	/**
	 * Returns Title in given language
	 *
	 * @param string $lang Language.
	 *
	 * @return string
	 */
	public function get_title( $lang = 'en' ) {
		return $this->title[ $lang ];
	}

	/**
	 * Sets Title in given language
	 *
	 * @param string $title Title.
	 * @param string $lang Language.
	 */
	public function set_title( $title, $lang = 'en' ) {
		$this->title[ $lang ] = $title;
	}

	/**
	 * Returns Description in given language
	 *
	 * @param string $lang Language.
	 *
	 * @return string
	 */
	public function get_description( $lang = 'en' ) {
		return $this->description[ $lang ];
	}

	/**
	 * Sets Description in given language
	 *
	 * @param string $description Description.
	 * @param string $lang Language.
	 */
	public function set_description( $description, $lang = 'en' ) {
		$this->description[ $lang ] = $description;
	}

	/**
	 * Returns Issued
	 *
	 * @return string
	 */
	public function get_issued() {
		return $this->issued;
	}

	/**
	 * Sets Issued
	 *
	 * @param string $issued Issued.
	 */
	public function set_issued( $issued ) {
		$this->issued = $issued;
	}

	/**
	 * Returns Modified
	 *
	 * @return string
	 */
	public function get_modified() {
		return $this->modified;
	}

	/**
	 * Sets modified
	 *
	 * @param string $modified Modified.
	 */
	public function set_modified( $modified ) {
		$this->modified = $modified;
	}

	/**
	 * Returns publisher
	 *
	 * @return string
	 */
	public function get_publisher() {
		return $this->publisher;
	}

	/**
	 * Sets publisher
	 *
	 * @param string $publisher Publisher.
	 */
	public function set_publisher( $publisher ) {
		$this->publisher = $publisher;
	}

	/**
	 * Adds a ContactPoint
	 *
	 * @param Ckan_Backend_ContactPoint_Model $contact_point ContactPoint to add.
	 *
	 * @return bool|WP_Error
	 */
	public function add_contact_point( $contact_point ) {
		if ( ! $contact_point instanceof Ckan_Backend_ContactPoint_Model ) {
			return new WP_Error( 'wrong_type', 'Contact Point has to be a Ckan_Backend_ContactPoint_Model type' );
		}
		$this->contact_points[ spl_object_hash( $contact_point ) ] = $contact_point;

		return true;
	}

	/**
	 * Removes ContactPoint
	 *
	 * @param Ckan_Backend_ContactPoint_Model $contact_point ContactPoint to remove.
	 *
	 * @return bool|WP_Error
	 */
	public function remove_contact_point( $contact_point ) {
		if ( ! $contact_point instanceof Ckan_Backend_ContactPoint_Model ) {
			return new WP_Error( 'wrong_type', 'Contact Point has to be a Ckan_Backend_ContactPoint_Model type' );
		}
		unset( $this->contact_points[ spl_object_hash( $contact_point ) ] );

		return true;
	}

	/**
	 * Returns all ContactPoints
	 *
	 * @return Ckan_Backend_ContactPoint_Model[]
	 */
	public function get_contact_points() {
		return $this->contact_points;
	}

	/**
	 * Returns all themes
	 *
	 * @return string[]
	 */
	public function get_themes() {
		return $this->themes;
	}

	/**
	 * Adds theme
	 *
	 * @param string $theme Theme to add.
	 */
	public function add_theme( $theme ) {
		$this->themes[] = $theme;
	}

	/**
	 * Removes theme
	 *
	 * @param string $theme Theme to remove.
	 */
	public function remove_theme( $theme ) {
		if ( ( $key = array_search( $theme, $this->get_themes() ) ) !== false ) {
			unset( $this->themes[ $key ] );
		}
	}

	/**
	 * Returns all Keywords
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return $this->keywords;
	}

	/**
	 * Adds keyword
	 *
	 * @param string $keyword Keyword to add.
	 */
	public function add_keyword( $keyword ) {
		$this->keywords[] = $keyword;
	}

	/**
	 * Removes keyword
	 *
	 * @param string $keyword Keyword to remove.
	 */
	public function remove_keyword( $keyword ) {
		if ( ( $key = array_search( $keyword, $this->get_keywords() ) ) !== false ) {
			unset( $this->keywords[ $key ] );
		}
	}

	/**
	 * Adds relation
	 *
	 * @param Ckan_Backend_Relation_Model $relation Relation to add.
	 *
	 * @return bool|WP_Error
	 */
	public function add_relation( $relation ) {
		if ( ! $relation instanceof Ckan_Backend_Relation_Model ) {
			return new WP_Error( 'wrong_type', 'Relation has to be a Ckan_Backend_Relation_Model type' );
		}
		$this->relations[ spl_object_hash( $relation ) ] = $relation;

		return true;
	}

	/**
	 * Removes relation
	 *
	 * @param Ckan_Backend_Relation_Model $relation Relation to remove.
	 *
	 * @return bool|WP_Error
	 */
	public function remove_relation( $relation ) {
		if ( ! $relation instanceof Ckan_Backend_Relation_Model ) {
			return new WP_Error( 'wrong_type', 'Relation has to be a Ckan_Backend_Relation_Model type' );
		}
		unset( $this->relations[ spl_object_hash( $relation ) ] );

		return true;
	}

	/**
	 * Returns all relations
	 *
	 * @return Ckan_Backend_Relation_Model[]
	 */
	public function get_relations() {
		return $this->relations;
	}

	/**
	 * Returns landing page
	 *
	 * @return string
	 */
	public function get_landing_page() {
		return $this->landing_page;
	}

	/**
	 * Sets landing page
	 *
	 * @param string $landing_page Landing page.
	 */
	public function set_landing_page( $landing_page ) {
		$this->landing_page = $landing_page;
	}

	/**
	 * Returns spatial
	 *
	 * @return string
	 */
	public function get_spatial() {
		return $this->spatial;
	}

	/**
	 * Sets spatial
	 *
	 * @param string $spatial Spatial.
	 */
	public function set_spatial( $spatial ) {
		$this->spatial = $spatial;
	}

	/**
	 * Returns coverage
	 *
	 * @return string
	 */
	public function get_coverage() {
		return $this->coverage;
	}

	/**
	 * Sets coverage
	 *
	 * @param string $coverage Coverage.
	 */
	public function set_coverage( $coverage ) {
		$this->coverage = $coverage;
	}

	/**
	 * Adds temporal
	 *
	 * @param Ckan_Backend_Temporal_Model $temporal Temporal to add.
	 *
	 * @return bool|WP_Error
	 */
	public function add_temporal( $temporal ) {
		if ( ! $temporal instanceof Ckan_Backend_Temporal_Model ) {
			return new WP_Error( 'wrong_type', 'Relation has to be a Ckan_Backend_Temporal_Model type' );
		}
		$this->temporals[ spl_object_hash( $temporal ) ] = $temporal;

		return true;
	}

	/**
	 * Removes temporal
	 *
	 * @param Ckan_Backend_Temporal_Model $temporal Temporal to remove.
	 *
	 * @return bool|WP_Error
	 */
	public function remove_temporal( $temporal ) {
		if ( ! $temporal instanceof Ckan_Backend_Temporal_Model ) {
			return new WP_Error( 'wrong_type', 'Relation has to be a Ckan_Backend_Temporal_Model type' );
		}
		unset( $this->temporals[ spl_object_hash( $temporal ) ] );

		return true;
	}

	/**
	 * Returns all temporals
	 *
	 * @return Ckan_Backend_Temporal_Model[]
	 */
	public function get_temporals() {
		return $this->temporals;
	}

	/**
	 * Returns accrual periodicity
	 *
	 * @return string
	 */
	public function get_accrual_periodicity() {
		return $this->accrual_periodicity;
	}

	/**
	 * Sets accrual periodicity
	 *
	 * @param string $accrual_periodicity Accrual periodicity.
	 */
	public function set_accrual_periodicity( $accrual_periodicity ) {
		$this->accrual_periodicity = $accrual_periodicity;
	}

	/**
	 * Adds SeeAlso
	 *
	 * @param Ckan_Backend_SeeAlso_Model $see_also SeeAlso to add.
	 *
	 * @return bool|WP_Error
	 */
	public function add_see_also( $see_also ) {
		if ( ! $see_also instanceof Ckan_Backend_SeeAlso_Model ) {
			return new WP_Error( 'wrong_type', 'Relation has to be a Ckan_Backend_SeeAlso_Model type' );
		}
		$this->see_alsos[ spl_object_hash( $see_also ) ] = $see_also;

		return true;
	}

	/**
	 * Removes SeeAlso
	 *
	 * @param Ckan_Backend_SeeAlso_Model $see_also SeeAlso to remove.
	 *
	 * @return bool|WP_Error
	 */
	public function remove_see_also( $see_also ) {
		if ( ! $see_also instanceof Ckan_Backend_SeeAlso_Model ) {
			return new WP_Error( 'wrong_type', 'Relation has to be a Ckan_Backend_SeeAlso_Model type' );
		}
		unset( $this->see_alsos[ spl_object_hash( $see_also ) ] );

		return true;
	}

	/**
	 * Returns all SeeAlsos
	 *
	 * @return Ckan_Backend_SeeAlso_Model[]
	 */
	public function get_see_alsos() {
		return $this->see_alsos;
	}

	/**
	 * Adds Distribution
	 *
	 * @param Ckan_Backend_Distribution_Model $distribution Distribution to add.
	 *
	 * @return bool|WP_Error
	 */
	public function add_distribution( $distribution ) {
		if ( ! $distribution instanceof Ckan_Backend_Distribution_Model ) {
			return new WP_Error( 'wrong_type', 'Relation has to be a Ckan_Backend_Distribution_Model type' );
		}
		$this->distributions[ spl_object_hash( $distribution ) ] = $distribution;

		return true;
	}

	/**
	 * Removes distribution
	 *
	 * @param Ckan_Backend_Distribution_Model $distribution Distribution to remove.
	 *
	 * @return bool|WP_Error
	 */
	public function remove_distribution( $distribution ) {
		if ( ! $distribution instanceof Ckan_Backend_Distribution_Model ) {
			return new WP_Error( 'wrong_type', 'Relation has to be a Ckan_Backend_Distribution_Model type' );
		}
		unset( $this->distributions[ spl_object_hash( $distribution ) ] );

		return true;
	}

	/**
	 * Returns all distributions
	 *
	 * @return Ckan_Backend_Distribution_Model[]
	 */
	public function get_distributions() {
		return $this->distributions;
	}

	/**
	 * Converts object to array
	 *
	 * @return array
	 */
	public function to_array() {
		global $language_priority;

		$dataset = array(
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'identifier'        => $this->get_identifier(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'issued'            => $this->get_issued(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'modified'          => $this->get_modified(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'publisher'         => $this->get_publisher(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'themes'            => $this->get_themes(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'keywords'          => $this->get_keywords(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'landing_page'      => $this->get_landing_page(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'spatial'           => $this->get_spatial(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'coverage'          => $this->get_coverage(),
			Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'accrual_periodicy' => $this->get_accrual_periodicity(),
		);

		foreach ( $language_priority as $lang ) {
			$dataset[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'title_' . $lang ]       = $this->get_title( $lang );
			$dataset[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'description_' . $lang ] = $this->get_description( $lang );
		}

		foreach ( $this->get_contact_points() as $contact_point ) {
			$dataset[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'contact_points' ][] = $contact_point->to_array();
		}
		foreach ( $this->get_relations() as $relation ) {
			$dataset[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'relations' ][] = $relation->to_array();
		}
		foreach ( $this->get_temporals() as $temporal ) {
			$dataset[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'temporals' ][] = $temporal->to_array();
		}
		foreach ( $this->get_see_alsos() as $see_also ) {
			$dataset[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'see_alsos' ][] = $see_also->to_array();
		}
		foreach ( $this->get_distributions() as $distribution ) {
			$dataset[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'distributions' ][] = $distribution->to_array();
		}

		return $dataset;
	}
}
