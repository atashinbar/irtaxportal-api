<?php
/**
 * Companies route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Companies extends Registrerar {

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
		$tablename	= $wpdb->prefix . "MA_companies";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );


		if ( ! is_array( $row ) ) {
			$user_id		= $userId;
			$companies[]		= $params;
			$companies		= json_encode( $companies, JSON_UNESCAPED_UNICODE );
			$sql			= $wpdb->prepare("INSERT INTO `$tablename` ( `user_id`, `companies` ) values (%d, %s)", $user_id, $companies);
			$wpdb->query( $sql );

			$message = 'شرکت جدید با نام ';
			$message .= $params['name'] . ' ';
			$message .= ' و کداقتصادی یا شماره‌ملی ';
			$message .= $params['cod_eqtesadi'];
			$message .= ' اضافه شد.';
			return static::create_response( $message, 200 );

		} else {
			foreach ( $row as $key => $companies ) {

				if ( $key === 'companies' ) {

					$companies = json_decode( $companies, false, 512, JSON_UNESCAPED_UNICODE );

					foreach ( $companies as $key => $company ) {

						if ( $company->cod_eqtesadi === $params['cod_eqtesadi'] && $company->cod_yekta === $params['cod_yekta'] ) {

							$message = 'در گذشته شرکتی توسط شما با این مشخصات ثبت شده است نام شرکت ';
							$message .= $company->name;
							$message .= ' می باشد.';
							return static::create_response( $message, 403 );

						} else if (
							$company->cod_eqtesadi === $params['cod_eqtesadi'] &&
							$company->cod_yekta !== $params['cod_yekta'] &&
							$company->name !== $params['name'] &&
							$company->license !== $params['license'] &&
							$company->private_key !== $params['private_key']
						) {

							$company->cod_yekta = $params['cod_yekta'];
							$company->name = $params['name'];
							$company->license = $params['license'];
							$company->private_key = $params['private_key'];

							break;

						} else if (
								$company->cod_eqtesadi !== $params['cod_eqtesadi'] &&
								$company->cod_yekta !== $params['cod_yekta'] &&
								$company->name !== $params['name'] &&
								$company->license !== $params['license'] &&
								$company->private_key !== $params['private_key']
						) {
							$newValue	= json_encode( [$params], JSON_UNESCAPED_UNICODE );
							$newValue	= json_decode( $newValue, false, 512, JSON_UNESCAPED_UNICODE );

							break;
						}

					}

					if ( isset( $newValue ) ) {
						$newValue	= json_encode( array_merge( $newValue, $companies ), JSON_UNESCAPED_UNICODE );
					} else {
						$newValue	= json_encode( $companies, JSON_UNESCAPED_UNICODE );
					}

					$update		= $wpdb->query( $wpdb->prepare( "UPDATE `$tablename` SET companies='$newValue' WHERE user_id= %d", $userId ) );

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
		$tablename	= $wpdb->prefix . "MA_companies";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( is_array( $row ) ) {

			$data = json_decode( $row['companies'] );
			foreach ( $data as $key => $value ) {

				if ( $value->cod_eqtesadi === $cod_eqtesadi ) {

					unset( $data[$key] );

					$newData	= json_encode( array_values( $data ), JSON_UNESCAPED_UNICODE );
					$update		= $wpdb->query($wpdb->prepare("UPDATE `$tablename` SET companies='$newData' WHERE user_id= %d", $userId));

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
		$tablename	= $wpdb->prefix . "MA_companies";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( ! is_array( $row ) ) {
           return static::create_response( ['companies'=>'{}'], 200 );
        }

		return static::create_response( $row, 200 );
	}
}
