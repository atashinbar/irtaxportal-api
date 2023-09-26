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
        $all_headers = $request->get_headers();
		$userId = get_current_user_id();

        if (!$userId) return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );

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
		$userId = get_current_user_id();

        if (!$userId) return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );

        global $wpdb;
        $tablename = $wpdb->prefix . "MA_products";
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", $userId), ARRAY_A);

        if (!is_array($row)) {
            $user_id     = $userId;
            $products[] = $params;
            $products    = json_encode($products);
            $sql = $wpdb->prepare("INSERT INTO `$tablename` (`user_id`, `products`) values (%d, %s)", $user_id, $products);
            $wpdb->query($sql);
        } else {
            foreach ($row as $key => $value) {
                if ($key === 'products'){
                    $value = json_decode($value);
                    $value[] = $params;
                    $newValue = json_encode($value);
                    $update = $wpdb->query($wpdb->prepare("UPDATE `$tablename` SET products='$newValue' WHERE user_id= %d", $userId));
                    // $value = $params;
                    if ( $update === 1 )
                    return static::create_response( 'با موفقیت اپدیت شد', 200 );
                    else 
                    return static::create_response( 'خطایی رخ داده است', 200 );
                }
            }
        }

		return static::create_response( 'با موفقیت اپدیت شد', 200 );
	}

	/**
	 * Delete product.
	 *
	 * @since 1.0.0
	 */
	public static function delete_single_product( $request ) {
        $params	= $request->get_params();
		$userId = get_current_user_id();
        return static::create_response('asd',200);
        if (!$userId) return static::create_response( 'شما مجوز لازم برای این کار را ندارید', 403 );

        global $wpdb;
        $tablename = $wpdb->prefix . "MA_products";
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", $userId), ARRAY_A);

        if (!is_array($row)) {
            $user_id     = $userId;
            $products[] = $params;
            $products    = json_encode($products);
            $sql = $wpdb->prepare("INSERT INTO `$tablename` (`user_id`, `products`) values (%d, %s)", $user_id, $products);
            $wpdb->query($sql);
        } else {
            foreach ($row as $key => $value) {
                if ($key === 'products'){
                    $value = json_decode($value);
                    $value[] = $params;
                    $newValue = json_encode($value);
                    $update = $wpdb->query($wpdb->prepare("UPDATE `$tablename` SET products='$newValue' WHERE user_id= %d", $userId));
                    // $value = $params;
                    if ( $update === 1 )
                    return static::create_response( 'با موفقیت اپدیت شد', 200 );
                    else 
                    return static::create_response( 'خطایی رخ داده است', 200 );
                }
            }
        }

		return static::create_response( 'با موفقیت اپدیت شد', 200 );
	}
}
