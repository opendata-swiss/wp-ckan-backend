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
	protected function after_trash_action( $post ) {
		// Select related datasets.
		$args                  = array(
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
		$load_from_post = false;
		if ( isset( $_POST['metadata_not_in_db'] ) && true === (bool) $_POST['metadata_not_in_db'] ) {
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

		if ( Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'parent', $load_from_post ) !== '' ) {
			$data['groups'] = array( array( 'name' => Ckan_Backend_Helper::get_metafield_value( $post->ID, $this->field_prefix . 'parent', $load_from_post ) ) );
		} else {
			$data['groups'] = array();
		}

		// do not change ckan name if there is already one in the database
		$ckan_name = get_post_meta( $post->ID, $this->field_prefix . 'ckan_name', true );
		if ( '' !== $ckan_name ) {
			$data['name'] = $ckan_name;
		}
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
		delete_transient( Ckan_Backend::$plugin_slug . '_organization_options' );
		delete_transient( Ckan_Backend::$plugin_slug . '_organization_' . sanitize_title_with_dashes( $post->post_title ) . '_exists' );
		delete_transient( Ckan_Backend::$plugin_slug . '_organization_title_' . $post->ID );
	}
}
