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
	 * @param WP_Post $post The post from WordPress.
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
		$keywords       = $this->prepare_keywords( $post );
		$tags           = $this->prepare_tags( Ckan_Backend_Helper::flatten( $keywords ) );
		$titles         = Ckan_Backend_Helper::prepare_multilingual_field( $post->ID, $this->field_prefix . 'title', $load_from_post );
		$descriptions   = Ckan_Backend_Helper::prepare_multilingual_field( $post->ID, $this->field_prefix . 'description', $load_from_post );
		$languages      = $this->gather_languages( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'distributions', $load_from_post ) );
		$issued         = $this->prepare_date( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'issued', $load_from_post ) );
		$modified       = $this->prepare_date( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'modified', $load_from_post ) );
		$identifier     = Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'identifier', $load_from_post );
		$relations      = $this->prepare_relations( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'relations', $load_from_post ) );
		$temporals      = $this->prepare_temporals( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'temporals', $load_from_post ) );
		$see_alsos      = $this->prepare_see_alsos( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'see_alsos', $load_from_post ) );

		$post_name = $post->post_name;
		if ( empty( $post_name ) ) {
			$post_name = sanitize_title( $post->post_title );
		}

		// if user is not allowed to change organisation -> reset organisation in identifier
		if ( ! current_user_can( 'edit_data_of_all_organisations' ) ) {
			if ( ! Ckan_Backend_Helper::is_own_organization( $identifier['organisation'], get_current_user_id() ) ) {
				$original_identifier = get_post_meta( $post->ID, $this->field_prefix . 'identifier', true );
				$identifier['organisation'] = $original_identifier['organisation'];
				$_POST[ $this->field_prefix . 'identifier' ] = $identifier;
			}
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
			'keywords'            => $keywords,
			'tags'                => $tags,
			'url'                 => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'landing_page', $load_from_post ),
			'spatial'             => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'spatial', $load_from_post ),
			'coverage'            => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'coverage', $load_from_post ),
			'accrual_periodicity' => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'accrual_periodicity', $load_from_post ),
			'resources'           => $resources,
			'groups'              => $groups,
			'state'               => 'active',
			'private'             => true,
			'relations'           => $relations,
			'temporals'           => $temporals,
			'see_alsos'           => $see_alsos,
		);

		$organisation = $identifier['organisation'];
		if ( ! empty( $organisation ) ) {
			$data['owner_org'] = $organisation;
		}

		// set ckan id if its available in database
		$ckan_id = get_post_meta( $post->ID, $this->field_prefix . 'ckan_id', true );
		if ( ! empty( $ckan_id ) ) {
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

		if ( is_array( $resources ) ) {
			foreach ( $resources as $resource ) {
				// Check if at least one mandatory field (access_url) is filled out. Because we don't want to add empty repeatable fields.
				if ( ! empty( $resource['access_url'] ) ) {
					$titles = array();
					$descriptions = array();
					foreach ( $language_priority as $lang ) {
						if ( key_exists( 'title_' . $lang, $resource ) ) {
							$titles[ $lang ] = $resource[ 'title_' . $lang ];
						} else {
							// use empty string instead of null because some ckan validators (fluent_text) don't allow null values
							$titles[ $lang ] = '';
						}
						if ( key_exists( 'description_' . $lang, $resource ) ) {
							$descriptions[ $lang ] = $resource[ 'description_' . $lang ];
						} else {
							// use empty string instead of null because some ckan validators (fluent_text) don't allow null values
							$descriptions[ $lang ] = '';
						}
					}
					$issued   = $this->prepare_date( $resource['issued'] );
					$modified = $this->prepare_date( $resource['modified'] );

					$ckan_resources[] = array(
						'identifier'   => $resource['identifier'],
						'title'        => $titles,
						'description'  => $descriptions,
						'issued'       => $issued,
						'modified'     => $modified,
						'language'     => key_exists( 'languages', $resource ) ? $resource['languages'] : array(),
						'url'          => $resource['access_url'],
						'download_url' => $resource['download_url'],
						'rights'       => $resource['rights'],
						'license'      => '',
						'byte_size'    => $resource['byte_size'],
						'media_type'   => $resource['media_type'],
						'format'       => key_exists( 'format', $resource ) ? $resource['format'] : '',
						'coverage'     => key_exists( 'coverage', $resource ) ? $resource['coverage'] : '',
					);
				}
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
	 * Create CKAN friendly array of all keywords
	 *
	 * @param WP_Post $post WordPress post.
	 *
	 * @return array
	 */
	protected function prepare_keywords( $post ) {
		$ckan_keywords = array(
			'de' => array(),
			'fr' => array(),
			'en' => array(),
			'it' => array(),
		);

		foreach ( Ckan_Backend::$keywords_tax_mapping as $lang => $taxonomy ) {
			$keywords = wp_get_post_terms( $post->ID, $taxonomy );
			foreach ( $keywords as $keyword ) {
				$ckan_keywords[ $lang ][] = $keyword->name;
			}
		}

		return $ckan_keywords;
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

		if ( is_array( $tags ) ) {
			foreach ( $tags as $tag ) {
				$ckan_tags[] = array(
					'name' => $tag,
				);
			}
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

		if ( is_array( $resources ) ) {
			foreach ( $resources as $resource ) {
				// Check if at least one mandatory field (access_url) is filled out. Because we don't want to add empty repeatable fields.
				if ( ! empty( $resource['access_url'] ) && key_exists( 'languages', $resource ) ) {
					$languages = array_merge( $languages, $resource['languages'] );
				}
			}
		}

		return $languages;
	}

	/**
	 * Creates CKAN friendly temporals field
	 *
	 * @param array $temporals Temporals.
	 *
	 * @return array
	 */
	protected function prepare_temporals( $temporals ) {
		$ckan_temporals = array();

		if ( is_array( $temporals ) ) {
			foreach ( $temporals as $temporal ) {
				// Check if at least one mandatory field (start_date) is filled out. Because we don't want to add empty repeatable fields.
				if ( ! empty( $temporal['start_date'] ) ) {
					$ckan_temporals[] = array(
						'start_date' => $this->prepare_date( $temporal['start_date'] ),
						'end_date'   => $this->prepare_date( $temporal['end_date'] ),
					);
				}
			}
		}

		return $ckan_temporals;
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

		// if $datetime is already a timestamp
		if ( is_int( $datetime ) ) {
			return $datetime;
		}

		return strtotime( $datetime );
	}

	/**
	 * Creates a CKAN friendly relations and returns it.
	 *
	 * @param array $relations All relations of the dataset.
	 *
	 * @return array
	 */
	protected function prepare_relations( $relations ) {
		$ckan_relations = array();

		if ( is_array( $relations ) ) {
			foreach ( $relations as $relation ) {
				// Check if at least one mandatory field (url) is filled out. Because we don't want to add empty repeatable fields.
				if ( ! empty( $relation['url'] ) ) {
					$ckan_relations[] = array(
						'url'   => $relation['url'],
						'label' => $relation['label'],
					);
				}
			}
		}

		return $ckan_relations;
	}

	/**
	 * Creates a CKAN friendly see alsos and returns it.
	 *
	 * @param array $see_alsos All see alsos of the dataset.
	 *
	 * @return array
	 */
	protected function prepare_see_alsos( $see_alsos ) {
		$ckan_see_alsos = array();

		if ( is_array( $see_alsos ) ) {
			foreach ( $see_alsos as $see_also ) {
				// Check if at least one mandatory field (dataset_identifier) is filled out. Because we don't want to add empty repeatable fields.
				if ( ! empty( $see_also['dataset_identifier'] ) ) {
					$ckan_see_alsos[] = array(
						'dataset_identifier' => $see_also['dataset_identifier'],
					);
				}
			}
		}

		return $ckan_see_alsos;
	}

	/**
	 * Hook for after-sync action.
	 *
	 * @param object $post The post from WordPress.
	 */
	protected function after_sync_action( $post ) {
		// Deletes all transients for this post-type instance.
		$identifier = get_post_meta( $post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'identifier', true );
		if ( ! empty( $identifier ) ) {
			delete_transient( Ckan_Backend::$plugin_slug . '_dataset_' . $identifier['original_identifier'] . '@' . $identifier['organisation'] );
		}
	}
}
