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
	public static $key = '1f170ade5b6f7271365f484c497108a9';

	/**
	 * EDD API Keys Token.
	 *
	 * @since  1.0.0
	 */
	public static $token = '978f0bee678b8798cbebcd4dbe6bc782';

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
		$params['type'] = sanitize_text_field( $params['type'] );

		$response = wp_remote_post( home_url( '/edd-api/sales/' ), array(
			'body'	=> [
				'key'	=> static::$key,
				'token'	=> static::$token,
				'email'	=> $params['email'],
			],
		) );

		if ( $response['response']['code'] !== 200 ) {
			return static::create_response( 'خطایی رخ داده است', $response['response']['code'] );
		}

		$response = json_decode( $response['body'] );

		$licenses = array();

		if ( isset( $response->sales ) ) {
			foreach ( $response->sales as $key => $item ) {
				foreach ( $item->licenses as $license ) {
					$response = wp_remote_post( home_url( '/' ), array(
						'body'	=> [
							'trusted'		=> 'true',
							'edd_action'	=> 'check_license',
							'item_id'		=> '636',
							'license'		=> $license->key,
						],
					) );

					$response = json_decode( $response['body'], JSON_UNESCAPED_UNICODE );
					if ( $params['type'] == 'list') {
						$licenses[] = array(
							'id'=>$item->ID,
							'date'=>$item->date,
							'expire' => str_replace('+00:00', 'Z', gmdate('c', strtotime($response['expires']))),
							'total'=>(int)$item->subtotal,
							'name' => $license->name,
							'status' => $response['activations_left'] > 0 ? 'قابل استفاده': 'سقف مجاز پر شده است',
							'key' => $license->key,
						);
					} else {
						$licenses[] = array(
							'name' => $license->name,
							'allowed' => $response['activations_left'] > 0 ? 1 : 0,
							'key' => $license->key,
						);
					}
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
