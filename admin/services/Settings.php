<?php
/**
 * Settings route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Settings extends Registrerar {

	private static $main_DB_name = 'MA_settings';

	/**
	 * Get settings.
	 *
	 * @since 1.0.0
	 */
	public static function get_settings($request) {
        $params	= $request->get_params();

		static::check_user_id('check');

		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

        global $wpdb;
        $tablename = $wpdb->prefix . self::$main_DB_name;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", $userId), ARRAY_A);

        if (!is_array($row)) {
            return [];
        }

		return static::create_response( $row, 200 );
	}

	/**
	 * Update setting.
	 *
	 * @since 1.0.0
	 */
	public static function update_settings( $request ) {
        $params	= $request->get_params();

		static::check_user_id('check');

		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

        global $wpdb;
        $tablename = $wpdb->prefix . self::$main_DB_name;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", $userId), ARRAY_A);
        if (!is_array($row)) {
            $user_id     = $userId;
            $settings    = json_encode($params,JSON_UNESCAPED_UNICODE);
            $sql = $wpdb->prepare("INSERT INTO `$tablename` (`user_id`, `settings`) values (%d, %s)", $user_id, $settings);
            $update = $wpdb->query($sql);
        } else {
            $newValue = json_encode($params,JSON_UNESCAPED_UNICODE);
            $update = $wpdb->query($wpdb->prepare("UPDATE `$tablename` SET settings='$newValue' WHERE user_id= %d", $userId));
        }

		if ( $update === 1 )
        	return static::create_response( 'تنظیمات ذخیره شد', 200 );
        else
        	return static::create_response( 'برای ذخیره سازی باید حداقل یک گزینه را تغییر دهید', 403 );
	}
}
