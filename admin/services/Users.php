<?php
/**
 * Settings route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Users extends Registrerar {

	/**
	 * Get products.
	 *
	 * @since 1.0.0
	 */
	public static function get_all_extra_users($request) {
        $params	= $request->get_params();
		$mainUserId = $params['mainUserId'];

		$userId = get_current_user_id();
        if (!$userId) return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );

        global $wpdb;
        $tablename = $wpdb->prefix . "MA_users";
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", $userId), ARRAY_A);

        if (!is_array($row)) {
            return [];
        }

		return static::create_response( $row, 200 );
	}

	/**
	 * check mobile.
	 *
	 * @since 1.0.0
	 */
	public static function check_extra_user_info( $request ) {
        $params	= $request->get_params();
		$type = sanitize_text_field($params['type']);
		$data = sanitize_text_field($params['data']);

		static::check_user_id();

		if ( $type == 'email') {
			$email = sanitize_email( $data );
			$exists = email_exists( $email );
			if ( $exists ) {
				return static::create_response( 'پست الکترونیک قبلا در سامانه ثبت شده است', 403 );
			}
		}

		if ( $type == 'mobile') {
			$username = sanitize_user( $data );
			$exists = username_exists( $username );
			if ( $exists ) {
				return static::create_response( 'شماره موبایل قبلا در سامانه ثبت شده است', 403 );
			}
		}

		return static::create_response( 'کاربر با موفقیت اضافه شد', 200 );
	}


}
