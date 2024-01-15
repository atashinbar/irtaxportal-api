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
	 * check mode
	 *
	 * @since 1.0.0
	 */
	public static function check_mode( $code_eghtesadi ) {
		global $wpdb;
		$tablename		= $wpdb->prefix . General::$MA_licenses;
		$code_eghtesadi	= esc_html( $code_eghtesadi );
		$row			= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE code_eghtesadi = %d", $code_eghtesadi ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			$mode = 1; //add new
		} else {
			$mode = 2; // edit
		}
		return $mode;
	}

	/**
	 * check mode
	 *
	 * @since 1.0.0
	 */
	public static function license_is_valid( $license ) {
		$response = wp_remote_post( home_url( '/' ), array(
			'body'	=> [
				'trusted'		=> 'true',
				'edd_action'	=> 'check_license',
				'item_id'		=> '636',
				'license'		=> esc_html( $license ),
			],
		) );

		$response = json_decode( $response['body'], JSON_UNESCAPED_UNICODE );
		return ($response['success'] && $response['activations_left'] > 0) ? true : false;
	}
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
		$params['company_id']	= isset( $params['company_id'] ) ? sanitize_text_field( $params['company_id'] ) : time();

		$mode = static::check_mode( $params['cod_eqtesadi'] );
		global $wpdb;

		if ( $mode === 1 ) { //add mode
			//check license is valid
			if ( ! static::license_is_valid( $params['license'] ) )
				return static::create_response( 'استفاده از این لایسنس مجاز نیست. لطفا لایسنس دیگری انتخاب کنید', 403 );

			$response = static::active_license( $params['license'], $params['cod_eqtesadi'] );
			if ( $response['success'] ) {

				$MA_companies_table	= $wpdb->prefix . General::$MA_companies;
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$MA_companies_table` WHERE user_id = %d", $userId ), ARRAY_A );

				if ( ! is_array( $row ) ) {
					$companies[$params['company_id']]	= $params;
					$companies		= json_encode( $companies, JSON_UNESCAPED_UNICODE );
					$add_to_MA_companies	 = $wpdb->query( $wpdb->prepare("INSERT INTO `$MA_companies_table` ( `user_id`, `companies` ) values (%d, %s)", $userId, $companies) );
				} else {
					$companies = json_decode( $row['companies'], JSON_UNESCAPED_UNICODE );
					$companies[$params['company_id']] = $params;
					$companies	 = json_encode( $companies, JSON_UNESCAPED_UNICODE );
					$add_to_MA_companies = $wpdb->query($wpdb->prepare("UPDATE `$MA_companies_table` SET companies='$companies' WHERE user_id= %d", $userId));
				}

				if ( $add_to_MA_companies === 1 ) {
					$MA_licenses_table	= $wpdb->prefix . General::$MA_licenses;
					$add_to_MA_licenses	= $wpdb->query(
						$wpdb->prepare(
							"INSERT INTO `$MA_licenses_table` ( `license`, `code_eghtesadi`, `price_id` ) values (%s, %s, %d)",
							$params['license'], $params['cod_eqtesadi'], ''
						)
					);

					if ( $add_to_MA_licenses === 1 ) {
						return static::create_response( 'فروشنده / شرکت با موفقیت افزوده شد', 200 );
					} else {
						static::deactive_license( $params['license'], $params['cod_eqtesadi'] );
						return static::create_response( 'خطایی رخ داده است', 403 );
					}
				} else {
					static::deactive_license( $params['license'], $params['cod_eqtesadi'] );
					return static::create_response( 'خطایی رخ داده است سیستم قادر به فعال سازی لایسنس نیست لطفا دقایقی بعد تلاش کنید. درصورت تداوم این مشکل لطفا از این خطا اسکرین شات گرفته و برای تیم پشتیبانی ارسال فرمایید', $response['response']['code'] );
				}
			}  elseif ( ! $response['success'] && isset( $response['error'] ) ) {
				$message = static::license_errors( $response['error'] );
				static::deactive_license( $params['license'], $params['cod_eqtesadi'] );
				return static::create_response( $message, 403 );
			}
		} else if ( $mode === 2 ) {
			$tablename	= $wpdb->prefix . General::$MA_licenses;
			$MA_licenses_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE code_eghtesadi = %d", $params['cod_eqtesadi'] ), ARRAY_A );
			if ( $MA_licenses_row['license'] != $params['license'] )
				return static::create_response( 'کد اقتصادی بر روی لایسنس دیگری فعال شده است. لطفا لایسنس درست را انتخاب کنید', 403 );

			$MA_companies_table	= $wpdb->prefix . General::$MA_companies;
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$MA_companies_table` WHERE user_id = %d", $userId ), ARRAY_A );

			if ( ! is_array( $row ) ) {
				$companies[$params['company_id']]	= $params;
				$companies		= json_encode( $companies, JSON_UNESCAPED_UNICODE );
				$update_MA_companies	 = $wpdb->query( $wpdb->prepare("INSERT INTO `$MA_companies_table` ( `user_id`, `companies` ) values (%d, %s)", $userId, $companies) );
			} else {
				$companies = json_decode( $row['companies'], JSON_UNESCAPED_UNICODE );
				$companies[$params['company_id']] = $params;
				$companies	 = json_encode( $companies, JSON_UNESCAPED_UNICODE );
				$update_MA_companies = $wpdb->query($wpdb->prepare("UPDATE `$MA_companies_table` SET companies='$companies' WHERE user_id= %d", $userId));
			}

			if ( $update_MA_companies === 1 ) {
				return static::create_response( 'فروشنده / شرکت با موفقیت بروزرسانی شد', 200 );
			} else {
				return static::create_response( 'خطایی رخ داده است', 403 );
			}
		}

		return static::create_response( 'خطایی رخ داده است', 403 );
	}

	/**
	* Active license.
	*
	* @since 1.0.0
	*/
	public static function active_license( $license, $cod_eqtesadi ) {
		$response = wp_remote_post( home_url( '/' ), array(
			'body'	=> [
				'trusted'		=> 'true',
				'edd_action'	=> 'activate_license',
				'item_id'		=> '636',
				'license'		=> esc_html( $license ),
				'url'			=> esc_html( $cod_eqtesadi ),
			],
		) );

		return json_decode( $response['body'], JSON_UNESCAPED_UNICODE );
	}

	/**
	* Deactive license.
	*
	* @since 1.0.0
	*/
	public static function deactive_license( $license, $cod_eqtesadi ) {
		$response = wp_remote_post( home_url( '/' ), array(
			'body'	=> [
				'trusted'		=> 'true',
				'edd_action'	=> 'deactivate_license',
				'item_id'		=> '636',
				'license'		=> esc_html( $license ),
				'url'			=> esc_html( $cod_eqtesadi ),
			],
		) );

		return json_decode( $response['body'], JSON_UNESCAPED_UNICODE );
	}

	/**
	* license errors.
	*
	* @since 1.0.0
	*/
	public static function license_errors( $error ) {
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
		$company_id	= (int) sanitize_text_field( $params['company_id'] );

		static::check_user_id('check');

		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		global $wpdb;
		$tablename	= $wpdb->prefix . General::$MA_companies;
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
		$tablename	= $wpdb->prefix . General::$MA_companies;
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( ! is_array( $row ) ) {
           return static::create_response( ['companies'=>'{}'], 200 );
        }

		return static::create_response( $row, 200 );
	}
}
