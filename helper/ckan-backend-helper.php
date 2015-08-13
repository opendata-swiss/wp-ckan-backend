<?php
/**
 * Helper function for this plugin
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Helper
 */
class Ckan_Backend_Helper {
	/**
	 * Sends a curl request with given data to specified CKAN endpoint.
	 *
	 * @param string $endpoint CKAN API endpoint which gets called.
	 * @param string $data JSON-encoded data to send.
	 *
	 * @return array The CKAN data as array
	 */
	public static function do_api_request( $endpoint, $data = '' ) {
		if ( is_array( $data ) ) {
			$data = wp_json_encode( $data );
		}

		$ch = curl_init( $endpoint );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization: ' . CKAN_API_KEY ) );

		// send request
		$response = curl_exec( $ch );
		$response = json_decode( $response, true );

		curl_close( $ch );

		return $response;
	}

	/**
	 * Validates CKAN API response
	 *
	 * @param array $response The JSON-decoded response from the CKAN API.
	 *
	 * @return array An Array with error messages if there where any.
	 */
	public static function check_response_for_errors( $response ) {
		$errors = array();
		if ( ! is_array( $response ) ) {
			$errors[] = 'There was a problem sending the request.';
		}

		if ( isset( $response['success'] ) && false === $response['success'] ) {
			if ( isset( $response['error'] ) && isset( $response['error']['message'] ) ) {
				$errors[] = $response['error']['message'];
			} else if ( isset( $response['error'] ) && isset( $response['error']['name'] ) && is_array( $response['error']['name'] ) ) {
				$errors[] = $response['error']['name'][0];
			} else if ( isset( $response['error'] ) && isset( $response['error']['id'] ) && is_array( $response['error']['id'] ) ) {
				$errors[] = $response['error']['id'][0];
			} else if ( isset( $response['error'] ) && isset( $response['error']['__type'] ) ) {
				$error = $response['error']['__type'];
				foreach( $response['error'] as $field => $messages ) {
					if( '__type' !== $field ) {
						$error .= '<br />[' . $field . '] ' . implode( ', ', $messages );
					}
				}
				$errors[] = $error;
			} else {
				$errors[] = 'API responded with unknown error.';
			}
		}

		return $errors;
	}

	/**
	 * Gets all group instances from CKAN and returns them in an array.
	 *
	 * @return array All group instances from CKAN
	 */
	public static function get_group_form_field_options() {
		return Ckan_Backend_Helper::get_form_field_options( 'group' );
	}

	/**
	 * Gets all organisation instances from CKAN and returns them in an array.
	 *
	 * @return array All organisation instances from CKAN
	 */
	public static function get_organisation_form_field_options() {
		return Ckan_Backend_Helper::get_form_field_options( 'organization' );
	}

	/**
	 * Gets all instances of given type from CKAN and returns them in an array.
	 *
	 * @param string $type Name of a CKAN type.
	 *
	 * @return array All instances from CKAN
	 */
	private static function get_form_field_options( $type ) {
		$available_types = array(
			'group',
			'organization',
		);
		if ( ! in_array( $type, $available_types ) ) {
			self::print_error_messages( array( 'Type not available!' ) );

			return false;
		}

		$transient_name = Ckan_Backend::$plugin_slug . '_' . $type . '_options';
		if ( false === ( $options = get_transient( $transient_name ) ) ) {
			$options  = array();
			$endpoint = CKAN_API_ENDPOINT . 'action/' . $type . '_list';
			$data     = array(
				'all_fields' => true,
			);
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );
			self::print_error_messages( $errors );

			if ( is_array( $response['result'] ) ) {
				foreach ( $response['result'] as $instance ) {
					$options[ $instance['name'] ] = self::get_localized_text( $instance['title'] );
					if( 'organization' === $type ) { // TODO remove this condition when organization multilingual bug is fixed
						$options[ $instance['name'] ] = $instance['name'];
					}
				}
			}

			// save result in transient
			set_transient( $transient_name, $options, 1 * HOUR_IN_SECONDS );
		}

