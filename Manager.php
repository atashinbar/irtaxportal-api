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
		add_filter( 'rest_url_prefix', [$this,'MAChangeAPIRoute']);
		
	}

	/**
	 * Change API route
	 *
	 * @since 1.0.0
	 */
	function MAChangeAPIRoute( $slug ) {
		return 'api';
	}


	/**
	 * Installation functions on activation.
	 *
	 * @since 1.0.0
	 */
	public function install() {
		$this->createProductsTable();
	}

	/**
	 * create products table in database
	 *
	 * @since 1.0.0
	 */
	public function createProductsTable() {
		global $wpdb;

   		$table_name = $wpdb->prefix . "MA_products"; 
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		user_id INT NOT NULL,
		products longtext NOT NULL,
		PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
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
