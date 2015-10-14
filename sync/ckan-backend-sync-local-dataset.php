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
	 * This method should return an array the ckan data
	 *
	 * @param object $post The post from WordPress.
	 *
	 * @return array $data Data to send
	 */
	protected function get_ckan_data( $post ) {
		$load_from_post = false;
		if ( isset( $_POST['metadata_not_in_db'] ) && true === (bool) $_POST['metadata_not_in_db'] ) {
			$load_from_post = true;
		}
		$resources      = $this->prepare_resources( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'distributions', $load_from_post ) );
		$groups         = $this->prepare_selected_groups( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'themes', $load_from_post ) );
		$tags           = $this->prepare_tags( wp_get_object_terms( $post->ID, 'post_tag' ) );
		$titles         = Ckan_Backend_Helper::prepare_multilingual_field( $post->ID, $this->field_prefix . 'title', $load_from_post );
		$descriptions   = Ckan_Backend_Helper::prepare_multilingual_field( $post->ID, $this->field_prefix . 'description', $load_from_post );
		$languages      = $this->gather_languages( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'distributions', $load_from_post ) );
		$issued         = $this->prepare_date( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'issued', $load_from_post ) );
		$modified       = $this->prepare_date( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'modified', $load_from_post ) );
		$identifier     = Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'identifier', $load_from_post );

		$post_name = $post->post_name;
		if ( empty( $post_name ) ) {
			$post_name = sanitize_title_with_dashes( $post->post_title );
		}

		$data = array(
			'name'                => $post_name,
			'title'               => $titles,
			'identifier'          => $identifier['original_identifier'] . '@' . $identifier['organisation'],
			'description'         => $descriptions,
			'issued'              => $issued,
			'modified'            => $modified,
			'publishers'          => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'publishers', $load_from_post ),
			'contact_points'      => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'contact_points', $load_from_post ),
			'language'            => $languages,
			'relations'           => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'relations', $load_from_post ),
			'tags'                => $tags,
			'url'                 => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'landing_page', $load_from_post ),
			'spatial'             => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'spatial', $load_from_post ),
			'coverage'            => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'coverage', $load_from_post ),
			'accrual_periodicity' => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'accrual_periodicity', $load_from_post ),
			'temporals'           => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'temporals', $load_from_post ),
			'see_alsos'           => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'see_alsos', $load_from_post ),
			'resources'           => $resources,
			'groups'              => $groups,
			'state'               => 'active',
			'private'             => true,
		);

		$organisation   = $identifier['organisation'];
		if ( ! empty( $organisation ) ) {
			$data['owner_org'] = $organisation;
		}

		// set ckan id if its available in database
		$ckan_id = get_post_meta( $post->ID, $this->field_prefix . 'ckan_id', true );
		if ( '' !== $ckan_id ) {
			$data['id'] = $ckan_id;
		}
		if ( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'disabled', $load_from_post ) === 'on' ) {
			$data['state'] = 'deleted';
		}
		if ( $post->post_status === 'publish' ) {
			$data['private'] = false;
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
		global $language_priority;
		$ckan_resources = array();

		// Check if resources are added. If yes generate CKAN friendly array.
		if ( '' !== $resources[0]['access_url'] ) {
			foreach ( $resources as $resource ) {
				$titles = array();
				foreach ( $language_priority as $lang ) {
					$titles[ $lang ] = $resource[ 'title_' . $lang ];
				}
				$descriptions = array();
				foreach ( $language_priority as $lang ) {
					$descriptions[ $lang ] = $resource[ 'description_' . $lang ];
				}
				$issued   = $this->prepare_date( $resource['issued'] );
				$modified = $this->prepare_date( $resource['modified'] );

				$ckan_resources[] = array(
					'identifier'   => $resource['identifier'],
					'title'        => $titles,
					'description'  => $descriptions,
					'issued'       => $issued,
					'modified'     => $modified,
					'language'     => $resource['languages'],
					'url'          => $resource['access_url'],
					'download_url' => $resource['download_url'],
					'rights'       => $resource['rights'],
					'license'      => '',
					'byte_size'    => $resource['byte_size'],
					'media_type'   => $resource['media_type'],
					'format'       => $resource['format'],
					'coverage'     => $resource['coverage'],
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
	 * @param array $tags WordPress tags.
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
	 * Gathers languages from all distributions and return them in an array.
	 *
	 * @param array $resources All distributions of the dataset.
	 *
	 * @return array
	 */
	protected function gather_languages( $resources ) {
		$languages = array();

		// Check if resources are added. If yes generate CKAN friendly array.
		if ( '' !== $resources[0]['download_url'] ) {
			foreach ( $resources as $resource ) {
				$languages = array_merge( $languages, $resource['languages'] );
			}
		}

		return $languages;
	}

	/**
	 * Creates a CKAN friendly date and returns it.
	 *
	 * @param array|string $datetime Datetime field as array or string.
	 *
	 * @return string
	 */
	protected function prepare_date( $datetime ) {
		if ( is_array( $datetime ) && array_key_exists( 'date', $datetime ) ) {
			$datetime = $datetime['date'];
		}

		return strtotime( $datetime );
	}

	/**
	 * Hook for after-sync action.
	 *
	 * @param object $post The post from WordPress.
	 */
	protected function after_sync_action( $post ) {
		// Deletes all transients for this post-type instance.
		delete_transient( Ckan_Backend::$plugin_slug . '_dataset_title_' . $post->post_name );
	}
}
