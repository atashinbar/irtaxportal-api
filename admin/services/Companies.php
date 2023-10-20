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
	 * Add/Update Company.
	 *
	 * @since 1.0.0
	 */
	public static function update_company( $request ) {
		$params			= $request->get_params();
		static::check_user_id('check');

		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		$params['cod_eqtesadi']	= sanitize_text_field( $params['cod_eqtesadi'] );
		$params['cod_yekta']	= sanitize_text_field( $params['cod_yekta'] );
		$params['name']			= sanitize_text_field( $params['name'] );
		$params['license']		= sanitize_text_field( $params['license'] );
		$params['private_key']	= sanitize_text_field( $params['private_key'] );
		$params['company_id']	= isset( $params['company_id'] ) ? $params['company_id'] : time();

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_companies";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( ! is_array( $row ) ) {
			$user_id		= $userId;
			$companies[$params['company_id']]	= $params;
			$companies		= json_encode( $companies, JSON_UNESCAPED_UNICODE );

			$response = wp_remote_post( home_url( '/' ), array(
				'body'	=> [
					'edd_action'	=> 'activate_license',
					'item_id'		=> '636',
					'license'		=> $params['license'],
					'url'			=> $params['cod_eqtesadi'],
				],
			) );

			$response = json_decode( $response['body'], JSON_UNESCAPED_UNICODE );

			if ( $response['success'] ) {

				$update		 = $wpdb->query( $wpdb->prepare("INSERT INTO `$tablename` ( `user_id`, `companies` ) values (%d, %s)", $user_id, $companies) );
				if ( $update === 1 ) {

					$edd_licenses	= $wpdb->prefix . "edd_licenses";
					$get_license	= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$edd_licenses` WHERE license_key = %d", $params['license'] ), ARRAY_A );
					if ( is_array( $get_license ) ) {

						$MA_licenses	= $wpdb->prefix . "MA_licenses";
						$add_license	= $wpdb->query(
							$wpdb->prepare(
								"INSERT INTO `$MA_licenses` ( `license`, `code_eghtesadi`, `price_id` ) values (%s, %s, %d)",
								$get_license['license_key'], $params['cod_eqtesadi'], $get_license['price_id']
							)
						);
						if ( $add_license === 1 ) {
							return static::create_response( 'بروزرسانی باموفقیت انجام شد', 200 );
						} else {
							return static::create_response( 'در هنگام ثبت لایسنس خطایی رخ داده است با پشتیبانی تماس بگیرید', 403 );
						}
					}

					return static::create_response( 'لایسنس یافت نشد', 404 );

				} else {
					return static::create_response( 'خطایی رخ داده است', 403 );
				}

			} else if ( ! $response['success'] && isset( $response['error'] ) ) {
				$message = static::edd_error( $response['error'] );
				return static::create_response( $message, 403 );
			} else {
				return static::create_response( 'خطایی رخ داده است سیستم قادر به فعال سازی لایسنس نیست لطفا دقایقی بعد تلاش کنید. درصورت تداوم این مشکل لطفا از این خطا اسکرین شات گرفته و برای تیم پشتیبانی ارسال فرمایید', $response['response']['code'] );
			}

		} else {

			if ( isset( $row['companies'] ) ) {

				$companies = json_decode( $row['companies'], JSON_UNESCAPED_UNICODE );

				$companies[$params['company_id']] = $params;
				$companies	 = json_encode( $companies, JSON_UNESCAPED_UNICODE );

				$response = wp_remote_post( home_url( '/' ), array(
					'body'	=> [
						'edd_action'	=> 'activate_license',
						'item_id'		=> '636',
						'license'		=> $params['license'],
						'url'			=> $params['cod_eqtesadi'],
					],
				) );
				$response = json_decode( $response['body'], JSON_UNESCAPED_UNICODE );

				if ( $response['success'] ) {
					$update		 = $wpdb->query( $wpdb->prepare( "UPDATE `$tablename` SET companies='$companies' WHERE user_id= %d", $userId ) );
					if ( $update === 1 ) {

						$edd_licenses	= $wpdb->prefix . "edd_licenses";
						$get_license	= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$edd_licenses` WHERE license_key = %d", $params['license'] ), ARRAY_A );
						if ( is_array( $get_license ) ) {

							$MA_licenses	= $wpdb->prefix . "MA_licenses";
							$add_license	= $wpdb->query(
								$wpdb->prepare(
									"INSERT INTO `$MA_licenses` ( `license`, `code_eghtesadi`, `price_id` ) values (%s, %s, %d)",
									$get_license['license_key'], $params['cod_eqtesadi'], $get_license['price_id']
								)
							);
							if ( $add_license === 1 ) {
								return static::create_response( 'بروزرسانی باموفقیت انجام شد', 200 );
							} else {
								return static::create_response( 'در هنگام ثبت لایسنس خطایی رخ داده است با پشتیبانی تماس بگیرید', 403 );
							}
						}

						return static::create_response( 'لایسنس یافت نشد', 404 );

					} else {
						return static::create_response( 'خطایی رخ داده است', 403 );
					}
				} else if ( ! $response['success'] && isset( $response['error'] ) ) {
					$message = static::edd_error( $response['error'] );
					return static::create_response( $message, 403 );
				} else {
					return static::create_response( 'خطایی رخ داده است سیستم قادر به فعال سازی لایسنس نیست لطفا دقایقی بعد تلاش کنید. درصورت تداوم این مشکل لطفا از این خطا اسکرین شات گرفته و برای تیم پشتیبانی ارسال فرمایید', $response['response']['code'] );
				}

			}
		}

		return static::create_response( 'خطایی رخ داده است', 403 );
	}

	/**
	* EDD errors.
	*
	* @since 1.0.0
	*/
	public static function edd_error( $error ) {
		switch ( $error ) {
			case 'missing':
				$message = 'لایسنس وجود ندارد';
			break;
			case 'missing_url':
				$message = 'کداقتصادی ارائه نشده است';
			break;
			case 'disabled':
				$message = 'لایسنس غیرفعال است';
			break;
			case 'no_activations_left':
				$message = 'تعداد مجاز استفاده از این لایسنس به حدنصاب خود رسیده است لطفا جهت خرید لایسنس جدید اقدام نمایید';
			break;
			case 'expired':
				$message = 'لایسنس منقضی شده است ، لطفا نسبت به تمدید آن اقدام نمایید';
			break;
			case 'item_name_mismatch':
			case 'key_mismatch':
				$message = 'لایسنس برای این محصول معتبر نیست';
			break;
			case 'invalid_item_id':
				$message = 'شناسه نامعتبر است';
			break;
			case 'site_inactive':
				$message = 'کداقتصادی برای این لایسنس فعال نیست';
			break;
			case 'invalid':
				$message = 'کلید لایسنس مطابقت ندارد';
			break;
		}

		return $message;
	}

	/**
	* Delete Company.
	*
	* @since 1.0.0
	*/
	public static function delete_company( $request ) {
		$params			= $request->get_params();
		$company_id	= (int)sanitize_text_field( $params['company_id'] );

		static::check_user_id('check');

		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_companies";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( is_array( $row ) ) {

			$data = json_decode( $row['companies'], JSON_UNESCAPED_UNICODE );

			foreach ( $data as $key => $value ) {

				if ( $key === $company_id ) {

					unset( $data[$company_id] );

					if ( empty( $data ) ) {
						$update = $wpdb->query($wpdb->prepare("DELETE FROM `$tablename` WHERE user_id= %d", static::check_user_id( 'get' ) ) );
					} else {
						$newData	= json_encode( $data, JSON_UNESCAPED_UNICODE );
						$update		= $wpdb->query( $wpdb->prepare( "UPDATE `$tablename` SET companies='$newData' WHERE user_id= %d", $userId ) );
					}

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
	* Delete Company.
	*
	* @since 1.0.0
	*/
	public static function get_company( $request ) {
		$params			= $request->get_params();

		static::check_user_id('check');

		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_companies";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( ! is_array( $row ) ) {
           return static::create_response( ['companies'=>'{}'], 200 );
        }

		return static::create_response( $row, 200 );
	}
}
