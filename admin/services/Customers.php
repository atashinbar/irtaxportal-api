<?php
/**
 * Customers route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Customers extends Registrerar {

	/**
	 * Add Customer.
	 *
	 * @since 1.0.0
	 */
	public static function update_customer( $request ) {
		$params			= $request->get_params();
		static::check_user_id('check');

		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		$params['customer_type'] = sanitize_text_field( $params['customer_type'] );
		$params['fullname']		 = sanitize_text_field( $params['fullname'] );
		$params['cod_meli']		 = sanitize_text_field( $params['cod_meli'] );
		$params['postal_code']	 = sanitize_text_field( $params['postal_code'] );
		$params['cod_eqtesadi']	 = sanitize_text_field( $params['cod_eqtesadi'] );

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_customers";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );


		if ( ! is_array( $row ) ) {
			$user_id		= $userId;
			$customers[$params['cod_meli']]	= $params;
			$customers		= json_encode( $customers, JSON_UNESCAPED_UNICODE );
			$sql			= $wpdb->prepare("INSERT INTO `$tablename` ( `user_id`, `customers` ) values (%d, %s)", $user_id, $customers);
			$wpdb->query( $sql );

			$message = 'مشتری جدید با نام ';
			$message .= $params['fullname'] . ' ';
			$message .= ' و کداقتصادی یا شماره‌ملی ';
			$message .= $params['cod_meli'];
			$message .= ' اضافه شد.';
			return static::create_response( $message, 200 );

		} else {

			if ( isset( $row['customers'] ) ) {

				$customers = json_decode( $row['customers'], JSON_UNESCAPED_UNICODE );

				$customers[$params['cod_meli']] = $params;
				$customers	 = json_encode( $customers, JSON_UNESCAPED_UNICODE );
				$update		 = $wpdb->query( $wpdb->prepare( "UPDATE `$tablename` SET customers='$customers' WHERE user_id= %d", $userId ) );

				if ( $update === 1 ) {
					$message = $params['fullname'] . ' باکدملی ' . $params['cod_meli'] . ' با موفقیت اضافه شد';
					return static::create_response( $message, 200 );
				} else {
					return static::create_response( 'خطایی رخ داده است', 403 );
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
	public static function delete_customer( $request ) {
		$params			= $request->get_params();
		$cod_meli	= sanitize_text_field( $params['cod_meli'] );

		static::check_user_id('check');

		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_customers";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( is_array( $row ) ) {

			$data = json_decode( $row['customers'], JSON_UNESCAPED_UNICODE );

			foreach ( $data as $key => $value ) {
				if ( $key === $cod_meli ) {

					unset( $data[$key] );

					$newData	= json_encode( array_values( $data ), JSON_UNESCAPED_UNICODE );
					$update		= $wpdb->query( $wpdb->prepare( "UPDATE `$tablename` SET customers='$newData' WHERE user_id= %d", $userId ) );

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
	public static function get_customer( $request ) {
		$params			= $request->get_params();

		static::check_user_id('check');

		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_customers";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( ! is_array( $row ) ) {
           return static::create_response( ['customers'=>'{}'], 200 );
        }

		return static::create_response( $row, 200 );
	}
}
