<?php
/**
 * Sync for the organisation.
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Sync_Local_Organisation
 */
class Ckan_Backend_Sync_Local_Organisation extends Ckan_Backend_Sync_Abstract {
	/**
	 * Hook for after-delete action.
	 *
	 * @param object $post The post being deleted.
	 *
	 * @return void
	 */
	protected function after_delete_action( $post ) {
		// Select related datasets.
		$args = array(
			// @codingStandardsIgnoreStart
			'meta_key'       => Ckan_Backend_Local_Dataset::FIELD_PREFIX . 'organisation',
			'meta_value'     => $post->name,
			// @codingStandardsIgnoreEnd
			'post_type'      => Ckan_Backend_Local_Dataset::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => - 1, // Select all posts
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

		// Generate slug of organisation. If no title is entered use an uniqid
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

		if ( Ckan_Backend_Helper::get_value_for_metafield( $post->ID, $this->field_prefix . 'parent' ) !== '' ) {
			$data['groups'] = array( array( 'name' => Ckan_Backend_Helper::get_value_for_metafield( $post->ID, $this->field_prefix . 'parent' ) ) );
		} else {
			$data['groups'] = array();
		}

		$ckan_id = get_post_meta( $post->ID, $this->field_prefix . 'ckan_id', true );
		if ( '' !== $ckan_id ) {
			$data['id'] = $ckan_id;
		}

		return $data;
	}
}
