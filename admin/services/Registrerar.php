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
					'callback'            => array( __CLASS__, 'login' ),
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
					'callback'            => array( __CLASS__, 'get_products' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'update_product' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( __CLASS__, 'delete_product' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'settings',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'update_company' ),
					'permission_callback' => array( $this , 'permission_callback' ),
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
		$url = home_url('/');
		$headers = array_change_key_case($request->get_headers(), CASE_LOWER);
        $headerKey = strtolower('authorization');
		if (isset($headers[$headerKey])) {
			$matches = [];
			preg_match(
				'/^(?:Bearer)?[\s]*(.*)$/mi',
				$headers[$headerKey][0],
				$matches
			);

			if (isset($matches[1]) && !empty(trim($matches[1]))) {
				$login_validate = wp_remote_post( $url . '?rest_route=/auth/v1/auth/validate', array(
					'body'    => [
						'JWT' => $matches[1],
					],
				) );
				$body = json_decode($login_validate['body']);
				$success = $body->success;
				if (!$success) {
					return new \WP_Error(
						'rest_forbidden',
						esc_html__( 'دسترسی شما به این بخش محدود شده است', 'text-domain' ),
						array( 'status' => 203 )
					);
				}
				return true;
			}
		}
		return new \WP_Error(
			'rest_forbidden',
			esc_html__( 'دسترسی شما به این بخش محدود شده است', 'text-domain' ),
			array( 'status' => 203 )
		);
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
	public static function login( $request ) {
		return Login::authentication( $request );
	}

	/**
	 * get products
	 *
	 * @since 1.0.0
	 */
	public static function get_products( $request ) {
		return Products::get_all_products( $request );
	}

	/**
	 * update product
	 *
	 * @since 1.0.0
	 */
	public static function update_product( $request ) {
		return Products::update_single_product( $request );
	}

	/**
	 * update product
	 *
	 * @since 1.0.0
	 */
	public static function delete_product( $request ) {
		return Products::delete_single_product( $request );
	}

	/**
	 * Add Company
	 *
	 * @since 1.0.0
	 */
	public static function update_company( $request ) {
		return Settings::update_company( $request );
	}
}
