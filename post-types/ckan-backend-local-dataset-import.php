<?php
/**
 * Post type ckan-local-dataset-import-page
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Local_Dataset_Import
 */
class Ckan_Backend_Local_Dataset_Import {

	/**
	 * Menu slug.
	 * @var string
	 */
	public $menu_slug = 'ckan-local-dataset-import-page';

	/**
	 * Constructor of this class.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );
	}

	/**
	 * Register a submenu page.
	 *
	 * @return void
	 */
	public function register_submenu_page() {
		add_submenu_page(
			'edit.php?post_type=' . Ckan_Backend_Local_Dataset::POST_TYPE,
			__( 'Import CKAN Dataset', 'ogdch' ),
			__( 'Import', 'ogdch' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'import_page_callback' )
		);
	}

	/**
	 * Callback for the import of a file.
	 *
	 * @return void
	 */
	public function import_page_callback() {
		// must check that the user has the required capability
		if ( ! current_user_can( 'create_datasets' ) ) {
			wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.' ) ) );
		}

		$import_submit_hidden_field_name = 'ckan_local_dataset_import_submit';
		$file_field_name                 = 'ckan_local_dataset_import_file';

		// Handle import
		if ( isset( $_POST[ $import_submit_hidden_field_name ] ) && 'Y' === $_POST[ $import_submit_hidden_field_name ] ) {
			$dataset_id = false;
			if ( isset( $_FILES[ $file_field_name ] ) ) {
				$dataset_id = $this->handle_file_import( $_FILES[ $file_field_name ] );
			}

			if ( $dataset_id > 0 ) {
				echo '<div class="updated"><p><strong>' . esc_html( __( 'Import successful', 'ogdch' ) ) . '</strong></p></div>';
				// @codingStandardsIgnoreStart
				printf( __( 'Click <a href="%s">here</a> to see the imported dataset.', 'ogdch' ), esc_url( admin_url( 'post.php?post=' . esc_attr( $dataset_id ) . '&action=edit' ) ) );
				// @codingStandardsIgnoreEnd
			}
		} ?>
		<div class="wrap">
			<h2><?php esc_html_e( __( 'Import CKAN Dataset', 'ogdch' ) ); ?></h2>

			<form enctype="multipart/form-data" action="" method="POST">
				<input type="hidden" name="<?php esc_attr_e( $import_submit_hidden_field_name ); ?>" value="Y">

				<p><?php esc_html_e( __( 'File:', 'ogdch' ) ); ?>
					<input type="file" name="<?php esc_attr_e( $file_field_name ); ?>" value="" size="20">
				</p>
				<hr/>

				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Import' ) ?>"/>
				</p>
			</form>
		</div>

		<?php
	}

