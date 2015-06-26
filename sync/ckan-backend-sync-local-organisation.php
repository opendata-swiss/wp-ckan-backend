<?php

class Ckan_Backend_Sync_Local_Organisation extends Ckan_Backend_Sync_Abstract {
	protected function additional_delete_action( $post ) {
		// CKAN removes parent connection on delete -> so do we
		update_post_meta( $post->ID, $this->field_prefix . 'parent', '' );
	}

	protected function get_update_data() {
		// Gernerate slug of organisation. If no title is entered use an uniqid
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

		if ( $_POST[ $this->field_prefix . 'parent' ] != '' ) {
			$data['groups'] = array( array( 'name' => $_POST[ $this->field_prefix . 'parent' ] ) );
		}

		if ( isset( $_POST[ $this->field_prefix . 'reference' ] ) && $_POST[ $this->field_prefix . 'reference' ] != '' ) {
			$data['id'] = $_POST[ $this->field_prefix . 'reference' ];
		}

		return $data;
	}
}