		return $options;
	}

	/**
	 * Returns title of given CKAN organisation.
	 *
	 * @param string $id Id of organisation.
	 *
	 * @return string
	 */
	public static function get_organisation_title( $id ) {
		$transient_name = Ckan_Backend::$plugin_slug . '_organization_title_' . $id;
		if ( false === ( $organisation_title = get_transient( $transient_name ) ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'action/organization_show';
			$data     = array(
				'id'               => $id,
				'include_datasets' => false,
			);
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );
			self::print_error_messages( $errors );
			$organisation_title = $response['result']['title'];
			$organisation_title = $response['result']['name']; // TODO remove this line when organisation multilingual bug is fixed

			// save result in transient
			set_transient( $transient_name, $organisation_title, 1 * HOUR_IN_SECONDS );
		}

		return self::get_localized_text( $organisation_title );
	}

	/**
	 * Checks if the group exsits.
	 *
	 * @param string $name The name of the group.
	 *
	 * @return bool
	 */
	public static function group_exists( $name ) {
		return Ckan_Backend_Helper::object_exists( 'group', $name );
	}

	/**
	 * Checks if the organization exists
	 *
	 * @param string $name The name of the organization.
	 *
	 * @return bool
	 */
	public static function organisation_exists( $name ) {
		return Ckan_Backend_Helper::object_exists( 'organization', $name );
	}

	/**
	 * Check if the object exists
	 *
	 * @param string $type Name of a CKAN type.
	 * @param string $name Name of the object.
	 *
	 * @return bool
	 */
	private static function object_exists( $type, $name ) {
		$available_types = array(
			'group',
			'organization',
		);
		if ( ! in_array( $type, $available_types ) ) {
			self::print_error_messages( array( 'Type not available!' ) );

			return false;
		}

		$transient_name = Ckan_Backend::$plugin_slug . '_' . $type . '_' . $name . '_exists';
		if ( false === ( $object_exists = get_transient( $transient_name ) ) ) {
			$endpoint      = CKAN_API_ENDPOINT . 'action/' . $type . '_show';
			$data          = array(
				'id' => $name,
			);
			$data          = wp_json_encode( $data );
			$response      = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors        = Ckan_Backend_Helper::check_response_for_errors( $response );
			$object_exists = count( $errors ) === 0;

			// save result in transient
			set_transient( $transient_name, $object_exists, 1 * HOUR_IN_SECONDS );
		}

		return $object_exists;
	}

	/**
	 * Displays all admin notices
	 *
	 * @param array $errors Array of errors.
	 *
	 * @return string
	 */
	public static function print_error_messages( $errors ) {
		//print the message
		if ( is_array( $errors ) && count( $errors ) > 0 ) {
			foreach ( $errors as $key => $m ) {
				// @codingStandardsIgnoreStart
				echo '<div class="error"><p>' . $m . '</p></div>';
				// @codingStandardsIgnoreEnd
			}
		}

		return true;
	}

	/**
	 * Checks if a string starts with a given needle
	 *
	 * @param string $haystack String to search in.
	 * @param string $needle String to look for.
	 *
	 * @return bool
	 */
	public static function starts_with( $haystack, $needle ) {
		return '' === $needle || strrpos( $haystack, $needle, - strlen( $haystack ) ) !== false;
	}

	/**
	 * Returns metafield value from $_POST if available. Otherwise returns value from database.
	 *
	 * @param int    $post_id ID of current post.
	 * @param string $field_name Name of metafield.
	 * @param bool   $load_from_post If true loads value from $_POST array.
	 *
	 * @return mixed
	 */
	public static function get_metafield_value( $post_id, $field_name, $load_from_post ) {
		if ( $load_from_post ) {
			return $_POST[ $field_name ];
		} else {
			return get_post_meta( $post_id, $field_name, true );
		}
	}

	/**
	 * Returns a CKAN friendly array for multilingual fields
	 *
	 * @param int    $post_id ID of current post.
	 * @param string $field_name Name of the field.
	 * @param bool   $load_from_post If true loads value from $_POST array.
	 *
	 * @return array
	 */
	public static function prepare_multilingual_field( $post_id, $field_name, $load_from_post ) {
		global $language_priority;

		$multilingual_field = array();
		foreach ( $language_priority as $lang ) {
			$multilingual_field[ $lang ] = self::get_metafield_value( $post_id, $field_name . '_' . $lang, $load_from_post );
		}

		return $multilingual_field;
	}

	/**
	 * Extracts localized text from given CKAN JSON.
	 *
	 * @param string $all_languages JSON from CKAN with all languages in it.
	 *
	 * @return string
	 */
	public static function get_localized_text( $all_languages ) {
		$all_languages_array = json_decode( $all_languages, true );

		$current_language = get_locale();
		$localized_text   = $all_languages_array[ substr( $current_language, 0, 2 ) ];
		if ( empty( $localized_text ) ) {
			$localized_text = $all_languages_array['en'];
		}
		if ( empty( $localized_text ) ) {
			return $all_languages;
		}

		return $localized_text;
	}
}
