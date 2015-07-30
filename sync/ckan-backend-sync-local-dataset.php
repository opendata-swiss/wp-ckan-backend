<?php

class Ckan_Backend_Sync_Local_Dataset extends Ckan_Backend_Sync_Abstract {
	protected function get_update_data( $post ) {
		$resources = $this->prepare_resources( $_POST[ $this->field_prefix . 'distributions' ] );
		$groups    = $this->prepare_selected_groups( $_POST[ $this->field_prefix . 'themes' ] );
		$tags      = $this->prepare_tags( $_POST['tax_input']['post_tag'] );

		// Gernerate slug of dataset. If no title is entered use an uniqid
		if ( $_POST[ $this->field_prefix . 'title_en' ] != '' ) {
			$slug = $_POST[ $this->field_prefix . 'title_en' ];
		} else {
			$slug = $_POST['post_title'];

			if ( $slug === '' ) {
				$slug = uniqid();
			}
		}
		$slug = sanitize_title_with_dashes( $slug );

		/**
		 * TODO
		 * - if publisher not exists us publishers[0]
		 * - if distribution[languages] not exists use languages
		 * - use tags as keywords
		 * - if distribution[access_urls] not exists use distribution[access_url]
		 * - if distribution[download_urls] not exists use distribution[download_url]
		 */

		$data = array(
			'name'             => $slug,
			'title'            => $_POST[ $this->field_prefix . 'title_de' ], // TODO: use all language here
			'maintainer'       => $_POST[ $this->field_prefix . 'contact_points' ][0]['name'],
			'maintainer_email' => $_POST[ $this->field_prefix . 'contact_points' ][0]['email'],
			'notes'            => $_POST[ $this->field_prefix . 'description_de' ], // TODO: use all language here
			'state'            => 'active',
			'resources'        => $resources,
			'groups'           => $groups,
			'owner_org'        => $_POST[ $this->field_prefix . 'publisher' ],
			'tags'             => $tags,
		);

		if ( isset( $_POST[ $this->field_prefix . 'reference' ] ) && $_POST[ $this->field_prefix . 'reference' ] != '' ) {
			$data['id'] = $_POST[ $this->field_prefix . 'reference' ];
		}
		// Check if user is allowed to disable datasets -> otherwise reset value
		if ( ! current_user_can( 'disable_datasets' ) ) {
			$disable_value                             = get_post_meta( $post->ID, $_POST[ $this->field_prefix . 'disabled' ], true );
			$_POST[ $this->field_prefix . 'disabled' ] = $disable_value;
		}
		if ( isset( $_POST[ $this->field_prefix . 'disabled' ] ) && $_POST[ $this->field_prefix . 'disabled' ] == 'on' ) {
			$data['state'] = 'deleted';
		}

		return $data;
	}

	/**
	 * Transforms resources field values from WP form to a CKAN friendly form.
	 *
	 * @return array CKAN friendly resources
	 */
	protected function prepare_resources( $resources ) {
		$ckan_resources = array();

		// Check if resources are added. If yes generate CKAN friendly array.
		if ( $resources[0]['download_url'] != '' ) {
			foreach ( $resources as $resource ) {
				$ckan_resources[] = array(
					'url'         => $resource['download_url'],
					'name'        => $resource['title_de'], // TODO: use all language here
					'description' => $resource['description_de'] // TODO: use all language here
				);
			}
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
				$ckan_groups[] = array(
					'name' => $group_slug
				);
			}
		}

		return $ckan_groups;
	}

	protected function prepare_tags( $tags ) {
		$ckan_tags = array();

		$tags_array = explode( ', ', $tags );
		foreach ( $tags_array as $tag ) {
			$ckan_tags[] = array(
				'name' => $tag
			);
		}

		return $ckan_tags;
	}
}