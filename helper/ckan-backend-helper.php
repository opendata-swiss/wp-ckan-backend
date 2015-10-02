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
				foreach ( $response['error'] as $field => $messages ) {
					if ( '__type' !== $field ) {
						// @codingStandardsIgnoreStart
						$error .= ' / [' . $field . '] ' . sanitize_text_field( var_export( $messages, true ) );
						// @codingStandardsIgnoreEnd
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
		return Ckan_Backend_Helper::get_form_field_options( Ckan_Backend_Local_Group::POST_TYPE, Ckan_Backend_Local_Group::FIELD_PREFIX );
	}

	/**
	 * Gets all organisation instances from CKAN and returns them in an array.
	 *
	 * @return array All organisation instances from CKAN
	 */
	public static function get_organisation_form_field_options() {
		return Ckan_Backend_Helper::get_form_field_options( Ckan_Backend_Local_Organisation::POST_TYPE, Ckan_Backend_Local_Organisation::FIELD_PREFIX );
	}

	/**
	 * Gets all instances of given type from CKAN and returns them in an array.
	 *
	 * @param string $type Name of a CKAN type.
	 *
	 * @return array All instances from CKAN
	 */
	private static function get_form_field_options( $post_type, $field_prefix ) {
		$transient_name = Ckan_Backend::$plugin_slug . '_' . $post_type . '_options';
		if ( false === ( $options = get_transient( $transient_name ) ) ) {
			$args  = array(
				'posts_per_page'   => -1,
				'meta_key'         => $field_prefix . 'title_' . self::get_current_language(),
				'orderby'          => 'meta_value',
				'order'            => 'ASC',
				'post_type'        => $post_type,
				'post_status'      => 'publish',
			);
			$posts = get_posts( $args );

			foreach ( $posts as $post ) {
				$name  = get_post_meta( $post->ID, $field_prefix . 'ckan_name', true );
				$title = get_post_meta( $post->ID, $field_prefix . 'title_' . self::get_current_language(), true );
				$options[ $name ] = $title;
			}

			// save result in transient
			set_transient( $transient_name, $options, 1 * HOUR_IN_SECONDS );
		}

		return $options;
	}

	/**
	 * Returns title of given CKAN organisation.
	 *
	 * @param string $name Name (slug) of organisation.
	 *
	 * @return string
	 */
	public static function get_organisation_title( $name ) {
		if ( '' === $name ) {
			return '';
		}
		$transient_name = Ckan_Backend::$plugin_slug . '_organization_title_' . $name;
		if ( false === ( $organisation_title = get_transient( $transient_name ) ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'organization_show';
			$data     = array(
				'id'               => $name,
				'include_datasets' => false,
			);
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				$organisation_title = $response['result']['title'];

				// save result in transient
				set_transient( $transient_name, $organisation_title, 1 * HOUR_IN_SECONDS );
			} else {
				self::print_error_messages( $errors );
			}
		}

		return self::get_localized_text( $organisation_title );
	}

	/**
	 * Returns title of given CKAN dataset.
	 *
	 * @param string $name Name (slug) of dataset.
	 *
	 * @return string
	 */
	public static function get_dataset_title( $name ) {
		if ( '' === $name ) {
			return '';
		}
		$transient_name = Ckan_Backend::$plugin_slug . '_dataset_title_' . $name;
		if ( false === ( $dataset_title = get_transient( $transient_name ) ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'package_show';
			$data     = array( 'id' => $name );
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				$dataset_title = $response['result']['title'];

				// save result in transient
				set_transient( $transient_name, $dataset_title, 1 * HOUR_IN_SECONDS );
			} else {
				self::print_error_messages( $errors );
			}
		}

		return self::get_localized_text( $dataset_title );
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
	 * @param string $name Name (slug) of the CKAN entity.
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
		if ( '' === $name ) {
			return false;
		}

		$transient_name = Ckan_Backend::$plugin_slug . '_' . $type . '_' . $name . '_exists';
		if ( false === ( $object_exists = get_transient( $transient_name ) ) ) {
			$endpoint      = CKAN_API_ENDPOINT . $type . '_show';
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
		global $language_priority;
		$org_languages = $all_languages;
		if ( ! is_array( $all_languages ) ) {
			$all_languages = json_decode( $all_languages, true );
		}

		$current_language = get_locale();
		$localized_text   = $all_languages[ substr( $current_language, 0, 2 ) ];
		if ( empty( $localized_text ) ) {
			foreach ( $language_priority as $lang ) {
				if ( '' !== $all_languages[ $lang ] ) {
					$localized_text = $all_languages[ $lang ];
					break;
				}
			}
		}
		if ( empty( $localized_text ) ) {
			return $org_languages;
		}

		return $localized_text;
	}

	/**
	 * Generates selectbox to filter organisations
	 *
	 * @param bool $disable_floating Disable floating of the selectbox which is default in WordPress.
	 */
	public static function print_organisation_filter( $disable_floating = false ) {
		$args          = array(
			'posts_per_page' => - 1,
			'post_type'      => Ckan_Backend_Local_Organisation::POST_TYPE,
			'post_status'    => 'any',
		);
		$organisations = get_posts( $args );
		?>
		<select name="organisation_filter" <?php echo ($disable_floating) ? 'style="float: none;"' : ''; ?>>
			<option value=""><?php esc_attr_e( 'All organisations', 'ogdch' ); ?></option>
			<?php
			$organisation_filter   = '';
			if ( isset( $_GET['organisation_filter'] ) ) {
				$organisation_filter = sanitize_text_field( $_GET['organisation_filter'] );
			} elseif ( ! members_current_user_has_role( 'administrator' ) ) {
				// set filter on first page load if user is not an administrator
				$organisation_filter = get_the_author_meta( Ckan_Backend::$plugin_slug . '_organisation', get_current_user_id() );
			}

			foreach ( $organisations as $organisation ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $organisation->post_name ),
					esc_attr( ( $organisation->post_name === $organisation_filter ) ? ' selected="selected"' : '' ),
					esc_attr( $organisation->post_name )
				);
			}
			?>
		</select>
		<?php
	}

	public static function get_current_language() {
		if( function_exists( 'pll_current_language' ) ) {
			return pll_current_language();
		} else {
			return substr( get_locale(), 0, 2 );
		}
	}
}
