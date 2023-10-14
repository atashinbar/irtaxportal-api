<?php
/**
 * Settings route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Settings extends Registrerar {

	/**
	 * Add Company.
	 *
	 * @since 1.0.0
	 */
	public static function update_company( $request ) {
		$params			= $request->get_params();
		$userId			= get_current_user_id();

		if ( ! $userId ) {
			return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );
		}

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_settings";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );


		if ( ! is_array( $row ) ) {
			$user_id		= $userId;
			$settings[]		= $params;
			$settings		= json_encode( $settings, JSON_UNESCAPED_UNICODE );
			$sql			= $wpdb->prepare("INSERT INTO `$tablename` ( `user_id`, `settings` ) values (%d, %s)", $user_id, $settings);
			$wpdb->query( $sql );

			$message = 'شرکت جدید با نام ';
			$message .= $params['name'] . ' ';
			$message .= ' و کداقتصادی یا شماره‌ملی ';
			$message .= $params['cod_eqtesadi'];
			$message .= ' اضافه شد.';
			return static::create_response( $message, 200 );

		} else {
			foreach ( $row as $key => $settings ) {

				if ( $key === 'settings' ) {

					$settings = json_decode( $settings, false, 512, JSON_UNESCAPED_UNICODE );

					foreach ( $settings as $key => $setting ) {

						if ( $setting->cod_eqtesadi === $params['cod_eqtesadi'] && $setting->cod_yekta === $params['cod_yekta'] ) {

							$message = 'در گذشته شرکتی توسط شما با این مشخصات ثبت شده است نام شرکت ';
							$message .= $setting->name;
							$message .= ' می باشد.';
							return static::create_response( $message, 403 );

						} else if (
							$setting->cod_eqtesadi === $params['cod_eqtesadi'] &&
							$setting->cod_yekta !== $params['cod_yekta'] &&
							$setting->name !== $params['name'] &&
							$setting->license !== $params['license'] &&
							$setting->private_key !== $params['private_key']
						) {

							$setting->cod_yekta = $params['cod_yekta'];
							$setting->name = $params['name'];
							$setting->license = $params['license'];
							$setting->private_key = $params['private_key'];

							break;

						} else if (
								$setting->cod_eqtesadi !== $params['cod_eqtesadi'] &&
								$setting->cod_yekta !== $params['cod_yekta'] &&
								$setting->name !== $params['name'] &&
								$setting->license !== $params['license'] &&
								$setting->private_key !== $params['private_key']
						) {
							$newValue	= json_encode( [$params], JSON_UNESCAPED_UNICODE );
							$newValue	= json_decode( $newValue, false, 512, JSON_UNESCAPED_UNICODE );

							break;
						}

					}



					if ( isset( $newValue ) ) {
						$newValue	= json_encode( array_merge( $newValue, $settings ), JSON_UNESCAPED_UNICODE );
					} else {
						$newValue	= json_encode( $settings, JSON_UNESCAPED_UNICODE );
					}

					$update		= $wpdb->query( $wpdb->prepare( "UPDATE `$tablename` SET settings='$newValue' WHERE user_id= %d", $userId ) );

					if ( $update === 1 ) {
						$message = 'بروز رسانی با موفقیت انجام شد';
						return static::create_response( $message, 200 );
					} else {
						return static::create_response( 'خطایی رخ داده است', 403 );
					}

				}

			}
		}


		$message = 'خطایی رخ داده است';
		return static::create_response( $message, 200 );
	}

	/**
	* Delete product.
	*
	* @since 1.0.0
	*/
	public static function delete_company( $request ) {
		$params			= $request->get_params();
		$userId			= get_current_user_id();
		$cod_eqtesadi	= sanitize_text_field( $params['cod_eqtesadi'] );


		if ( ! $userId ) {
			return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );
		}

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_settings";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( is_array( $row ) ) {

			$data = json_decode( $row['settings'] );
			foreach ( $data as $key => $value ) {

				if ( $value->cod_eqtesadi === $cod_eqtesadi ) {

					unset( $data[$key] );

					$newData	= json_encode( array_values( $data ), JSON_UNESCAPED_UNICODE );
					$update		= $wpdb->query($wpdb->prepare("UPDATE `$tablename` SET settings='$newData' WHERE user_id= %d", $userId));

					if ( $update === 1 ) {
						return static::create_response( 'با موفقیت حذف شد', 200 );
					} else {
						return static::create_response( 'خطایی رخ داده است', 403 );
					}

				}
			}
		}
		return static::create_response( 'اطلاعات خواسته شده یافت نشد', 403 );
	}

	/**
	* Delete product.
	*
	* @since 1.0.0
	*/
	public static function get_company( $request ) {
		$params			= $request->get_params();
		$userId			= get_current_user_id();


		if ( ! $userId ) {
			return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );
		}

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_settings";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( ! is_array( $row ) ) {
           return static::create_response( ['settings'=>'{}'], 200 );
        }

		return static::create_response( $row, 200 );
	}
}
