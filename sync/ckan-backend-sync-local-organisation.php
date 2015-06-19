<?php

class Ckan_Backend_Sync_Local_Organisation extends Ckan_Backend_Sync_Abstract {

	public function __construct() {
		parent::__construct( Ckan_Backend_Local_Organisation::POST_TYPE, Ckan_Backend_Local_Organisation::FIELD_PREFIX );
	}

	protected function get_update_data() {
		// Gernerate slug of organisation. If no title is entered use an uniqid
		if ( $_POST[Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'name'] != '' ) {
			$title = $_POST[Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'name'];
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
			'description'      => $_POST[Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'description_de'], // TODO: use all language here
			'image_url'        => $_POST[Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'image'],
			'state'            => $_POST[Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'visibility'],
		);

		if ( isset( $_POST[ Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'reference' ] ) && $_POST[ Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'reference' ] != '' ) {
			$data['id'] = $_POST[ Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'reference' ];
		}

		return $data;
	}
}