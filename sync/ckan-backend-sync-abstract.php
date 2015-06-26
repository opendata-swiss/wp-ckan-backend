<?php

abstract class Ckan_Backend_Sync_Abstract {

	public $post_type = '';
	public $field_prefix = '';
	private $type_api_mapping = array(
		'ckan-local-dataset' => 'package',
		'ckan-local-org'     => 'organization',
		'ckan-local-group'   => 'group',
	);
	private $api_type = '';

	public function __construct( $post_type, $field_prefix ) {
		$this->post_type    = $post_type;
		$this->field_prefix = $field_prefix;

		if ( array_key_exists( $post_type, $this->type_api_mapping ) ) {
			$this->api_type = $this->type_api_mapping[ $post_type ];
		} else {
			return false;
		}

		// add save post action for current post type
		add_action( 'save_post_' . $this->post_type, array( $this, 'do_sync' ), 0, 2 );

		// add before delete post action
		add_action( 'before_delete_post', array( $this, 'do_delete' ), 0, 1 );

		// display all notices after saving post
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ), 0 );
	}

	/**
	 * This action gets called when a CKAN post-type is saved, changed, trashed or deleted.
	 */
	public function do_sync( $post_id, $post ) {
		// TODO use publish settings from WP for visibility

		// Exit if WP is doing an auto-save
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// If action is trash -> set CKAN dataset to deleted
		if ( isset( $_GET ) && ( $_GET['action'] === 'trash' ) ) {
			$success = $this->trash_action( $post_id );
		} // If action is untrash -> set CKAN dataset to active
		elseif ( isset( $_GET ) && $_GET['action'] === 'untrash' ) {
			$success = $this->untrash_action( $post_id );
		} // If action is delete -> delete the CKAN dataset completely
		elseif ( isset( $_GET ) && $_GET['action'] === 'delete' ) {
			// The delete action is handled by the before_delete_post hook -> do nothing
			return;
		} // Or generate data for insert/update
		else {
			// Exit if saved post is a revision (revisions are deactivated in wp-config... but just in case)
			if ( wp_is_post_revision( $post_id ) || ! isset( $post->post_status ) ) {
				return;
			}

			if ( $post->post_status == 'publish' ) {
				$data = $this->get_update_data();
				// If post data holds reference id -> do update in CKAN
				if ( isset( $data['id'] ) ) {
					$success = $this->update_action( $data );
				} else {
					// Insert new dataset
					$success = $this->insert_action( $post, $data );
				}
			} else {
				// if post gets unpublished -> set CKAN dataset to deleted
				$success = $this->trash_action( $post_id );
			}
		}
		return $success;
	}

	/**
	 * Gets called when a CKAN post-type is deleted.
	 *
	 * @return bool|void
	 */
	public function do_delete( $post_id ) {
		global $post_type;
		if ( $post_type != $this->post_type ) {
			return;
		}

		return $this->delete_action( $post_id );
	}

	/**
	 * Just a convenience function which calls trash_action with preset untrash parameter
	 *
	 * @param int $post_id ID of CKAN post-type
	 *
	 * @return bool True when CKAN request was successful.
	 */
	protected function untrash_action( $post_id ) {
		return $this->trash_action( $post_id, true );
	}

	/**
	 * Gets called when a CKAN data is trashed or untrashed.
	 * Updates CKAN state.
	 *
	 * @param int $post_id ID of CKAN post-type
	 * @param bool $untrash Set true if dataset should be untrashed
	 *
	 * @return bool True when CKAN request was successful.
	 */
	protected function trash_action( $post_id, $untrash = false ) {
		$ckan_ref = get_post_meta( $post_id, $this->field_prefix . 'reference', true );

		// If no CKAN reference id is defined don't send request a to CKAN
		if ( $ckan_ref === '' ) {
			return true;
		}

		$data     = array(
			'id' => $ckan_ref
		);
		if ( $untrash ) {
			// Set CKAN state to active.
			$data['state'] = 'active';
		} else {
			// Set CKAN state to deleted
			$data['state'] = 'deleted';
		}

		return $this->update_action($data);
	}

	/**
	 * Purges data in CKAN database
	 *
	 * @param int $post_id ID of CKAN post-type
	 *
	 * @return bool True when CKAN request was successful.
	 */
	protected function delete_action( $post_id ) {
		$ckan_ref = get_post_meta( $post_id, $this->field_prefix . 'reference', true );

		// If no CKAN reference id is defined don't send request a to CKAN
		if ( $ckan_ref === '' ) {
			return true;
		}

		$endpoint = CKAN_API_ENDPOINT . 'action/' . $this->api_type . '_delete';
		$data     = array(
			'id' => $ckan_ref
		);
		$data     = json_encode( $data );
		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );
		$this->store_errors_in_notices_option( $errors );

		// Return true if there were no errors
		return count( $errors ) == 0;
	}

	/**
	 * This method should return an array with the updated data
	 *
	 * @return array $data Updated data to send
	 */
	abstract protected function get_update_data();

	/**
	 * Gets called when a CKAN data is updated.
	 * Sends updated data to CKAN.
	 *
	 * @param array $data The updated data to send
	 *
	 * @return bool True if data was successfully updated in CKAN
	 */
	protected function update_action( $data ) {
		// Define endpoint for request
		$endpoint = CKAN_API_ENDPOINT . 'action/' . $this->api_type . '_patch';

		$data = json_encode( $data );
		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );
		$this->store_errors_in_notices_option( $errors );

		// Return true if there were no errors
		return count( $errors ) == 0;
	}

	/**
	 * Gets called when a CKAN data is inserted.
	 * Sends inserted data to CKAN and updates reference id and name (slug) from CKAN.

	 *
	 * @param object $post The post from WordPress which is inserted
	 * @param array $data The inserted data to send
	 *
	 * @return bool True if data was successfully inserted in CKAN
	 */
	protected function insert_action( $post, $data ) {
		// Define endpoint for request
		$endpoint = CKAN_API_ENDPOINT . 'action/' . $this->api_type . '_create';

		$data = json_encode( $data );

		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );
		$this->store_errors_in_notices_option( $errors );
		if ( count( $errors ) == 0 ) {
			$result = $response['result'];
			if ( isset( $result['id'] ) && $result['id'] != '' ) {
				// Set reference id from CKAN and add it to $_POST because the real meta save will follow after this action
				update_post_meta( $post->ID, $this->field_prefix . 'reference', $result['id'] );
				update_post_meta( $post->ID, $this->field_prefix . 'name', $result['name'] );
				$_POST[ $this->field_prefix . 'reference' ] = $result['id'];
				$_POST[ $this->field_prefix . 'name' ]      = $result['name'];
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Stores all error messages in Option to print them out after redirect of save action
	 *
	 * @param array $errors Array with error messages
	 *
	 * @return bool True if error messages were stored successfully
	 */
	protected function store_errors_in_notices_option( $errors ) {
		if ( is_array( $errors ) && count( $errors ) > 0 ) {
			// store all error notices in option array
			$notices = get_option( $this->field_prefix . 'notices' );
			foreach ( $errors as $key => $m ) {
				$notices[] = $m;
			}
			return update_option( $this->field_prefix . 'notices', $notices );
		}

		return true;
	}

	/**
	 * Displays all admin notices
	 *
	 * @return string
	 */
	public function show_admin_notices() {
		$notices = get_option( $this->field_prefix . 'notices' );
		if ( empty( $notices ) ) {
			return '';
		}
		//print the message
		foreach ( $notices as $key => $m ) {
			echo '<div class="error"><p>' . $m . '</p></div>';
		}
		return delete_option( $this->field_prefix . 'notices' );
	}
}