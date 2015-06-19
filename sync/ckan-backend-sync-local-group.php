<?php

class Ckan_Backend_Sync_Local_Group extends Ckan_Backend_Sync_Abstract {

	public function __construct() {
		parent::__construct( Ckan_Backend_Local_Group::POST_TYPE, Ckan_Backend_Local_Group::FIELD_PREFIX );
	}

	protected function get_update_data() {
		// Gernerate slug of group. If no title is entered use an uniqid
		if ( $_POST[Ckan_Backend_Local_Group::FIELD_PREFIX . 'name'] != '' ) {
			$title = $_POST[Ckan_Backend_Local_Group::FIELD_PREFIX . 'name'];
		} else {
			$title = $_POST['post_title'];

			if ( $title === '' ) {
				$title = uniqid();
			}
		}
		$slug = sanitize_title_with_dashes( $title );

		$data = array(
			'name'             => $slug,
			'title'            => $_POST['post_title'], // TODO: use all language here
			'description'      => $_POST[Ckan_Backend_Local_Group::FIELD_PREFIX . 'description_de'], // TODO: use all language here
			'image_url'        => $_POST[Ckan_Backend_Local_Group::FIELD_PREFIX . 'image'],
			'state'            => $_POST[Ckan_Backend_Local_Group::FIELD_PREFIX . 'visibility'],
		);

		if ( isset( $_POST[ Ckan_Backend_Local_Group::FIELD_PREFIX . 'reference' ] ) && $_POST[ Ckan_Backend_Local_Group::FIELD_PREFIX . 'reference' ] != '' ) {
			$data['id'] = $_POST[ Ckan_Backend_Local_Group::FIELD_PREFIX . 'reference' ];
		}

		return $data;
	}
}