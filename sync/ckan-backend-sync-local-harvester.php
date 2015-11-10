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
		return $post;
	}
}
