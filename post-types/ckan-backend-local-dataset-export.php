<?php
/**
 * Export functionality of ckan-local-dataset
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Local_Dataset_Export
 */
class Ckan_Backend_Local_Dataset_Export {

	/**
	 * Used namespaces
	 *
	 * @var array
	 */
	protected $namespaces = array(
		'dct'    => 'http://purl.org/dc/terms/',
		'dc'     => 'http://purl.org/dc/elements/1.1/',
		'dcat'   => 'http://www.w3.org/ns/dcat#',
		'foaf'   => 'http://xmlns.com/foaf/0.1/',
		'xsd'    => 'http://www.w3.org/2001/XMLSchema#',
		'rdfs'   => 'http://www.w3.org/2000/01/rdf-schema#',
		'rdf'    => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
		'vcard'  => 'http://www.w3.org/2006/vcard/ns#',
		'odrs'   => 'http://schema.theodi.org/odrs#',
		'schema' => 'http://schema.org/',
	);

	/**
	 * Callback for the import of a file.
	 */
	public function __construct() {
		if ( class_exists( 'Seravo_Custom_Bulk_Action' ) ) {
			$bulk_actions = new Seravo_Custom_Bulk_Action( array( 'post_type' => Ckan_Backend_Local_Dataset::POST_TYPE ) );

			$bulk_actions->register_bulk_action( array(
				'menu_text'    => __( 'Export', 'ogdch' ),
				'admin_notice' => __( 'Datasets exported', 'ogdch' ),
				'callback'     => array( $this, 'export_datasets' ),
			));

			$bulk_actions->init();
		}
	}

	/**
	 * Export dataset.
	 *
	 * @param array $post_ids Ids of all posts which should be exported.
	 */
	public function export_datasets( $post_ids ) {
		$export_file_name = 'opendata.swiss-export';
		$xml_base = '<?xml version="1.0" encoding="utf-8" ?>';
		$xml_base .= '<rdf:RDF ';

		foreach ( $this->namespaces as $key => $namespace ) {
			$xml_base .= 'xmlns:' . $key . '="' . $namespace . '" ';
		}

		$xml_base .= '/>';

		$xml = new SimpleXMLElement( $xml_base );

		$catalog = $xml->addChild( 'Catalog', null, $this->namespaces['dcat'] );

		foreach ( $post_ids as $post_id ) {
			$dataset_slug = $this->add_dataset( $catalog, $post_id );
			$export_file_name .= '_' . $dataset_slug;
		}
		// limit export filename to 128 characters.
		$export_file_name = substr( $export_file_name, 0, 128 );

		header( 'Content-type: text/xml' );
		header( 'Content-Disposition: attachment; filename="' . $export_file_name . '.xml"' );
		$dom = dom_import_simplexml( $xml )->ownerDocument;
		$dom->formatOutput = true;
		$formatted_xml = $dom->saveXML();
		// @codingStandardsIgnoreStart
		print( $formatted_xml );
		// @codingStandardsIgnoreEnd

		// We have to exit here because we changed the header to start a file download and this can take some time.
		// If we would redirect here (which happens after the return statement) the download wouldn't start.
		exit();
	}

	/**
	 * Adds dataset record to catalog
	 *
	 * @param SimpleXMLElement $catalog_xml The catalog xml (Passed by reference!).
	 * @param int              $post_id Id of dataset to export.
	 *
	 * @return bool|int|WP_Error
	 */
	public function add_dataset( $catalog_xml, $post_id ) {
		global $language_priority;
		$post = get_post( $post_id );

		// prepare array to store all languages from distributions
		$dataset_languages = array();
		foreach ( $language_priority as $lang ) {
			$dataset_languages[ $lang ] = false;
		}

		$dataset_root_xml = $catalog_xml->addChild( 'dataset', null, $this->namespaces['dcat'] );
		$dataset_xml = $dataset_root_xml->addChild( 'Dataset', null, $this->namespaces['dcat'] );

		$identifier = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'identifier', true );
		if ( ! empty( $identifier ) ) {
			$dataset_xml->addChild( 'identifier', $identifier['original_identifier'] . '@' . $identifier['organisation'], $this->namespaces['dct'] );
		}

