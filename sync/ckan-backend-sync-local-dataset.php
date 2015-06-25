<?php

class Ckan_Backend_Sync_Local_Dataset extends Ckan_Backend_Sync_Abstract {

	public function __construct() {
		parent::__construct( Ckan_Backend_Local_Dataset::POST_TYPE, Ckan_Backend_Local_Dataset::FIELD_PREFIX );
	}

	protected function get_update_data() {
		$extras    = $this->prepare_custom_fields( $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'custom_fields' ] );
		$resources = $this->prepare_resources( $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'resources' ] );
		$groups    = $this->prepare_selected_groups( $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'groups' ] );

		// Gernerate slug of dataset. If no title is entered use an uniqid
		if ( $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'name' ] != '' ) {
			$title = $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'name' ];
		} else {
			$title = $_POST['post_title'];

			if ( $title === '' ) {
				$title = uniqid();
			}
		}
		$slug = sanitize_title_with_dashes( $title );

		$data = array(
			'name'             => $slug,
			'title'            => $_POST['post_title'], // TODO: use all language here
			'maintainer'       => $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'maintainer' ],
			'maintainer_email' => $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'maintainer_email' ],
			'author'           => $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'author' ],
			'author_email'     => $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'author_email' ],
			'notes'            => $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'description_de' ], // TODO: use all language here
			'version'          => $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'version' ],
			'state'            => 'active',
			'extras'           => $extras,
			'resources'        => $resources,
			'groups'           => $groups,
			'owner_org'        => $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'organisation' ],
		);

		if ( isset( $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'reference' ] ) && $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'reference' ] != '' ) {
			$data['id'] = $_POST[ Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'reference' ];
		}

		return $data;
	}

	/**
	 * Transforms custom field values from WP form to a CKAN friendly form.
	 *
	 * @return array CKAN friendly custom fields
	 */
	protected function prepare_custom_fields( $custom_fields ) {
		$ckan_custom_fields = array();

		// Check if custom fields are added. If yes generate CKAN friendly array.
		if ( $custom_fields[0]['key'] != '' ) {
			foreach ( $custom_fields as $custom_field ) {
				$ckan_custom_fields[] = array(
					'key'   => $custom_field['key'],
					'value' => $custom_field['value']
				);
			}
		}

		return $ckan_custom_fields;
	}


	/**
	 * Transforms resources field values from WP form to a CKAN friendly form.
	 *
	 * @return array CKAN friendly custom fields
	 */
	protected function prepare_resources( $resources ) {
		$ckan_resources = array();
		foreach ( $resources as $attachment_id => $url ) {
			$attachment       = get_post( $attachment_id );
			$ckan_resources[] = array(
				'url'         => $url,
				'name'        => $attachment->post_title,
				'description' => $attachment->post_content
			);
		}

		return $ckan_resources;
	}

	/**
	 * Create CKAN friendly array of all selected groups
	 *
	 * @param array $selected_groups IDs of selected groups
	 *
	 * @return array CKAN friendly array with all selected groups
	 */
	protected function prepare_selected_groups( $selected_groups ) {
		$ckan_groups = array();

		if ( is_array( $selected_groups ) ) {
			foreach ( $selected_groups as $key => $group_slug ) {
				$ckan_groups[] = array( 'name' => $group_slug );
			}
		}

		return $ckan_groups;
	}
}