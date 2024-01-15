<?php
/**
 * Settings route functionality
 *
 * @since 1.0.0
 */
namespace MoadianAbzar\Admin\Services;
use Melipayamak\MelipayamakApi;

defined( 'ABSPATH' ) || exit;

class General extends Registrerar {

	public static $main_DB_name = 'MA_main_bill';
	public static $sandbox_DB_name = 'MA_sandbox_bill';
	public static $MA_customers = 'MA_customers';
	public static $MA_companies = 'MA_companies';
	public static $MA_licenses = 'MA_licenses';
	public static $MA_products = 'MA_products';
	public static $MA_settings = 'MA_settings';
	public static $MA_users = 'MA_users';
	public static $sendURL = 'https://taxportal.woobill.ir/';

	/**
	 * Send Code.
	 *
	 * @since 1.0.0
	 */
	// Send code from FarazSMS
	public static function sendCodeFarazSMS( $mobile, $pattern, $code ) {
		//FarazSMS
		$username = "09126183621";
        $password = "0493305378";
        $from = "+983000505";
        $pattern_code = $pattern;
        $to = array( $mobile );
        $input_data = array("code" => (int)$code);
        $url = "https://ippanel.com/patterns/pattern?username=" . $username . "&password=" . urlencode($password) . "&from=$from&to=" . json_encode($to) . "&input_data=" . urlencode(json_encode($input_data)) . "&pattern_code=$pattern_code";
        $handler = curl_init($url);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($handler, CURLOPT_POSTFIELDS, $input_data);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handler);
        return $response;
	}

	// Send code from mellipayak
	public static function sendCodeMelliPayamak($mobile,$pattern,$code) {
		$url = 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber';
		$data = array(
			'username'=>'9355012489',
		 	'password'=> '5f367c',
			'to' => $mobile,
			'bodyId'=> (int)$pattern,
			'text'=>$code
		);
		$data_string = json_encode($data);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

		// Next line makes the request absolute insecure
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		// Use it when you have trouble installing local issuer certificate
		// See https://stackoverflow.com/a/31830614/1743997

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER,
			array('Content-Type: application/json')
		);
		$result = curl_exec($ch);
		return json_decode($result);

	}

	// Generate PIN
	public static function generatePIN($digits = 4){
        $i = 0; //counter
        $pin = ""; //our default pin is blank.
        while($i < $digits){
            //generate a random number between 0 and 9.
            $pin .= mt_rand(0, 9);
            $i++;
        }
        return $pin;
    }

	// COnver FA number to EN number
	public static function convertFatoEn($string) {
		return strtr($string, array('۰'=>'0', '۱'=>'1', '۲'=>'2', '۳'=>'3', '۴'=>'4', '۵'=>'5', '۶'=>'6', '۷'=>'7', '۸'=>'8', '۹'=>'9', '٠'=>'0', '١'=>'1', '٢'=>'2', '٣'=>'3', '٤'=>'4', '٥'=>'5', '٦'=>'6', '٧'=>'7', '٨'=>'8', '٩'=>'9'));
	}

	// Generate UID code
	public static function generateUidv4($data = null) {
		$data = $data ?? random_bytes(16);
		assert(strlen($data) == 16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	// Get all date when app is loading
	public static function get_all_data($request) {
		$params	= $request->get_params();
		static::check_user_id('check');
		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		global $wpdb;
		$tablename	= $wpdb->prefix . "MA_customers";
		$customers		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );
		if ( ! is_array( $customers ) ) {
			$data['customers'] = ['customers'=>'{}'];
        } else {
			$data['customers'] = $customers;
		}

		$tablename	= $wpdb->prefix . "MA_companies";
		$companies		= $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$tablename` WHERE user_id = %d", $userId ), ARRAY_A );
		if ( ! is_array( $companies ) ) {
			$data['companies'] = ['companies'=>'{}'];
        } else {
			$data['companies'] = $companies;
		}

        $tablename = $wpdb->prefix . "MA_products";
        $products = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", $userId), ARRAY_A);

        if ( ! is_array( $products ) ) {
			$data['products'] = [];
        } else {
			$data['products'] = $products;
		}

		$tablename = $wpdb->prefix . "MA_settings";
        $settings = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$tablename` WHERE user_id = %d", $userId), ARRAY_A);

        if (!is_array($settings)) {
			$data['settings'] = [];
        } else {
			$data['settings'] = $settings;
		}

		$db			= sanitize_text_field( $params['database'] );
		$db 		= $db === 'sandbox' ? self::$sandbox_DB_name : self::$main_DB_name ;
		$tablename	= $wpdb->prefix . $db;
		$total		= $wpdb->get_var( "SELECT COUNT(*) FROM `$tablename` WHERE main_user_id = $userId" );
		if ( $total ) {
			$data['totalBills'] = $total;
		}

		return static::create_response( $data, 200 );

	}

	// JSON Validator
	public static function json_validate($string){
		// decode the JSON data
		$result = json_decode($string);

		// switch and check possible JSON errors
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$error = ''; // JSON is valid // No error has occurred
				break;
			case JSON_ERROR_DEPTH:
				$error = 'The maximum stack depth has been exceeded.';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$error = 'Invalid or malformed JSON.';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$error = 'Control character error, possibly incorrectly encoded.';
				break;
			case JSON_ERROR_SYNTAX:
				$error = 'Syntax error, malformed JSON.';
				break;
			// PHP >= 5.3.3
			case JSON_ERROR_UTF8:
				$error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
				break;
			// PHP >= 5.5.0
			case JSON_ERROR_RECURSION:
				$error = 'One or more recursive references in the value to be encoded.';
				break;
			// PHP >= 5.5.0
			case JSON_ERROR_INF_OR_NAN:
				$error = 'One or more NAN or INF values in the value to be encoded.';
				break;
			case JSON_ERROR_UNSUPPORTED_TYPE:
				$error = 'A value of a type that cannot be encoded was given.';
				break;
			default:
				$error = 'Unknown JSON error occured.';
				break;
		}

		if ($error !== '') {
			// throw the Exception or exit // or whatever :)
			exit($error);
		}

		// everything is OK
		return true;
	}

}
