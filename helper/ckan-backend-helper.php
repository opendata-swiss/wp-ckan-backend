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

		$curl_headers = array(
			'Authorization: ' . CKAN_API_KEY,
			'Content-Type: application/json',
		);
		$ch = curl_init( $endpoint );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER,  $curl_headers );

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
			$errors[] = __( 'There was a problem sending the request.', 'ogdch-backend' );
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
				$errors[] = __( 'API responded with unknown error.', 'ogdch-backend' );
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
		return self::get_form_field_options( Ckan_Backend_Local_Group::POST_TYPE, Ckan_Backend_Local_Group::FIELD_PREFIX );
	}

	/**
	 * Gets all organisation instances from CKAN and returns them in an array.
	 *
	 * @return array All organisation instances from CKAN
	 */
	public static function get_organisation_form_field_options() {
		return self::get_form_field_options( Ckan_Backend_Local_Organisation::POST_TYPE, Ckan_Backend_Local_Organisation::FIELD_PREFIX );
	}

	/**
	 * Gets all instances of given type from CKAN and returns them in an array.
	 *
	 * @param string $post_type WordPress post type.
	 * @param string $field_prefix Field prefix of post type.
	 *
	 * @return array All instances from CKAN
	 */
	private static function get_form_field_options( $post_type, $field_prefix ) {
		$current_language = self::get_current_language();
		$transient_name = Ckan_Backend::$plugin_slug . '_' . $post_type . '_options_' . $current_language;
		if ( false === ( $options = get_transient( $transient_name ) ) ) {
			$args  = array(
				// @codingStandardsIgnoreStart
				'posts_per_page' => -1,
				// @codingStandardsIgnoreEnd
				'order'          => 'ASC',
				'post_type'      => $post_type,
				'post_status'    => 'publish',
			);
			$posts = get_posts( $args );
			foreach ( $posts as $post ) {
				$name  = get_post_meta( $post->ID, $field_prefix . 'ckan_name', true );
				$title = get_post_meta( $post->ID, $field_prefix . 'title_' . $current_language, true );
				// if title in current language is not set -> find fallback title in other language
				if ( empty( $title ) ) {
					global $language_priority;
					if ( isset( $language_priority ) ) {
						foreach ( $language_priority as $lang ) {
							$title = get_post_meta( $post->ID, $field_prefix . 'title_' . $lang, true );
							if ( ! empty( $title ) ) {
								break;
							}
						}
					}
				}
				// if title in all languages is empty use post title
				if ( empty( $title ) ) {
					$title = $post->post_title;
				}
				$options[ $name ] = $title;
			}

			// TODO find a way to sort unicode values (like umlauts)
			asort( $options, SORT_NATURAL );

			// save result in transient
			set_transient( $transient_name, $options, 1 * HOUR_IN_SECONDS );
		}

		return $options;
	}

	/**
	 * Returns title of given CKAN dataset.
	 *
	 * @param string $identifier Identifier of dataset as string.
	 *
	 * @return string
	 */
	public static function get_dataset_title( $identifier ) {
		if ( empty( $identifier ) ) {
			return '';
		}
		$dataset = self::get_dataset( $identifier );

		return self::get_localized_text( $dataset['title'] );
	}

	/**
	 * Returns dataset information of given dataset identifier.
	 *
	 * @param string $identifier Identifier of dataset as string.
	 * @param bool   $show_errors If true errors get printed.
	 *
	 * @return array|boolean
	 */
	public static function get_dataset( $identifier, $show_errors = true ) {
		if ( empty( $identifier ) ) {
			return '';
		}
		$transient_name = Ckan_Backend::$plugin_slug . '_dataset_' . $identifier;
		if ( false === ( $dataset = get_transient( $transient_name ) ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'ogdch_dataset_by_identifier';
			$data     = array( 'identifier' => $identifier );
			$data     = wp_json_encode( $data );

			$response = self::do_api_request( $endpoint, $data );
			$errors   = self::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				$dataset = $response['result'];

				// save result in transient
				set_transient( $transient_name, $dataset, 1 * HOUR_IN_SECONDS );
			} else {
				if ( $show_errors ) {
					self::print_error_messages( $errors );
				}
			}
		}

		return $dataset;
	}

	/**
	 * Returns title of given CKAN organization.
	 *
	 * @param string $name Name (slug) of organization.
	 *
	 * @return string
	 */
	public static function get_organization_title( $name ) {
		if ( '' === $name ) {
			return '';
		}
		$current_language = self::get_current_language();
		$transient_name = Ckan_Backend::$plugin_slug . '_organization_title_' . $name . '_' . $current_language;
		if ( false === ( $organization_title = get_transient( $transient_name ) ) ) {
			$args  = array(
				'posts_per_page'   => 1,
				'post_type'        => Ckan_Backend_Local_Organisation::POST_TYPE,
				'post_status'      => 'publish',
				// @codingStandardsIgnoreStart
				'meta_key'         => Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'ckan_name',
				'meta_value'       => $name,
				// @codingStandardsIgnoreEnd

			);
			$organisations = get_posts( $args );
			if ( count( $organisations ) > 0 ) {
				$organization_title = get_post_meta( $organisations[0]->ID, Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'title_' . $current_language, true );
				// if title in current language is not set -> find fallback title in other language
				if ( empty( $organization_title ) ) {
					global $language_priority;
					if ( isset( $language_priority ) ) {
						foreach ( $language_priority as $lang ) {
							$organization_title = get_post_meta( $organisations[0]->ID, Ckan_Backend_Local_Organisation::FIELD_PREFIX . 'title_' . $lang, true );
							if ( ! empty( $organization_title ) ) {
								break;
							}
						}
					}
				}
			}
			// if title in all languages is empty use $name
			if ( empty( $organization_title ) ) {
				$organization_title = $name;
			}

			// save result in transient
			set_transient( $transient_name, $organization_title, 1 * HOUR_IN_SECONDS );

		}

		return $organization_title;
	}

	/**
	 * Checks if the group exsits.
	 *
	 * @param string $name The name of the group.
	 *
	 * @return bool
	 */
	public static function group_exists( $name ) {
		return self::object_exists( Ckan_Backend_Local_Group::POST_TYPE, Ckan_Backend_Local_Group::FIELD_PREFIX, $name );
	}

	/**
	 * Checks if the organization exists
	 *
	 * @param string $name The name of the organization.
	 *
	 * @return bool
	 */
	public static function organisation_exists( $name ) {
		return self::object_exists( Ckan_Backend_Local_Organisation::POST_TYPE, Ckan_Backend_Local_Organisation::FIELD_PREFIX, $name );
	}

	/**
	 * Check if the object exists
	 *
	 * @param string $post_type WordPress post type.
	 * @param string $field_prefix Field prefix of post type.
	 * @param string $name Name (slug) of the CKAN entity.
	 *
	 * @return bool
	 */
	private static function object_exists( $post_type, $field_prefix, $name ) {
		$transient_name = Ckan_Backend::$plugin_slug . '_' . $post_type . '_' . $name . '_exists';
		if ( false === ( $object_exists = get_transient( $transient_name ) ) ) {
			$args  = array(
				// @codingStandardsIgnoreStart
				'posts_per_page'   => -1,
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'meta_key'         => $field_prefix . 'ckan_name',
				'meta_value'       => $name,
				// @codingStandardsIgnoreEnd
			);
			$posts = get_posts( $args );
			$object_exists = count( $posts ) > 0;

			// save result in transient
			set_transient( $transient_name, $object_exists, 1 * HOUR_IN_SECONDS );
		}

		return $object_exists;
	}

	/**
	 * Displays admin errors
	 *
	 * @param array|string $errors Array or string of errors.
	 *
	 * @return string
	 */
	public static function print_error_messages( $errors ) {
		// wrap in array
		if ( ! is_array( $errors ) && ! empty( $errors ) ) {
			$errors = array( $errors );
		}
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
	 * Displays admin notices
	 *
	 * @param array|string $msgs Array or string of messages.
	 *
	 * @return string
	 */
	public static function print_messages( $msgs ) {
		// wrap in array
		if ( ! is_array( $msgs ) && ! empty( $msgs ) ) {
			$msgs = array( $msgs );
		}
		//print the message
		if ( is_array( $msgs ) && count( $msgs ) > 0 ) {
			foreach ( $msgs as $key => $m ) {
				// @codingStandardsIgnoreStart
				echo '<div class="notice notice-success"><p>' . $m . '</p></div>';
				// @codingStandardsIgnoreEnd
			}
		}

		return true;
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
			if ( isset( $_POST[ $field_name ] ) ) {
				// remove magic quotes which WordPress adds in wp_includes/load.php -> wp_magic_quotes()
				return stripslashes_deep( $_POST[ $field_name ] );
			}
		} else {
			$value_from_db = get_post_meta( $post_id, $field_name, true );
			// return empty string instead of null because some ckan validators (fluent_text) don't allow null values
			return ( null !== $value_from_db ? $value_from_db : '' );
		}
		return '';
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
	 * Extracts localized text from given array or JSON.
	 *
	 * @param string $multilingual_text Array or JSON with text in all languages.
	 * @param string $default Text to return if text is empty in all languages.
	 *
	 * @return string
	 */
	public static function get_localized_text( $multilingual_text, $default = '' ) {
		if ( ! is_array( $multilingual_text ) ) {
			$multilingual_text = json_decode( $multilingual_text, true );
		}

		$localized_text   = $multilingual_text[ self::get_current_language() ];
		if ( ! empty( $localized_text ) ) {
			return $localized_text;
		}

		global $language_priority;
		if ( isset( $language_priority ) ) {
			foreach ( $language_priority as $lang ) {
				if ( ! empty( $multilingual_text[ $lang ] ) ) {
					return $multilingual_text[ $lang ];
				}
			}
		}

		return $default;
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
			<option value=""><?php esc_attr_e( 'All organizations', 'ogdch-backend' ); ?></option>
			<?php
			$organisation_filter   = '';
			if ( isset( $_GET['organisation_filter'] ) ) {
				$organisation_filter = sanitize_text_field( $_GET['organisation_filter'] );
			} elseif ( ! members_current_user_has_role( 'administrator' ) ) {
				// set filter on first page load if user is not an administrator
				$organisation_filter = get_the_author_meta( Ckan_Backend::$plugin_slug . '_organisation', get_current_user_id() );
			}

			usort( $organisations, array( 'Ckan_Backend_Helper', 'compare' ) );

			foreach ( $organisations as $organisation ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $organisation->post_name ),
					esc_attr( ( $organisation->post_name === $organisation_filter ) ? ' selected="selected"' : '' ),
					esc_attr( self::get_organization_title( $organisation->post_name ) )
				);
			}
			?>
		</select>
		<?php
	}

	/**
	 * Compare organization-post_names to sort them
	 *
	 * @param object $a First Object to compare.
	 * @param object $b Second Object to compare.
	 *
	 * @return int
	 */
	public static function compare( $a, $b ) {
		return strcmp( self::get_organization_title( $a->post_name ), self::get_organization_title( $b->post_name ) );
	}

	/**
	 * Returns current language slug
	 *
	 * @return string
	 */
	public static function get_current_language() {
		return substr( get_locale(), 0, 2 );
	}

	/**
	 * Returns Original Identifier and Organisation ID extracted from given identifier
	 *
	 * @param string $identifier Identifier in following format: <original_id>@<organisation_id>.
	 *
	 * @return array Format: array( 'original_identifier' = '123', 'organisation' = 'ABC' );
	 */
	public static function split_identifier( $identifier ) {
		$splitted_identifier = array(
			'original_identifier' => substr( $identifier, 0, strrpos( $identifier, '@' ) ),
			'organisation'        => substr( strrchr( $identifier, '@' ), 1 ),
		);

		return $splitted_identifier;
	}

	/**
	 * Returns the given array flattened.
	 *
	 * @param array $array The array to be flattened.
	 *
	 * @return array
	 */
	public static function flatten(array $array) {
		$return = array();
		array_walk_recursive(
			$array,
			function( $a ) use ( &$return ) {
				$return[] = $a;
			}
		);
		return $return;
	}

	/**
	 * Adds a child node with CDATA content to an XML document and returns it.
	 *
	 * @param SimpleXMLElement $parent Element that the CDATA child node should be attached to.
	 * @param string           $name Name of the child node that should contain CDATA content.
	 * @param string           $value Value that should be inserted into a CDATA child node.
	 * @param string           $namespace Namespace of new child node.
	 *
	 * @return SimpleXMLElement Child node with CDATA content.
	 */
	public static function add_child_with_cdata( &$parent, $name, $value = null, $namespace = null ) {
		$child = $parent->addChild( $name, null, $namespace );

		if ( null !== $child && ! empty( $value ) ) {
			$child_node = dom_import_simplexml( $child );
			$child_owner = $child_node->ownerDocument;
			$child_node->appendChild( $child_owner->createCDATASection( $value ) );
		}

		return $child;
	}

	/**
	 * Converts UTC date to local date.
	 *
	 * @param string $date_string Date which is passed to the constructor of DateTime.
	 * @param string $format The format of the outputted date string.
	 *
	 * @return string Formatted local date.
	 */
	public static function get_local_date( $date_string, $format = 'd.m.Y H:i:s' ) {
		$date_obj = new DateTime( $date_string );
		$date_obj->setTimezone( new DateTimeZone( get_option( 'timezone_string' ) ) );
		return $date_obj->format( $format );
	}

	/**
	 * Converts UTC date into readable format.
	 *
	 * @param string $date_string Date which is passed to the constructor of DateTime.
	 * @param string $format The format of the outputted date string.
	 * @param string $default The default text which should be returned when $date_string is empty.
	 *
	 * @return string
	 */
	public static function convert_date_to_readable_format( $date_string, $format = 'd.m.Y H:i:s', $default = '-' ) {
		return ( ! empty( $date_string ) ? Ckan_Backend_Helper::get_local_date( $date_string, $format ) : $default );
	}
}
