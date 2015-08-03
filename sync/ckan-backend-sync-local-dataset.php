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
		$resources    = $this->prepare_resources( $_POST[ $this->field_prefix . 'distributions' ] );
		$groups       = $this->prepare_selected_groups( $_POST[ $this->field_prefix . 'themes' ] );
		$tags         = $this->prepare_tags( wp_get_object_terms( $post->ID, 'post_tag' ) );
		$titles       = $this->prepare_multilingual_field( $_POST, $this->field_prefix . 'title' );
		$descriptions = $this->prepare_multilingual_field( $_POST, $this->field_prefix . 'description' );

		// Generate slug of dataset. If no title is entered use an uniqid
		if ( $_POST[ $this->field_prefix . 'title_en' ] !== '' ) {
			$slug = $_POST[ $this->field_prefix . 'title_en' ];
		} else {
			$slug = $post->post_title;

			if ( '' === $slug ) {
				$slug = uniqid();
			}
		}
		$slug = sanitize_title_with_dashes( $slug );

		/**
		 * TODO
		 * - if publisher not exists us publishers[0]
		 * - if distribution[languages] not exists use languages
		 * - if distribution[access_urls] not exists use distribution[access_url]
		 * - if distribution[download_urls] not exists use distribution[download_url]
		 */

		$data = array(
			'name'             => $slug,
			'title'            => $titles,
			'maintainer'       => $_POST[ $this->field_prefix . 'contact_points' ][0]['name'],
			'maintainer_email' => $_POST[ $this->field_prefix . 'contact_points' ][0]['email'],
			'notes'            => $descriptions,
			'state'            => 'active',
			'resources'        => $resources,
			'groups'           => $groups,
			'owner_org'        => $_POST[ $this->field_prefix . 'publisher' ],
			'tags'             => $tags,
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
	 * Transforms resources field values from WP form to a CKAN friendly form.
	 *
	 * @param array $resources Array of resource objects.
	 *
	 * @return array CKAN friendly resources
	 */
	protected function prepare_resources( $resources ) {
		$ckan_resources = array();

		// Check if resources are added. If yes generate CKAN friendly array.
		if ( '' !== $resources[0]['download_url'] ) {
			foreach ( $resources as $resource ) {
				$titles       = $this->prepare_multilingual_field( $resource, 'title' );
				$descriptions = $this->prepare_multilingual_field( $resource, 'description' );
				$ckan_resources[] = array(
					'url'         => $resource['download_url'],
					'name'        => $titles,
					'description' => $descriptions,
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
				$ckan_groups[] = array(
					'name' => $group_slug,
				);
			}
		}

		return $ckan_groups;
	}

	/**
	 * Create CKAN friendly array of all tags
	 *
	 * @param string $tags Comma-seperated tags.
	 *
	 * @return array
	 */
	protected function prepare_tags( $tags ) {
		$ckan_tags = array();

		foreach ( $tags as $tag ) {
			$ckan_tags[] = array(
				'name' => $tag->name,
			);
		}

		return $ckan_tags;
	}

	/**
	 * Returns a CKAN friendly array for multilingual fields
	 *
	 * @param array $base_array Array with the raw values. Format: array( 'field_de', 'field_en', ... ).
	 * @param string $field_name Name of the field.
	 *
	 * @return array
	 */
	protected function prepare_multilingual_field( $base_array, $field_name ) {
		global $language_priority;

		$multilingual_field = array();
		foreach ( $language_priority as $lang ) {
			$multilingual_field[ $lang ] = $base_array[ $field_name . '_' . $lang ];
		}
		return $multilingual_field;
	}
}
