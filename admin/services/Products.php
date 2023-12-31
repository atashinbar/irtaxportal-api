<?php
/**
 * Settings route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Products extends Registrerar {

	/**
	 * Get products.
	 *
	 * @since 1.0.0
	 */
	public static function get_all_products($request) {
        $params	= $request->get_params();

		static::check_user_id('check');
		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

        global $wpdb;
        $tablename = $wpdb->prefix . "MA_products";
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", $userId), ARRAY_A);

        if (!is_array($row)) {
            return [];
        }

		return static::create_response( $row, 200 );
	}

	/**
	 * Update product.
	 *
	 * @since 1.0.0
	 */
	public static function update_single_product( $request ) {
        $params	= $request->get_params();

		$params['name']		= sanitize_text_field( $params['name'] );
		$params['id']		= sanitize_text_field( $params['id'] );
		$params['taxRate']	= sanitize_text_field( $params['taxRate'] );

		$userId = get_current_user_id();

        if (!$userId) return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );

		$userId = static::check_main_user_id($userId);

        global $wpdb;
        $tablename = $wpdb->prefix . "MA_products";
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", $userId), ARRAY_A);

        if (!is_array($row)) {
            $user_id     = $userId;
            $products[] = $params;
            $products    = json_encode($products,JSON_UNESCAPED_UNICODE);
            $sql = $wpdb->prepare("INSERT INTO `$tablename` (`user_id`, `products`) values (%d, %s)", $user_id, $products);
            $wpdb->query($sql);
        } else {
            foreach ($row as $key => $value) {
                if ($key === 'products'){
                    $value = json_decode($value, false, 512, JSON_UNESCAPED_UNICODE);
					foreach ($value as $key => $valuee) {
						if ($valuee->id === $params['id']) return static::create_response( 'شناسه محصول قبلا توسط شما ثبت شده است. لطفا شناسه دیگری وارد نمایید', 403 );
					}
                    $value[] = $params;
                    $newValue = json_encode($value,JSON_UNESCAPED_UNICODE);
                    $update = $wpdb->query($wpdb->prepare("UPDATE `$tablename` SET products='$newValue' WHERE user_id= %d", $userId));
                    // $value = $params;
                    if ( $update === 1 )
                    return static::create_response( 'شناسه محصول اضافه شد', 200 );
                    else
                    return static::create_response( 'خطایی رخ داده است', 403 );
                }
            }
        }

		return static::create_response( 'شناسه محصول اضافه شد', 200 );
	}

	/**
	 * Delete product.
	 *
	 * @since 1.0.0
	 */
	public static function delete_single_product( $request ) {
        $params	= $request->get_params();
        $PId	= (int) sanitize_text_field( $params[0] );
		$userId	= get_current_user_id();
        if ( ! $userId ) return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );

		$userId = static::check_main_user_id($userId);

        global $wpdb;
        $tablename = $wpdb->prefix . "MA_products";
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", $userId), ARRAY_A);

        if (is_array($row)) {
            $data = json_decode($row['products']);
            foreach ($data as $key => $value) {
                if ((int)$value->id === $PId) {
                    unset($data[$key]);
                    $newData = json_encode(array_values($data),JSON_UNESCAPED_UNICODE);;
                    $update = $wpdb->query($wpdb->prepare("UPDATE `$tablename` SET products='$newData' WHERE user_id= %d", $userId));
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
