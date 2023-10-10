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

	/**
	 * Send Code.
	 *
	 * @since 1.0.0
	 */
	public static function sendCodeFarazSMS($mobile,$pattern,$code) {
		//FarazSMS
		$username = "09126183621";
        $password = "0493305378";
        $from = "+983000505";
        $pattern_code = $pattern;
        $to = array($mobile);
        $input_data = array("code" => (int)$code);
        $url = "https://ippanel.com/patterns/pattern?username=" . $username . "&password=" . urlencode($password) . "&from=$from&to=" . json_encode($to) . "&input_data=" . urlencode(json_encode($input_data)) . "&pattern_code=$pattern_code";
        $handler = curl_init($url);
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($handler, CURLOPT_POSTFIELDS, $input_data);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handler);
        return $response;
	}
	public static function sendCodeMelliPayamak($mobile,$pattern,$code) {
		//MelliPayamak
		// $username = "9355012489";
        // $password = "5f367c";
        // $from = "+983000505";
        // $pattern_code = (int)$pattern;
        // $to = $mobile;
        // $input_data = array($code);
        // $url = "http://api.payamak-panel.com/post/Send.asmx?wsdl" . $username . "&password=" . urlencode($password) . "&from=$from&to=" . $to . "&input_data=" . urlencode(json_encode($input_data)) . "&bodyId=$pattern_code";
        // $handler = curl_init($url);
        // curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
        // curl_setopt($handler, CURLOPT_POSTFIELDS, $input_data);
        // curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        // $response = curl_exec($handler);
        // return $response;


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

}
