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
			__( 'Add Organization User', 'ogdch' ),
			__( 'Organization User', 'ogdch' ),
			'edit_organisations',
			$this->menu_slug,
			array( $this, 'user_page_callback' )
		);
	}

	/**
	 *  Callback for the user page.
	 */
	public function user_page_callback() {
		// must check that the user has the required capability
		if ( ! current_user_can( 'edit_organisations' ) ) {
			wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.' ) ) );
		}

		// check if the form was sent
		if ( isset( $_REQUEST['action'] ) && 'add_user' === $_REQUEST['action'] ) {
			$random_password = wp_generate_password( 8, false );
			$userdata = array(
				'user_login'  => $_REQUEST['user_login'],
				'user_pass'   => $random_password,
				'user_email'  => $_REQUEST['email'],
				'last_name'   => $_REQUEST['last_name'],
				'first_name'  => $_REQUEST['first_name'],
				'role'        => $_REQUEST['role'],
			);

			if ( empty( $userdata['user_login'] ) ) {
				Ckan_Backend_Helper::print_error_messages( __( 'Username is mandatory' ) );
			} elseif ( empty( $userdata['user_email'] ) ) {
				Ckan_Backend_Helper::print_error_messages( __( 'E-Mail is mandatory' ) );
			} elseif ( username_exists( $userdata['user_login'] ) ) {
				Ckan_Backend_Helper::print_error_messages( __( 'User with this username already exists, choose a different one.' ) );
			} elseif ( email_exists( $userdata['user_email'] ) ) {
				Ckan_Backend_Helper::print_error_messages( __( 'User with this e-mail address already exists, choose a different one.' ) );
			} else {
				$user_id = wp_insert_user( $userdata );
				if ( ! is_wp_error( $user_id ) ) {
					update_user_meta( $user_id, Ckan_Backend::$plugin_slug . '_organisation', $_REQUEST[ Ckan_Backend::$plugin_slug . '_organisation' ] );
					Ckan_Backend_Helper::print_messages( sprintf( esc_html__( 'User %s successfully created', 'ogdch' ), $user_id ) );
				} else {
					Ckan_Backend_Helper::print_error_messages( sprintf( esc_html__( 'Error while creating user: %s', 'ogdch' ), $user_id->get_error_message() ) );
				}
			}
			$login = (isset( $_REQUEST['user_login'] ) ? $_REQUEST['user_login'] : '' );
			$email = (isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : '' );
			$first_name = ( isset( $_REQUEST['first_name'] ) ? $_REQUEST['first_name'] : '' );
			$last_name = ( isset( $_REQUEST['last_name'] ) ? $_REQUEST['last_name'] : '' );
			$role = (isset( $_REQUEST['role'] ) ? $_REQUEST['role'] : '' );
		} else {
			$login = '';
			$email = '';
			$first_name = '';
			$last_name = '';
			$role = '';
		}

		?>
		<div class="wrap organization_user">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form enctype="multipart/form-data" action="" method="POST">
			<input type="hidden" name="page" value="<?php echo esc_attr( ( isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : '' ) ); ?>" />
			<input type="hidden" name="action" value="add_user" />
			<div class="postbox">
				<div class="inside">

			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row"><label for="user_login"><?php esc_html_e( 'Username', 'ogdch' ); ?> <span class="description">(<?php esc_html_e( 'required', 'ogdch' ); ?>)</span></label></th>
					<td><input name="user_login" type="text" id="user_login" value="<?php echo esc_attr( $login ); ?>" aria-required="true" autocapitalize="none" autocorrect="off" /></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="email"><?php esc_html_e( 'E-Mail', 'ogdch' ); ?> <span class="description">(<?php esc_html_e( 'required', 'ogdch' ); ?>)</span></label></th>
					<td><input name="email" type="email" id="email" value="<?php echo esc_attr( $email ); ?>" /></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="first_name"><?php esc_html_e( 'First name', 'ogdch' ); ?></label></th>
					<td><input name="first_name" type="text" id="first_name" value="<?php echo esc_attr( $first_name ); ?>" /></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="last_name"><?php esc_html_e( 'Last name', 'ogdch' ); ?></label></th>
					<td><input name="last_name" type="text" id="last_name" value="<?php echo esc_attr( $last_name ); ?>" /></td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="role"><?php esc_html_e( 'Role', 'ogdch' ); ?></label></th>
					<td><select name="role" id="role">
							<?php foreach ( get_editable_roles() as $role_name => $role_info ): ?>
								<option value="<?php echo esc_attr( $role_name ) ?>" <?php echo ( $role_name === $role ? "selected='selected'" : '' ); ?>><?php echo esc_html( $role_info['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row">
						<label for="ckan-backend_organisation"><?php esc_html_e( 'Organisation', 'ogdch' ); ?></label>
						<span class="description">(<?php esc_html_e( 'required', 'ogdch' ); ?>)</span>
					</th>
					<td>
						<?php
						$organisation = get_the_author_meta( Ckan_Backend::$plugin_slug . '_organisation', get_current_user_id() );
						$org_title = Ckan_Backend_Helper::get_organization_title( $organisation );
						?>
						<select name="ckan-backend_organisation" id="ckan-backend_organisation" aria-required="true">
							<option value="<?php echo esc_attr( $organisation ); ?>" selected="selected"><?php echo esc_html( $org_title ); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Add new user', 'ogdch' ), 'primary', 'show', false ); ?>
			</form>
		</div>
		<?php
	}
}
