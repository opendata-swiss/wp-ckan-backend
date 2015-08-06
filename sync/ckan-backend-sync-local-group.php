<?php
/**
 * Sync of groups
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Sync_Local_Group
 */
class Ckan_Backend_Sync_Local_Group extends Ckan_Backend_Sync_Abstract {
	/**
	 * This method should return an array with the updated data
	 *
	 * @param object $post The post from WordPress.
	 *
	 * @return array $data Updated data to send
	 */
	protected function get_ckan_data( $post ) {
		$titles       = Ckan_Backend_Helper::prepare_multilingual_field( $post->ID, $this->field_prefix . 'title' );
		$descriptions = Ckan_Backend_Helper::prepare_multilingual_field( $post->ID, $this->field_prefix . 'description' );

		// Gernerate slug of group. If no title is entered use an uniqid
		if ( Ckan_Backend_Helper::get_value_for_metafield( $post->ID, $this->field_prefix . 'title_en' ) !== '' ) {
			$slug = Ckan_Backend_Helper::get_value_for_metafield( $post->ID, $this->field_prefix . 'title_en' );
		} else {
			$slug = $post->post_title;

			if ( '' === $slug ) {
				$slug = uniqid();
			}
		}
		$slug = sanitize_title_with_dashes( $slug );

		$data = array(
			'name'        => $slug,
			'title'       => $titles,
			'description' => $descriptions,
			'image_url'   => Ckan_Backend_Helper::get_value_for_metafield( $post->ID, $this->field_prefix . 'image' ),
			'state'       => 'active',
		);

		$ckan_id = get_post_meta( $post->ID, $this->field_prefix . 'reference', true );
		if ( $ckan_id !== '' ) {
			$data['id'] = $ckan_id;
		}

		return $data;
	}
}
