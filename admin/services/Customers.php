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
		$userId			= get_current_user_id();

		if ( ! $userId ) {
			return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );
		}

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_customers";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );


		if ( ! is_array( $row ) ) {
			$user_id		= $userId;
			$customers[]		= $params;
			$customers		= json_encode( $customers, JSON_UNESCAPED_UNICODE );
			$sql			= $wpdb->prepare("INSERT INTO `$tablename` ( `user_id`, `customers` ) values (%d, %s)", $user_id, $customers);
			$wpdb->query( $sql );

			$message = 'شرکت جدید با نام ';
			$message .= $params['name'] . ' ';
			$message .= ' و کداقتصادی یا شماره‌ملی ';
			$message .= $params['cod_eqtesadi'];
			$message .= ' اضافه شد.';
			return static::create_response( $message, 200 );

		} else {
			foreach ( $row as $key => $customers ) {

				if ( $key === 'customers' ) {

					$customers = json_decode( $customers, false, 512, JSON_UNESCAPED_UNICODE );

					foreach ( $customers as $key => $customer ) {

						if ( $customer->cod_meli === $params['cod_meli'] && $customer->cod_eqtesadi === $params['cod_eqtesadi'] ) {

							$message = 'در گذشته مشتری توسط شما با این مشخصات ثبت شده است نام شرکت ';
							$message .= $customer->name;
							$message .= ' می باشد.';
							return static::create_response( $message, 403 );

						} else if (
							$customer->cod_meli === $params['cod_meli'] &&
							$customer->cod_eqtesadi === $params['cod_eqtesadi'] &&
							$customer->fullname !== $params['fullname'] &&
							$customer->postal_code !== $params['postal_code'] &&
							$customer->customer_type !== $params['customer_type']
						) {

							$customer->fullname = $params['fullname'];
							$customer->postal_code = $params['postal_code'];
							$customer->customer_type = $params['customer_type'];

							break;

						} else if (
								$customer->cod_meli !== $params['cod_meli'] &&
								$customer->cod_eqtesadi !== $params['cod_eqtesadi'] &&
								$customer->fullname !== $params['fullname'] &&
								$customer->postal_code !== $params['postal_code'] &&
								$customer->customer_type !== $params['customer_type']
						) {
							$newValue	= json_encode( [$params], JSON_UNESCAPED_UNICODE );
							$newValue	= json_decode( $newValue, false, 512, JSON_UNESCAPED_UNICODE );

							break;
						}

					}

					if ( isset( $newValue ) ) {
						$newValue	= json_encode( array_merge( $newValue, $customers ), JSON_UNESCAPED_UNICODE );
					} else {
						$newValue	= json_encode( $customers, JSON_UNESCAPED_UNICODE );
					}

					$update		= $wpdb->query( $wpdb->prepare( "UPDATE `$tablename` SET customers='$newValue' WHERE user_id= %d", $userId ) );

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
	public static function delete_customer( $request ) {
		$params			= $request->get_params();
		$userId			= get_current_user_id();
		$cod_eqtesadi	= sanitize_text_field( $params['cod_eqtesadi'] );


		if ( ! $userId ) {
			return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );
		}

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_customers";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( is_array( $row ) ) {

			$data = json_decode( $row['customers'] );
			foreach ( $data as $key => $value ) {

				if ( $value->cod_eqtesadi === $cod_eqtesadi ) {

					unset( $data[$key] );

					$newData	= json_encode( array_values( $data ), JSON_UNESCAPED_UNICODE );
					$update		= $wpdb->query($wpdb->prepare("UPDATE `$tablename` SET customers='$newData' WHERE user_id= %d", $userId));

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
		$userId			= get_current_user_id();


		if ( ! $userId ) {
			return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );
		}

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_customers";
		$row		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );

		if ( ! is_array( $row ) ) {
           return static::create_response( ['customers'=>'{}'], 200 );
        }

		return static::create_response( $row, 200 );
	}
}
