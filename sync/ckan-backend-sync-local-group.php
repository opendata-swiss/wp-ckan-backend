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
		$load_from_post = false;
		if( isset( $_POST['metadata_not_in_db'] ) && $_POST['metadata_not_in_db'] == true ) {
			$load_from_post = true;
		}
		$titles       = Ckan_Backend_Helper::prepare_multilingual_field( $post->ID, $this->field_prefix . 'title', $load_from_post );
		$descriptions = Ckan_Backend_Helper::prepare_multilingual_field( $post->ID, $this->field_prefix . 'description', $load_from_post );

		$data = array(
			'name'        => sanitize_title_with_dashes( $post->post_title ),
			'title'       => $titles,
			'description' => $descriptions,
			'image_url'   => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'image', $load_from_post ),
			'state'       => 'active',
		);

		$ckan_id = get_post_meta( $post->ID, $this->field_prefix . 'ckan_id', true );
		if ( '' !== $ckan_id ) {
			$data['id'] = $ckan_id;
		}

		return $data;
	}
}
