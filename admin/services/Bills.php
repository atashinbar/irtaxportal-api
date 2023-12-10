<?php
/**
 * Bills route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Bills extends Registrerar {

	// DB name
	public static $main_DB_name = 'MA_main_bill';
	public static $sandbox_DB_name = 'MA_sandbox_bill';

	// 1- ersale bill be portale maliat
	public static function send_bill($request) {
		$params	= $request->get_params();
		$formData = isset($params['data']) && is_array($params['data']) ? $params['data']: null;

		if (is_null($formData)) return static::create_response( 'فرم شما خالی است. لطفا فرم را پر کنید و مجدد ارسال نمایید', 403 );

		static::check_user_id('check');
		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );



		return $formData;
	}

	// 3- send function (gereftane parametr ha va jaygozin kardane unha va ersal be samane)
	public static function send_to_portal() {
		return true;
	}

	// 2- Insert bill to DB


	// 4- ebtale bill

	// 5- estelame vaziat

	// 6- eslahe bill
}
