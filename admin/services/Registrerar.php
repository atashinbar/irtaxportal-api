<?php
/**
 * API routes registeration
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

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
			'MoadianAbzar/v1',
			'authentication',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'login' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'products',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => array($this , 'permission_callback'),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_product' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_product' ),
					'permission_callback' => '__return_true',
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
		$headers = array_change_key_case($request->get_headers(), CASE_LOWER);
        $headerKey = strtolower('authorization');
		return new \WP_Error(
			'rest_forbidden',
			$headers[$headerKey],
			array( 'status' => 200 )
		);
		if (isset($headers[$headerKey])) {
			$matches = [];
			preg_match(
				'/^(?:Bearer)?[\s]*(.*)$/mi',
				$headers[$headerKey],
				$matches
			);

			if (isset($matches[1]) && !empty(trim($matches[1]))) {
				return new \WP_Error(
					'rest_forbidden',
					$matches[1],
					array( 'status' => 200 )
				);
			}
		}

		
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

	/**
	 * get products
	 *
	 * @since 1.0.0
	 */
	public function get_products( $request ) {
		return Products::get_all_products( $request );
	}

	/**
	 * update product
	 *
	 * @since 1.0.0
	 */
	public function update_product( $request ) {
		return Products::update_single_product( $request );
	}

	/**
	 * update product
	 *
	 * @since 1.0.0
	 */
	public function delete_product( $request ) {
		return Products::delete_single_product( $request );
	}
}
