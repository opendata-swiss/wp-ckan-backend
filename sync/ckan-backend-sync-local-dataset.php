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
		if( isset( $_POST['metadata_not_in_db'] ) && $_POST['metadata_not_in_db'] == true ) {
			$load_from_post = true;
		}
		$resources    = $this->prepare_resources( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'distributions', $load_from_post ) );
		$groups       = $this->prepare_selected_groups( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'themes', $load_from_post ) );
		$tags         = $this->prepare_tags( wp_get_object_terms( $post->ID, 'post_tag' ) );
		$titles       = Ckan_Backend_Helper::prepare_multilingual_field( $post->ID, $this->field_prefix . 'title', $load_from_post );
		$descriptions = Ckan_Backend_Helper::prepare_multilingual_field( $post->ID, $this->field_prefix . 'description', $load_from_post );

		/**
		 * TODO
		 * - calculate languages from all distribution[languages]
		 * - if distribution[access_urls] not exists use distribution[access_url]
		 * - if distribution[download_urls] not exists use distribution[download_url]
		 */

		$contact_points = Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'contact_points', $load_from_post );

		$data = array(
			'name'             => sanitize_title_with_dashes( $post->post_title ),
			'title'            => $titles,
			'maintainer'       => $contact_points[0]['name'],
			'maintainer_email' => $contact_points[0]['email'],
			'notes'            => $descriptions,
			'state'            => 'active',
			'private'          => true,
			'resources'        => $resources,
			'groups'           => $groups,
			'owner_org'        => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'publisher', $load_from_post ),
			'tags'             => $tags,
		);

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
		if ( '' !== $resources[0]['download_url'] ) {
			foreach ( $resources as $resource ) {

				$titles = array();
				foreach ( $language_priority as $lang ) {
					$titles[ $lang ] = $resource[ 'title_' . $lang ];
				}
				$descriptions = array();
				foreach ( $language_priority as $lang ) {
					$descriptions[ $lang ] = $resource[ 'description_' . $lang ];
				}

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
}
