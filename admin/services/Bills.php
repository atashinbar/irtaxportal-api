<?php
/**
 * Bills route functionality
 *
 * @since 1.0.0
 */

namespace MoadianAbzar\Admin\Services;

defined( 'ABSPATH' ) || exit;

class Bills extends Registrerar {

	// DB name
	private static $main_DB_name = 'MA_main_bill';
	private static $sandbox_DB_name = 'MA_sandbox_bill';
	private static $sendURL = 'https://taxportal.woobill.ir/';

	// 1- ersale bill be portale maliat
	public static function send_bill($request) {
		$params	= $request->get_params();
		$formData = isset($params['data']) && is_array($params['data']) ? $params['data']: null;
		$publishStatus = isset($formData['publishStatus']) ? $formData['publishStatus'] : null;

		// Check if formData is null
		if (is_null($formData)) return static::create_response( 'فرم شما خالی است. لطفا فرم را پر کنید و مجدد ارسال نمایید', 403 );

		// Check User id
		static::check_user_id('check');
		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		// Send and Get result from samaneye maliat
		$response = '';
		if ($publishStatus === 'published') {
			$response = static::send_to_portal($formData , 'send');
			// $body = isset($response['body']) ? json_decode($response['body']) : null;
		}

		// $final_response = '';
		// if ( (isset($body) && !is_null($body) && isset($body->success) && $body->success) || $publishStatus === 'draft' ) {
		// 	$final_response = static::save_on_DB($formData, $response,$userId);
		// }

		return static::create_response( $response, 200 );
	}

