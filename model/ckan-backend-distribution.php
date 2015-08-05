<?php
/**
 * Model for Distribution
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Distribution_Model
 */
class Ckan_Backend_Distribution_Model {
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
	 * All languages
	 *
	 * @var string[]
	 */
	protected $languages = array();

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
	 * All Access URLs
	 *
	 * @var string[]
	 */
	protected $access_urls = array();

	/**
	 * Right reference
	 *
	 * @var string
	 */
	protected $right_reference = '';

	/**
	 * Right non-commercial
	 *
	 * @var string
	 */
	protected $right_non_commercial = '';

	/**
	 * Right commercial
	 *
	 * @var string
	 */
	protected $right_commercial = '';

	/**
	 * License
	 *
	 * @var string
	 */
	protected $license = '';

	/**
	 * All Download URLs
	 *
	 * @var string[]
	 */
	protected $download_urls = array();

	/**
	 * Byte size
	 *
	 * @var int
	 */
	protected $byte_size = 0;

	/**
	 * Media type
	 *
	 * @var string
	 */
	protected $media_type = '';

	/**
	 * Format
	 *
	 * @var string
	 */
	protected $format = '';

	/**
	 * Coverage
	 *
	 * @var string
	 */
	protected $coverage = '';

	/**
	 * Returns identifier
	 *
	 * @return string
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * Sets identifier
	 *
	 * @param string $identifier Identifier.
	 */
	public function set_identifier( $identifier ) {
		$this->identifier = $identifier;
	}

	/**
	 * Returns title in given language
	 *
	 * @param string $lang Language.
	 *
	 * @return string
	 */
	public function get_title( $lang = 'en' ) {
		return $this->title[ $lang ];
	}

	/**
	 * Sets title for given language
	 *
	 * @param string $title Title.
	 * @param string $lang Language.
	 */
	public function set_title( $title, $lang = 'en' ) {
		$this->title[ $lang ] = $title;
	}

	/**
	 * Returns description in given language
	 *
	 * @param string $lang Language.
	 *
	 * @return string
	 */
	public function get_description( $lang = 'en' ) {
		return $this->description[ $lang ];
	}

	/**
	 * Sets description for given language
	 *
	 * @param string $description Description.
	 * @param string $lang Language.
	 */
	public function set_description( $description, $lang = 'en' ) {
		$this->description[ $lang ] = $description;
	}

	/**
	 * Returns all languages
	 *
	 * @return string[]
	 */
	public function get_languages() {
		return $this->languages;
	}

	/**
	 * Adds language
	 *
	 * @param string $language Language.
	 */
	public function add_language( $language ) {
		$this->languages[] = $language;
	}

	/**
	 * Removes language
	 *
	 * @param string $language Language.
	 */
	public function remove_language( $language ) {
		if ( ( $key = array_search( $language, $this->get_languages() ) ) !== false ) {
			unset( $this->languages[ $key ] );
		}
	}

	/**
	 * Returns issued
	 *
	 * @return string
	 */
	public function get_issued() {
		return $this->issued;
	}

	/**
	 * Sets issued
	 *
	 * @param string $issued Issued.
	 */
	public function set_issued( $issued ) {
		$this->issued = $issued;
	}

	/**
	 * Returns modified
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
	 * Returns all access urls
	 *
	 * @return string[]
	 */
	public function get_access_urls() {
		return $this->access_urls;
	}

	/**
	 * Adds access url
	 *
	 * @param string $access_url Access url to add.
	 */
	public function add_access_url( $access_url ) {
		$this->access_urls[] = $access_url;
	}

	/**
	 * Removes access url
	 *
	 * @param string $access_url Access url to remove.
	 */
	public function remove_access_url( $access_url ) {
		if ( ( $key = array_search( $access_url, $this->get_access_urls() ) ) !== false ) {
			unset( $this->access_urls[ $key ] );
		}
	}

	/**
	 * Returns right reference
	 *
	 * @return string
	 */
	public function get_right_reference() {
		return $this->right_reference;
	}

