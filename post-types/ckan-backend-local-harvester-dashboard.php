<?php
/**
 * Menu page ckan-local-harvester-dashboard-page
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
	 * Page suffix.
	 * @var string
	 */
	public $page_suffix = '';

	/**
	 * Job status which mean that the it is still running.
	 * @var array
	 */
	public $running_job_status = array(
		'New',
		'Running',
	);

	/**
	 * Current url without action parameter.
	 * @var string
	 */
	public $current_url_without_action = '';

	/**
	 * Constructor of this class.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ), 10, 1 );
	}

	/**
	 * Register a submenu page.
	 *
	 * @return void
	 */
	public function register_submenu_page() {
		$this->page_suffix = add_submenu_page(
			'edit.php?post_type=' . Ckan_Backend_Local_Harvester::POST_TYPE,
			__( 'Harvester Dashboard', 'ogdch-backend' ),
			__( 'Dashboard', 'ogdch-backend' ),
			'create_harvesters',
			$this->menu_slug,
			array( $this, 'dashboard_page_callback' )
		);
	}

	/**
	 * Adds scripts
	 *
	 * @param string $suffix Suffix of current page.
	 */
	public function add_scripts( $suffix ) {
		if ( $suffix !== $this->page_suffix ) {
			return;
		}

		wp_enqueue_script( 'harvester-dashboard', plugins_url( '../assets/javascript/harvester-dashboard.js', __FILE__ ), array( 'jquery-ui-accordion', 'jquery-effects-core' ) );
	}

	/**
	 *  Callback for the harvester dashboard page.
	 */
	public function dashboard_page_callback() {
		// must check that the user has the required capability
		if ( ! current_user_can( 'create_harvesters' ) ) {
			wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.' ) ) );
		}
		$this->current_url_without_action = remove_query_arg( 'action' );

		$harvester_selection_field_name = 'harvester_id';
		$selected_harvester_id = '';
		if ( isset( $_GET[ $harvester_selection_field_name ] ) ) {
			$selected_harvester_id = $_GET[ $harvester_selection_field_name ];
		}
		$current_action = '';
		if ( isset( $_GET['action'] ) ) {
			$current_action = $_GET['action'];
		}
		if ( 'reharvest' === $current_action && ! empty( $selected_harvester_id ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'harvest_job_create';
			$data     = array( 'source_id' => $selected_harvester_id );
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				echo '<div class="updated"><p>' . esc_html__( 'Successfully created new harvester job.', 'ogdch-backend' ) . '</p></div>';
			} else {
				Ckan_Backend_Helper::print_error_messages( $errors );
			}
		}
		if ( 'abort' === $current_action && ! empty( $selected_harvester_id ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'harvest_job_abort';
			$data     = array( 'source_id' => $selected_harvester_id );
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				echo '<div class="updated"><p>' . esc_html__( 'Current harvester job successfully aborted.', 'ogdch-backend' ) . '</p></div>';
			} else {
				Ckan_Backend_Helper::print_error_messages( $errors );
			}
		}
		if ( 'clear' === $current_action && ! empty( $selected_harvester_id ) ) {
			$endpoint = CKAN_API_ENDPOINT . 'harvest_source_clear';
			$data     = array( 'id' => $selected_harvester_id );
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
			$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

			if ( 0 === count( $errors ) ) {
				echo '<div class="updated"><p>' . esc_html__( 'Successfully cleared all harvester datasets.', 'ogdch-backend' ) . '</p></div>';
			} else {
				Ckan_Backend_Helper::print_error_messages( $errors );
			}
		}

		$harvesters = $this->get_harvester_selection_form_field_options();
		?>
		<div class="wrap harvester_dashboard">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form enctype="multipart/form-data" action="" method="GET">
				<input type="hidden" name="post_type" value="<?php echo esc_attr( ( isset( $_GET['post_type'] ) ? $_GET['post_type'] : '' ) ); ?>" />
				<input type="hidden" name="page" value="<?php echo esc_attr( ( isset( $_GET['page'] ) ? $_GET['page'] : '' ) ); ?>" />
				<div class="postbox">
					<div class="inside">
						<table class="form-table">
							<tbody>
								<tr>
									<th scope="row">
										<label for="harvester_selection"><?php esc_html_e( __( 'Choose Harvester:', 'ogdch-backend' ) ); ?></label>
									</th>
									<td>
										<select id="harvester_selection" name="<?php echo esc_attr( $harvester_selection_field_name ); ?>">
											<option value=""><?php esc_html_e( '- Please choose -', 'ogdch-backend' ); ?></option>
											<?php
											foreach ( $harvesters as $id => $title ) {
												echo '<option value="' . esc_attr( $id ) . '"' . ( $id === $selected_harvester_id ? 'selected="selected"' : '' ) . '>' . esc_html( $title ) . '</option>';
											}
											?>
										</select>
										<?php submit_button( __( 'Show', 'ogdch-backend' ), 'primary', 'show', false ); ?>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<?php
				if ( ! empty( $selected_harvester_id ) && array_key_exists( $selected_harvester_id, $harvesters ) ) {
					$this->render_harvester_detail( $selected_harvester_id, $harvesters[ $selected_harvester_id ] );
				} else {
					echo '<p>' . esc_html__( 'Please select a harvester first.', 'ogdch-backend' ) . '</p>';
				}
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders harvester detail part
	 *
	 * @param int    $harvester_id ID of harvester which is selected.
	 * @param string $harvester_title Title of selected harvester.
	 */
	public function render_harvester_detail( $harvester_id, $harvester_title ) {
		echo '<h2>' . esc_html( $harvester_title ) . '</h2>';
		echo '<p><span class="dashicons dashicons-image-rotate"></span> <a href="' . esc_url( $this->current_url_without_action ) . '">' . esc_html( __( 'Refresh page', 'ogdch-backend' ) ) . '</a></p>';
		printf( esc_html_x( 'Harvester ID: %s', '%s contains the id of the harvester', 'ogdch-backend' ), esc_html( $harvester_id ) );

		$harvester_search_args = array(
			// @codingStandardsIgnoreStart
			'meta_key'       => Ckan_Backend_Local_Harvester::FIELD_PREFIX . 'ckan_id',
			'meta_value'     => $harvester_id,
			// @codingStandardsIgnoreEnd
			'post_type'      => Ckan_Backend_Local_Harvester::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => 1,
		);
		$harvesters            = get_posts( $harvester_search_args );
		if ( is_array( $harvesters ) && count( $harvesters ) > 0 ) {
			echo ' <a href="' . esc_url( admin_url( 'post.php?post=' . esc_attr( $harvesters[0]->ID ) . '&action=edit' ) ) . '">' . esc_html__( '(edit)', 'ogdch-backend' ) . '</a>';
		}

		if ( isset( $_GET['job_id'] ) ) {
			$this->render_job_detail( $_GET['job_id'] );
		} else {
			$show_all_jobs = false;
			if ( isset( $_GET['show_more'] ) && 1 === intval( $_GET['show_more'] ) ) {
				$show_all_jobs = true;
			}
			if ( $show_all_jobs ) {
				$harvester_jobs = $this->get_harvester_jobs( $harvester_id );
			} else {
				$harvester_status = $this->get_harvester_status( $harvester_id );
				$harvester_jobs = array();
				if ( ! empty( $harvester_status['last_job'] ) ) {
					$harvester_jobs[] = $harvester_status['last_job'];
				}
			}
			$this->render_harvester_job_list( $harvester_jobs, $show_all_jobs );

			if ( ! $show_all_jobs ) {
				echo '<a href="' . esc_url( add_query_arg( 'show_more', 1, $this->current_url_without_action ) ) .'" class="button button-secondary">' . esc_html__( 'Show all jobs', 'ogdch-backend' ) . '</a>';
			} else {
				echo '<a href="' . esc_url( remove_query_arg( 'show_more', $this->current_url_without_action ) ) .'" class="button button-secondary">' . esc_html__( 'Show less jobs', 'ogdch-backend' ) . '</a>';
			}
		}
	}

	/**
	 * Renders job list of harvester
	 *
	 * @param array   $jobs All jobs of current harvester.
	 * @param boolean $show_all_jobs True when currently all jobs of this harvester are displayed.
	 */
	public function render_harvester_job_list( $jobs, $show_all_jobs ) {
		$has_unfinished_job = false;
		foreach ( $jobs as $harvester_job ) {
			if ( in_array( $harvester_job['status'], $this->running_job_status ) ) {
				$has_unfinished_job = true;
				break;
			}
		}
		?>
		<div class="actions">
			<?php
			if ( $has_unfinished_job ) {
				$abort_onclick_confirm = "if( !confirm('" . esc_attr__( 'Are you sure you want to abort the current job of this harvester?', 'ogdch-backend' ) . "') ) return false;";
				echo '<a href="' . esc_url( add_query_arg( 'action', 'abort' ) ) . '" class="button delete" onclick="' . esc_attr( $abort_onclick_confirm ) . '">' . esc_html__( 'Abort unfinished job', 'ogdch-backend' ) . '</a>';
			} else {
				echo '<a href="' . esc_url( add_query_arg( 'action', 'reharvest' ) ) . '" class="button button-secondary">' . esc_html__( 'Reharvest', 'ogdch-backend' ) . '</a>';
			}

			echo ' ';
			$clear_onclick_confirm = "if( !confirm('" . esc_attr__( 'Are you sure you want to clear all data of this harvester?', 'ogdch-backend' ) . "') ) return false;";
			echo '<a href="' . esc_url( add_query_arg( 'action', 'clear' ) ) . '" class="button delete" onclick="' . esc_attr( $clear_onclick_confirm ) . '">' . esc_html__( 'Clear', 'ogdch-backend' ) . '</a>';
			?>
		</div>
		<div class="all-jobs">
			<h3>
				<?php
				if ( $show_all_jobs ) {
					esc_html_e( 'All Harvest Jobs', 'ogdch-backend' );
				} else {
					esc_html_e( 'Latest Harvest Job', 'ogdch-backend' );
				}
				?>
			</h3>
			<?php
			if ( empty( $jobs ) ) {
				echo '<p>' . esc_html__( 'No Jobs found for this harvester.', 'ogdch-backend' ) . '</p>';
			} else {
				$collapsed = false;
				foreach ( $jobs as $job ) {
					$this->render_job_table( $job, $collapsed );
					$collapsed = true;
				}
			}
			?>
		</div>
		<?php
	}

	/**
	 * Renders job table with all information about it
	 *
	 * @param array $job Job to render.
	 * @param bool  $collapsed If job should be collapsed on load.
	 * @param bool  $show_detail_button Add show detail button at the bottom.
	 */
	public function render_job_table( $job, $collapsed = true, $show_detail_button = true ) {
		$job_created = Ckan_Backend_Helper::convert_date_to_readable_format( $job['created'] );
		$collapsed_class = ( $collapsed ? 'collapsed' : 'open' );
		?>
		<div class="postbox">
			<div class="inside collapsible <?php echo esc_attr( $collapsed_class ); ?>">
				<h4><?php printf( esc_html_x( 'Job created at %s', '%s contains the date and time when the jobs was created.', 'ogdch-backend' ), esc_html( $job_created ) ); ?></h4>
				<div>
					<?php
					if ( ! empty( $job['stats'] ) ) {
						?>
						<div class="harvester-status">
							<span class="label label-added"><?php printf( esc_html_x( '%s added', '%s contains the number of added datasets.', 'ogdch-backend' ), esc_html( ( array_key_exists( 'added', $job['stats'] ) ? $job['stats']['added'] : 0 ) ) ); ?></span>
							<span class="label label-updated"><?php printf( esc_html_x( '%s updated', '%s contains the number of updated datasets.', 'ogdch-backend' ), esc_html( ( array_key_exists( 'updated', $job['stats'] ) ? $job['stats']['updated'] : 0 ) ) ); ?></span>
							<span class="label label-deleted"><?php printf( esc_html_x( '%s deleted', '%s contains the number of deleted datasets.', 'ogdch-backend' ), esc_html( ( array_key_exists( 'deleted', $job['stats'] ) ? $job['stats']['deleted'] : 0 ) ) ); ?></span>
							<span class="label label-errored"><?php printf( esc_html_x( '%s errors', '%s contains the number of errors.', 'ogdch-backend' ), esc_html( ( array_key_exists( 'errored', $job['stats'] ) ) ? $job['stats']['errored'] : 0 ) ); ?></span>
						</div>
						<?php
					}
					?>
					<table class="job-table table-striped">
						<tr>
							<th><?php esc_html_e( 'Job ID', 'ogdch-backend' ); ?></th>
							<td><?php echo esc_html( $job['id'] ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Created', 'ogdch-backend' ); ?></th>
							<td><?php echo esc_html( Ckan_Backend_Helper::convert_date_to_readable_format( $job['created'] ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Started', 'ogdch-backend' ); ?></th>
							<td><?php echo esc_html( Ckan_Backend_Helper::convert_date_to_readable_format( $job['gather_started'] ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Finished', 'ogdch-backend' ); ?></th>
							<td><?php echo esc_html( Ckan_Backend_Helper::convert_date_to_readable_format( $job['finished'] ) ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Status', 'ogdch-backend' ); ?></th>
							<td><?php echo esc_html( $job['status'] ); ?></td>
						</tr>
					</table>
					<?php
					if ( $show_detail_button ) {
						echo '<p><a href="' . esc_url( add_query_arg( 'job_id', $job['id'], $this->current_url_without_action ) ) . '">' . esc_html( __( 'Show Job details', 'ogdch-backend' ) ) . '</a></p>';
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders job detail page
	 *
	 * @param int $job_id ID of current job.
	 */
	public function render_job_detail( $job_id ) {
		$job = $this->get_job( $job_id );

		echo '<h3>' . esc_html( __( 'Job detail', 'ogdch-backend' ) ) . '</h3>';
		echo '<p><span class="dashicons dashicons-arrow-left-alt2"></span> <a href="' . esc_url( remove_query_arg( 'job_id' ), $this->current_url_without_action ) . '">' . esc_html( __( 'Back to Job list', 'ogdch-backend' ) ) . '</a></p>';
		if ( ! empty( $job ) ) {
			$this->render_job_table( $job, false, false );
			if ( 'Finished' === $job['status'] ) {
				$this->render_job_error_summary( $job );
				$this->render_job_report( $this->get_job_report( $job_id ) );
			}
		}
	}

	/**
	 * Renders job error summary
	 *
	 * @param array $job Current job.
	 */
	public function render_job_error_summary( $job ) {
		?>
		<h3><?php esc_html_e( 'Error summary', 'ogdch-backend' ); ?></h3>
		<div class="postbox">
			<div class="inside">
				<?php
				if (
					isset( $job['object_error_summary'] ) &&
					0 === count( $job['object_error_summary'] ) &&
					isset( $job['gather_error_summary'] ) &&
					0 === count( $job['gather_error_summary'] )
				) {
					echo '<p>' . esc_html__( 'No errors for this job', 'ogdch-backend' ) . '</p>';
				} else {
					if ( isset( $job['gather_error_summary'] ) && 0 < count( $job['gather_error_summary'] ) ) {
						?>
						<h4><?php esc_html_e( 'Job Errors', 'ogdch-backend' ); ?></h4>
						<?php
						$this->render_job_error_table( $job['gather_error_summary'] );
					}
					if ( isset( $job['object_error_summary'] ) && 0 < count( $job['object_error_summary'] ) ) {
						?>
						<h4><?php esc_html_e( 'Document Errors', 'ogdch-backend' ); ?></h4>
						<?php
						$this->render_job_error_table( $job['object_error_summary'] );
					}
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders job report
	 *
	 * @param array $job_report Report of current job.
	 */
	public function render_job_report( $job_report ) {
		if ( count( $job_report['gather_errors'] ) > 0 || count( $job_report['object_errors'] ) > 0 ) {
			?>
			<h3><?php esc_html_e( 'Error Report', 'ogdch-backend' ); ?></h3>
			<div class="postbox">
				<div class="inside">
					<?php
					if ( count( $job_report['gather_errors'] ) > 0 ) {
						?>
						<h4><?php esc_html_e( 'Job Errors', 'ogdch-backend' ); ?></h4>
						<ul>
							<?php foreach ( $job_report['gather_errors'] as $gather_error ) { ?>
								<li><?php echo esc_html( $gather_error['message'] ); ?></li>
							<?php } ?>
						</ul>
						<?php
					}
					if ( count( $job_report['object_errors'] ) > 0 ) {
						?>
						<h4><?php esc_html_e( 'Document Errors', 'ogdch-backend' ); ?></h4>
						<ul>
							<?php foreach ( $job_report['object_errors'] as $object_error ) { ?>
								<li>
									<h5><?php echo esc_html( $object_error['guid'] ); ?></h5>
									<?php
									foreach ( $object_error['errors'] as $error ) {
										$line = '';
										if ( ! empty( $error['line'] ) ) {
											$line = ' <span>' . printf( esc_html_x( '(Line: %s)', '%s contains the line number of the error.', 'ogdch-backend' ), esc_html( $error['line'] ) ) . '</span>';
										}
										echo '<p>' . esc_html( $error['message'] . $line ) . '</p>';
									}
									?>
								</li>
							<?php } ?>
						</ul>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Renders error table.
	 *
	 * @param array $errors Errors to render.
	 */
	public function render_job_error_table( $errors ) {
		?>
		<table class="error-table table-striped">
			<thead>
			<tr>
				<th class="count"><?php esc_html_e( 'Count', 'ogdch-backend' ); ?></th>
				<th><?php esc_html_e( 'Message', 'ogdch-backend' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $errors as $error ) { ?>
				<tr>
					<td><?php echo esc_html( $error['error_count'] ); ?></td>
					<td><?php echo esc_html( $error['message'] ); ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
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
			$data     = array( 'only_active' => true );
			$data     = wp_json_encode( $data );

			$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
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
	 * Returns current status of given harvester. Warning: Status should never be saved in transient because we have no control over it!
	 *
	 * @param int $harvester_id ID of harvester to get status from.
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
	 * Returns all jobs of given harvester. Warning: Jobs should never be saved in transient because we have no control over them!
	 *
	 * @param int $harvester_id ID of harvester to get jobs from.
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

	/**
	 * Returns job details. Warning: Jobs should never be saved in transient because we have no control over them!
	 *
	 * @param int $job_id ID of job to get detail from.
	 *
	 * @return array
	 */
	public function get_job( $job_id ) {
		$job = array();

		$endpoint = CKAN_API_ENDPOINT . 'harvest_job_show';
		$data     = array( 'id' => $job_id );
		$data     = wp_json_encode( $data );

		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

		if ( 0 === count( $errors ) ) {
			$job = $response['result'];
		} else {
			Ckan_Backend_Helper::print_error_messages( $errors );
		}

		return $job;
	}

	/**
	 * Returns report of a given job. Warning: Jobs should never be saved in transient because we have no control over them!
	 *
	 * @param int $job_id ID of job to get report from.
	 *
	 * @return array
	 */
	public function get_job_report( $job_id ) {
		$job_report = array();

		$endpoint = CKAN_API_ENDPOINT . 'harvest_job_report';
		$data     = array( 'id' => $job_id );
		$data     = wp_json_encode( $data );

		$response = Ckan_Backend_Helper::do_api_request( $endpoint, $data );
		$errors   = Ckan_Backend_Helper::check_response_for_errors( $response );

		if ( 0 === count( $errors ) ) {
			$job_report = $response['result'];
		} else {
			Ckan_Backend_Helper::print_error_messages( $errors );
		}

		return $job_report;
	}
}
