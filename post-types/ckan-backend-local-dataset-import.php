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
			'create_datasets',
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
			$dataset_id = 0;
			if ( isset( $_FILES[ $file_field_name ] ) ) {
				$dataset_id = $this->handle_file_import( $_FILES[ $file_field_name ] );
			}

			if ( is_wp_error( $dataset_id ) ) {
				echo '<div class="error"><p>';
				echo esc_attr( $dataset_id->get_error_message() );
				echo '</p></div>';
				$dataset_id = 0;
			}

			// check for notices
			$notices = get_option( Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'notices' );
			delete_option( Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'notices' );

			if ( ! empty( $notices ) ) {
				// print available notices
				foreach ( $notices as $key => $m ) {
					echo '<div class="error"><p>' . esc_html( $m ) . '</p></div>';
				}
			} else {
				if ( $dataset_id > 0 ) {
					echo '<div class="updated">';
					echo '<p><strong>' . esc_html( __( 'Import successful', 'ogdch' ) ) . '</strong></p><p>';
					// @codingStandardsIgnoreStart
					printf(
						__( 'You can edit it here: <a href="%s">%s</a>.', 'ogdch' ),
						esc_url( admin_url( 'post.php?post=' . esc_attr( $dataset_id ) . '&action=edit' ) ),
						esc_attr( get_the_title( $dataset_id ) )
					);
					// @codingStandardsIgnoreEnd
					echo '</p></div>';
				}
			}
		} ?>
		<div class="wrap import_ckan_dataset">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form enctype="multipart/form-data" action="" method="POST">
				<input type="hidden" name="<?php esc_attr_e( $import_submit_hidden_field_name ); ?>" value="Y">
				<?php // Field shows that the metadata is not yet saved in database when save_post hook is called -> get values from $_POST array ?>
				<input type="hidden" id="metadata_not_in_db" name="metadata_not_in_db" value="1"/>

				<div class="postbox">
					<div class="inside">
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">
										<label for="import_file"><?php esc_html_e( __( 'DCAT-AP File:', 'ogdch' ) ); ?></label>
									</th>

									<td>
										<input type="file" id="import_file" name="<?php esc_attr_e( $file_field_name ); ?>"/>
										<br/>
										<span class="description"><?php esc_html_e( __( 'File has to be in DACT-AP Switzerland format.', 'ogdch' ) ); ?></span>
									</td>
								</tr>
							</tbody>
						</table>

						<hr/>

						<p class="submit">
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Import' ) ?>"/>
						</p>
					</div>
				</div>
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
	 */
	public function handle_file_import( $file ) {
		// Undefined | Multiple Files | $_FILES Corruption Attack
		// If this request falls under any of them, treat it invalid.
		if (
			! isset( $file['error'] ) ||
			is_array( $file['error'] )
		) {
			return new WP_Error( 'invalid_parameters', 'Invalid parameters.' );
		}

		// Check $file['error'] value.
		switch ( $file['error'] ) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				return new WP_Error( 'missing_file', 'Missing import file.' );
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return new WP_Error( 'exceeded_filesize', 'Exceeded filesize limit.' );
			default:
				return new WP_Error( 'unknown_errors', 'Unknown errors.' );
		}

		$xml = simplexml_load_file( $file['tmp_name'] );
		if ( ! is_object( $xml ) ) {
			return new WP_Error( 'invalid_xml', 'Uploaded file is not a vaild XML file.' );
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

		// if there was an error in the xml document
		if ( false === $dataset ) {
			return false;
		}

		/*
		 * TODO
		 * - Tags import
         * - accrualPeriodicity -> Select box -> Taxonomy -> URI Mapping
		 */

		// simulate $_POST data to make post_save hook work correctly
		$_POST = array_merge( $_POST, $dataset->to_array() );

		$dataset_search_args = array(
			// @codingStandardsIgnoreStart
			'meta_key'    => Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'identifier',
			'meta_value'  => maybe_serialize( $dataset->get_identifier() ),
			// @codingStandardsIgnoreEnd
			'post_type'   => Ckan_Backend_Local_Dataset::POST_TYPE,
			'post_status' => 'any',
		);
		$datasets            = get_posts( $dataset_search_args );

		if ( is_array( $datasets ) && count( $datasets ) > 0 ) {
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
	 * @param int                        $dataset_id ID of dataset to update.
	 * @param Ckan_Backend_Dataset_Model $dataset Dataset instance with values.
	 */
	protected function update( $dataset_id, $dataset ) {
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'disabled' ] = get_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'disabled', true );
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'ckan_id' ]  = get_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'ckan_id', true );

		$dataset_args = array(
			'ID'         => $dataset_id,
			'post_title' => $dataset->get_main_title(),
			'tags_input' => $dataset->get_keywords(),
		);

		if ( '' !== $dataset->get_issued() ) {
			$dataset_args['post_date'] = date( 'Y-m-d H:i:s', $dataset->get_issued() );
			// We also have to set post_date_gmt to get post_status update to work correctly
			$dataset_args['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $dataset->get_issued() );
		}
		// set post status to future if needed
		if ( $dataset->get_issued() > time() ) {
			if ( get_post_status( $dataset_id ) === 'publish' ) {
				$dataset_args['post_status'] = 'future';
			}
		} else {
			if ( get_post_status( $dataset_id ) === 'future' ) {
				$dataset_args['post_status'] = 'draft';
			}
		}

		wp_update_post( $dataset_args );
		foreach ( $dataset->to_array() as $field => $value ) {
			update_post_meta( $dataset_id, $field, $value );
		}
	}

	/**
	 * Inserts a new dataset
	 *
	 * @param Ckan_Backend_Dataset_Model $dataset Dataset instance with values.
	 *
	 * @return bool|int|WP_Error
	 */
	protected function insert( $dataset ) {
		$dataset_args = array(
			'post_title'   => $dataset->get_main_title(),
			'post_status'  => ( ( $dataset->get_issued() > time() ) ? 'future' : 'draft' ),
			'post_type'    => Ckan_Backend_Local_Dataset::POST_TYPE,
			'post_excerpt' => '',
			'tags_input'   => $dataset->get_keywords(),
		);

		if ( '' !== $dataset->get_issued() ) {
			$dataset_args['post_date'] = date( 'Y-m-d H:i:s', $dataset->get_issued() );
		} else {
			$dataset_args['post_date'] = date( 'Y-m-d H:i:s' );
		}

		$dataset_id = wp_insert_post( $dataset_args );
		foreach ( $dataset->to_array() as $field => $value ) {
			add_post_meta( $dataset_id, $field, $value, true );
		}

		return $dataset_id;
	}

	/**
	 * Returns a dataset object from given xml
	 *
	 * @param SimpleXMLElement $xml XML content from file.
	 *
	 * @return Ckan_Backend_Dataset_Model
	 *
	 * @throws Exception If there are validation errors.
	 */
	protected function get_dataset_object( $xml ) {
		global $language_priority;

		try {
			$dataset = new Ckan_Backend_Dataset_Model();
			$identifier = (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:identifier' );
			if ( '' === $identifier ) {
				throw new Exception( __( 'Please provide an identifier for the dataset (eg. <dct:title xml:lang="en">My Dataset</dct:title>)', 'ogdch' ) );
			}
			$splitted_identifier = $this->split_identifier( $identifier );
			if ( empty( $splitted_identifier['original_identifier'] ) ) {
				throw new Exception( __( 'The original identifier of your dataset is missing. Please provide the dataset identifier in the following form <dct:identifier>[original_dataset_id]@[organisation_id]</dct:identifier>. Import aborted.', 'ogdch' ) );
			}

			$this->validate_organisation( $splitted_identifier['organisation'] );
			$dataset->set_identifier( $splitted_identifier );
			foreach ( $language_priority as $lang ) {
				$dataset->set_title( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:title[@xml:lang="' . $lang . '"]' ), $lang );
				$dataset->set_description( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:description[@xml:lang="' . $lang . '"]' ), $lang );
			}
			if ( '' === $dataset->get_main_title() ) {
				//TODO: throw custom exception
				throw new Exception( 'Please provide a title in at least one language for the dataset (eg. <dct:title xml:lang="en">My Dataset</dct:title>)' );
			}
			$issued = strtotime( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:issued' ) );
			$dataset->set_issued( $issued );
			$modified = strtotime( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:modified' ) );
			$dataset->set_modified( $modified );
			$publishers = $xml->xpath( '//dcat:Dataset/dct:publisher' );
			foreach ( $publishers as $publisher_xml ) {
				$dataset->add_publisher( $this->get_publisher_object( $publisher_xml ) );
			}
			$contact_points = $xml->xpath( '//dcat:Dataset/dcat:contactPoint/*' );
			foreach ( $contact_points as $contact_point_xml ) {
				$dataset->add_contact_point( $this->get_contact_point_object( $contact_point_xml ) );
			}
			$themes = $xml->xpath( '//dcat:Dataset/dcat:theme' );
			foreach ( $themes as $theme ) {
				if ( is_object( $theme ) ) {
					$theme_attributes = $theme->attributes( 'rdf', true );
					$theme_uri        = (string) $theme_attributes['resource'];

					$dataset->add_theme( $this->get_theme_name( $theme_uri ) );
				}
			}
			$relations = $xml->xpath( '//dcat:Dataset/dct:relation/rdf:Description' );
			foreach ( $relations as $relation_xml ) {
				$dataset->add_relation( $this->get_relation_object( $relation_xml ) );
			}
			$keywords = $xml->xpath( '//dcat:Dataset/dcat:keyword' );
			foreach ( $keywords as $keyword ) {
				$dataset->add_keyword( (string) $keyword );
			}
			$dataset->set_landing_page( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dcat:landingPage' ) );
			$dataset->set_spatial( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:spatial/@rdf:resource' ) );
			$dataset->set_coverage( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:coverage' ) );
			$temporals = $xml->xpath( '//dcat:Dataset/dct:temporal/dct:PeriodOfTime' );
			foreach ( $temporals as $temporal_xml ) {
				$dataset->add_temporal( $this->get_temporal_object( $temporal_xml ) );
			}
			$dataset->set_accrual_periodicity( (string) $this->get_single_element_from_xpath( $xml, '//dcat:Dataset/dct:accrualPeriodicity/@rdf:resource' ) );
			$see_alsos = $xml->xpath( '//dcat:Dataset/rdfs:seeAlso' );
			foreach ( $see_alsos as $see_also ) {
				$dataset->add_see_also( (string) $see_also );
			}

			$distributions = $xml->xpath( '//dcat:Dataset/dcat:distribution' );
			foreach ( $distributions as $distribution_xml ) {
				$dataset->add_distribution( $this->get_distribution_object( $distribution_xml ) );
			}

			return $dataset;
		} catch (Exception $e) {
			$this->store_error_in_notices_option( $e->getMessage() );
			return false;
		}

	}

	/**
	 * Validates given organisation
	 *
	 * @param string $organisation Organisation to validate.
	 *
	 * @return bool
	 * @throws Exception If there are validation errors.
	 */
	protected function validate_organisation( $organisation ) {
		// Check if organisation is set
		if ( empty( $organisation ) ) {
			throw new Exception( __( 'The organisation id is missing in the identifier. Please provide the dataset identifier in the following form <dct:identifier>[original_dataset_id]@[organisation_id]</dct:identifier>. Import aborted.', 'ogdch' ) );
		}
		// If user isn't allowed to edit_others_organisations for another organisation -> check if he has provided his own organisation
		if ( ! current_user_can( 'edit_others_organisations' ) ) {
			$user_organisation = get_the_author_meta( Ckan_Backend::$plugin_slug . '_organisation', get_current_user_id() );
			if ( $user_organisation !== $organisation ) {
				throw new Exception( __( 'You are not allowed to add a dataset for another organistaion. Please provide the dataset identifier in the following form <dct:identifier>[original_dataset_id]@[your_organisation_id]</dct:identifier>. Import aborted.', 'ogdch' ) );
			}
		}
		// Check if organisation exists in CKAN
		if ( ! Ckan_Backend_Helper::organisation_exists( $organisation ) ) {
			throw new Exception( sprintf( __( 'Organisation %1$s does not exist! Import aborted.', 'ogdch' ), $organisation ) );
		}

		return true;
	}

	/**
	 * Returns a Publisher object from given xml
	 *
	 * @param SimpleXMLElement $xml XML content from file.
	 *
	 * @return Ckan_Backend_Publisher_Model
	 */
	protected function get_publisher_object( $xml ) {
		$publisher                     = new Ckan_Backend_Publisher_Model();
		$publisher->set_termdat_reference( (string) $this->get_single_element_from_xpath( $xml, 'rdf:Description/@rdf:about' ) );
		$publisher->set_label( (string) $this->get_single_element_from_xpath( $xml, 'rdf:Description/rdfs:label' ) );

		return $publisher;
	}

	/**
	 * Returns a ContactPoint object from given xml
	 *
	 * @param SimpleXMLElement $xml XML content from file.
	 *
	 * @return Ckan_Backend_ContactPoint_Model
	 */
	protected function get_contact_point_object( $xml ) {
		$contact_point = new Ckan_Backend_ContactPoint_Model();
		$contact_point->set_name( (string) $this->get_single_element_from_xpath( $xml, 'vcard:fn' ) );
		$contact_point_email = $this->get_single_element_from_xpath( $xml, 'vcard:hasEmail/@rdf:resource' );
		$contact_point->set_email( str_replace( 'mailto:', '', (string) $contact_point_email ) );

		return $contact_point;
	}

	/**
	 * Returns a relation object from given xml
	 *
	 * @param SimpleXMLElement $xml XML content from file.
	 *
	 * @return Ckan_Backend_Relation_Model
	 */
	protected function get_relation_object( $xml ) {
		$relation = new Ckan_Backend_Relation_Model();
		$relation->set_url( (string) $this->get_single_element_from_xpath( $xml, '@rdf:about' ) );
		$relation->set_label( (string) $this->get_single_element_from_xpath( $xml, 'rdfs:label' ) );

		return $relation;
	}

	/**
	 * Returns a Temporal object from given xml
	 *
	 * @param SimpleXMLElement $xml XML content from file.
	 *
	 * @return Ckan_Backend_Temporal_Model
	 */
	protected function get_temporal_object( $xml ) {
		$temporal   = new Ckan_Backend_Temporal_Model();
		$start_date = strtotime( (string) $this->get_single_element_from_xpath( $xml, 'schema:startDate' ) );
		$temporal->set_start_date( $start_date );
		$end_date = strtotime( (string) $this->get_single_element_from_xpath( $xml, 'schema:endDate' ) );
		$temporal->set_end_date( $end_date );

		return $temporal;
	}

	/**
	 * Returns a distribution object from given xml
	 *
	 * @param SimpleXMLElement $xml XML content from file.
	 *
	 * @return Ckan_Backend_Distribution_Model
	 */
	protected function get_distribution_object( $xml ) {
		global $language_priority;

		$distribution = new Ckan_Backend_Distribution_Model();
		$distribution->set_identifier( (string) $this->get_single_element_from_xpath( $xml, 'dct:identifier' ) );
		foreach ( $language_priority as $lang ) {
			$distribution->set_title( (string) $this->get_single_element_from_xpath( $xml, 'dct:title[@xml:lang="' . $lang . '"]' ), $lang );
			$distribution->set_description( (string) $this->get_single_element_from_xpath( $xml, 'dct:description[@xml:lang="' . $lang . '"]' ), $lang );
		}
		$languages = $xml->xpath( 'dct:language' );
		foreach ( $languages as $language ) {
			$distribution->add_language( (string) $language );
		}

		$issued = strtotime( (string) $this->get_single_element_from_xpath( $xml, 'dct:issued' ) );
		$distribution->set_issued( $issued );
		$modified = strtotime( (string) $this->get_single_element_from_xpath( $xml, 'dct:modified' ) );
		$distribution->set_modified( $modified );
		$access_urls = $xml->xpath( 'dcat:accessURL' );
		foreach ( $access_urls as $access_url ) {
			$distribution->add_access_url( (string) $access_url );
		}
		$download_urls = $xml->xpath( 'dcat:downloadURL' );
		foreach ( $download_urls as $download_url ) {
			$distribution->add_download_url( (string) $download_url );
		}
		$rights = $xml->xpath( 'dct:rights/odrs:dataLicence' );
		foreach ( $rights as $right ) {
			if ( Ckan_Backend_Helper::starts_with( (string) $right, 'reference_' ) ) {
				$distribution->set_right_reference( (string) $right );
				continue;
			}
			if ( Ckan_Backend_Helper::starts_with( (string) $right, 'non-commercial_' ) ) {
				$distribution->set_right_non_commercial( (string) $right );
				continue;
			}
			if ( Ckan_Backend_Helper::starts_with( (string) $right, 'commercial_' ) ) {
				$distribution->set_right_commercial( (string) $right );
				continue;
			}
		}
		$distribution->set_license( (string) $this->get_single_element_from_xpath( $xml, 'dct:license' ) );
		$distribution->set_byte_size( (string) $this->get_single_element_from_xpath( $xml, 'dcat:byteSize' ) );
		$distribution->set_media_type( (string) $this->get_single_element_from_xpath( $xml, 'dcat:mediaType' ) );
		$distribution->set_format( (string) $this->get_single_element_from_xpath( $xml, 'dct:format' ) );
		$distribution->set_coverage( (string) $this->get_single_element_from_xpath( $xml, 'dct:coverage' ) );

		return $distribution;
	}

	/**
	 * Returns a single xml element from a given xpath
	 *
	 * @param SimpleXMLElement $xml XML content from file.
	 * @param String           $xpath Xpath for query.
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

	/**
	 * Stores error message in option to print them out after redirect of save action
	 *
	 * @param string $m Error message.
	 *
	 * @return bool True if error message was stored successfully.
	 */
	protected function store_error_in_notices_option( $m ) {
		// store error notice in option array
		$notices   = get_option( Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'notices' );
		$notices[] = $m;

		return update_option( Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'notices', $notices );
	}

	/**
	 * Transforms theme uris to names
	 *
	 * @param string $theme_uri The theme URI from the RDF.
	 *
	 * @return Name of the theme (group)
	 *
	 * @throws Exception If group doesn't exist.
	 */
	public function get_theme_name( $theme_uri ) {
		// TODO save result to transient (@odi)
		$group_search_args = array(
			// @codingStandardsIgnoreStart
			'meta_key'    => Ckan_Backend_Local_Group::FIELD_PREFIX . 'rdf_uri',
			'meta_value'  => $theme_uri,
			// @codingStandardsIgnoreEnd
			'post_type'   => Ckan_Backend_Local_Group::POST_TYPE,
		);
		$groups            = get_posts( $group_search_args );
		if ( is_array( $groups ) && count( $groups ) > 0 ) {
			$theme_name = get_post_meta( $groups[0]->ID, Ckan_Backend_Local_Group::FIELD_PREFIX . 'ckan_name', true );
			if ( empty( $theme_name ) || ! Ckan_Backend_Helper::group_exists( $theme_name ) ) {
				throw new Exception( __( sprintf( __( 'Group %1$s does not exist! Import aborted.', 'ogdch' ), $theme_uri ) ) );
			}
			return $theme_name;
		} else {
			throw new Exception( sprintf( __( 'Group %1$s does not exist! Import aborted.', 'ogdch' ), $theme_uri ) );
		}
	}

	/**
	 * Returns Original Identifiert and Organisation ID extracted from given identifier
	 *
	 * @param string $identifier Identifier in following format: <original_id>@<organisation_id>.
	 *
	 * @return array Format: array( 'original_identifier' = '123', 'organisation' = 'ABC' );
	 */
	public function split_identifier( $identifier ) {
		$splitted_identifier = array(
			'original_identifier' => substr( $identifier, 0, strrpos( $identifier, '@' ) ),
			'organisation'        => substr( strrchr( $identifier, '@' ), 1 ),
		);

		return $splitted_identifier;
	}
}
