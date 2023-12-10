<?php
/**
 * Settings route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;
use SimpleJWTLogin\Libraries\JWT\JWT;
use SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;

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
		wp_redirect(home_url('/'));
		exit;
	}

	/**
	 *
	 * @since 1.0.0
	 */
	public static function authentication( $request ) {
		$params	= $request->get_params();
		$url = home_url('/');

		// Login by Mobile number
		if ( is_numeric( $params['username'] ) ) {

			$get_user_by = get_user_by( 'login', sanitize_text_field( $params['username'] ) );

			if ( ! $get_user_by ) {
				return static::create_response( 'با این شماره موبایل اکانتی در سایت ثبت نشده است' , 403 );
			}

			$payload = array(
				'iat'       => time(),
				'email'     => sanitize_text_field( $get_user_by->user_email ),
				'id'        => sanitize_text_field( $get_user_by->ID ),
				'username'  => sanitize_text_field( $get_user_by->user_login ),
			);

			$jwtSettings = new SimpleJWTLoginSettings(new WordPressData());

			$response = [
				'success' => true,
				'data' => [
					'jwt' => JWT::encode(
						$payload,
						JwtKeyFactory::getFactory($jwtSettings)->getPrivateKey(),
						$jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
					)
				]
			];

		} else {

			$login_response = wp_remote_post( $url . '?rest_route=/auth/v1/auth', array(
				'body'    => [
					'email' => sanitize_text_field( $params['username'] ),
					'password' => sanitize_text_field( $params['password'] )
				],
			) );

			if ($login_response['response']['code'] != 200 ) return static::create_response( 'ورود شما ناموفق بود. لطفا نام کاربری و رمز عبور خود را  به صورت صحیح وارد کنید' , 403 );

			$response = json_decode($login_response['body']);
		}

		if ( isset( $response->data->jwt ) ) {
			$jwt = $response->data->jwt;
		} else {
			$jwt = $response['data']['jwt'];
		}

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
				'user_mobile' => $user->user_login,
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
