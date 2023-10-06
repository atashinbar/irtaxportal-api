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
	public static function get_extraUsers($request) {
        $params	= $request->get_params();

		static::check_user_id('check');

        global $wpdb;
        $tablename = $wpdb->prefix . "MA_users";
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", static::check_user_id('get')), ARRAY_A);

        if (!is_array($row)) {
            return [];
        }

		foreach ($row as $key => $value) {
			if ($key == 'extra_users') {
				$userIds = json_decode($value);
			}
		}

		$users =[];
		if (is_array($userIds)) {
			foreach ($userIds as $key => $userId) {
				$user = get_user_by('ID', $userId);
				$userTemp['ID'] = $user->data->ID;
				$userTemp['display_name'] = $user->data->display_name;
				$userTemp['user_email'] = $user->data->user_email;
				$userTemp['user_login'] = $user->data->user_login;
				$userTemp['avatar'] = get_avatar_url($userTemp['user_email']);
				$users[] = $userTemp;
			}
		}

		return static::create_response( $users, 200 );
	}

	/**
	 * save/update extra user
	 *
	 * @since 1.0.0
	 */
	public static function update_extraUsers( $request ) {
        $params	= rest_sanitize_object($request->get_params());

		static::check_user_id('check');

		// user Data
		$first_name = sanitize_text_field($params['first_name']);
		$last_name = sanitize_text_field($params['last_name']);
		$national_code = sanitize_text_field($params['national_code']);
		$user_login = sanitize_user($params['user_login']);
		$user_email = sanitize_email($params['user_email']);
		$user_pass = sanitize_text_field($params['user_pass']);

		// Check email
		$error_message = [];
		$exists = email_exists( $user_email );
		if ( $exists ) {
			$error_message[] = 'پست الکترونیک قبلا در سامانه ثبت شده است';
		}

		// check username
		$exists = username_exists( $user_login );
		if ( $exists ) {
			$error_message[] = 'شماره موبایل قبلا در سامانه ثبت شده است';
		}

		$user_data = array(
			'first_name' => $first_name,
			'last_name' => $last_name,
			'user_login' => $user_login,
			'user_email' => $user_email,
			'user_pass' => $user_pass,
		);

		$extra_user_id = wp_insert_user( wp_slash( $user_data ) );

		if ( is_wp_error( $extra_user_id ) ) {
			return static::create_response( $error_message, 403 );
		}

        global $wpdb;
        $tablename = $wpdb->prefix . "MA_users";
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", static::check_user_id('get')), ARRAY_A);

        if (!is_array($row)) {
            $user_id = static::check_user_id('get');
            $extra_users[] = $extra_user_id;
            $extra_users    = json_encode($extra_users,JSON_UNESCAPED_UNICODE);
            $sql = $wpdb->prepare("INSERT INTO `$tablename` (`user_id`, `extra_users`) values (%d, %s)", $user_id, $extra_users);
            $result = $wpdb->query($sql);
			if ( $result === 1 ) {
				return static::create_response( 'کاربر با موفقیت اضافه شد', 200 );
			} else {
				$delete_user = wp_delete_user($extra_user_id);
				if ( !$delete_user ) {
					$error_message[] = 'خطایی رخ داده است. لطفا با پشتیبانی سامانه در ارتباط باشید';
				} else {
					$error_message[] = 'خطایی رخ داده است. کمی صبر کنید و مجدد تلاش کنید';
				}
			}
        } else {
            foreach ($row as $key => $value) {
                if ($key === 'extra_users'){
                    $value = json_decode($value, false, 512, JSON_UNESCAPED_UNICODE);
					foreach ($value as $key => $valuee) {
						if ($valuee === $extra_user_id) return static::create_response( 'کاربر قبلا توسط شما ثبت شده است. لطفا کاربر دیگری ثبت نمایید', 403 );
					}
                    $value[] = $extra_user_id;
                    $newValue = json_encode($value,JSON_UNESCAPED_UNICODE);
                    $update = $wpdb->query($wpdb->prepare("UPDATE `$tablename` SET extra_users='$newValue' WHERE user_id= %d", static::check_user_id('get')));
                    // $value = $params;
                    if ( $update === 1 ){
						return static::create_response( 'کاربر با موفقیت اضافه شد', 200 );
					} else {
						$delete_user = wp_delete_user($extra_user_id);
						if ( !$delete_user ) {
							$error_message[] = 'خطایی رخ داده است. لطفا با پشتیبانی سامانه در ارتباط باشید';
						} else {
							$error_message[] = 'خطایی رخ داده است. کمی صبر کنید و مجدد تلاش کنید';
						}
					}
                }
            }
        }

		return static::create_response( $error_message, 403 );
	}


	/**
	 * Delete extra user.
	 *
	 * @since 1.0.0
	 */
	public static function delete_single_extra_user( $request ) {
        $params	= $request->get_params();
        $extraUserId = $params[0];

		static::check_user_id('check');

        global $wpdb;
        $tablename = $wpdb->prefix . "MA_users";
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", static::check_user_id('get')), ARRAY_A);

        if (is_array($row)) {
            $data = json_decode($row['extra_users']);
            foreach ($data as $key => $value) {
                if ((int)$value === $extraUserId) {
                    unset($data[$key]);
                    $newData = json_encode(array_values($data),JSON_UNESCAPED_UNICODE);;
                    $update = $wpdb->query($wpdb->prepare("UPDATE `$tablename` SET extra_users='$newData' WHERE user_id= %d", static::check_user_id('get')));
                    if ( $update === 1 )
                    return static::create_response( 'با موفقیت اپدیت شد', 200 );
                    else
                    return static::create_response( 'خطایی رخ داده است', 403 );
                }
            }
        }
        return static::create_response( 'اطلاعات خواسته شده یافت نشد', 403 );
	}


}
