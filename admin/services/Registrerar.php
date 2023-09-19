<?php
/**
 * API routes registeration
 *
 * @since 1.0.0
 */

namespace IRTaxPortalAPI\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Registrerar {

	/**
	 * Instance of this class.
	 *
	 * @since  1.0.0
	 */
	public static $instance;

	/**
	 * Provides access to a single instance of a module using the singleton pattern.
	 *
	 * @since  1.0.0
	 *
	 * @return object
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		register_rest_route(
			'IRTaxPortalAPI/v1',
			'authentication',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'login' ),
					// 'permission_callback' => array( $this, 'permission_callback' ),
				),
			)
		);

	}

	/**
	 * Register routes.
	 *
	 * @since 1.0.0
	 */
	public function permission_callback( $request ) {
		// Check if the user is authenticated or has the necessary capabilities
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permission to access this route.', 'text-domain' ),
				array( 'status' => 403 )
			);
		}

		// Return true if the user has the necessary permissions
		return true;
	}

	/**
	 * Create Response.
	 *
	 * @since 1.0.0
	 */
	public static function create_response( $message, $code ) {
        return new \WP_REST_Response(
			array(
				'response' => $message,
				'status' => $code
			)
		);
    }

	/**
	 * Authentication
	 *
	 * @since 1.0.0
	 */
	public function login( $request ) {
		return Login::authentication( $request );
	}
}
