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
			'logout',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'logout' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'sendLoginCode',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'send_login_code' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'loginWithCode',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'login_with_code' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'GetSecretKey',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_secret_key' ),
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
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_product' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_product' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'companies',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_company' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_company' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_company' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'checkExtraUserData',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'check_extra_user_data' ),
					'permission_callback' => array($this , 'permission_callback'),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'extraUsers',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_extra_users' ),
					'permission_callback' => array( $this , 'permission_callback'),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_extra_users' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_extra_users' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'sendExtraUserCode',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'send_extra_user_code' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'customers',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_customer' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_customer' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_customer' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'licenses',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_licenses' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'bill',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_bill' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_bill' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'cancel_bill' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'bills',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_bills' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_total_bills' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'inquiry',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_inquiry' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);


		register_rest_route(
			'MoadianAbzar/v1',
			'settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				),
			)
		);

		register_rest_route(
			'MoadianAbzar/v1',
			'allData',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all_data' ),
					'permission_callback' => array( $this , 'permission_callback' ),
				)
			)
		);
	}

	/**
	 * permission callback.
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
	 * check userId
	 *
	 * @since 1.0.0
	 */
	public static function check_user_id($type = null) {
		$userId = get_current_user_id();

        if ($type == 'check' && !$userId) return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );
		if ($type == 'get') return $userId;

		return false;
	}

	/**
	 * Get user
	 *
	 * @since 1.0.0
	 */
	public static function get_user( $id ) {
		static::check_user_id( 'check' );
		return get_user_by( 'id', $id );
	}

	/**
	 * check MainUserId
	 *
	 * @since 1.0.0
	 */
	public static function check_main_user_id($userId = null) {
		$mainUser = get_user_meta( $userId, 'MAMainUser', true );
		return (isset($mainUser) && !empty($mainUser)) ? $mainUser : $userId;
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
	 * Authentication
	 *
	 * @since 1.0.0
	 */
	public static function logout( $request ) {
		return Login::logoutFromWP( $request );
	}

	/**
	 * send Login Code
	 *
	 * @since 1.0.0
	 */
	public static function send_login_code( $request ) {
		return Login::send_login_code( $request );
	}

	/**
	 * send Login Code
	 *
	 * @since 1.0.0
	 */
	public static function login_with_code( $request ) {
		return Login::login_with_code( $request );
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
	 * Get Company
	 *
	 * @since 1.0.0
	 */
	public static function get_company( $request ) {
		return Companies::get_company( $request );
	}

	/**
	 * update Company
	 *
	 * @since 1.0.0
	 */
	public static function update_company( $request ) {
		return Companies::update_company( $request );
	}

	/**
	 * Delete Company
	 *
	 * @since 1.0.0
	 */
	public static function delete_company( $request ) {
		return Companies::delete_company( $request );
	}

	/**
	 * Get Customer
	 *
	 * @since 1.0.0
	 */
	public static function get_customer( $request ) {
		return Customers::get_customer( $request );
	}

	/**
	 * update Customer
	 *
	 * @since 1.0.0
	 */
	public static function update_customer( $request ) {
		return Customers::update_customer( $request );
	}

	/**
	 * Delete Customer
	 *
	 * @since 1.0.0
	 */
	public static function delete_customer( $request ) {
		return Customers::delete_customer( $request );
	}

	/**
	 * check extra user data
	 *
	 * @since 1.0.0
	 */
	public function check_extra_user_data( $request ) {
		return Users::check_extra_user_info( $request );
	}

	/**
	 * Authentication
	 *
	 * @since 1.0.0
	 */
	public static function get_secret_key( $request ) {
		return Users::get_secret_key( $request );
	}

	/**
	 * check extra user data
	 *
	 * @since 1.0.0
	 */
	public function get_extra_users( $request ) {
		return Users::get_extraUsers( $request );
	}

	/**
	 * check extra user data
	 *
	 * @since 1.0.0
	 */
	public function update_extra_users( $request ) {
		return Users::update_extraUsers( $request );
	}

	/**
	 * check extra user data
	 *
	 * @since 1.0.0
	 */
	public function send_extra_user_code( $request ) {
		return Users::extraUserCode( $request );
	}

	/**
	 * delete extra user
	 *
	 * @since 1.0.0
	 */
	public static function delete_extra_users( $request ) {
		return Users::delete_single_extra_user( $request );
	}

	/**
	 * Get user licenses
	 *
	 * @since 1.0.0
	 */
	public static function get_licenses( $request ) {
		return Licenses::get_licenses( $request );
	}

	/**
	 * Get bill info from tax portal
	 *
	 * @since 1.0.0
	 */
	public static function get_bill( $request ) {
		return Bills::get_bill( $request );
	}

	/**
	 * send bill to tax portal
	 *
	 * @since 1.0.0
	 */
	public static function send_bill( $request ) {
		return Bills::send_bill( $request );
	}

	/**
	 * cancel bill
	 *
	 * @since 1.0.0
	 */
	public static function cancel_bill( $request ) {
		return Bills::cancel_bill( $request );
	}

	/**
	 * get bills
	 *
	 * @since 1.0.0
	 */
	public static function get_bills( $request ) {
		return Bills::get_bills( $request );
	}

	/**
	 * Get total bills
	 *
	 * @since 1.0.0
	 */
	public static function get_total_bills( $request ) {
		return Bills::get_total_bills( $request );
	}

	/**
	 * Get inquiry info from tax portal
	 *
	 * @since 1.0.0
	 */
	public static function get_inquiry( $request ) {
		return Bills::get_inquiry( $request );
	}

	/**
	 * get settings
	 *
	 * @since 1.0.0
	 */
	public static function get_settings( $request ) {
		return Settings::get_settings( $request );
	}

	/**
	 * update settings
	 *
	 * @since 1.0.0
	 */
	public static function update_settings( $request ) {
		return Settings::update_settings( $request );
	}

	/**
	 * get all data
	 *
	 * @since 1.0.0
	 */
	public static function get_all_data( $request ) {
		return General::get_all_data( $request );
	}
}