	/**
	 * Handle to uploaded file.
	 *
	 * @param array $file Array with the information of the uploaded file.
	 *
	 * @return bool|int|WP_Error
	 *
	 * @throws RuntimeException If the file cannot be processed.
	 */
	public function handle_file_import( $file ) {
		try {
			// Undefined | Multiple Files | $_FILES Corruption Attack
			// If this request falls under any of them, treat it invalid.
			if (
				! isset( $file['error'] ) ||
				is_array( $file['error'] )
			) {
				throw new RuntimeException( 'Invalid parameters.' );
			}

			// Check $file['error'] value.
			switch ( $file['error'] ) {
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_NO_FILE:
					throw new RuntimeException( 'No file sent.' );
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					throw new RuntimeException( 'Exceeded filesize limit.' );
				default:
					throw new RuntimeException( 'Unknown errors.' );
			}

			$xml = simplexml_load_file( $file['tmp_name'] );
			if ( ! is_object( $xml ) ) {
				throw new RuntimeException( 'Uploaded file is not a vaild XML file' );
			}
			$xml->registerXPathNamespace( 'dcat', 'http://www.w3.org/ns/dcat#' );
			$xml->registerXPathNamespace( 'dct', 'http://purl.org/dc/terms/' );
			$xml->registerXPathNamespace( 'dc', 'http://purl.org/dc/elements/1.1/' );
			$xml->registerXPathNamespace( 'foaf', 'http://xmlns.com/foaf/0.1/' );
			$xml->registerXPathNamespace( 'rdfs', 'http://www.w3.org/2000/01/rdf-schema#' );
			$xml->registerXPathNamespace( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
			$xml->registerXPathNamespace( 'vcard', 'http://www.w3.org/2006/vcard/ns#' );
			$xml->registerXPathNamespace( 'odrs', 'http://schema.theodi.org/odrs#' );
			$xml->registerXPathNamespace( 'schema', 'http://schema.org/' );

			return $this->import_dataset( $xml );
		} catch ( RuntimeException $e ) {
			esc_html_e( $e->getMessage() );
		}
	}

	/**
	 * Imports a dataset from a given XML
	 *
	 * @param SimpleXMLElement $xml The XML to be imported.
	 *
	 * @return bool|int|WP_Error
	 */
	public function import_dataset( $xml ) {
		$dataset = $this->get_dataset_object( $xml );

		foreach ( $dataset->getThemes() as $group ) {
			if ( ! Ckan_Backend_Helper::group_exists( $group ) ) {
				echo '<div class="error"><p>';
				printf( __( 'Group %1$s does not exist! Import aborted.', 'ogdch' ), $group );
				echo '</p></div>';

				return false;
			}
		}

		$publishers = $dataset->getPublishers();
		if ( count( $publishers ) > 0 ) {
			// use only first element
			$publisher = reset( $publishers );
			if ( ! Ckan_Backend_Helper::organisation_exists( $publisher->getName() ) ) {
				echo '<div class="error"><p>';
				printf( __( 'Organisation %1$s does not exist! Import aborted.', 'ogdch' ), $publisher->getName() );
				echo '</p></div>';

				return false;
			}
		}

		// simulate $_POST data to make post_save hook work correctly
		$_POST = array_merge( $_POST, $dataset->toArray() );
		// add native WordPress $_POST variables
		$_POST['tax_input']['post_tag'] = implode( ', ', $dataset->getKeywords() );
		$_POST['post_title']            = $dataset->getTitle( 'en' );

		$dataset_search_args = array(
			'meta_key'    => Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'identifier',
			'meta_value'  => $dataset->getIdentifier(),
			'post_type'   => Ckan_Backend_Local_Dataset::POST_TYPE,
			'post_status' => 'any',
		);
		$datasets            = get_posts( $dataset_search_args );

		if ( count( $datasets ) > 0 ) {
			// Dataset already exists -> update
			$dataset_id = $datasets[0]->ID;
			$this->update( $dataset_id, $dataset );
		} else {
			// Create new dataset
			$dataset_id = $this->insert( $dataset );
		}

		return $dataset_id;
	}

	/**
	 * Updates an existing dataset
	 *
	 * @param int $dataset_id
	 * @param Ckan_Backend_Dataset_Model $dataset
	 */
	protected function update( $dataset_id, $dataset ) {
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'disabled' ]  = get_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'disabled', true );
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'reference' ] = get_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'reference', true );

		$dataset_args = array(
			'ID'         => $dataset_id,
			'post_title' => $dataset->getTitle( 'en' ),
		);

		wp_update_post( $dataset_args );
		foreach ( $dataset->toArray() as $field => $value ) {
			update_post_meta( $dataset_id, $field, $value );
		}
		wp_set_object_terms( $dataset_id, $dataset->getKeywords(), 'post_tag' );
	}

	/**
	 * Inserts a new dataset
	 *
	 * @param Ckan_Backend_Dataset_Model $dataset
	 *
	 * @return bool|int|WP_Error
	 */
	protected function insert( $dataset ) {
		$dataset_args = array(
			'post_title'   => $dataset->getTitle( 'en' ),
			'post_status'  => 'publish',
			'post_type'    => Ckan_Backend_Local_Dataset::POST_TYPE,
			'post_excerpt' => '',
		);

		$dataset_id = wp_insert_post( $dataset_args );
		foreach ( $dataset->toArray() as $field => $value ) {
			add_post_meta( $dataset_id, $field, $value, true );
		}
		wp_set_object_terms( $dataset_id, $dataset->getKeywords(), 'post_tag' );

		return $dataset_id;
	}

	/**
	 * Returns a dataset object from given xml
	 *
	 * @param SimpleXMLElement $xml
	 *
	 * @return Ckan_Backend_Dataset_Model
	 */
	protected function get_dataset_object( $xml ) {
		global $language_priority;

		$dataset = new Ckan_Backend_Dataset_Model();
		$dataset->setIdentifier( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:identifier' ) );
		foreach ( $language_priority as $lang ) {
			$dataset->setTitle( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:title[@xml:lang="' . $lang . '"]' ), $lang );
			$dataset->setDescription( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:description[@xml:lang="' . $lang . '"]' ), $lang );
		}
		$dataset->setIssued( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:issued' ) );
		$dataset->setModified( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:modified' ) );

		$publishers = $xml->xpath( '//dcat:Dataset/dct:publisher/foaf:Organization' );
		foreach ( $publishers as $publisher_xml ) {
			$dataset->addPublisher( $this->get_publisher_object( $publisher_xml ) );
		}
		$contact_points = $xml->xpath( '//dcat:Dataset/dcat:contactPoint/vcard:Organization' );
		foreach ( $contact_points as $contact_point_xml ) {
			$dataset->addContactPoint( $this->get_contact_point_object( $contact_point_xml ) );
		}
		$themes = $xml->xpath( '//dcat:Dataset/dcat:theme' );
		foreach ( $themes as $theme ) {
			$dataset->addTheme( (string) $theme );
		}
		$languages = $xml->xpath( '//dcat:Dataset/dct:language' );
		foreach ( $languages as $language ) {
			$dataset->addLanguage( (string) $language );
		}
		$relations = $xml->xpath( '//dcat:Dataset/dct:relation/rdf:Description' );
		foreach ( $relations as $relation_xml ) {
			$dataset->addRelation( $this->get_relation_object( $relation_xml ) );
		}
		$keywords = $xml->xpath( '//dcat:Dataset/dcat:keyword' );
		foreach ( $keywords as $keyword ) {
			$dataset->addKeyword( (string) $keyword );
		}
		$dataset->setLandingPage( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dcat:landingPage' ) );
		$spatial_element    = $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:spatial' );
		$spatial_attributes = $spatial_element->attributes( 'rdf', true );
		$dataset->setSpatial( (string) $spatial_attributes['resource'] );
		$dataset->setCoverage( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:coverage' ) );
		$temporals = $xml->xpath( '//dcat:Dataset/dct:temporal/dct:PeriodOfTime' );
		foreach ( $temporals as $temporal_xml ) {
			$dataset->addTemporal( $this->get_temporal_object( $temporal_xml ) );
		}
		$accrual_periodicity_element    = $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:accrualPeriodicity' );
		$accrual_periodicity_attributes = $accrual_periodicity_element->attributes( 'rdf', true );
		$dataset->setAccrualPeriodicy( (string) $accrual_periodicity_attributes['resource'] );
		$see_alsos = $xml->xpath( '//dcat:Dataset/rdfs:seeAlso/rdf:Description' );
		foreach ( $see_alsos as $see_also_xml ) {
			$dataset->addSeeAlso( $this->get_see_also_object( $see_also_xml ) );
		}

		$distributions = $xml->xpath( '//dcat:Dataset/dcat:distribution' );
		foreach ( $distributions as $distribution_xml ) {
			$dataset->addDistribution( $this->get_distribution_object( $distribution_xml ) );
		}

		return $dataset;
	}

	/**
	 * Returns a publisher object from given xml
	 *
	 * @param SimpleXMLElement $xml
	 *
	 * @return Ckan_Backend_Publisher_Model
	 */
	protected function get_publisher_object( $xml ) {
		$publisher = new Ckan_Backend_Publisher_Model();
		$publisher->setName( (string) $this->get_single_element_from_xpath( $xml, 'foaf:name' ) );
		$publisher->setMbox( (string) $this->get_single_element_from_xpath( $xml, 'foaf:mbox' ) );

		return $publisher;
	}

	/**
	 * Returns a ContactPoint object from given xml
	 *
	 * @param SimpleXMLElement $xml
	 *
	 * @return Ckan_Backend_ContactPoint_Model
	 */
	protected function get_contact_point_object( $xml ) {
		$contact_point = new Ckan_Backend_ContactPoint_Model();
		$contact_point->setName( (string) $this->get_single_element_from_xpath( $xml, 'vcard:fn' ) );
		$contact_point_email_element    = $this->get_single_element_from_xpath( $xml, 'vcard:hasEmail' );
		$contact_point_email_attributes = $contact_point_email_element->attributes( 'rdf', true );
		$contact_point_email            = str_replace( 'mailto:', '', (string) $contact_point_email_attributes['resource'] );
		$contact_point->setEmail( $contact_point_email );

		return $contact_point;
	}

	/**
	 * Returns a relation object from given xml
	 *
	 * @param SimpleXMLElement $xml
	 *
	 * @return Ckan_Backend_Relation_Model
	 */
	protected function get_relation_object( $xml ) {
		$relation            = new Ckan_Backend_Relation_Model();
		$relation_attributes = $xml->attributes( 'rdf', true );
		$relation->setDescription( (string) $relation_attributes['about'] );
		$relation->setLabel( (string) $this->get_single_element_from_xpath( $xml, 'rdfs:label' ) );

		return $relation;
	}

	/**
	 * Returns a Temporal object from given xml
	 *
	 * @param SimpleXMLElement $xml
	 *
	 * @return Ckan_Backend_Temporal_Model
	 */
	protected function get_temporal_object( $xml ) {
		$temporal = new Ckan_Backend_Temporal_Model();
		$temporal->setStartDate( (string) $this->get_single_element_from_xpath( $xml, 'schema:startDate' ) );
		$temporal->setEndDate( (string) $this->get_single_element_from_xpath( $xml, 'schema:endDate' ) );

		return $temporal;
	}

	/**
	 * Returns a SeeAlso object from given xml
	 *
	 * @param SimpleXMLElement $xml
	 *
	 * @return Ckan_Backend_SeeAlso_Model
	 */
	protected function get_see_also_object( $xml ) {
		$see_also            = new Ckan_Backend_SeeAlso_Model();
		$relation_attributes = $xml->attributes( 'rdf', true );
		$see_also->setAbout( (string) $relation_attributes['about'] );
		$see_also->setFormat( (string) $this->get_single_element_from_xpath( $xml, 'dc:format' ) );

		return $see_also;
	}

	/**
	 * Returns a distribution object from given xml
	 *
	 * @param SimpleXMLElement $xml
	 *
	 * @return Ckan_Backend_Distribution_Model
	 */
	protected function get_distribution_object( $xml ) {
		global $language_priority;

		$distribution = new Ckan_Backend_Distribution_Model();
		$distribution->setIdentifier( (string) $this->get_single_element_from_xpath( $xml, 'dct:identifier' ) );
		foreach ( $language_priority as $lang ) {
			$distribution->setTitle( (string) $this->get_single_element_from_xpath( $xml, 'dct:title[@xml:lang="' . $lang . '"]' ), $lang );
			$distribution->setDescription( (string) $this->get_single_element_from_xpath( $xml, 'dct:description[@xml:lang="' . $lang . '"]' ), $lang );
		}
		$distribution->setIssued( (string) $this->get_single_element_from_xpath( $xml, 'dct:issued' ) );
		$distribution->setModified( (string) $this->get_single_element_from_xpath( $xml, 'dct:modified' ) );
		$access_urls = $xml->xpath( 'dcat:accessURL' );
		foreach ( $access_urls as $access_url ) {
			$distribution->addAccessUrl( (string) $access_url );
		}
		$download_urls = $xml->xpath( 'dcat:downloadURL' );
		foreach ( $download_urls as $download_url ) {
			$distribution->addDownloadUrl( (string) $download_url );
		}
		$rights = $xml->xpath( 'dcat:rights/odrs:dataLicence' );
		foreach ( $rights as $right ) {
			$distribution->addRight( (string) $right );
		}
		$distribution->setLicense( (string) $this->get_single_element_from_xpath( $xml, 'dct:license' ) );
		$distribution->setByteSize( (string) $this->get_single_element_from_xpath( $xml, 'dcat:byteSize' ) );
		$distribution->setMediaType( (string) $this->get_single_element_from_xpath( $xml, 'dcat:mediaType' ) );
		$distribution->setFormat( (string) $this->get_single_element_from_xpath( $xml, 'dct:format' ) );
		$distribution->setCoverage( (string) $this->get_single_element_from_xpath( $xml, 'dct:coverage' ) );

		return $distribution;
	}

	/**
	 * Returns a single xml element from a given xpath
	 *
	 * @param SimpleXMLElement $xml
	 * @param String $xpath
	 *
	 * @return SimpleXMLElement|bool
	 */
	protected function get_single_element_from_xpath( $xml, $xpath ) {
		$elements = $xml->xpath( $xpath );
		if ( ! empty( $elements ) ) {
			return $elements[0];
		}

		return false;
	}
}