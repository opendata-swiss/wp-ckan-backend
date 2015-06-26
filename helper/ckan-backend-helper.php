<?php

class Ckan_Backend_Helper {
	/**
	 * Sends a curl request with given data to specified CKAN endpoint.
	 *
	 * @param string $endpoint CKAN API endpoint which gets called
	 * @param string $data JSON-encoded data to send
	 *
	 * @return array The CKAN data as array
	 */
	public static function do_api_request( $endpoint, $data = '' ) {
		if ( is_array( $data ) ) {
			$data = json_encode( $data );
		}

		$ch = curl_init( $endpoint );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [ 'Authorization: ' . CKAN_API_KEY . '' ] );

		// send request
		$response = curl_exec( $ch );
		$response = json_decode( $response, true );

		curl_close( $ch );

		return $response;
	}

	/**
	 * Validates CKAN API response
	 *
	 * @param array $response The JSON-decoded response from the CKAN API
	 *
	 * @return bool An Array with error messages if there where any.
	 */
	public static function check_response_for_errors( $response ) {
		$errors = array();
		if ( ! is_array( $response ) ) {
			$errors[] = 'There was a problem sending the request.';
		}

		if ( isset( $response['success'] ) && $response['success'] === false ) {
			if ( isset( $response['error'] ) && isset( $response['error']['message'] ) ) {
				$errors[] = $response['error']['message'];
			} else if ( isset( $response['error'] ) && isset( $response['error']['name'] ) && is_array( $response['error']['name'] ) ) {
				$errors[] = $response['error']['name'][0];
			} else if ( isset( $response['error'] ) && isset( $response['error']['id'] ) && is_array( $response['error']['id'] ) ) {
				$errors[] = $response['error']['id'][0];
			} else {
				$errors[] = 'API responded with unknown error.';
			}
		}

		return $errors;
	}


	/**
	 * Gets all instances of given type from CKAN and returns them in an array.
	 *
	 * @return array All instances from CKAN
	 */
	public static function get_form_field_options( $type ) {
		$available_types = array(
			'group',
			'organization'
		);
		if ( ! in_array( $type, $available_types ) ) {
			self::print_error_messages( array( 'Type not available!' ) );

			return false;
		}

		$options  = array();
		$endpoint = CKAN_API_ENDPOINT . 'action/' . $type . '_list';
		$data     = array(
			'all_fields' => true
		);
		$data     = json_encode( $data );

		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );
		self::print_error_messages( $errors );

		foreach ( $response['result'] as $instance ) {
			$options[ $instance['name'] ] = $instance['title'];
		}

		return $options;
	}


	/**
	 * Displays all admin notices
	 *
	 * @return string
	 */
	public static function print_error_messages( $errors ) {
		//print the message
		if ( is_array( $errors ) && count( $errors ) > 0 ) {
			foreach ( $errors as $key => $m ) {
				echo '<div class="error"><p>' . $m . '</p></div>';
			}
		}

		return true;

	}
}