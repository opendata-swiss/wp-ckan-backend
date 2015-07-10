<?php

class Ckan_Backend_Sync_Local_Group extends Ckan_Backend_Sync_Abstract {
	protected function get_update_data() {
		// Gernerate slug of group. If no title is entered use an uniqid
		if ( $_POST[ $this->field_prefix . 'name' ] != '' ) {
			$title = $_POST[ $this->field_prefix . 'name' ];
		} else {
			$title = $_POST['post_title'];

			if ( $title === '' ) {
				$title = uniqid();
			}
		}
		$slug = sanitize_title_with_dashes( $title );

		$data = array(
			'name'        => $slug,
			'title'       => $_POST['post_title'], // TODO: use all language here
			'description' => $_POST[ $this->field_prefix . 'description_de' ], // TODO: use all language here
			'image_url'   => $_POST[ $this->field_prefix . 'image' ],
			'state'       => 'active',
		);

		if ( isset( $_POST[ $this->field_prefix . 'reference' ] ) && $_POST[ $this->field_prefix . 'reference' ] != '' ) {
			$data['id'] = $_POST[ $this->field_prefix . 'reference' ];
		}

		return $data;
	}
}