<?php
/**
 * Plugin Name: CKAN Backend
 * Description: Syncs data from and to CKAN
 * Author: Team Jazz <juerg.hunziker@liip.ch>
 * Version: 1.0
 * Date: 17.06.2015
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once plugin_dir_path( __FILE__ ) . 'ckan-backend-main.php';

function run_ckan_backend_main() {
	$ckan_backend_main = new Ckan_Backend_Main();
}

run_ckan_backend_main();