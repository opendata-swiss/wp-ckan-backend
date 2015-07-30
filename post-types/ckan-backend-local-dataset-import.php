<?php

class Ckan_Backend_Local_Dataset_Import {

	public $menu_slug = 'ckan-local-dataset-import-page';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );
	}

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

	public function import_page_callback() {
		// must check that the user has the required capability
		if ( ! current_user_can( 'create_datasets' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$import_submit_hidden_field_name = 'ckan_local_dataset_import_submit';
		$file_field_name                 = 'ckan_local_dataset_import_file';

		// Handle import
		if ( isset( $_POST[ $import_submit_hidden_field_name ] ) && $_POST[ $import_submit_hidden_field_name ] == 'Y' ) {
			$dataset_id = false;
			if ( isset( $_FILES[ $file_field_name ] ) ) {
				$dataset_id = $this->handle_file_import( $_FILES[ $file_field_name ] );
			}

			if ( $dataset_id > 0 ) {
				echo '<div class="updated"><p><strong>' . __( 'Import successful', 'ogdch' ) . '</strong></p></div>';
				printf(__('Click <a href="%s">here</a> to see the imported dataset.', 'ogdch'), esc_url( admin_url( 'post.php?post=' . $dataset_id . '&action=edit' ) ));
			}
		} ?>
		<div class="wrap">
			<h2><?php _e( 'Import CKAN Dataset', 'ogdch' ); ?></h2>

			<form enctype="multipart/form-data" action="" method="POST">
				<input type="hidden" name="<?php echo $import_submit_hidden_field_name; ?>" value="Y">

				<p><?php _e( "File:", 'ogdch' ); ?>
					<input type="file" name="<?php echo $file_field_name; ?>" value="" size="20">
				</p>
				<hr/>

				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Import' ) ?>"/>
				</p>
			</form>
		</div>

		<?php
	}

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
			$xml->registerXPathNamespace('dcat', 'http://www.w3.org/ns/dcat#');
			$xml->registerXPathNamespace('dct', 'http://purl.org/dc/terms/');
			$xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
			$xml->registerXPathNamespace('foaf', 'http://xmlns.com/foaf/0.1/');
			$xml->registerXPathNamespace('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
			$xml->registerXPathNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
			$xml->registerXPathNamespace('vcard', 'http://www.w3.org/2006/vcard/ns#');
			$xml->registerXPathNamespace('odrs', 'http://schema.theodi.org/odrs#');
			$xml->registerXPathNamespace('schema', 'http://schema.org/');

			return $this->import_dataset( $xml );
		} catch ( RuntimeException $e ) {
			echo $e->getMessage();
		}
	}

	public function import_dataset( $xml ) {
		$dataset = $this->get_dataset_object( $xml );

		foreach ( $xml->xpath('//dcat:Dataset/dcat:theme') as $group ) {
			if ( ! Ckan_Backend_Helper::group_exists( (string) $group ) ) {
				echo '<div class="error"><p>';
				printf( __( 'Group %1$s does not exist! Import aborted.', 'ogdch' ), (string) $group );
				echo '</p></div>';

				return false;
			}
		}

		$publisher =  $xml->xpath('//dcat:Dataset/dct:publisher/foaf:Organization/foaf:name');
		if( count( $publisher ) > 0 ) {
			if ( ! Ckan_Backend_Helper::organisation_exists( (string) $publisher[0] ) ) {
				echo '<div class="error"><p>';
				printf( __( 'Organisation %1$s does not exist! Import aborted.', 'ogdch' ), (string) $publisher[0] );
				echo '</p></div>';

				return false;
			}
		}

		$groups = $this->prepare_groups( $xml );
		$resources = $this->prepare_resources( $xml );

		$this->prepare_post_fields($xml, $resources, $groups);

		$dataset_search_args = array(
			'meta_key'    => Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'masterid',
			'meta_value'  => (string) $xml->masterid,
			'post_type'   => Ckan_Backend_Local_Dataset::POST_TYPE,
			'post_status' => 'any',
		);
		$datasets            = get_posts( $dataset_search_args );

		if ( count( $datasets ) > 0 ) {
			// Dataset already exists -> update
			$dataset_id = $datasets[0]->ID;
			$this->update( $dataset_id, $xml, $resources, $groups );
		} else {
			// Create new dataset
			$dataset_id = $this->insert( $xml, $resources, $groups );
		}

		return $dataset_id;
	}

	protected function prepare_resources( $xml ) {
		$resources = array();
		foreach ( $xml->xpath('//dcat:Dataset/dcat:distribution/dcat:Distribution') as $resource ) {
			$download_urls = $resource->xpath('dcat:downloadURL');
			$title_de = $resource->xpath('dct:title[@xml:lang="de"]');
			$description_de = $resource->xpath('dct:description[@xml:lang="de"]');
			$resources[] = array(
				'url' => (string) $download_urls[0],
				'title' => (string) $title_de[0],
				'description_de' => (string) $description_de[0],
			);
		}
		return $resources;
	}

	protected function prepare_groups( $xml ) {
		$groups = array();
		foreach ( $xml->xpath('//dcat:Dataset/dcat:theme') as $group ) {
			$groups[] = (string) $group;
		}
		return $groups;
	}

	protected function prepare_post_fields($xml, $resources, $groups) {
		$title_en = $xml->xpath('//dcat:Dataset/dct:title[@xml:lang="en"]');
		$title_de = $xml->xpath('//dcat:Dataset/dct:title[@xml:lang="de"]');
		$description_de = $xml->xpath('//dcat:Dataset/dct:description[@xml:lang="de"]');
		$contact_point_name = $xml->xpath('//dcat:Dataset/dcat:contactPoint/vcard:Organization/vcard:fn');
		$contact_point_email_element = $xml->xpath('//dcat:Dataset/dcat:contactPoint/vcard:Organization/vcard:hasEmail');
		$contact_point_email = str_replace( 'mailto:', '', $contact_point_email_element[0]->attributes('rdf', true));

		// simulate $_POST data to make post_save hook work correctly
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'resources' ]        = $resources;
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'groups' ]           = $groups;
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'title_en' ]         = (string) $title_en[0];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'title_de' ]         = (string) $title_de[0];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'contact_point' ]    = (string) $contact_point_name[0];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'maintainer_email' ] = (string) $contact_point_email;
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'description_de' ]   = (string) $description_de[0];
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'organisation' ]     = (string) $xml->owner_org;
	}

	protected function update( $dataset_id, $xml, $resources, $groups ) {
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'disabled' ]  = get_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'disabled', true );
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'reference' ] = get_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'reference', true );

		$dataset_args = array(
			'ID'         => $dataset_id,
			'post_name'  => (string) $xml->name,
			'post_title' => (string) $xml->title,
		);

		wp_update_post( $dataset_args );

		// manually update all dataset metafields
		update_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'name_de', (string) $xml->title );
		update_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'description_de', (string) $xml->description_de );
		update_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'resources', $resources );
		update_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'groups', $groups );
		update_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'organisation', (string) $xml->owner_org );
	}

	protected function insert( $xml, $resources, $groups ) {
		$_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'disabled' ] = '';

		$dataset_args = array(
			'post_name'    => (string) $xml->name,
			'post_title'   => (string) $xml->title,
			'post_status'  => 'publish',
			'post_type'    => Ckan_Backend_Local_Dataset::POST_TYPE,
			'post_excerpt' => '',
		);

		$dataset_id = wp_insert_post( $dataset_args );

		// manually insert all dataset metafields
		add_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'name_de', (string) $xml->title, true );
		add_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'description_de', (string) $xml->description_de, true );
		add_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'resources', $resources, true );
		add_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'groups', $groups, true );
		add_post_meta( $dataset_id, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'organisation', (string) $xml->owner_org, true );

		return $dataset_id;
	}

	protected function get_dataset_object($xml) {
		global $language_priority;

		$dataset = new Ckan_Backend_Dataset_Model();
		$dataset->setIdentifier((string) $this->get_single_element_from_xpath($xml, '//dcat:Dataset/dct:identifier'));
		foreach($language_priority as $lang) {
			$dataset->setTitle((string) $this->get_single_element_from_xpath($xml, '//dcat:Dataset/dct:title[@xml:lang="' . $lang . '"]'), $lang);
			$dataset->setDescription((string) $this->get_single_element_from_xpath($xml, '//dcat:Dataset/dct:description[@xml:lang="' . $lang . '"]'), $lang);
		}
		$dataset->setIssued((string) $this->get_single_element_from_xpath($xml, '//dcat:Dataset/dct:issued'));
		$dataset->setModified((string) $this->get_single_element_from_xpath($xml, '//dcat:Dataset/dct:modified'));

		$publishers = $xml->xpath('//dcat:Dataset/dct:publisher/foaf:Organization');
		foreach($publishers as $publisher_xml) {
			$publisher = new Ckan_Backend_Publisher_Model();
			$publisher->setName((string) $this->get_single_element_from_xpath($publisher_xml, 'foaf:name'));
			$publisher->setMbox((string) $this->get_single_element_from_xpath($publisher_xml, 'foaf:mbox'));
			$dataset->addPublisher($publisher);
		}
		$contact_points = $xml->xpath('//dcat:Dataset/dcat:contactPoint/vcard:Organization');
		foreach($contact_points as $contact_point_xml) {
			$contact_point = new Ckan_Backend_ContactPoint_Model();
			$contact_point->setName((string) $this->get_single_element_from_xpath($contact_point_xml, 'vcard:fn'));
			$contact_point_email_element = $this->get_single_element_from_xpath($contact_point_xml, 'vcard:hasEmail');
			$contact_point_email_attributes = $contact_point_email_element->attributes('rdf', true);
			$contact_point_email = str_replace( 'mailto:', '', (string) $contact_point_email_attributes['resource'] );
			$contact_point->setEmail($contact_point_email);
			$dataset->addContactPoint($contact_point);
		}
		$themes = $xml->xpath('//dcat:Dataset/dcat:theme');
		foreach($themes as $theme) {
			$dataset->addTheme( (string) $theme );
		}
		$languages = $xml->xpath('//dcat:Dataset/dct:language');
		foreach($languages as $language) {
			$dataset->addLanguage( (string) $language );
		}
		$relations = $xml->xpath('//dcat:Dataset/dct:relation/rdf:Description');
		foreach($relations as $relation_xml) {
			$relation = new Ckan_Backend_Relation_Model();
			$relation_attributes = $relation_xml->attributes('rdf', true);
			$relation->setDescription((string) $relation_attributes['about']);
			$relation->setLabel((string) $this->get_single_element_from_xpath($relation_xml, 'rdfs:label'));
			$dataset->addRelation($relation);
		}
		$keywords = $xml->xpath('//dcat:Dataset/dcat:keyword');
		foreach($keywords as $keyword) {
			$dataset->addKeyword( (string) $keyword );
		}
		$dataset->setLandingPage((string) $this->get_single_element_from_xpath($xml, '//dcat:Dataset/dcat:landingPage'));
		$spatial_element = $this->get_single_element_from_xpath($xml, '//dcat:Dataset/dct:spatial');
		$spatial_attributes = $spatial_element->attributes('rdf', true);
		$dataset->setSpatial((string) $spatial_attributes['resource']);
		$dataset->setCoverage((string) $this->get_single_element_from_xpath($xml, '//dcat:Dataset/dct:coverage'));
		$temporals = $xml->xpath('//dcat:Dataset/dct:temporal/dct:PeriodOfTime');
		foreach($temporals as $temporal_xml) {
			$temporal = new Ckan_Backend_Temporal_Model();
			$temporal->setStartDate((string) $this->get_single_element_from_xpath($temporal_xml, 'schema:startDate'));
			$temporal->setEndDate((string) $this->get_single_element_from_xpath($temporal_xml, 'schema:endDate'));
			$dataset->addTemporal($temporal);
		}
		$accural_periodicity_element = $this->get_single_element_from_xpath($xml, '//dcat:Dataset/dct:accrualPeriodicity');
		$accural_periodicity_attributes = $accural_periodicity_element->attributes('rdf', true);
		$dataset->setAccrualPeriodicy((string) $accural_periodicity_attributes['resource']);
		$see_alsos = $xml->xpath('//dcat:Dataset/rdfs:seeAlso/rdf:Description');
		foreach($see_alsos as $see_also_xml) {
			$see_also = new Ckan_Backend_SeeAlso_Model();
			$relation_attributes = $see_also_xml->attributes('rdf', true);
			$see_also->setAbout((string) $relation_attributes['about']);
			$see_also->setFormat((string) $this->get_single_element_from_xpath($see_also_xml, 'dc:format'));
			$dataset->addSeeAlso($see_also);
		}

		print_r($dataset);
		die();
	}

	protected function get_single_element_from_xpath($xml, $xpath) {
		$elements = $xml->xpath($xpath);
		if( ! empty($elements) ) {
			return $elements[0];
		}
		return false;
	}
}