		foreach ( $language_priority as $lang ) {
			$title = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'title_' . $lang, true );
			if ( ! empty( $title ) ) {
				$title_xml = $dataset_xml->addChild( 'title', $title, $this->namespaces['dct'] );
				$title_xml->addAttribute( 'xml:lang', $lang, 'xml' );
			}
			$description = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'description_' . $lang, true );
			if ( ! empty( $description ) ) {
				$title_xml = $dataset_xml->addChild( 'description', $description, $this->namespaces['dct'] );
				$title_xml->addAttribute( 'xml:lang', $lang, 'xml' );
			}
		}

		$issued = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'issued', true );
		if ( ! empty( $issued ) ) {
			$issued_xml = $dataset_xml->addChild( 'issued', date( 'c', $issued ), $this->namespaces['dct'] );
			$issued_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#dateTime', $this->namespaces['rdf'] );
		}
		$modified = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'modified', true );
		if ( ! empty( $modified ) ) {
			$modified_xml = $dataset_xml->addChild( 'modified', date( 'c', $modified ), $this->namespaces['dct'] );
			$modified_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#dateTime', $this->namespaces['rdf'] );
		}

		$publishers = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'publishers', true );
		foreach ( $publishers as $publisher ) {
			$publisher_xml = $dataset_xml->addChild( 'publisher', null, $this->namespaces['dct'] );
			$publisher_description_xml = $publisher_xml->addChild( 'Description', null, $this->namespaces['rdf'] );
			if ( ! empty( $publisher['termdat_reference'] ) ) {
				$publisher_description_xml->addAttribute( 'rdf:about', $publisher['termdat_reference'], $this->namespaces['rdf'] );
			}
			$publisher_description_xml->addChild( 'label', $publisher['label'], $this->namespaces['rdfs'] );
		}

		$contact_points = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'contact_points', true );
		foreach ( $contact_points as $contact_point ) {
			$contact_point_xml = $dataset_xml->addChild( 'contactPoint', null, $this->namespaces['dcat'] );
			$contact_point_organization_xml = $contact_point_xml->addChild( 'Organization', null, $this->namespaces['vcard'] );
			if ( ! empty( $contact_point['name'] ) ) {
				$contact_point_organization_xml->addChild( 'fn', $contact_point['name'], $this->namespaces['vcard'] );
			}
			if ( ! empty( $contact_point['email'] ) ) {
				$contact_point_organization_email_xml = $contact_point_organization_xml->addChild( 'hasEmail', null, $this->namespaces['vcard'] );
				$contact_point_organization_email_xml->addAttribute( 'rdf:resource', 'mailto:' . $contact_point['email'], $this->namespaces['rdf'] );
			}
		}

		$themes = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'themes', true );
		foreach ( $themes as $theme ) {
			$group_search_args = array(
				'name'           => $theme,
				'post_type'      => Ckan_Backend_Local_Group::POST_TYPE,
				'posts_per_page' => 1,
			);
			$groups            = get_posts( $group_search_args );
			if ( is_array( $groups ) && count( $groups ) > 0 ) {
				$theme_uri = get_post_meta( $groups[0]->ID, Ckan_Backend_Local_Group::FIELD_PREFIX . 'rdf_uri', true );
				$theme_xml = $dataset_xml->addChild( 'theme', null, $this->namespaces['dcat'] );
				$theme_xml->addAttribute( 'rdf:resource', $theme_uri, $this->namespaces['rdf'] );
			}
		}

		$relations = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'relations', true );
		foreach ( $relations as $relation ) {
			$relation_xml = $dataset_xml->addChild( 'relation', null, $this->namespaces['dct'] );
			$relation_description_xml = $relation_xml->addChild( 'Description', null, $this->namespaces['rdf'] );
			if ( ! empty( $relation['url'] ) ) {
				$relation_description_xml->addAttribute( 'rdf:about', $relation['url'], $this->namespaces['rdf'] );
			}
			if ( ! empty( $relation['label'] ) ) {
				$relation_description_xml->addChild( 'label', $relation['label'], $this->namespaces['rdfs'] );
			}
		}

		// Add Keywords
		foreach( Ckan_Backend::$keywords_tax_mapping as $lang => $tax_class ) {
			$keywords = wp_get_post_terms( $post->ID, $tax_class::TAXONOMY );
			foreach ( $keywords as $keyword ) {
				$tag_xml = $dataset_xml->addChild( 'keyword', $keyword->name, $this->namespaces['dcat'] );
				$tag_xml->addAttribute( 'xml:lang', $lang, 'xml' );
			}
		}

		$landing_page = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'landing_page', true );
		if ( ! empty( $landing_page ) ) {
			$dataset_xml->addChild( 'landingPage', $landing_page, $this->namespaces['dcat'] );
		}

		$spatial = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'spatial', true );
		if ( ! empty( $spatial ) ) {
			$dataset_xml->addChild( 'spatial', $spatial, $this->namespaces['dct'] );
		}
		$coverage = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'coverage', true );
		if ( ! empty( $coverage ) ) {
			$dataset_xml->addChild( 'coverage', $coverage, $this->namespaces['dct'] );
		}

		$temporals = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'temporals', true );
		foreach ( $temporals as $temporal ) {
			$temporal_xml = $dataset_xml->addChild( 'temporal', null, $this->namespaces['dct'] );
			$temporal_periodoftime_xml = $temporal_xml->addChild( 'PeriodOfTime', null, $this->namespaces['dct'] );
			if ( ! empty( $temporal['start_date'] ) ) {
				$temporal_periodoftime_startdate_xml = $temporal_periodoftime_xml->addChild( 'startDate', date( 'c', $temporal['start_date'] ), $this->namespaces['schema'] );
				$temporal_periodoftime_startdate_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#date', $this->namespaces['rdf'] );
			}
			if ( ! empty( $temporal['end_date'] ) ) {
				$temporal_periodoftime_enddate_xml = $temporal_periodoftime_xml->addChild( 'endDate', date( 'c', $temporal['end_date'] ), $this->namespaces['schema'] );
				$temporal_periodoftime_enddate_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#date', $this->namespaces['rdf'] );
			}
		}

		$accrual_periodicity = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'accrual_periodicity', true );
		if ( ! empty( $accrual_periodicity ) ) {
			$accrual_periodicity_xml = $dataset_xml->addChild( 'accrualPeriodicity', null, $this->namespaces['dct'] );
			$accrual_periodicity_xml->addAttribute( 'rdf:resource', $accrual_periodicity, $this->namespaces['rdf'] );
		}

		$see_alsos = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'see_alsos', true );
		foreach ( $see_alsos as $see_also ) {
			$dataset_xml->addChild( 'seeAlso', $see_also['dataset_identifier'], $this->namespaces['rdfs'] );
		}

		$distributions = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'distributions', true );
		foreach ( $distributions as $distribution ) {
			$distribution_xml = $dataset_xml->addChild( 'distribution', null, $this->namespaces['dcat'] );

			if ( ! empty( $distribution['identifier'] ) ) {
				$distribution_xml->addChild( 'identifier', $distribution['identifier'], $this->namespaces['dct'] );
			}

			foreach ( $language_priority as $lang ) {
				if ( ! empty( $distribution[ 'title_' . $lang ] ) ) {
					$distribution_title_xml = $distribution_xml->addChild( 'title', $distribution[ 'title_' . $lang ], $this->namespaces['dct'] );
					$distribution_title_xml->addAttribute( 'xml:lang', $lang, 'xml' );
				}
				if ( ! empty( $distribution[ 'description_' . $lang ] ) ) {
					$distribution_description_xml = $distribution_xml->addChild( 'description', $distribution[ 'description_' . $lang ], $this->namespaces['dct'] );
					$distribution_description_xml->addAttribute( 'xml:lang', $lang, 'xml' );
				}
			}

			if ( ! empty( $distribution['issued'] ) ) {
				$distribution_issued_xml = $distribution_xml->addChild( 'issued', date( 'c', $distribution['issued'] ), $this->namespaces['dct'] );
				$distribution_issued_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#dateTime', $this->namespaces['rdf'] );
			}
			if ( ! empty( $distribution['modified'] ) ) {
				$distribution_modified_xml = $distribution_xml->addChild( 'modified', date( 'c', $distribution['modified'] ), $this->namespaces['dct'] );
				$distribution_modified_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#dateTime', $this->namespaces['rdf'] );
			}

			foreach ( $distribution['languages'] as $language ) {
				$distribution_xml->addChild( 'language', $language, $this->namespaces['dct'] );
				$dataset_languages[ $language ] = true;
			}

			if ( ! empty( $distribution['access_url'] ) ) {
				$distribution_accessurl_xml = $distribution_xml->addChild( 'accessURL', $distribution['access_url'], $this->namespaces['dcat'] );
				$distribution_accessurl_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#anyURI', $this->namespaces['rdf'] );
			}
			if ( ! empty( $distribution['download_url'] ) ) {
				$distribution_downloadurl_xml = $distribution_xml->addChild( 'downloadURL', $distribution['download_url'], $this->namespaces['dcat'] );
				$distribution_downloadurl_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#anyURI', $this->namespaces['rdf'] );
			}

			if ( ! empty( $distribution['rights'] ) ) {
				$distribution_rights_xml = $distribution_xml->addChild( 'rights', null, $this->namespaces['dct'] );
				$distribution_rights_xml->addChild( 'dataLicence', $distribution['rights'], $this->namespaces['odrs'] );
			}

			if ( ! empty( $distribution['license'] ) ) {
				$distribution_xml->addChild( 'license', $distribution['license'], $this->namespaces['dct'] );
			}

			if ( ! empty( $distribution['byte_size'] ) ) {
				$distribution_xml->addChild( 'byteSize', $distribution['byte_size'], $this->namespaces['dcat'] );
			}

			if ( ! empty( $distribution['media_type'] ) ) {
				$distribution_xml->addChild( 'mediaType', $distribution['media_type'], $this->namespaces['dcat'] );
			}

			if ( ! empty( $distribution['format'] ) ) {
				$distribution_xml->addChild( 'format', $distribution['format'], $this->namespaces['dct'] );
			}

			if ( ! empty( $distribution['coverage'] ) ) {
				$distribution_xml->addChild( 'coverage', $distribution['coverage'], $this->namespaces['dct'] );
			}
		}

		foreach ( $dataset_languages as $dataset_language => $available ) {
			if ( $available ) {
				$dataset_xml->addChild( 'language', $dataset_language, $this->namespaces['dct'] );
			}
		}

		return $post->post_name;
	}
}
