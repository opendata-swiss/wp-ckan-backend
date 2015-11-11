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
		?>
		<div class="wrap harvester_dashboard">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		</div>

		<?php
	}

}
