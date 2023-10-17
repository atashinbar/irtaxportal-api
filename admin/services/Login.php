<?php
/**
 * Settings route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Login extends Registrerar {

	private static $updateSettings = false;

	/**
	 * logout
	 *
	 * @since 1.0.0
	 */
	public function logoutFromWP ( $request ){
		static::check_user_id('check');
		wp_logout();
		wp_redirect('https://moadianabzar.ir');
		exit;
	}

	/**
	 *
	 * @since 1.0.0
	 */
	public static function authentication( $request ) {
		$params	= $request->get_params();
		$url = home_url('/');

		$login_response = wp_remote_post( $url . '?rest_route=/auth/v1/auth', array(
			'body'    => [
				'email' => $params['username'],
				'password' => $params['password']
			],
		) );

		if ($login_response['response']['code'] != 200 ) return static::create_response( 'ورود شما ناموفق بود. لطفا نام کاربری و رمز عبور خود را  به صورت صحیح وارد کنید' , 403 );


		$response = json_decode($login_response['body']);
		$jwt = $response->data->jwt;

		$login_validate = wp_remote_post( $url . '?rest_route=/auth/v1/auth/validate', array(
			'body'    => [
				'JWT' => $jwt,
			],
		) );


		if ($login_validate['response']['code'] != 200 ) return static::create_response( 'نام کاربری یا رمز عبور شما اشتباه است' , 403 );

		$response = json_decode($login_validate['body']);
		$data = $response->data;
		$user = $data->user;
		$roles = $data->roles;
		$mainUser = get_user_meta( $user->ID, 'MAMainUser', true );
		$userInfo = [
			'token' => $jwt,
			'user' => [
				'user_email' => $user->user_email,
				'display_name' => $user->display_name,
				'roles' => $roles,
				'main_user' => $mainUser,
			]
		];

		return static::create_response( $userInfo, 200 );

	}

	/**
	 * Update plugin settings.
	 *
	 * @since 1.0.0
	 */
	public static function checkToken( $request ) {
		$params		= $request->get_params();
		$old_settings	= get_option( 'MoadianAbzar_admin' );

		if ( $old_settings === $params) return static::create_response( $old_settings, 200 );
		$update_status = update_option( 'MoadianAbzar_admin', $params  );
		$settings	= get_option( 'MoadianAbzar_admin' );

		if ( $update_status ) {
			return static::create_response( $settings, 200 );
		}

		return static::create_response( __( 'There is an unexpected error', 'moadian_abzar' ), 403 );
	}
}
