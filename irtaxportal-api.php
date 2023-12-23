<?php
/*
 * Plugin Name:       Moadianabzar API
 * Plugin URI:        https://irtaxportal.com
 * Description:       -
 * Version:          10.0.0
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            Moadianabzar
 * Author URI:        https://profiles.wordpress.org/irwp/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       moadian_abzar
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MOADIANABZAR_VERSION', '1.0.0' );
define( 'MOADIANABZAR_PATH', plugin_dir_path( __FILE__ ) );
define( 'MOADIANABZAR_URL', plugin_dir_url( __FILE__ ) );
define( 'MOADIANABZAR_ASSETS', MOADIANABZAR_URL . 'public/dist/' );
define( 'MOADIANABZAR_PREFIX', 'moadian_abzar' );

require MOADIANABZAR_PATH . 'vendor/autoload.php';

/**
 * Get an instance of the Plugin class.
 *
 * @since 1.0.0
 */
if ( class_exists( 'MoadianAbzar\Manager' ) ) {

	MoadianAbzar\Manager::instance();
}

/**
 * Fire installation functions by plugin activation.
 *
 * @since 1.0.0
 */
function MoadianAbzar_activation() {
	MoadianAbzar\Manager::instance()->install();
}
register_activation_hook( __FILE__, 'MoadianAbzar_activation' );
