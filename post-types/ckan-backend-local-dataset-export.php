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
	 * Callback for the import of a file.
	 *
	 * @return void
	 */
	public function __construct() {
		//Define bulk actions for custom-post-type property
		$bulk_actions = new Seravo_Custom_Bulk_Action( array( 'post_type' => Ckan_Backend_Local_Dataset::POST_TYPE ) );

		$bulk_actions->register_bulk_action( array(
			'menu_text'   => __('Export', 'ogdch'),
			'admin_notice'=> __('Datasets exported', 'ogdch'),
			'callback'    => array( $this, 'export_datasets' ),
		));
		$bulk_actions->init();
	}

	/**
	 * Export dataset.
	 *
	 * @param array $post_ids Ids of all posts which should be exported
	 *
	 * @return bool|int|WP_Error
	 */
	public function export_datasets( $post_ids ) {

		$xml_base = '<?xml version="1.0" encoding="utf-8" ?>';
		$xml_base .= '<rdf:RDF
									 xmlns:dct="http://purl.org/dc/terms/"
									 xmlns:dc="http://purl.org/dc/elements/1.1/"
									 xmlns:dcat="http://www.w3.org/ns/dcat#"
									 xmlns:foaf="http://xmlns.com/foaf/0.1/"
									 xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
									 xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
									 xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
									 xmlns:vcard="http://www.w3.org/2006/vcard/ns#"
									 xmlns:odrs="http://schema.theodi.org/odrs#"
									 xmlns:schema="http://schema.org/" />';


		$xml = new SimpleXMLElement($xml_base);

		$catalog = $xml->addChild( 'dcat:Catalog', null, 'http://www.w3.org/ns/dcat#' );

		foreach ( $post_ids as $post_id ) {
			$this->add_dataset( $catalog, $post_id );
		}

		Header('Content-type: text/xml');
		print($xml->asXML());
		exit();

		return true;
	}

	/**
	 * Adds dataset record to catalog
	 *
	 * @param SimpleXMLElement $catalog_xml The catalog xml (Passed by reference!).
	 *
	 * @return bool|int|WP_Error
	 */
	public function add_dataset( $catalog_xml, $post_id ) {
		global $language_priority;
		$post = get_post( $post_id );

		$dataset_root_xml = $catalog_xml->addChild( 'dcat:dataset', null, 'http://www.w3.org/ns/dcat#' );
		$dataset_xml = $dataset_root_xml->addChild( 'dcat:Dataset', null, 'http://www.w3.org/ns/dcat#' );

		$identifier = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'identifier', true );
		if ( ! empty( $identifier ) ) {
			$dataset_xml->addChild( 'dct:identifier', $identifier['original_identifier'] . '@' . $identifier['organisation'], 'http://purl.org/dc/terms/' );
		}

		foreach( $language_priority as $lang ) {
			$title = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'title_' . $lang, true );
			if ( ! empty( $title ) ) {
				$title_xml = $dataset_xml->addChild( 'dct:title', $title, 'http://purl.org/dc/terms/' );
				$title_xml->addAttribute( 'xml:lang', $lang, 'xml' );
			}
			$description = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'description_' . $lang, true );
			if ( ! empty( $description ) ) {
				$title_xml = $dataset_xml->addChild( 'dct:description', $description, 'http://purl.org/dc/terms/' );
				$title_xml->addAttribute( 'xml:lang', $lang, 'xml' );
			}
		}

		$issued = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'issued', true );
		if( ! empty( $issued ) ) {
			$issued_xml = $dataset_xml->addChild( 'dct:issued', date( 'c', $issued ), 'http://www.w3.org/ns/dcat#' );
			$issued_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#dateTime', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		}
		$modified = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'modified', true );
		if( ! empty( $modified ) ) {
			$modified_xml = $dataset_xml->addChild( 'dct:modified', date( 'c', $modified ), 'http://www.w3.org/ns/dcat#' );
			$modified_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#dateTime', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		}

		$publishers = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'publishers', true );
		foreach( $publishers as $publisher ) {
			$publisher_xml = $dataset_xml->addChild( 'dct:publisher', null, 'http://purl.org/dc/terms/' );
			$publisher_description_xml = $publisher_xml->addChild( 'rdf:Description', null, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
			if( ! empty( $publisher['termdat_reference'] ) ) {
				$publisher_description_xml->addAttribute( 'rdf:about', $publisher['termdat_reference'], 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
			}
			$publisher_description_xml->addChild( 'rdfs:label', $publisher['label'], 'http://www.w3.org/2000/01/rdf-schema#' );
		}

		$contact_points = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'contact_points', true );
		foreach( $contact_points as $contact_point ) {
			$contact_point_xml = $dataset_xml->addChild( 'dcat:contactPoint', null, 'http://www.w3.org/ns/dcat#' );
			$contact_point_organization_xml = $contact_point_xml->addChild( 'vcard:Organization', null, 'http://www.w3.org/2006/vcard/ns#' );
			if( ! empty( $contact_point['name'] ) ) {
				$contact_point_organization_xml->addChild( 'vcard:fn', $contact_point['name'], 'http://www.w3.org/2006/vcard/ns#' );
			}
			if( ! empty( $contact_point['email'] ) ) {
				$contact_point_organization_email_xml = $contact_point_organization_xml->addChild( 'vcard:hasEmail', null, 'http://www.w3.org/2006/vcard/ns#' );
				$contact_point_organization_email_xml->addAttribute( 'rdf:resource', 'mailto:' . $contact_point['email'], 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
			}
		}

		$themes = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'themes', true );
		foreach( $themes as $theme ) {
			$group_search_args = array(
				'name'           => $theme,
				'post_type'      => Ckan_Backend_Local_Group::POST_TYPE,
				'posts_per_page' => 1,
			);
			$groups            = get_posts( $group_search_args );
			if ( is_array( $groups ) && count( $groups ) > 0 ) {
				$theme_uri = get_post_meta( $groups[0]->ID, Ckan_Backend_Local_Group::FIELD_PREFIX . 'rdf_uri', true );
				$theme_xml = $dataset_xml->addChild( 'dcat:theme', null, 'http://www.w3.org/ns/dcat#' );
				$theme_xml->addAttribute( 'rdf:resource', $theme_uri, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
			}
		}

		$relations = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'relations', true );
		foreach( $relations as $relation ) {
			$relation_xml = $dataset_xml->addChild( 'dct:relation', null, 'http://purl.org/dc/terms/' );
			$relation_description_xml = $relation_xml->addChild( 'rdf:Description', null, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
			if( ! empty( $relation['url'] ) ) {
				$relation_description_xml->addAttribute( 'rdf:about', $relation['url'], 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
			}
			if( ! empty( $relation['label'] ) ) {
				$relation_description_xml->addChild( 'rdfs:label', $relation['label'], 'http://www.w3.org/2000/01/rdf-schema#' );
			}
		}

		// TODO add calculated <dct:language>
		// TODO add keywords <dcat:keyword>
		/*
        <dcat:keyword xml:lang="de" rdf:about="#eisenbahn">Eisenbahn</dcat:keyword>
        <dcat:keyword xml:lang="fr" rdf:about="#eisenbahn">Chemin-de-fer</dcat:keyword>
        <dcat:keyword xml:lang="it" rdf:about="#eisenbahn">Ferrovia</dcat:keyword>
        <dcat:keyword xml:lang="en" rdf:about="#eisenbahn">Railroad</dcat:keyword>
        <dcat:keyword xml:lang="de" rdf:about="#nacht">Nacht</dcat:keyword>
        <dcat:keyword xml:lang="fr" rdf:about="#nacht">Nuit</dcat:keyword>
        <dcat:keyword xml:lang="it" rdf:about="#nacht">Noche</dcat:keyword>
        <dcat:keyword xml:lang="en" rdf:about="#nacht">Night</dcat:keyword>
		 */

		$landing_page = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'landing_page', true );
		if ( ! empty( $landing_page ) ) {
			$dataset_xml->addChild( 'dcat:landingPage', $landing_page, 'http://www.w3.org/ns/dcat#' );
		}

		$spatial = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'spatial', true );
		if ( ! empty( $spatial ) ) {
			$dataset_xml->addChild( 'dct:spatial', $spatial, 'http://purl.org/dc/terms/' );
		}
		$coverage = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'coverage', true );
		if ( ! empty( $coverage ) ) {
			$dataset_xml->addChild( 'dct:coverage', $coverage, 'http://purl.org/dc/terms/' );
		}

		$temporals = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'temporals', true );
		foreach( $temporals as $temporal ) {
			$temporal_xml = $dataset_xml->addChild( 'dct:temporal', null, 'http://purl.org/dc/terms/' );
			$temporal_periodoftime_xml = $temporal_xml->addChild( 'dct:PeriodOfTime', null, 'http://purl.org/dc/terms/' );
			if( ! empty( $temporal['start_date'] ) ) {
				$temporal_periodoftime_startdate_xml = $temporal_periodoftime_xml->addChild( 'schema:startDate', date( 'c', $temporal['start_date'] ), 'http://schema.org/' );
				$temporal_periodoftime_startdate_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#date', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
			}
			if( ! empty( $temporal['end_date'] ) ) {
				$temporal_periodoftime_enddate_xml = $temporal_periodoftime_xml->addChild( 'schema:endDate', date( 'c', $temporal['end_date'] ), 'http://schema.org/' );
				$temporal_periodoftime_enddate_xml->addAttribute( 'rdf:datatype', 'http://www.w3.org/2001/XMLSchema#date', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
			}
		}

		$accrual_periodicity = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'accrual_periodicity', true );
		if ( ! empty( $accrual_periodicity ) ) {
			$accrual_periodicity_xml = $dataset_xml->addChild( 'dct:accrualPeriodicity', null, 'http://purl.org/dc/terms/' );
			$accrual_periodicity_xml->addAttribute( 'rdf:resource', $accrual_periodicity, 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		}

		$see_alsos = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'see_alsos', true );
		foreach( $see_alsos as $see_also ) {
			$dataset_xml->addChild( 'rdfs:seeAlso', $see_also['dataset_identifier'], 'http://www.w3.org/2000/01/rdf-schema#' );
		}


		/*
        <dcat:distribution>
          <dct:identifier>ch.bafu.laerm-bahnlaerm_nacht</dct:identifier>
          <dct:title xml:lang="de">WMS (ch.bafu.laerm-bahnlaerm_nacht)</dct:title>
          <dct:title xml:lang="en">WMS (ch.bafu.laerm-bahnlaerm_nacht)</dct:title>
          <dct:description xml:lang="de">Die Angaben basieren auf flächendeckenden Modellberechnungen.</dct:description>
          <dct:description xml:lang="en">The information is based on comprehensive model calculations.</dct:description>
          <dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2013-05-11T00:00:00Z</dct:issued>
          <dct:modified rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2015-04-26T00:00:00Z</dct:modified>
          <dct:language>de</dct:language>
          <dct:language>en</dct:language>
          <dcat:accessURL rdf:datatype="http://www.w3.org/2001/XMLSchema#anyURI">http://wms.geo.admin.ch/</dcat:accessURL>
          <dct:rights>
            <odrs:dataLicence>NonCommercialAllowed-CommercialAllowed-ReferenceNotRequired</odrs:dataLicence>
          </dct:rights>
          <dct:license/>
          <dcat:byteSize>1024</dcat:byteSize>
          <dcat:mediaType>text/html</dcat:mediaType>
          <dct:format/>
          <dct:coverage/>
        </dcat:distribution>
		 */
	}
}
