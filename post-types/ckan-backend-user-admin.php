<?php
/**
 * Menu page ckan-user-admin
 *
 * @package CKAN\Backend
 */

/**
 * Class Ckan_Backend_User_Admin
 */
class Ckan_Backend_User_Admin {

	/**
	 * Menu slug.
	 * @var string
	 */
	public $menu_slug = 'ckan-user-admin-page';

	/**
	 * Page suffix.
	 * @var string
	 */
	public $page_suffix = '';

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
		$this->page_suffix = add_submenu_page(
			'users.php',
			__( 'Add New Organization User', 'ogdch-backend' ),
			__( 'Organization User', 'ogdch-backend' ),
			'edit_organisation_users',
			$this->menu_slug,
			array( $this, 'user_page_callback' )
		);
	}

	/**
	 *  Callback for the user page.
	 */
	public function user_page_callback() {
		// must check that the user has the required capability
		if ( ! current_user_can( 'edit_organisation_users' ) ) {
			wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.' ) ) );
		}

		// check if the form was sent
		if ( isset( $_REQUEST['action'] ) && 'add_user' === $_REQUEST['action'] ) {
			// check the nonce before we do anything else
			check_admin_referer( 'create_user', 'create_user_nonce' );

			$random_password = wp_generate_password();
			$organisation = isset( $_REQUEST[ Ckan_Backend::$plugin_slug . '_organisation' ] ) ? $_REQUEST[ Ckan_Backend::$plugin_slug . '_organisation' ] : '';
			$userdata = array(
				'user_login'  => $_REQUEST['user_login'],
				'user_pass'   => $random_password,
				'user_email'  => $_REQUEST['email'],
				'last_name'   => $_REQUEST['last_name'],
				'first_name'  => $_REQUEST['first_name'],
				'role'        => $_REQUEST['role'],
			);
			if ( ! in_array( $userdata['role'], array_keys( get_editable_roles() ) ) ) {
				wp_die( esc_html( __( 'You do not have permission to create users for this role.' ) ) );
			}

			$success = true;
			if ( empty( $userdata['user_login'] ) ) {
				Ckan_Backend_Helper::print_error_messages( __( 'Username is mandatory' ) );
				$success = false;
			}
			if ( empty( $userdata['user_email'] ) ) {
				Ckan_Backend_Helper::print_error_messages( __( 'E-Mail is mandatory' ) );
				$success = false;
			}
			if ( empty( $organisation ) || ! Ckan_Backend_Helper::is_own_organization( $organisation ) ) {
				Ckan_Backend_Helper::print_error_messages( __( 'Please select a valid organization.' ) );
				// reset organisation if value is invalid
				$organisation = get_the_author_meta( Ckan_Backend::$plugin_slug . '_organisation', get_current_user_id() );
				$success = false;
			}
			if ( username_exists( $userdata['user_login'] ) ) {
				Ckan_Backend_Helper::print_error_messages( __( 'User with this username already exists, choose a different one.' ) );
				$success = false;
			}
			if ( email_exists( $userdata['user_email'] ) ) {
				Ckan_Backend_Helper::print_error_messages( __( 'User with this e-mail address already exists, choose a different one.' ) );
				$success = false;
			}
			if ( $success ) {
				$user_id = wp_insert_user( $userdata );
				wp_new_user_notification( $user_id, null, 'both' );

				if ( ! is_wp_error( $user_id ) ) {
					update_user_meta( $user_id, Ckan_Backend::$plugin_slug . '_organisation', $organisation );
					Ckan_Backend_Helper::print_messages( sprintf( esc_html__( 'User %s successfully created. An e-mail to set the password has been sent to the user.', 'ogdch-backend' ), $userdata['user_login'] ) );
				} else {
					Ckan_Backend_Helper::print_error_messages( sprintf( esc_html__( 'Error while creating user: %s', 'ogdch-backend' ), $user_id->get_error_message() ) );
					$success = false;
				}
			}
			$login = ( ! $success && isset( $_REQUEST['user_login'] ) ? $_REQUEST['user_login'] : '' );
			$email = ( ! $success && isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : '' );
			$first_name = ( ! $success &&  isset( $_REQUEST['first_name'] ) ? $_REQUEST['first_name'] : '' );
			$last_name = ( ! $success &&  isset( $_REQUEST['last_name'] ) ? $_REQUEST['last_name'] : '' );
			$role = ( ! $success && isset( $_REQUEST['role'] ) ? $_REQUEST['role'] : '' );
		} else {
			$login = '';
			$email = '';
			$first_name = '';
			$last_name = '';
			$role = '';
			$organisation = get_the_author_meta( Ckan_Backend::$plugin_slug . '_organisation', get_current_user_id() );
		}

		?>
		<div class="wrap organization_user">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="" method="POST">
			<?php wp_nonce_field( 'create_user', 'create_user_nonce' ); ?>
			<input type="hidden" name="page" value="<?php echo esc_attr( ( isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '' ) ); ?>" />
			<input type="hidden" name="action" value="add_user" />
			<div class="postbox">
				<div class="inside">

			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row"><label for="user_login"><?php esc_html_e( 'Username', 'ogdch-backend' ); ?> <span class="description">(<?php esc_html_e( 'required', 'ogdch-backend' ); ?>)</span></label></th>
					<td><input name="user_login" type="text" id="user_login" value="<?php echo esc_attr( $login ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="email"><?php esc_html_e( 'E-Mail', 'ogdch-backend' ); ?> <span class="description">(<?php esc_html_e( 'required', 'ogdch-backend' ); ?>)</span></label></th>
					<td><input name="email" type="email" id="email" value="<?php echo esc_attr( $email ); ?>" /></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="first_name"><?php esc_html_e( 'First name', 'ogdch-backend' ); ?></label></th>
					<td><input name="first_name" type="text" id="first_name" value="<?php echo esc_attr( $first_name ); ?>" /></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="last_name"><?php esc_html_e( 'Last name', 'ogdch-backend' ); ?></label></th>
					<td><input name="last_name" type="text" id="last_name" value="<?php echo esc_attr( $last_name ); ?>" /></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="role"><?php esc_html_e( 'Role', 'ogdch-backend' ); ?></label></th>
					<td><select name="role" id="role">
							<?php foreach ( get_editable_roles() as $role_name => $role_info ) : ?>
								<option value="<?php echo esc_attr( $role_name ) ?>" <?php echo esc_attr( $role_name === $role ? "selected='selected'" : '' ); ?>><?php echo esc_html( $role_info['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row">
						<label for="ckan-backend_organisation"><?php esc_html_e( 'Organisation', 'ogdch-backend' ); ?></label>
						<span class="description">(<?php esc_html_e( 'required', 'ogdch-backend' ); ?>)</span>
					</th>
					<td>
						<?php
						$filter_organizations = true;
						$organisation_list = Ckan_Backend_Helper::get_organisation_form_field_options( $filter_organizations );

						if ( count( $organisation_list ) > 1 ) {
							?>
							<select name="<?php echo esc_attr( Ckan_Backend::$plugin_slug . '_organisation' ); ?>" id="<?php echo esc_attr( Ckan_Backend::$plugin_slug . '_organisation' ); ?>">
								<?php
								echo '<option value="">' . esc_attr__( '- Please choose -', 'ogdch-backend' ) . '</option>';
								foreach ( $organisation_list as $key => $title ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $organisation, $key, false ) . '>' . esc_html( $title ) . '</option>';
								}
								?>
							</select>
							<?php
						} else {
							$organisation_title = Ckan_Backend_Helper::get_organization_title( $organisation );
							?>
							<input name="<?php echo esc_attr( Ckan_Backend::$plugin_slug . '_organisation' ); ?>" type="hidden" id="<?php echo esc_attr( Ckan_Backend::$plugin_slug . '_organisation' ); ?>" value="<?php echo esc_attr( $organisation ); ?>" />
							<?php
							echo esc_html( $organisation_title );
						}
						?>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Add new user', 'ogdch-backend' ), 'primary', 'show', false ); ?>
			</form>
		</div>
		<?php
	}
}
