<?php

abstract class Ckan_Backend_Sync_Abstract {

	public $post_type = '';
	public $field_prefix = '';
	private $type_api_mapping = array(
		'ckan-local-dataset'      => 'package',
		'ckan-local-org' => 'organization',
		'ckan-local-group'        => 'group',
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
		add_action( 'save_post_' . $this->post_type, array( $this, 'do_sync' ) );

		// display all notices after saving post
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ), 0 );
	}

	/**
	 * This action gets called when a CKAN post-type is saved, changed, trashed or deleted.
	 */
	public function do_sync() {
		// Exit if WP is doing an auto-save
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// If action is trash or delete set CKAN dataset to deleted
		if ( isset( $_GET ) && ( $_GET['action'] === 'trash' || $_GET['action'] === 'delete' ) ) {
			// If action was executed by selecting post with checkbox and choose delete in menu $_GET['post'] is array with all selected post ids
			if ( is_array( $_GET['post'] ) ) {
				foreach ( $_GET['post'] as $post_id ) {
					$this->trash_action( $post_id );
				}
			} else {
				// If action was executed with delete link next to post $_GET['post'] corresponds to id of post
				$this->trash_action( $_GET['post'] );
			}
		} // If action is untrash set CKAN dataset to active
		elseif ( isset( $_GET ) && $_GET['action'] === 'untrash' ) {
			// If Undo Link was clicked the $_GET['post'] variable isn't set
			if ( $_GET['doaction'] === 'undo' ) {
				$post_ids = explode( ',', $_GET['ids'] );
				foreach ( $post_ids as $post_id ) {
					$this->untrash_action( $post_id );
				}
			} else {
				// If action was executed by selecting post with checkbox and choose untrash in menu $_GET['post'] is array with all selected post ids
				if ( is_array( $_GET['post'] ) ) {
					foreach ( $_GET['post'] as $post_id ) {
						$this->untrash_action( $post_id );
					}
				} else {
					// If action was executed with untrash link next to post $_GET['post'] corresponds to id of post
					$this->untrash_action( $_GET['post'] );
				}
			}
		} // Or generate data for insert/update
		else {
			global $post;

			// Exit if $post is empty (Should never happen when post gets inserted or updated)
			if ( ! $post ) {
				return;
			}

			// Exit if saved post is a revision (revisions are deactivated in wp-config... but just in case)
			if ( is_object( $post ) && ( wp_is_post_revision( $post->ID ) || ! isset( $post->post_status ) ) ) {
				return;
			}
			$data = $this->get_update_data();
			$this->update_action( $post, $data );
		}
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
	 * Gets called when a CKAN post-type is trashed, untrashed or deleted.
	 * Updates internal post visibility field and CKAN state.
	 *
	 * @param int $post_id ID of CKAN post-type
	 * @param bool $untrash Set true if dataset should be untrashed
	 *
	 * @return bool True when CKAN request was successful.
	 */
	protected function trash_action( $post_id, $untrash = false ) {
		if ( $untrash ) {
			// Set internal post visibility state to active
			update_post_meta( $post_id, $this->field_prefix . 'visibility', 'active' );
		} else {
			// Set internal post visibility state to deleted
			update_post_meta( $post_id, $this->field_prefix . 'visibility', 'deleted' );
		}

		$ckan_ref = get_post_meta( $post_id, $this->field_prefix . 'reference', true );

		// If no CKAN reference id is defined don't send request a to CKAN
		if ( $ckan_ref === '' ) {
			return false;
		}

		// Get current CKAN data and update state property
		$endpoint = CKAN_API_ENDPOINT . 'action/' . $this->api_type . '_show?id=' . $ckan_ref;
		$response = $this->do_api_request( $endpoint );
		$success  = $this->check_response_for_errors( $response );
		$data     = $response->result;

		if ( $untrash ) {
			// Set CKAN state to active.
			$data->state = 'active';
		} else {
			// Set CKAN state to deleted
			$data->state = 'deleted';
		}

		$data = json_encode( $data );

		// Send updated data to CKAN
		$endpoint = CKAN_API_ENDPOINT . 'action/' . $this->api_type . '_update';
		$response = $this->do_api_request( $endpoint, $data );

		return $this->check_response_for_errors( $response );
	}

	/**
	 * Sends a curl request with given data to specified CKAN endpoint.
	 *
	 * @param string $endpoint CKAN API endpoint which gets called
	 * @param string $data Data to send
	 *
	 * @return object The CKAN data as object
	 */
	protected function do_api_request( $endpoint, $data = '' ) {
		$ch = curl_init( $endpoint );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [ 'Authorization: ' . CKAN_API_KEY . '' ] );

		// send request
		$response = curl_exec( $ch );
		$response = json_decode( $response );

		curl_close( $ch );

		return $response;
	}

	/**
	 * Validates CKAN API response
	 *
	 * @param object $response The json_decoded response from the CKAN API
	 *
	 * @return bool True if response looks good
	 */
	protected function check_response_for_errors( $response ) {
		// store all error notices in option array
		$notice = get_option( $this->field_prefix . 'notice' );
		if ( ! is_object( $response ) ) {
			$notice[] = 'There was a problem sending the request.';
		}

		if ( isset( $response->success ) && $response->success === false ) {
			if ( isset( $response->error ) && isset( $response->error->name ) && is_array( $response->error->name ) ) {
				$notice[] = $response->error->name[0];
			} else if ( isset( $response->error ) && isset( $response->error->id ) && is_array( $response->error->id ) ) {
				$notice[] = $response->error->id[0];
			} else {
				$notice[] = 'API responded with unknown error.';
			}
		}
		update_option( $this->field_prefix . 'notice', $notice );

		return true;
	}

	/**
	 * This method should return an array with the updated data
	 *
	 * @return array $data Updated data to send
	 */
	abstract protected function get_update_data();

	/**
	 * Gets called when a CKAN data is saved/updated.
	 * Sends new/updated data to CKAN and updates reference id and name (slug) from CKAN.
	 *
	 * @param object $post The post from WordPress which is updated/saved
	 * @param array $data The updated/saved data to send
	 *
	 * @return bool True when CKAN request was successful.
	 */
	protected function update_action( $post, $data ) {

		// Define endpoint for request
		$endpoint = CKAN_API_ENDPOINT . 'action/';

		// If post data holds reference id -> do update in CKAN
		if ( isset( $data['id'] ) ) {
			$endpoint .= $this->api_type . '_update';
		} else {
			// Insert new dataset
			$endpoint .= $this->api_type . '_create';
		}

		$data = json_encode( $data );

		$response = $this->do_api_request( $endpoint, $data );

		$success = $this->check_response_for_errors( $response );
		if ( $success ) {
			$result = $response->result;
			if ( isset( $result->id ) && $result->id != '' ) {
				// Set reference id from CKAN and add it to $_POST because the real meta save will follow after this action
				update_post_meta( $post->ID, $this->field_prefix . 'reference', $result->id );
				update_post_meta( $post->ID, $this->field_prefix . 'name', $result->name );
				$_POST[ $this->field_prefix . 'reference' ] = $result->id;
				$_POST[ $this->field_prefix . 'name' ]      = $result->name;
			}
		}

		return $success;
	}

	/**
	 * Displays all admin notices
	 *
	 * @return string
	 */
	public function show_admin_notices() {
		$notice = get_option( $this->field_prefix . 'notice' );
		if ( empty( $notice ) ) {
			return '';
		}
		//print the message
		foreach ( $notice as $key => $m ) {
			echo '<div class="error"><p>' . $m . '</p></div>';
		}
		delete_option( $this->field_prefix . 'notice' );
	}
}