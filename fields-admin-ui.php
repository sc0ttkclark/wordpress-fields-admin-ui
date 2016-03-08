<?php
/**
 * Plugin Name: Fields Admin UI
 * Plugin URI: https://github.com/sc0ttkclark/wordpress-fields-ui
 * Description: An Administrative UI for the WordPress Fields API, which lets you add new sections and fields to the currently supported Fields API forms
 * Version: 1.0.1
 * Author: Scott Kingsley Clark
 * Author URI: http://scottkclark.com/
 * License: GPL2+
 * GitHub Plugin URI: https://github.com/sc0ttkclark/wordpress-fields-ui
 * GitHub Branch: master
 * Requires WP: 4.4
 */

/**
 * The absolute server path to the Fields UI directory.
 */
define( 'FIELDS_ADMIN_UI_DIR', plugin_dir_path( __FILE__ ) );

function fields_ui_setup() {

	require_once( FIELDS_ADMIN_UI_DIR . 'classes/class-fields-admin-ui.php' );

	$fields_ui = Fields_Admin_UI::setup();

}
add_action( 'plugins_loaded', 'fields_ui_setup' );