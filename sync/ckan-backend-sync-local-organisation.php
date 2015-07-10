<?php

class Ckan_Backend_Sync_Local_Organisation extends Ckan_Backend_Sync_Abstract {
	protected function after_delete_action( $post ) {
		// Select related datasets
		$args = array(
			'meta_key'       => Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'organisation',
			'meta_value'     => $post->name,
			'post_type'      => Ckan_Backend_Local_Dataset::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => - 1 // select all posts
		);
		$related_dataset_posts = get_posts( $args );

		foreach ( $related_dataset_posts as $dataset_post ) {
			// CKAN removes organisation relationship on delete -> so do we
			update_post_meta( $dataset_post->ID, Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'organisation', '' );
			// CKAN sets all related datasets to deleted -> so do we
			wp_trash_post( $dataset_post->ID );
		}

		// CKAN removes parent relationship on delete -> so do we
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