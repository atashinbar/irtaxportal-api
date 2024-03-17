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

		$login_response = wp_remote_post( $url . '?rest_route=/auth/v1/auth', array(
			'body'    => [
				'email' => sanitize_text_field( $params['username'] ),
				'password' => sanitize_text_field( $params['password'] )
			],
		) );

		if ($login_response['response']['code'] != 200 ) return static::create_response( 'ورود شما ناموفق بود. لطفا نام کاربری و رمز عبور خود را  به صورت صحیح وارد کنید' , 403 );

		$response = json_decode($login_response['body']);

		$userInfo = static::loginInfo($response);

		return static::create_response( $userInfo, 200 );

	}

	public static function loginInfo ($response) {
		$url = home_url('/');
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

		return $userInfo;
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

	/**
	 * Send login code
	 *
	 * @since 1.0.0
	 */
	public static function send_login_code( $request ) {
		$params	= $request->get_params();
		$mobile = sanitize_text_field($params['mobile']);

		$mobile_exist = username_exists( $mobile );

		if (!$mobile_exist) {
			$userdata = array(
				'user_login' =>  $mobile,
				'user_email' => $mobile . '@testexample.com',
				'user_pass'  =>  NULL // When creating an user, `user_pass` is expected.
			);
			$user_id = wp_insert_user( $userdata ) ;
		}

		$pin = General::generatePIN(6);
		$sendText = $pin;
		$code = General::sendCodeMelliPayamak( $mobile , '165925', $sendText );

		if ( $code->RetStatus === 1 ) {
			$time = floor(microtime(true) * 1000);
			$object = new \stdClass();
			$object->code = $pin;
			$object->time = $time;
			$get_user_by = get_user_by( 'login', sanitize_text_field( $mobile ) );

			$updated = update_user_meta( $get_user_by->ID, 'login_code', json_encode($object,JSON_UNESCAPED_UNICODE) );
			$data = array('mobile'=> $mobile, 'time' => $time);
			return static::create_response(json_encode($data), 200 );
		} else {
			return static::create_response('کد ارسال نشد. دقایقی دیگر مجدد تلاش کنید', 403 );
		}

		return false;
		// return get_user_meta( $get_user_by->ID, 'login_code', true);
	}

	public static function login_with_code( $request ) {
		$params	= $request->get_params();
		$mobile = sanitize_text_field($params['mobile']);
		$code = sanitize_text_field($params['code']);

		$get_user_by = get_user_by( 'login', sanitize_text_field( $mobile ) );
		$data = json_decode(get_user_meta( $get_user_by->ID, 'login_code', true));
		$time_now = floor(microtime(true) * 1000);
		$diff_milisecond = abs($time_now - $data->time);
		$diff_second = $diff_milisecond / 1000;

		if ( $diff_second > 300 ) {
			return static::create_response('کد شما منقضی شده است، لطفا مجدد کد را دریافت کنید', 403 );
		} else {
			if ( (int)$code === (int)$data->code ) {
				$get_user_by = get_user_by( 'login', sanitize_text_field( $mobile ) );

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

				$userInfo = static::loginInfo($response);
				return static::create_response($userInfo, 200 );
			} else {
				return static::create_response('کد وارد شده اشتباه است', 403 );
			}
		}
	}
}
