<?php
/**
 * Sync of datasets
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Sync_Local_Dataset
 */
class Ckan_Backend_Sync_Local_Dataset extends Ckan_Backend_Sync_Abstract {
	/**
	 * This method should return an array with the updated data
	 *
	 * @param object $post The post from WordPress.
	 *
	 * @return array $data Updated data to send
	 */
	protected function get_update_data( $post ) {
		$extras    = $this->prepare_custom_fields( $_POST[ $this->field_prefix . 'custom_fields' ] );
		$resources = $this->prepare_resources( $_POST[ $this->field_prefix . 'resources' ] );
		$groups    = $this->prepare_selected_groups( $_POST[ $this->field_prefix . 'groups' ] );

		// Generate slug of dataset. If no title is entered use an uniqid
		if ( $_POST[ $this->field_prefix . 'name' ] !== '' ) {
			$title = $_POST[ $this->field_prefix . 'name' ];
		} else {
			$title = $_POST['post_title'];

			if ( '' === $title ) {
				$title = uniqid();
			}
		}
		$slug = sanitize_title_with_dashes( $title );

		$data = array(
			'name'             => $slug,
			'title'            => $_POST['post_title'], // TODO: use all language here
			'maintainer'       => $_POST[ $this->field_prefix . 'maintainer' ],
			'maintainer_email' => $_POST[ $this->field_prefix . 'maintainer_email' ],
			'author'           => $_POST[ $this->field_prefix . 'author' ],
			'author_email'     => $_POST[ $this->field_prefix . 'author_email' ],
			'notes'            => $_POST[ $this->field_prefix . 'description_de' ], // TODO: use all language here
			'version'          => $_POST[ $this->field_prefix . 'version' ],
			'state'            => 'active',
			'extras'           => $extras,
			'resources'        => $resources,
			'groups'           => $groups,
			'owner_org'        => $_POST[ $this->field_prefix . 'organisation' ],
		);

		if ( isset( $_POST[ $this->field_prefix . 'reference' ] ) && $_POST[ $this->field_prefix . 'reference' ] !== '' ) {
			$data['id'] = $_POST[ $this->field_prefix . 'reference' ];
		}
		// Check if user is allowed to disable datasets -> otherwise reset value
		if ( ! current_user_can( 'disable_datasets' ) ) {
			$disable_value                             = get_post_meta( $post->ID, $_POST[ $this->field_prefix . 'disabled' ], true );
			$_POST[ $this->field_prefix . 'disabled' ] = $disable_value;
		}
		if ( isset( $_POST[ $this->field_prefix . 'disabled' ] ) && $_POST[ $this->field_prefix . 'disabled' ] === 'on' ) {
			$data['state'] = 'deleted';
		}

		return $data;
	}

	/**
	 * Transforms custom field values from WP form to a CKAN friendly form.
	 *
	 * @param array $custom_fields Array of custom field objects.
	 *
	 * @return array CKAN friendly custom fields
	 */
	protected function prepare_custom_fields( $custom_fields ) {
		$ckan_custom_fields = array();

		// Check if custom fields are added. If yes generate CKAN friendly array.
		if ( '' !== $custom_fields[0]['key'] ) {
			foreach ( $custom_fields as $custom_field ) {
				$ckan_custom_fields[] = array(
					'key'   => $custom_field['key'],
					'value' => $custom_field['value'],
				);
			}
		}

		return $ckan_custom_fields;
	}


	/**
	 * Transforms resources field values from WP form to a CKAN friendly form.
	 *
	 * @param array $resources Array of resource objects.
	 *
	 * @return array CKAN friendly custom fields
	 */
	protected function prepare_resources( $resources ) {
		$ckan_resources = array();

		// Check if resources are added. If yes generate CKAN friendly array.
		if ( '' !== $resources[0]['url'] ) {
			foreach ( $resources as $resource ) {
				$ckan_resources[] = array(
					'url'         => $resource['url'],
					'name'        => $resource['title'], // TODO: use all language here
					'description' => $resource['description_de'], // TODO: use all language here
				);
			}
		}

		return $ckan_resources;
	}

	/**
	 * Create CKAN friendly array of all selected groups
	 *
	 * @param array $selected_groups IDs of selected groups.
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
