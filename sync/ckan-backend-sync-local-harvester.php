<?php
/**
 * Sync for the harvester.
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Sync_Local_Harvester
 */
class Ckan_Backend_Sync_Local_Harvester extends Ckan_Backend_Sync_Abstract {
	/**
	* This method should return an array the ckan data
	*
	* @param object $post The post from WordPress.
	*
	* @return array $data Data to send
	*/
	protected function get_ckan_data( $post ) {
		// Harvesters are only manageable in the WordPress GUI -> always use $_POST values
		$load_from_post = true;

		$config = json_encode( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'configuration', $load_from_post ) );

		$post_name = $post->post_name;
		if ( empty( $post_name ) ) {
			$post_name = sanitize_title_with_dashes( $post->post_title );
		}

		$data = array(
			'name'        => $post_name,
			'title'       => $post->post_title,
			'url'         => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'url', $load_from_post ),
			'notes'       => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'description', $load_from_post ),
			'source_type' => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'source_type', $load_from_post ),
			'frequency'   => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'update_frequency', $load_from_post ),
			'config'      => $config,
		);

		// set ckan id if its available in database
		$ckan_id = get_post_meta( $post->ID, $this->field_prefix . 'ckan_id', true );
		if ( '' !== $ckan_id ) {
			$data['id'] = $ckan_id;
		}

		return $data;
	}
}
