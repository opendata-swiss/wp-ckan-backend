<?php
/**
 * ckan-local-harvester-dashboard-page
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_Local_Harvester_Dashboard
 */
class Ckan_Backend_Local_Harvester_Dashboard {

	/**
	 * Menu slug.
	 * @var string
	 */
	public $menu_slug = 'ckan-local-harvester-dashboard-page';

	/**
	 * Constructor of this class.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );
	}

	/**
	 * Register a submenu page.
	 *
	 * @return void
	 */
	public function register_submenu_page() {
		add_submenu_page(
			'edit.php?post_type=' . Ckan_Backend_Local_Harvester::POST_TYPE,
			__( 'Harvester Dashboard', 'ogdch' ),
			__( 'Dashboard', 'ogdch' ),
			'create_harvesters',
			$this->menu_slug,
			array( $this, 'dashboard_page_callback' )
		);
	}

	/**
	 * Callback for the harvester dashboard page.
	 *
	 * @return void
	 */
	public function dashboard_page_callback() {
		// must check that the user has the required capability
		if ( ! current_user_can( 'create_harvesters' ) ) {
			wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.' ) ) );
		}

		$harvester_selection_field_name = 'ckan_local_harvester_dashboard_harvester';
		$selected_harvester_id = '';
		if( isset( $_POST[ $harvester_selection_field_name ] ) ) {
			$selected_harvester_id = $_POST[ $harvester_selection_field_name ];
		}
		if( isset( $_POST[ 'reharvest' ] ) && ! empty( $selected_harvester_id ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'harvest_job_create';
			$data     = array( 'source_id' => $selected_harvester_id );
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				echo '<div class="updated"><p>' . esc_attr__( 'Successfully created new harvester job.', 'ogdch' ) . '</p></div>';
			} else {
				Ckan_Backend_Helper::print_error_messages( $errors );
			}
		}
		if( isset( $_POST[ 'abort' ] ) && ! empty( $selected_harvester_id ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'harvest_job_abort';
			$data     = array( 'id' => $selected_harvester_id );
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				echo '<div class="updated"><p>' . esc_attr__( 'Current harvester job successfully aborted', 'ogdch' ) . '</p></div>';
			} else {
				Ckan_Backend_Helper::print_error_messages( $errors );
			}
		}
		if( isset( $_POST[ 'clear' ] ) && ! empty( $selected_harvester_id ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'harvest_source_clear';
			$data     = array( 'id' => $selected_harvester_id );
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				echo '<div class="updated"><p>' . esc_attr__( 'Successfully cleared all harvester datasets', 'ogdch' ) . '</p></div>';
			} else {
				Ckan_Backend_Helper::print_error_messages( $errors );
			}
		}

		$harvesters = $this->get_harvester_selection_form_field_options();
		?>
		<div class="wrap harvester_dashboard">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form enctype="multipart/form-data" action="" method="POST">
				<div class="postbox">
					<div class="inside">
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">
										<label for="harvester_selection"><?php esc_html_e( __( 'Choose Harvester:', 'ogdch' ) ); ?></label>
									</th>
									<td>
										<select id="harvester_selection" name="<?php esc_attr_e( $harvester_selection_field_name ); ?>">
											<option value=""><?php esc_attr_e( '- Please choose -', 'ogdch' ); ?></option>
											<?php
											foreach( $harvesters as $id => $title ) {
												echo '<option value="' . esc_attr( $id ) . '"' . ( $id === $selected_harvester_id ? 'selected="selected"' : '' ) . '>' . esc_attr( $title ) . '</option>';
											}
											?>
										</select>
										<input type="submit" name="show" class="button-primary" value="<?php esc_attr_e( 'Show', 'ogdch' ); ?>">
									</td>
								</tr>
							</tbody>
						</table>
						<hr />
						<?php
						if ( ! empty( $selected_harvester_id ) ) {
							$this->render_harvester_detail( $selected_harvester_id, $harvesters[ $selected_harvester_id ] );
						} else {
							echo '<p>' . esc_attr__( 'Please select a harvester first.', 'ogdch' ) . '</p>';
						}
						?>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	public function render_harvester_detail( $harvester_id, $harvester_title ) {
		$harvester_status = $this->get_harvester_status( $harvester_id );
		$harvester_jobs = $this->get_harvester_jobs( $harvester_id );
		$date_format = 'd.m.Y H:i:s';

		?>
		<h2><?php esc_attr_e( $harvester_title ); ?></h2>
		<div class="actions">
			<input type="submit" name="reharvest" class="button-secondary" value="<?php esc_attr_e( 'Reharvest', 'ogdch' ); ?>">
			<input type="submit" name="clear" class="button-secondary" value="<?php esc_attr_e( 'Clear', 'ogdch' ); ?>">
			<input type="submit" name="abort" class="button-secondary" value="<?php esc_attr_e( 'Abort', 'ogdch' ); ?>">
		</div>

		<?php
		if( ! empty( $harvester_status ) && ! empty( $harvester_status['last_job'] ) ) {
			$last_job = $harvester_status['last_job'];
			$last_job_created = '-';
			if( ! empty( $last_job['created'] ) ) {
				$last_job_created = date( $date_format, strtotime( $last_job['created'] ) );
			}
			$last_job_started = '-';
			if( ! empty( $last_job['gather_started'] ) ) {
				$last_job_started = date( $date_format, strtotime( $last_job['gather_started'] ) );
			}
			$last_job_finished = '-';
			if( ! empty( $last_job['gather_started'] ) ) {
				$last_job_finished = date( $date_format, strtotime( $last_job['gather_finished'] ) );
			}
			?>
			<div class="latest-job">
				<h3><?php esc_attr_e( 'Latest Harvest Job', 'ogdch' ); ?></h3>
				<table class="table-small">
					<tr>
						<th><?php esc_attr_e( 'ID' ); ?></th>
						<td><?php esc_attr_e( $last_job['id'] ); ?></td>
					</tr>
					<tr>
						<th><?php esc_attr_e( 'Created' ); ?></th>
						<td><?php esc_attr_e( $last_job_created ); ?></td>
					</tr>
					<tr>
						<th><?php esc_attr_e( 'Started' ); ?></th>
						<td><?php esc_attr_e( $last_job_started ); ?></td>
					</tr>
					<tr>
						<th><?php esc_attr_e( 'Finished' ); ?></th>
						<td><?php esc_attr_e( $last_job_finished ); ?></td>
					</tr>
					<tr>
						<th><?php esc_attr_e( 'Status' ); ?></th>
						<td><?php esc_attr_e( $last_job['status'] ); ?></td>
					</tr>
				</table>
			</div>
			<?php
		}
		?>


		<div class="all-jobs">
			<h3><?php esc_attr_e( 'All Harvest Jobs', 'ogdch' ); ?></h3>
			<?php
			if( ! empty( $harvester_jobs ) ) {
				foreach( $harvester_jobs as $job ) {
					$job_created = '-';
					if( ! empty( $job['created'] ) ) {
						$job_created = date( $date_format, strtotime( $job['created'] ) );
					}
					$job_started = '-';
					if( ! empty( $job['gather_started'] ) ) {
						$job_started = date( $date_format, strtotime( $job['gather_started'] ) );
					}
					$job_finished = '-';
					if( ! empty( $job['gather_started'] ) ) {
						$job_finished = date( $date_format, strtotime( $job['gather_finished'] ) );
					}
					?>
					<table class="table-small">
						<tr>
							<th><?php esc_attr_e( 'ID' ); ?></th>
							<td><?php esc_attr_e( $job['id'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_attr_e( 'Created' ); ?></th>
							<td><?php esc_attr_e( $job_created ); ?></td>
						</tr>
						<tr>
							<th><?php esc_attr_e( 'Started' ); ?></th>
							<td><?php esc_attr_e( $job_started ); ?></td>
						</tr>
						<tr>
							<th><?php esc_attr_e( 'Finished' ); ?></th>
							<td><?php esc_attr_e( $job_finished ); ?></td>
						</tr>
						<tr>
							<th><?php esc_attr_e( 'Status' ); ?></th>
							<td><?php esc_attr_e( $job['status'] ); ?></td>
						</tr>
					</table>
					<?php
				}
			} else {
				echo '<p>' . esc_attr__( 'No Jobs found for this harvester.', 'ogdch' ) . '</p>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Returns all available source types as an options array.
	 *
	 * @return array
	 */
	public function get_harvester_selection_form_field_options() {
		$harvester_options = array();

		$transient_name = Ckan_Backend::$plugin_slug . '_harvesters';
		if ( false === ( $harvesters = get_transient( $transient_name ) ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'harvest_source_list';
			$response = Ckan_Backend_Helper::do_api_request( $endpoint );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				$harvesters = $response['result'];

				// save result in transient
				set_transient( $transient_name, $harvesters, 1 * HOUR_IN_SECONDS );
			} else {
				Ckan_Backend_Helper::print_error_messages( $errors );
			}
		}

		foreach ( $harvesters as $harvester ) {
			$harvester_options[ $harvester['id'] ] = $harvester['title'];
		}

		return $harvester_options;
	}

	/**
	 * Returns current status of given harvester. Warning: Status shouldn't be saved in transient because we have no control over it!
	 *
	 * @return array
	 */
	public function get_harvester_status( $harvester_id ) {
		$harvester_status = array();

		$endpoint = CKAN_API_ENDPOINT . 'harvest_source_show_status';
		$data     = array( 'id' => $harvester_id );
		$data     = wp_json_encode( $data );

		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

		if ( 0 === count( $errors ) ) {
			$harvester_status = $response['result'];
		} else {
			Ckan_Backend_Helper::print_error_messages( $errors );
		}

		return $harvester_status;
	}

	/**
	 * Returns all jobs of given harvester. Warning: Jobs shouldn't be saved in transient because we have no control over them!
	 *
	 * @return array
	 */
	public function get_harvester_jobs( $harvester_id ) {
		$harvester_jobs = array();

		$endpoint = CKAN_API_ENDPOINT . 'harvest_job_list';
		$data     = array( 'source_id' => $harvester_id );
		$data     = wp_json_encode( $data );

		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

		if ( 0 === count( $errors ) ) {
			$harvester_jobs = $response['result'];
		} else {
			Ckan_Backend_Helper::print_error_messages( $errors );
		}

		return $harvester_jobs;
	}

}
