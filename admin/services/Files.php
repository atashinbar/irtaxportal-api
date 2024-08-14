<?php
/**
 * Companies route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Files extends Registrerar {
	public static function get_file_bills ($request) {
		$params			= $request->get_params();

		if (!static::check_user_id('check')) return;
		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );
		$unique_key = isset($params['unique_key']) ? $params['unique_key'] : 'none';

		global $wpdb;
		$tablename	= $wpdb->prefix . General::$MA_files_bills;
		$row		= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE ref_unique_key = %d AND user_id = %d", array( $unique_key, $userId ) ), ARRAY_A );
		if ( is_array( $row ) ) {
			foreach ($row as $key => $value) {
				$value['key'] = $value['main_unique_key'];
				$row[$key] = $value;
			}
			return static::create_response( $row, 200 );
		}
		return static::create_response( [], 200 );
	}
}
