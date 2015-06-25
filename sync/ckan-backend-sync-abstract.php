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

		if ( $this->api_type != 'package' ) {
			// add before delete post action
			add_action( 'before_delete_post', array( $this, 'do_delete' ), 0, 1 );
		}

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
			$this->trash_action( $post_id );
		} // If action is untrash -> set CKAN dataset to active
		elseif ( isset( $_GET ) && $_GET['action'] === 'untrash' ) {
			$this->untrash_action( $post_id );
		} // If action is delete -> delete the CKAN dataset completely
		elseif ( isset( $_GET ) && $_GET['action'] === 'delete' ) {
			// this action is handled by the before_delete_post hook -> do nothing
		} // Or generate data for insert/update
		else {
			// Exit if saved post is a revision (revisions are deactivated in wp-config... but just in case)
			if ( wp_is_post_revision( $post_id ) || ! isset( $post->post_status ) ) {
				return;
			}

			if ( $post->post_status == 'publish' ) {
				$data = $this->get_update_data();
				$this->update_action( $post, $data );
			} else {
				// if post gets unpublished set status in ckan to deleted
				$this->trash_action( $post_id );
			}
		}
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

		$success = $this->delete_action( $post_id );

		// TODO: do not delete post in WordPress if there was an error sending the ckan request

		return $success;
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
			return false;
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

		$data = json_encode( $data );

		// Send updated data to CKAN
		$endpoint = CKAN_API_ENDPOINT . 'action/' . $this->api_type . '_patch';
		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );
		$this->store_errors_in_notices_option( $errors );

		return true;
	}

	/**
	 * Purges data in CKAN database
	 *
	 * @param int $post_id ID of CKAN post-type
	 *
	 * @return bool True when CKAN request was successful.
	 */
	protected function delete_action( $post_id ) {
		// purge command not available for datasets
		if ( $this->api_type == 'package' ) {
			return false;
		}

		$ckan_ref = get_post_meta( $post_id, $this->field_prefix . 'reference', true );

		// If no CKAN reference id is defined don't send request a to CKAN
		if ( $ckan_ref === '' ) {
			return true;
		}

		$endpoint = CKAN_API_ENDPOINT . 'action/' . $this->api_type . '_purge';
		$data     = array(
			'id' => $ckan_ref
		);
		$data     = json_encode( $data );
		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );
		$this->store_errors_in_notices_option( $errors );

		if ( count( $errors ) > 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * This method should return an array with the updated data
	 *
	 * @return array $data Updated data to send
	 */
	abstract protected function get_update_data();

	/**
	 * Gets called when a CKAN data is inserted or updated.
	 * Sends new/updated data to CKAN and updates reference id and name (slug) from CKAN.
	 *
	 * @param object $post The post from WordPress which is updated/inserted
	 * @param array $data The inserted/updated data to send
	 *
	 * @return bool True when CKAN request was successful.
	 */
	protected function update_action( $post, $data ) {
		// Define endpoint for request
		$endpoint = CKAN_API_ENDPOINT . 'action/';

		// If post data holds reference id -> do update in CKAN
		if ( isset( $data['id'] ) ) {
			$endpoint .= $this->api_type . '_patch';
		} else {
			// Insert new dataset
			$endpoint .= $this->api_type . '_create';
		}

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
		}

		return true;
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
			update_option( $this->field_prefix . 'notices', $notices );
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
		delete_option( $this->field_prefix . 'notices' );
	}
}