	// 3- send function (gereftane parametr ha va jaygozin kardane unha va ersal be samaneye maliat)
	public static function send_to_portal($formData, $command) {

		$companyInfo = isset($formData['company']) ? $formData['company'] : null;
		$customerInfo = isset($formData['customer']) ? $formData['customer'] : null;
		$formInfo = isset($formData['formData']) ? $formData['formData'] : null;

		// Generate Parameters
		$indatim = isset($formInfo['indatim']) ? (int)(esc_html( $formInfo['indatim'] )) : null;
		$Indati2m = isset($formInfo['Indati2m']) ? (int)(esc_html( $formInfo['Indati2m'] )) : null;
		$inty = isset($formInfo['inty']) ? (int)(esc_html( $formInfo['inty'] )) : null;
		$inp = isset($formInfo['inp']) ? (int)(esc_html( $formInfo['inp'] )) : null;
		$tins = isset($companyInfo['codeEghtesadi']) ? esc_html( General::convertFatoEn($companyInfo['codeEghtesadi']) ) : null;
		$tprdis = isset($formInfo['tprdis']) ? (int)(esc_html( str_replace(',', '', $formInfo['tprdis']) )) : null;
		$tadis = isset($formInfo['tadis']) ? (int)(esc_html( str_replace(',', '', $formInfo['tadis']) )) : null;
		$tvam = isset($formInfo['tvam']) ? (int)(esc_html( str_replace(',', '', $formInfo['tvam']) )) : null;
		$todam = isset($formInfo['todam']) ? (int)(esc_html( str_replace(',', '', $formInfo['todam']) )) : null;
		$tbill = isset($formInfo['tbill']) ? (int)(esc_html( str_replace(',', '', $formInfo['tbill']) )) : null;
		$inno = isset($formInfo['inno']) ? esc_html( $formInfo['inno'] ) : null;
		$tob = isset($customerInfo['customer_type']) ? (int)(esc_html( $customerInfo['customer_type'] )) : null;
		$bid = isset($customerInfo['cod_meli']) ? esc_html( General::convertFatoEn($customerInfo['cod_meli']) ) : null;
		$tinb = isset($customerInfo['cod_eqtesadi']) ? esc_html( General::convertFatoEn($customerInfo['cod_eqtesadi']) ) : null;
		$bpc = isset($customerInfo['postal_code']) ? esc_html( General::convertFatoEn($customerInfo['postal_code']) ) : null;
		$bbc = isset($formInfo['bbc']) ? esc_html( General::convertFatoEn($formInfo['bbc']) ) : null;
		$sbc = isset($formInfo['sbc']) ? esc_html( General::convertFatoEn($formInfo['sbc']) ) : null;
		$ft = isset($formInfo['ft']) ? (int)(esc_html( $formInfo['ft'] )) : null;
		$bpn = isset($formInfo['bpn']) ? esc_html( General::convertFatoEn($formInfo['bpn']) ) : null;
		$scln = isset($formInfo['scln']) ? esc_html( General::convertFatoEn($formInfo['scln']) ) : null;
		$scc = isset($formInfo['scc']) ? esc_html( General::convertFatoEn($formInfo['scc']) ) : null;
		$cdcn = isset($formInfo['cdcn']) ? esc_html( General::convertFatoEn($formInfo['cdcn']) ) : null;
		$cdcd = isset($formInfo['cdcd']) ? (int)(esc_html( $formInfo['cdcd'] )) : null;
		$crn = isset($formInfo['crn']) ? esc_html( General::convertFatoEn($formInfo['crn']) ) : null;
		$billid = isset($formInfo['billid']) ? esc_html( General::convertFatoEn($formInfo['billid']) ) : null;
		$tdis = isset($formInfo['tdis']) ? (int)(esc_html( str_replace(',', '', $formInfo['tdis']) )) : 0;
		$tonw = isset($formInfo['tonw']) ? (int)(esc_html( $formInfo['tonw'] )) : null;
		$torv = isset($formInfo['torv']) ? (int)(esc_html( str_replace(',', '', $formInfo['torv']) )) : null;
		$tocv = isset($formInfo['tocv']) ? (int)(esc_html( str_replace(',', '', $formInfo['tocv']) )) : null;
		$setm = isset($formInfo['setm']) ? (int)(esc_html( $formInfo['setm'] )) : null;
		$cap = isset($formInfo['cap']) ? (int)(esc_html( str_replace(',', '', $formInfo['cap']) )) : 0;
		$insp = isset($formInfo['insp']) ? (int)(esc_html( str_replace(',', '', $formInfo['insp']) )) : 0;
		$tvop = isset($formInfo['tvop']) ? (int)(esc_html( str_replace(',', '', $formInfo['tvop']) )) : 0;
		$tax17 = isset($formInfo['tax17']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($formInfo['tax17'])) )) : null;

		// Header
		$header = array();
		$header['taxid'] =  ""; // Generate Automatically via system
		$header['indatim'] =  $indatim; //تاریخ و زمان صدور صورتحساب (میلادی)
		$header['Indati2m'] =  $Indati2m; //تاریخ و زمان ایجاد صورتحساب (میلادی)
		$header['inty'] =  $inty; //نوع صورتحساب
		$header['inp'] =  $inp; //الگوی صورتحساب
		$header['ins'] =  1; //موضوع صورتحساب
		$header['tins'] =  $tins; //شماره اقتصادی فروشنده
		$header['sbc'] = $sbc; //کد شعبه فروشنده
		$header['tprdis'] = $tprdis; //مجموع مبلغ قبل از کسر تخفیف
		$header['tadis'] = $tadis; //مجموع مبلغ پس از کسر تخفیف
		$header['tvam'] = $tvam; //مجموع مالیات بر ارزش افزوده
		$header['todam'] = $todam; //مجموع سایر مالیات، عوارض و وجوه قانونی
		$header['tbill'] = $tbill; //مجموع صورتحساب
		$header['inno'] =  $inno; //سریال صورتحساب داخلی حافظه مالیاتی / شماره فاکتور
		// $header['irtaxid'] =  ""; //شماره منحصر به فرد مالیاتی صورتحساب مرجع - برای کنسل کردن یا ویرایش صورتحساب باید فعال شود
		$header['tob'] =  $tob; //نوع شخص خریدار
		$header['bid'] = $bid; //شناسه ملی/ شماره ملی/ شناسه مشارکت مدنی/ کد فراگیر اتباع غیرایرانی خریدار
		$header['tinb'] = $tinb; //شماره اقتصادی خریدار
		$header['bpc'] = $bpc; //کد پستی خریدار
		$header['bbc'] = $bbc; //کد شعبه خریدار
		$header['bpn'] = $bpn; //شماره گذرنامه خریدار
		$header['ft'] = $ft; //نوع پرواز
		$header['scln'] = $scln; //شماره پروانه گمرکی
		$header['scc'] = $scc; //کد گمرک محل اظهار فروشنده
		$header['cdcn'] = $cdcn; //شماره کوتاژ اظهارنامه گمرکی
		$header['cdcd'] = $cdcd; //تاریخ کوتاژ اظهارنامه گمرکی
		$header['crn'] = $crn; //شناسه یکتای ثبت قرارداد فروشنده
		$header['billid'] = $billid; //شماره اشتراک /شناسه قبض بهره بردار
		$header['tdis'] = $tdis; //مجموع تخفیفات
		$header['tonw'] = $tonw; //مجموع وزن خالص
		$header['torv'] = $torv; //مجموع ارزش ریالی
		$header['tocv'] = $tocv; //مجموع ارزش ارزی
		$header['setm'] = $setm; //روش تسویه
		$header['cap'] = $cap; //مبلغ پرداختی نقدی
		$header['insp'] = $insp; //مبلغ نسیه
		$header['tvop'] = $tvop; //مجموع سهم مالیات بر ارزش افزوده از پرداخت
		$header['tax17'] = $tax17; //مالیات موضوع ماده 17


		$body = $final_body = array();
		$products = isset($formInfo['products']) ? $formInfo['products'] : null;

		if (is_null($products)) return false;

		foreach ($products as $key => $value) {
			$sstid = isset($value['sstid']) ? esc_html( General::convertFatoEn($value['sstid']) ) : null;
			$sstt = isset($value['sstt']) ? esc_html( $value['sstt'] ) : null;
			$am = isset($value['am']) ? (int)(esc_html( $value['am'] )) : 0;
			$fee = isset($value['fee']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['fee'])) )) : 0;
			$prdis = isset($value['prdis']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['prdis'])) )) : 0;
			$dis = isset($value['dis']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['dis'])) )) : 0;
			$adis = isset($value['adis']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['adis'])) )) : 0;
			$vra = isset($value['vra']) ? (int)(esc_html( $value['vra'] )) : 0;
			$vam = isset($value['vam']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['vam'])) )) : 0;
			$tsstam = isset($value['tsstam']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['tsstam'])) )) : 0;
			$nw = isset($value['nw']) ? (int)(esc_html( $value['nw'] )) : null;
			$cfee = isset($value['cfee']) ? (int)(esc_html( $value['cfee'] )) : null;
			$cut = isset($value['cut']) ? (esc_html( $value['cut'] )) : null;
			$exr = isset($value['exr']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['exr'])) )) : null;
			$ssrv = isset($value['ssrv']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['ssrv'])) )) : null;
			$sscv = isset($value['sscv']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['sscv'])) )) : null;
			$odt = isset($value['odt']) ? esc_html( $value['odt'] ) : null;
			$odr = isset($value['odr']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['odr'])) )) : null;
			$odam = isset($value['odam']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['odam'])) )) : null;
			$olt = isset($value['olt']) ? esc_html( $value['olt'] ) : null;
			$olr = isset($value['olr']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['olr'])) )) : null;
			$olam = isset($value['olam']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['olam'])) )) : null;
			$consfee = isset($value['consfee']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['consfee'])) )) : null;
			$spro = isset($value['spro']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['spro'])) )) : null;
			$bros = isset($value['bros']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['bros'])) )) : null;
			$tcpbs = isset($value['tcpbs']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['tcpbs'])) )) : null;
			$cop = isset($value['cop']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['cop'])) )) : 0;
			$vop = isset($value['vop']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($value['vop'])) )) : 0;
			$bsrn = isset($value['bsrn']) ? esc_html( $value['bsrn'] ) : null;

			$body['sstid'] = $sstid; //شناسه کالا/خدمت
			$body['am'] = $am; //تعداد/مقدار
			$body['fee'] = $fee; //مبلغ واحد
			$body['prdis'] = $prdis; //مبلغ قبل از تخفیف
			$body['dis'] = $dis; //مبلغ تخفیف
			$body['adis'] = $adis; //مبلغ بعد از تخفیف
			$body['vra'] = $vra; //نرخ مالیات بر ارزش افزوده
			$body['vam'] = $vam; //مبلغ مالیات بر ارزش افزوده
			$body['tsstam'] = $tsstam; //مبلغ کل کالا/خدمت
			$body['sstt'] = $sstt; //شرح کالا/ خدمت
			$body['nw'] = $nw; //وزن خالص
			$body['cfee'] = $cfee; //میزان ارز
			$body['cut'] = $cut; //نوع ارز
			$body['exr'] = $exr; //نرخ برابری ارز با ریال
			$body['ssrv'] = $ssrv; //ارزش ریالی کالا
			$body['sscv'] = $sscv; //ارزش ارزی کالا
			$body['odt'] = $odt; //موضوع سایر مالیات و عوارض
			$body['odr'] = $odr; //نرخ سایر مالیات و عوارض
			$body['odam'] = $odam; //مبلغ سایر مالیات و عوارض
			$body['olt'] = $olt; //موضوع سایر وجوه قانونی
			$body['olr'] = $olr; //نرخ سایر وجوه قانونی
			$body['olam'] = $olam; //مبلغ سایر وجوه قانونی
			$body['consfee'] = $consfee; //اجرت ساخت
			$body['spro'] = $spro; //سود فروشنده
			$body['bros'] = $bros; //حق العمل
			$body['tcpbs'] = $tcpbs; //جمع کل اجرت، حق العمل و سود
			$body['cop'] = $cop; //سهم نقدی از پرداخت
			$body['vop'] = $vop; //سهم مالیات بر ارزش افزوده از پرداخت
			$body['bsrn'] = $bsrn; //شناسه یکتای ثبت قرارداد حق العمل کاری

			$final_body[] = $body;
		}

		$payments = array();
		if ($formInfo['payments']) {
			$iinn = isset($value['iinn']) ? esc_html(  General::convertFatoEn($formInfo['iinn']) ) : null;
			$acn = isset($value['acn']) ? esc_html(  General::convertFatoEn($formInfo['acn']) ) : null;
			$trmn = isset($value['trmn']) ? esc_html(  General::convertFatoEn($formInfo['trmn']) ) : null;
			$pmt = isset($value['pmt']) ? (int)(esc_html(  General::convertFatoEn($formInfo['pmt']) )) : null;
			$trn = isset($value['trn']) ? esc_html(  General::convertFatoEn($formInfo['trn']) ) : null;
			$pcn = isset($value['pcn']) ? esc_html(  General::convertFatoEn($formInfo['pcn']) ) : null;
			$pid = isset($value['pid']) ? esc_html(  General::convertFatoEn($formInfo['pid']) ) : null;
			$pdt = isset($value['pdt']) ? (int)(esc_html( $formInfo['pdt'] )) : null;
			$pv = isset($value['pv']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($formInfo['pv'])) )) : null;
			$payments['iinn'] = $iinn; //شماره سوئیچ پرداخت
			$payments['acn'] = $acn; //شماره پذیرنده فروشگاهی
			$payments['trmn'] = $trmn; //شماره پایانه
			$payments['pmt'] = $pmt; //روش پرداخت
			$payments['trn'] = $trn; //شماره پیگیری/شماره مرجع
			$payments['pcn'] = $pcn; //شماره کارت پرداخت کننده صورتحساب
			$payments['pid'] = $pid; //شماره/شناسه ملی/کد فراگیر پرداخت کننده صورتحساب
			$payments['pdt'] = $pdt; //تاریخ و زمان پرداخت
			$payments['pv'] = $pv; //مبلغ پرداختی
		}


		$invoice = array();
		$invoice['extension'] = array();
		if ($formInfo['payments']) $invoice['payments'] =  array($payments);
		$invoice['header'] = $header;
		$invoice['body'] = $final_body;

		$data = array(
			"command" => $command,
			"user_name"=>$companyInfo['shenaseYekta'],
			"private_key"=>$companyInfo['privateCode'],
			"is_sandbox"=> $formInfo['tins'] === 'sandbox' ? 1 : 0,
			"uid"=> General::generateUidv4(),
			"invoice"=> $invoice
		);

		$body = wp_json_encode( $data );

		$response = wp_remote_post( self::$sendURL, array(
			'method'      => 'POST',
			'body'        => $body,
			'headers'     => [
				'Content-Type' => 'application/json',
			],
			'timeout'     => 60,
			'redirection' => 5,
			'blocking'    => true,
			'httpversion' => '1.0',
			'sslverify'   => false,
			'data_format' => 'body',
			)
		);
		return $response;

	}

	// 2- Insert bill to DB
	public static function save_on_DB($formData, $response = '',$userId = '') {

		$companyInfo = isset($formData['company']) ? $formData['company'] : null;
		$customerInfo = isset($formData['customer']) ? $formData['customer'] : null;
		$formInfo = isset($formData['formData']) ? $formData['formData'] : null;
		$publishStatus = isset($formData['publishStatus']) ? $formData['publishStatus'] : null;

		$body = ($response !== '' && isset($response['body'])) ? json_decode($response['body']) : null;
		$ref_number = !is_null($body) ? $body->referenceNumber : null;
		$irtaxid = !is_null($body) ? $body->taxId : null;
		$send_status = !is_null($body) && $body->success ? 'ارسال شده' : null;

		$MAMainUser = get_user_meta( $userId, 'MAMainUser', true );
		$mainUser = $MAMainUser === '' ? $userId : $MAMainUser;
		$hamkar_user_id = $MAMainUser === '' ? null : $userId;

		global $wpdb;
		$db = $formInfo['tins'] === 'sandbox' ? self::$sandbox_DB_name : self::$main_DB_name;
		$tablename = $wpdb->prefix . $db;
		$sql = $wpdb->prepare("INSERT INTO `$tablename` (
			`customer_id`,
			`company_id`,
			`send_status`,
			`publish_status`,
			`irtaxid`,
			`ref_number`,
			`form_data`,
			`main_user_id`,
			`hamkar_user_id`,
			`nested_id`
		 ) values (%s, %s, %s, %s, %s, %s, %s, %d, %d, %d)",
			$customerInfo['id'],
			$formInfo['tins'],
			$send_status,
			$publishStatus,
			$irtaxid,
			$ref_number,
			json_encode($formInfo,JSON_UNESCAPED_UNICODE),
			$mainUser,
			$hamkar_user_id,
			''
		);

		$result = $wpdb->query($sql);

		return $result;
	}

	// 4- ebtale bill

	// 5- estelame vaziat

	// 6- eslahe bill

	// Get all Bill
	public static function get_bills($request) {
		$params	= $request->get_params();

		global $wpdb;
        $pagenum = isset( $params['pagination']['current'] ) ? absint( $params['pagination']['current'] ) : 1;
        $limit = $params['pagination']['pageSize'];
        $offset = ($pagenum-1) * $limit;
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM wp_MA_sandbox_bill" );
        $num_of_pages = ceil( $total / $limit );

        $qry="select * from wp_MA_sandbox_bill LIMIT $offset, $limit";
        $result = $wpdb->get_results($qry, object);

		$data['total'] = $total;
		$data['result'] = $result;

		return $data;
	}

	// Get Single Bill
	public static function get_single_bill($request) {

	}
}
