<?php
/**
 * Post type ckan-local-harvester
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Local_Harvester
 */
class Ckan_Backend_Local_Harvester {

	// Be careful: POST_TYPE max. 20 characters allowed!
	const POST_TYPE = 'ckan-local-harvester';
	const FIELD_PREFIX = '_ckan_local_harvester_';

	/**
	 * Information about all harvesters.
	 *
	 * @var array
	 */
	protected static $harvest_sources = array();

	/**
	 * Constructor of this class.
	 */
	public function __construct() {
		$this->register_post_type();

		// add custom columns to admin list
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_list_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'add_list_columns_data' ), 10, 2 );

		add_action( 'cmb2_init', array( $this, 'define_fields' ) );

		// initialize local harvester sync
		new Ckan_Backend_Sync_Local_Harvester( self::POST_TYPE, self::FIELD_PREFIX );
	}

	/**
	 * Registers the post type in WordPress
	 *
	 * @return void
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Harvesters', 'ogdch-backend' ),
			'singular_name'      => __( 'Harvester', 'ogdch-backend' ),
			'menu_name'          => __( 'Harvesters', 'ogdch-backend' ),
			'name_admin_bar'     => __( 'Harvesters', 'ogdch-backend' ),
			'all_items'          => __( 'All Harvesters', 'ogdch-backend' ),
			'add_new_item'       => __( 'Add New Harvester', 'ogdch-backend' ),
			'add_new'            => __( 'Add New', 'ogdch-backend' ),
			'new_item'           => __( 'New Harvester', 'ogdch-backend' ),
			'edit_item'          => __( 'Edit Harvester', 'ogdch-backend' ),
			'update_item'        => __( 'Update Harvester', 'ogdch-backend' ),
			'view_item'          => __( 'View Harvester', 'ogdch-backend' ),
			'search_items'       => __( 'Search Harvesters', 'ogdch-backend' ),
			'not_found'          => __( 'No Harvesters found', 'ogdch-backend' ),
			'not_found_in_trash' => __( 'No Harvesters found in Trash', 'ogdch-backend' ),
		);

		$args = array(
			'label'               => __( 'Harvesters', 'ogdch-backend' ),
			'description'         => __( 'Harvesters which get synced with CKAN', 'ogdch-backend' ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 23,
			'menu_icon'           => 'dashicons-download',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'map_meta_cap'        => true,
			'capability_type'     => array( 'harvester', 'harvesters' ),
			'capabilities'        => array(
				'edit_posts'             => 'edit_harvesters',
				'edit_others_posts'      => 'edit_others_harvesters',
				'publish_posts'          => 'publish_harvesters',
				'read_private_posts'     => 'read_private_harvesters',
				'delete_posts'           => 'delete_harvesters',
				'delete_private_posts'   => 'delete_private_harvesters',
				'delete_published_posts' => 'delete_published_harvesters',
				'delete_others_posts'    => 'delete_others_harvesters',
				'edit_private_posts'     => 'edit_private_harvesters',
				'edit_published_posts'   => 'edit_published_harvesters',
				'create_posts'           => 'create_harvesters',
				// Meta capabilites assigned by WordPress. Do not give to any role.
				'edit_post'              => 'edit_harvester',
				'read_post'              => 'read_harvester',
				'delete_post'            => 'delete_harvester',
			),
		);
		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Adds custom columns to admin list
	 *
	 * @param array $columns Array with all current columns.
	 *
	 * @return array
	 */
	public function add_list_columns( $columns ) {
		$new_columns = array(
			self::FIELD_PREFIX . 'status' => __( 'Status', 'ogdch-backend' ),
			self::FIELD_PREFIX . 'dashboard' => __( 'Dashboard', 'ogdch-backend' ),
		);

		return array_merge( $columns, $new_columns );
	}

	/**
	 * Prints data to custom list columns
	 *
	 * @param string $column Name of custom column.
	 * @param int    $post_id Id of current post.
	 */
	public function add_list_columns_data( $column, $post_id ) {
		$ckan_id = get_post_meta( $post_id, self::FIELD_PREFIX . 'ckan_id', true );
		if ( self::FIELD_PREFIX . 'status' === $column ) {
			$harvest_source_status = $this->get_harvest_source_status( $ckan_id );
			if ( $harvest_source_status ) {
				$status_text = '';
				if ( $harvest_source_status['status']['job_count'] > 0 ) {
					$created_date = Ckan_Backend_Helper::convert_date_to_readable_format( $harvest_source_status['last_job_status']['created'] );
					$started_date = Ckan_Backend_Helper::convert_date_to_readable_format( $harvest_source_status['last_job_status']['gather_started'] );
					$finished_date = Ckan_Backend_Helper::convert_date_to_readable_format( $harvest_source_status['last_job_status']['finished'] );
					$status_text .= '<div class="harvester-status">';
					$status_text .= sprintf( esc_html_x( 'Job count: %s', '%s contains job count of harvester', 'ogdch-backend' ), $harvest_source_status['status']['job_count'] ) . '<br />';
					$status_text .= '
						<div class="last-harvest">
							' . sprintf( esc_html_x( 'Last job created: %s', '%s contains the date when the last job was created', 'ogdch-backend' ), $created_date ) . '<br />
							' . sprintf( esc_html_x( 'Last job started: %s', '%s contains the date when the last job started', 'ogdch-backend' ), $started_date ) . '<br />
							' . sprintf( esc_html_x( 'Last job finished: %s', '%s contains the date when the last job finished', 'ogdch-backend' ), $finished_date ) . '<br />
							<span class="label label-added">' . sprintf( esc_html_x( '%s added', '%s contains the number of added datasets.', 'ogdch-backend' ), esc_html( $harvest_source_status['last_job_status']['stats']['added'] ) ) . '</span>
							<span class="label label-updated">' . sprintf( esc_html_x( '%s updated', '%s contains the number of updated datasets.', 'ogdch-backend' ), esc_html( $harvest_source_status['last_job_status']['stats']['updated'] ) ) . '</span>
							<span class="label label-deleted">' . sprintf( esc_html_x( '%s deleted', '%s contains the number of deleted datasets.', 'ogdch-backend' ), esc_html( $harvest_source_status['last_job_status']['stats']['deleted'] ) ) . '</span>
							<span class="label label-errored">' . sprintf( esc_html_x( '%s errored', '%s contains the number of errored dataset.', 'ogdch-backend' ), esc_html( $harvest_source_status['last_job_status']['stats']['errored'] ) ) . '</span>
						</div>';
					$status_text .= '</div>';
				} else {
					$status_text .= esc_html__( 'No jobs yet', 'ogdch-backend' );
				}

				// @codingStandardsIgnoreStart
				echo $status_text;
				// @codingStandardsIgnoreEnd
			} else {
				esc_html_e( 'Could not retrieve harvest source status', 'ogdch-backend' );
			}
		} else if ( self::FIELD_PREFIX . 'dashboard' === $column ) {
			// @codingStandardsIgnoreStart
			echo '<a href="edit.php?post_type=' . esc_attr( self::POST_TYPE ) . '&page=ckan-local-harvester-dashboard-page&harvester_id=' . esc_attr( $ckan_id ) . '""><span class="dashicons dashicons-dashboard"></span> ' . esc_html__( 'Dashboard', 'ogdch-backend' ) . '</a>';
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Retrieves last job status of given harvest source. Warning: Status should never be saved in transient because we have no control over it!
	 *
	 * @param int $harvest_id ID of harvest source to get status from.
	 *
	 * @return array|bool Last job status of given harvest source.
	 */
	protected function get_harvest_source_status( $harvest_id ) {
		if ( empty( self::$harvest_sources ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'harvest_source_list';
			$data     = array( 'return_last_job_status' => true );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				self::$harvest_sources = $response['result'];
			} else {
				Ckan_Backend_Helper::print_error_messages( $errors );
			}
		}

		foreach ( self::$harvest_sources as $harvest_source ) {
			if ( $harvest_id === $harvest_source['id'] ) {
				return $harvest_source;
			}
		}

		return false;
	}

	/**
	 * Add custom fields.
	 *
	 * @return void
	 */
	public function define_fields() {
		$cmb = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-box',
			'title'        => __( 'Harvester', 'ogdch-backend' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		) );

		/* Harvester Information */
		$cmb->add_field( array(
			'name' => __( 'Harvester Information', 'ogdch-backend' ),
			'type' => 'title',
			'id'   => 'harvester_information_title',
		) );

		$cmb->add_field( array(
			'name'       => __( 'URL', 'ogdch-backend' ) . '*',
			'id'         => self::FIELD_PREFIX . 'url',
			'type'       => 'text_url',
			'attributes' => array(
				'required' => 'required',
			),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Description', 'ogdch-backend' ),
			'id'         => self::FIELD_PREFIX . 'description',
			'type'       => 'textarea_code',
			'attributes' => array( 'rows' => 3 ),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Source type', 'ogdch-backend' ) . '*',
			'id'         => self::FIELD_PREFIX . 'source_type',
			'type'       => 'select',
			'options'    => array( $this, 'get_source_type_form_field_options' ),
			'attributes' => array(
				'required' => 'required',
			),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Organization', 'ogdch-backend' ) . '*',
			'id'         => self::FIELD_PREFIX . 'organisation',
			'type'       => 'select',
			'options'    => array( 'Ckan_Backend_Helper', 'get_organisation_form_field_options' ),
			'attributes' => array(
				'required' => 'required',
			),
		) );

		$cmb->add_field( array(
			'name'    => __( 'Update frequency', 'ogdch-backend' ) . '*',
			'id'      => self::FIELD_PREFIX . 'update_frequency',
			'type'    => 'select',
			'options' => array(
				'MANUAL'   => __( 'Manual', 'ogdch-backend' ),
				'MONTHLY'  => __( 'Monthly', 'ogdch-backend' ),
				'WEEKLY'   => __( 'Weekly', 'ogdch-backend' ),
				'BIWEEKLY' => __( 'Biweekly', 'ogdch-backend' ),
				'DAILY'    => __( 'Daily', 'ogdch-backend' ),
				'ALWAYS'   => __( 'Always', 'ogdch-backend' ),
			),
			'attributes' => array(
				'required' => 'required',
			),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Configuration', 'ogdch-backend' ),
			'id'         => self::FIELD_PREFIX . 'config',
			'desc'       => __( 'Only a valid JSON object is allowed. Eg. { "option": "value" }', 'ogdch-backend' ),
			'type'       => 'textarea_code',
			'attributes' => array( 'rows' => 3 ),
		) );

		$cmb_side_ckan = new_cmb2_box( array(
			'id'           => self::POST_TYPE . '-sidebox',
			'title'        => __( 'CKAN Data', 'ogdch-backend' ),
			'object_types' => array( self::POST_TYPE ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		) );

		/* Ckan id (If Set -> update. Set on first save) */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'ID', 'ogdch-backend' ),
			'id'         => self::FIELD_PREFIX . 'ckan_id',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		/* Ckan name */
		$cmb_side_ckan->add_field( array(
			'name'       => __( 'Name (Slug)', 'ogdch-backend' ),
			'id'         => self::FIELD_PREFIX . 'ckan_name',
			'type'       => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
		) );

		$cmb_side_ckan->add_field( array(
			'name' => __( 'Sync Status', 'ogdch-backend' ),
			'type' => 'ckan_synced',
			'id'   => self::FIELD_PREFIX . 'ckan_synced',
		) );
	}

	/**
	 * Returns all available source types as an options array.
	 *
	 * @return array
	 */
	public static function get_source_type_form_field_options() {
		$source_type_options = array();

		$transient_name = Ckan_Backend::$plugin_slug . '_harvester_source_types';
		if ( false === ( $source_types = get_transient( $transient_name ) ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'harvesters_info_show';
			$response = Ckan_Backend_Helper::do_api_request( $endpoint );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				$source_types = $response['result'];

				// save result in transient
				set_transient( $transient_name, $source_types, 1 * HOUR_IN_SECONDS );
			} else {
				Ckan_Backend_Helper::print_error_messages( $errors );
			}
		}

		foreach ( $source_types as $source_type ) {
			$source_type_options[ $source_type['name'] ] = $source_type['title'];
		}

		return $source_type_options;
	}
}