	/**
	 * Sets right reference
	 *
	 * @param string $right_reference Right reference.
	 */
	public function set_right_reference( $right_reference ) {
		$this->right_reference = $right_reference;
	}

	/**
	 * Returns right non commercial
	 *
	 * @return string
	 */
	public function get_right_non_commercial() {
		return $this->right_non_commercial;
	}

	/**
	 * Sets right non commercial
	 *
	 * @param string $right_non_commercial Right non commercial.
	 */
	public function set_right_non_commercial( $right_non_commercial ) {
		$this->right_non_commercial = $right_non_commercial;
	}

	/**
	 * Returns right commercial
	 *
	 * @return string
	 */
	public function get_right_commercial() {
		return $this->right_commercial;
	}

	/**
	 * Sets right commercial
	 *
	 * @param string $right_commercial Right commercial.
	 */
	public function set_right_commercial( $right_commercial ) {
		$this->right_commercial = $right_commercial;
	}

	/**
	 * Returns license
	 *
	 * @return string
	 */
	public function get_license() {
		return $this->license;
	}

	/**
	 * Sets license
	 *
	 * @param string $license License.
	 */
	public function set_license( $license ) {
		$this->license = $license;
	}

	/**
	 * Returns all download urls
	 *
	 * @return string[]
	 */
	public function get_download_urls() {
		return $this->download_urls;
	}

	/**
	 * Adds download url
	 *
	 * @param string $download_url Download url to add.
	 */
	public function add_download_url( $download_url ) {
		$this->download_urls[] = $download_url;
	}

	/**
	 * Removes download url
	 *
	 * @param string $download_url Download url to remove.
	 */
	public function remove_download_url( $download_url ) {
		if ( ( $key = array_search( $download_url, $this->get_download_urls() ) ) !== false ) {
			unset( $this->download_urls[ $key ] );
		}
	}

	/**
	 * Returns byte size
	 *
	 * @return int
	 */
	public function get_byte_size() {
		return $this->byte_size;
	}

	/**
	 * Sets byte size
	 *
	 * @param int $byte_size Byte size.
	 */
	public function set_byte_size( $byte_size ) {
		$this->byte_size = $byte_size;
	}

	/**
	 * Returns media type
	 *
	 * @return string
	 */
	public function get_media_type() {
		return $this->media_type;
	}

	/**
	 * Sets media type
	 *
	 * @param string $media_type Media type.
	 */
	public function set_media_type( $media_type ) {
		$this->media_type = $media_type;
	}

	/**
	 * Returns format
	 *
	 * @return string
	 */
	public function get_format() {
		return $this->format;
	}

	/**
	 * Sets format
	 *
	 * @param string $format Format.
	 */
	public function set_format( $format ) {
		$this->format = $format;
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
	 * Converts object to array
	 *
	 * @return array
	 */
	public function to_array() {
		global $language_priority;

		$distribution = array(
			'identifier'           => $this->get_identifier(),
			'languages'            => $this->get_languages(),
			'issued'               => $this->get_issued(),
			'modified'             => $this->get_modified(),
			'access_urls'          => $this->get_access_urls(),
			'download_urls'        => $this->get_download_urls(),
			'right_reference'      => $this->get_right_reference(),
			'right_non_commercial' => $this->get_right_non_commercial(),
			'right_commercial'     => $this->get_right_commercial(),
			'license'              => $this->get_license(),
			'byte_size'            => $this->get_byte_size(),
			'media_type'           => $this->get_media_type(),
			'format'               => $this->get_format(),
			'coverage'             => $this->get_coverage(),
		);

		foreach ( $language_priority as $lang ) {
			$distribution[ 'title_' . $lang ]       = $this->get_title( $lang );
			$distribution[ 'description_' . $lang ] = $this->get_description( $lang );
		}

		// TODO remove these lines when better backend gui solution is found
		$access_urls                  = $this->get_access_urls();
		$distribution['access_url']   = reset( $access_urls );
		$download_urls                = $this->get_download_urls();
		$distribution['download_url'] = reset( $download_urls );

		return $distribution;
	}
}
