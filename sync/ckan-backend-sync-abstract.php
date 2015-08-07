<?php
/**
 * Abstract class for syncs
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Sync_Abstract
 */
abstract class Ckan_Backend_Sync_Abstract {

	/**
	 * The post type.
	 * @var string
	 */
	public $post_type = '';

	/**
	 * The prefix of the field.
	 * @var string
	 */
	public $field_prefix = '';

	/**
	 * Mapping WordPress -> CKAN
	 * @var array
	 */
	private $api_type_mapping = array(
		'ckan-local-dataset' => 'package',
		'ckan-local-org'     => 'organization',
		'ckan-local-group'   => 'group',
	);

	/**
	 * Type of API.
	 * @var string
	 */
	private $api_type = '';

	/**
	 * The constructor of the class.
	 *
	 * @param string $post_type The post type.
	 * @param string $field_prefix The field prefix.
	 */
	public function __construct( $post_type, $field_prefix ) {
		$this->post_type    = $post_type;
		$this->field_prefix = $field_prefix;

		if ( array_key_exists( $post_type, $this->api_type_mapping ) ) {
			$this->api_type = $this->api_type_mapping[ $post_type ];
		} else {
			return false;
		}

		// add save post action for current post type
		add_action( 'save_post_' . $this->post_type, array( $this, 'do_sync' ), 0, 2 );

		// display all notices after saving post
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ), 0 );
	}

	/**
	 * This action gets called when a CKAN post-type is saved, changed, trashed or deleted.
	 *
	 * @param integer $post_id The ID of the post to sync.
	 * @param object  $post The wordpress post.
	 *
	 * @return bool|void
	 */
	public function do_sync( $post_id, $post ) {
		// Exit if WP is doing an auto-save
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Exit if WP is saving an auto-draft post (on add new action)
		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		// Check if the post title is set -> otherwise do not sync to CKAN
		if ( '' === $post->post_title ) {
			$this->store_errors_in_notices_option( array( __( 'CKAN Sync aborted! Please provide a title for this post.', 'ogdch' ) ) );

			return;
		}

		// If action is trash -> set CKAN dataset to deleted
		if ( isset( $_GET ) && ( 'trash' === $_GET['action'] ) ) {
			$success = $this->trash_action( $post );
		} // If action is untrash -> set CKAN dataset to active
		elseif ( isset( $_GET ) && 'unstrash' === $_GET['action'] ) {
			$success = $this->untrash_action( $post );
		} // If action is delete -> delete the CKAN dataset completely
		elseif ( isset( $_GET ) && 'delete' === $_GET['action'] ) {
			// The trash action already set the CKAN dataset to deleted -> do nothing
			return;
		} // Or generate data for insert/update
		else {
			// Exit if saved post is a revision (revisions are deactivated in wp-config... but just in case)
			if ( wp_is_post_revision( $post_id ) || ! isset( $post->post_status ) ) {
				return;
			}

			$data    = $this->get_ckan_data( $post );
			$success = $this->upsert_action( $post, $data );
		}

		return $success;
	}

	/**
	 * Gets called when a CKAN data is untrashed.
	 * Sets CKAN state to active.
	 *
	 * @param object $post The post from WordPress which is untrashed.
	 *
	 * @return bool True when CKAN request was successful.
	 */
	protected function untrash_action( $post ) {
		$ckan_id = get_post_meta( $post->ID, $this->field_prefix . 'ckan_id', true );

		// If no CKAN id is defined don't send request a to CKAN
		if ( '' === $ckan_id ) {
			return true;
		}

		$data = array(
			'id'    => $ckan_id,
			'state' => 'active',
		);

		return $this->upsert_action( $post, $data );
	}

	/**
	 * Gets called when a CKAN data is trashed.
	 * Sets CKAN state to deleted.
	 *
	 * @param object $post The post from WordPress which is deleted.
	 *
	 * @return bool True when CKAN request was successful.
	 */
	protected function trash_action( $post ) {
		$ckan_id = get_post_meta( $post->ID, $this->field_prefix . 'ckan_id', true );

		// If no CKAN id is defined don't send request a to CKAN
		if ( '' === $ckan_id ) {
			return true;
		}

		$endpoint = CKAN_API_ENDPOINT . 'action/' . $this->api_type . '_delete';
		$data     = array(
			'id' => $ckan_id,
		);
		$data     = wp_json_encode( $data );

		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );
		$this->store_errors_in_notices_option( $errors );

		$this->after_trash_action( $post );

		// Return true if there were no errors
		return count( $errors ) === 0;
	}

	/**
	 * Possibility for specific sync classes to do additional actions after deleting data in CKAN
	 *
	 * @param object $post The post from WordPress which is deleted.
	 */
	protected function after_trash_action( $post ) {
		return;
	}

	/**
	 * This method should return an array with the updated data
	 *
	 * @param object $post The post from WordPress.
	 *
	 * @return array $data Updated data to send
	 */
	abstract protected function get_ckan_data( $post );

	/**
	 * Gets called when a CKAN data is inserted or updated.
	 * Sends inserted/updated data to CKAN.
	 *
	 * @param object $post The post from WordPress which is inserted/updated.
	 * @param array  $data The inserted/updated data to send.
	 *
	 * @return bool True if data was successfully inserted/updated in CKAN
	 */
	protected function upsert_action( $post, $data ) {
		// If data to send holds CKAN id -> do update in CKAN
		if ( isset( $data['id'] ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'action/' . $this->api_type . '_patch';
		} else {
			// Insert new dataset
			$endpoint = CKAN_API_ENDPOINT . 'action/' . $this->api_type . '_create';
		}

		$data     = wp_json_encode( $data );
		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );
		$this->store_errors_in_notices_option( $errors );
		if ( count( $errors ) === 0 ) {
			return $this->update_ckan_data( $post, $response['result'] );
		} else {
			return false;
		}
	}

	/**
	 * Updates CKAN data in WordPress dataset
	 *
	 * @param object $post The post from WordPress which is inserted.
	 * @param array  $result Result from the CKAN api request.
	 *
	 * @return bool
	 */
	protected function update_ckan_data( $post, $result ) {
		if ( isset( $result['id'] ) && '' !== $result['id'] ) {
			// Set ckan_id and ckan_name from CKAN and add it to $_POST because the real meta save will follow after this action
			update_post_meta( $post->ID, $this->field_prefix . 'ckan_id', $result['id'] );
			update_post_meta( $post->ID, $this->field_prefix . 'ckan_name', $result['name'] );
			$_POST[ $this->field_prefix . 'ckan_id' ]   = $result['id'];
			$_POST[ $this->field_prefix . 'ckan_name' ] = $result['name'];

			return true;
		}

		return false;
	}

	/**
	 * Stores all error messages in Option to print them out after redirect of save action
	 *
	 * @param array $errors Array with error messages.
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
			echo '<div class="error"><p>' . esc_html( $m ) . '</p></div>';
		}

		return delete_option( $this->field_prefix . 'notices' );
	}
}
