<?php
/*
 * Plugin Name:       Moadianabzar ap
 * Plugin URI:        https://irtaxportal.com
 * Description:       -
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            IRWP
 * Author URI:        https://profiles.wordpress.org/irwp/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       irtaxportal_api
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'IRTAXPORTALAPI_VERSION', '1.0.0' );
define( 'IRTAXPORTALAPI_PATH', plugin_dir_path( __FILE__ ) );
define( 'IRTAXPORTALAPI_URL', plugin_dir_url( __FILE__ ) );
define( 'IRTAXPORTALAPI_ASSETS', IRTAXPORTALAPI_URL . 'public/dist/' );
define( 'IRTAXPORTALAPI_PREFIX', 'irtaxportal_api' );

require IRTAXPORTALAPI_PATH . 'vendor/autoload.php';

/**
 * Get an instance of the Plugin class.
 *
 * @since 1.0.0
 */
if ( class_exists( 'IRTaxPortalAPI\Manager' ) ) {

	IRTaxPortalAPI\Manager::instance();
}

/**
 * Fire installation functions by plugin activation.
 *
 * @since 1.0.0
 */
function IRTaxPortalAPI_activation() {
	IRTaxPortalAPI\Manager::instance()->install();
}
register_activation_hook( __FILE__, 'IRTaxPortalAPI_activation' );