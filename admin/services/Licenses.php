<?php
/**
 * Licenses route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Licenses extends Registrerar {

	/**
	 * EDD API Keys Publick Key.
	 *
	 * @since  1.0.0
	 */
	public static $key = 'cca946cefc899e03c435ea3a1efa163a';

	/**
	 * EDD API Keys Token.
	 *
	 * @since  1.0.0
	 */
	public static $token = '3cc70411acbcf15044c6775a2b6c1edb';

	/**
	* Get licenses.
	*
	* @since 1.0.0
	*/
	public static function get_licenses( $request ) {
		$params			= $request->get_params();

		static::check_user_id('check');

		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );
		$params['email'] = sanitize_email( $params['email'] );

		$response = wp_remote_post( home_url( '/edd-api/sales/' ), array(
			'body'	=> [
				'key'	=> static::$key,
				'token'	=> static::$token,
				'email'	=> $params['email'],
			],
		) );

		return static::create_response($response , 403 );

		if ( $response['response']['code'] !== 200 ) {
			return static::create_response( 'خطایی رخ داده است', $response['response']['code'] );
		}

		$response = json_decode( $response['body'] );

		$licenses = array();

		if ( isset( $response->sales ) ) {
			foreach ( $response->sales as $key => $item ) {
				foreach ( $item->licenses as $license ) {
					$licenses[$license->key] = array(
						'name' => $license->name,
						'status' => $license->status,
						'key' => $license->key,
					);
				}
			}
		}

		if ( ! empty( $licenses ) ) {
			$response = json_encode( $licenses );
			return static::create_response( [ 'licenses' => $response ], 200 );
		}

		return static::create_response( ['licenses'=>[]], 200 );
	}
}
