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
			'owner_org'   => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'organisation', $load_from_post ),
			'frequency'   => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'update_frequency', $load_from_post ),
			'config'      => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'config', $load_from_post ),
		);

		// set ckan id if its available in database
		$ckan_id = get_post_meta( $post->ID, $this->field_prefix . 'ckan_id', true );
		if ( '' !== $ckan_id ) {
			$data['id'] = $ckan_id;
		}

		return $data;
	}

	/**
	 * Hook for after-sync action.
	 *
	 * @param object $post The post from WordPress.
	 */
	protected function after_sync_action( $post ) {
		// Deletes all transients for this post-type instance.
		delete_transient( Ckan_Backend::$plugin_slug . '_harvesters' );
	}
}
