<?php

class Ckan_Backend_Sync_Local_Dataset extends Ckan_Backend_Sync_Abstract {

	public function __construct() {
		parent::__construct( Ckan_Backend_Local_Dataset::POST_TYPE, Ckan_Backend_Local_Dataset::FIELD_PREFIX );
	}

	protected function get_update_data() {
		$ckan_organisation_slug = $this->get_selected_organisation_slug( $_POST['ckan_organisation'] );
		$extras                 = $this->prepare_custom_fields( $_POST['_ckan_local_dataset_custom_fields'] );
		$resources              = $this->prepare_resources( $_POST['_ckan_local_dataset_resources'] );
		$groups                 = $this->get_selected_groups( $_POST['tax_input']['ckan_group'] );

		// Gernerate slug of dataset. If no title is entered use an uniqid
		if ( $_POST[Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'name'] != '' ) {
			$title = $_POST[Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'name'];
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
			'maintainer'       => $_POST['_ckan_local_dataset_maintainer'],
			'maintainer_email' => $_POST['_ckan_local_dataset_maintainer_email'],
			'author'           => $_POST['_ckan_local_dataset_author'],
			'author_email'     => $_POST['_ckan_local_dataset_author_email'],
			'notes'            => $_POST['_ckan_local_dataset_description_de'], // TODO: use all language here
			'version'          => $_POST['_ckan_local_dataset_version'],
			'state'            => $_POST['_ckan_local_dataset_visibility'],
			'extras'           => $extras,
			'resources'        => $resources,
			'groups'           => $groups,
			'owner_org'        => $ckan_organisation_slug
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
	 * Gets slug from selected organisation
	 *
	 * @param int $organisation_id
	 *
	 * @return string Slug of organisation or empty string if organisation wasn't found
	 */
	protected function get_selected_organisation_slug( $organisation_id ) {
		if ( $organisation_id < 1 ) {
			return '';
		}

		$organisation = get_term( $organisation_id, 'ckan_organisation' );
		if ( is_object( $organisation ) && $organisation->slug != '' ) {
			return $organisation->slug;
		}

		return '';
	}

	/**
	 * Create CKAN friendly array of all selected groups
	 *
	 * @param array $selected_groups IDs of selected groups
	 *
	 * @return array CKAN friendly array with all selected groups
	 */
	protected function get_selected_groups( $selected_groups ) {
		$ckan_groups = array();

		foreach ( $selected_groups as $group_id ) {
			// First entry is always a 0 -> not used
			if ( $group_id == 0 ) {
				continue;
			}

			$group = get_term( $group_id, 'ckan_group' );
			if ( is_object( $group ) && $group->slug != '' ) {
				$ckan_groups[] = array( 'name' => $group->slug );
			}
		}

		return $ckan_groups;
	}
}