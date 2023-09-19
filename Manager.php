<?php
/**
 * Manager.
 *
 * @package IRTaxPortalAPI
 */

namespace IRTaxPortalAPI;

defined( 'ABSPATH' ) || exit;

final class Manager {

	/**
	 * Instance of this class.
	 *
	 * @since   1.0.0
	 */
	public static $instance;

	/**
	 * Provides access to a single instance of a module using the singleton pattern.
	 *
	 * @since   1.0.0
	 *
	 * @return  object
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->hooks();
		$this->setup();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.0.0
	 */
	private function hooks() {
		add_action( 'init', [ $this, 'load_plugin_textdomain'] );
	}


	/**
	 * Installation functions on activation.
	 *
	 * @since 1.0.0
	 */
	public function install() {
		// if ( ! get_option( 'IRTaxPortalAPI_settings' ) ) {
		// 	add_option( 'IRTaxPortalAPI_settings', json_encode( array() ) );
		// }
		// if ( ! get_option( 'IRTaxPortalAPI_identifiers' ) ) {
		// 	add_option( 'IRTaxPortalAPI_identifiers', json_encode( array(
		// 		array( 'productID' => '', 'productName' => '' )
		// 	) ) );
		// }
	}

	/**
	 * Load the IRTaxPortalAPI dependencies.
	 *
	 * @since 1.0.0
	 */
	private function setup() {
		apply_filters(
			'IRTaxPortalAPI/setup',
			[
				'rest_api'			=> Admin\Services\Registrerar::instance()
			]
		);

		/**
		 * Plugin loaded hook
		 */
		do_action( 'IRTaxPortalAPI/loaded' );
	}

	/**
	 * Load the localisation file.
	 *
	 * @since	1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'irtaxportal_api', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

}
