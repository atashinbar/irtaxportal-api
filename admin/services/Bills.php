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
			$body = (isset($response) && !empty($response)) ? $response : null;
		}

		// معمولا اگر ارتباط با سامانه برقرار نشود ارور میدهد
		if (!$body->success) {
			return static::create_response( $body->erorr , 403 );
		}

		$formInfo = isset($formData['formData']) ? $formData['formData'] : null;
		$data['sandbox'] = $formInfo['tins'] === 'sandbox' ? 1 : 0;
		$data['ref_number'] = $body->referenceNumber;
		$data['company'] = $formData['company'];

		$result = self::get_inquiry_status($data);
		if ($result[0]->status === 'FAILED') {
			return  static::create_response($result[0]->erorrs , 403 );
		}

		$final_response = 0;
		if ( (isset($result) && isset($result[0]->status) && $result[0]->status === 'SUCCESS') || $publishStatus === 'draft' ) {
			if ($formData['edit']){
				$singleId = isset($formData['singleId']) ? $formData['singleId'] : null;
				if ( $singleId ) {
					if ( str_contains($singleId, 'nested') ) {
						$main_id = explode('-', $singleId);
						$singleId = (int)$main_id[1];
					}
					$time = floor(microtime(true) * 1000);
					$id = 'nested-' . $singleId . '-' . $time;
					$tableName = $formData['formData']['tins'] === 'sandbox' ?  self::$sandbox_DB_name : self::$main_DB_name ;
					$object = new \stdClass();
					$object->id = $id;
					$object->submit_date = $time;
					$object->send_status = '0';
					$object->irtaxid = $body->taxId;
					$object->ref_number = $body->referenceNumber;
					$object->sandbox = $formData['formData']['tins'] === 'sandbox' ? 1 : 0;
					$object->form_data = json_encode($formData['formData'],JSON_UNESCAPED_UNICODE);
					$final_response = self::update_DB($tableName, $userId, $singleId, 'nested' , $object);
					return $final_response;
				}
			} else {
				$final_response = static::save_on_DB($formData, $response,$userId);
			}

		}

		if ( $final_response === 1 && $publishStatus === 'draft') {
			return static::create_response( 'صورتحساب شما با موفقیت ذخیره شد', 200 );
		} elseif( $final_response !== 1 && $publishStatus === 'draft' ) {
			return static::create_response( 'صورتحساب شما ذخیر نشد. لطفا دقایقی دیگر مجدد تلاش کنید', 403 );
		}

		if ( $final_response === 1 && $publishStatus === 'published' ) {
			return static::create_response( 'صورتحساب شما با موفقیت ارسال شد. لطفا قبل از هر اقدامی، استعلام صورتحساب را انجام دهید تا وضعیت آن مشخص شود.', 200 );
		} elseif ( $final_response !== 1 && $publishStatus === 'published' ) {
			return static::create_response( $body, 403 );
		}

		return static::create_response( $final_response, 403 );
	}

	// 3- send function (gereftane parametr ha va jaygozin kardane unha va ersal be samaneye maliat)
	public static function send_to_portal($formData, $command) {

		$companyInfo = isset($formData['company']) ? $formData['company'] : null;
		$customerInfo = isset($formData['customer']) ? $formData['customer'] : null;
		$formInfo = isset($formData['formData']) ? $formData['formData'] : null;
		$irtaxid = isset($formData['irtaxid']) ? $formData['irtaxid'] : null;

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
		if ( $formData['edit'] && isset($irtaxid) && !is_null( $irtaxid ) && !empty( $irtaxid )) {
			$header['irtaxid'] = $irtaxid;
		}
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
			$iinn = isset($formInfo['iinn']) ? esc_html(  General::convertFatoEn($formInfo['iinn']) ) : null;
			$acn = isset($formInfo['acn']) ? esc_html(  General::convertFatoEn($formInfo['acn']) ) : null;
			$trmn = isset($formInfo['trmn']) ? esc_html(  General::convertFatoEn($formInfo['trmn']) ) : null;
			$pmt = isset($formInfo['pmt']) ? (int)(esc_html(  General::convertFatoEn($formInfo['pmt']) )) : null;
			$trn = isset($formInfo['trn']) ? esc_html(  General::convertFatoEn($formInfo['trn']) ) : null;
			$pcn = isset($formInfo['pcn']) ? esc_html(  General::convertFatoEn($formInfo['pcn']) ) : null;
			$pid = isset($formInfo['pid']) ? esc_html(  General::convertFatoEn($formInfo['pid']) ) : null;
			$pdt = isset($formInfo['pdt']) ? (int)(esc_html( $formInfo['pdt'] )) : null;
			$pv = isset($formInfo['pv']) ? (int)(esc_html( str_replace(',', '', General::convertFatoEn($formInfo['pv'])) )) : null;
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

		$result = self::send_to_tax_portal($data);
		return $result;
	}

	// 2- Insert bill to DB
	public static function save_on_DB($formData, $response = '',$userId = '') {

		$companyInfo = isset($formData['company']) ? $formData['company'] : null;
		$customerInfo = isset($formData['customer']) ? $formData['customer'] : null;
		$formInfo = isset($formData['formData']) ? $formData['formData'] : null;
		$publishStatus = isset($formData['publishStatus']) ? $formData['publishStatus'] : null;

		$body = (isset($response) && !empty($response) ) ? $response : null;
		$ref_number = !is_null($body) ? $body->referenceNumber : null;
		$irtaxid = !is_null($body) ? $body->taxId : null;
		$send_status = !is_null($body) && $body->success ? 0 : null;

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
			`nested`
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
	public static function cancel_bill( $request ) {
		$params	= $request->get_params();

		// Check User id
		static::check_user_id('check');
		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		$header['indatim'] = (int)$params['submit_date'];
		$header['ins'] = 3;
		$header['irtaxid'] =  $params['irtaxid'];
		$header['inno'] = (string)rand(1000000000,9999999999);
		$header['tins'] =  $params['company']['codeEghtesadi'];

		$invoice = array();
		$invoice['extension'] = array();
		$invoice['body'] = array();
		$invoice['header'] = $header;

		$data = array(
			"command" => 'send',
			"user_name"=> $params['company']['shenaseYekta'],
			"private_key"=> $params['company']['privateCode'],
			"is_sandbox"=> (int)$params['sandbox'],
			"uid"=> General::generateUidv4(),
			"invoice"=> $invoice
		);

		$result = self::send_to_tax_portal($data);

		$tableName = (int)$params['sandbox'] === 1 ?  self::$sandbox_DB_name : self::$main_DB_name ;
		if (isset($result->success) && $result->success === true) {
			$db_result = self::update_DB($tableName, $userId, $params['id'], 'send_status' , '-1');
			if( $db_result === 1 ) {
				if ( str_contains($params['id'], 'nested') ) {
					$main_id = explode('-', $params['id']);
					$params['id'] = (int)$main_id[1];
				}
				$time = floor(microtime(true) * 1000);
				$id = 'nested-' . $params['id'] . '-' . $time;
				$object = new \stdClass();
   				$object->id = $id;
   				$object->submit_date = $time;
   				$object->send_status = '-100';
   				$object->irtaxid = $result->taxId;
   				$object->ref_number = $result->referenceNumber;

				$update_nested = self::update_DB($tableName, $userId, $params['id'], 'nested' , $object);

				if ($update_nested === 1) {
					$message = "فاکتور شما با موفقیت ابطال شد";
					return static::create_response( $message, 200 );
				} else {
					$message = "ابطال فاکتور با خطا مواجه شد";
					return static::create_response( $message, 403 );
				}
			} else {
				$message = "ابطال فاکتور با خطا مواجه شد";
				return static::create_response( $message, 403 );
			}
		}

	}

	// 5- estelame vaziat
	public static function get_inquiry($request) {
		$params	= $request->get_params();

		$refNumber = isset($params['ref_number']) ? $params['ref_number'] : null;
		if(is_null($refNumber) || empty($refNumber)) return static::create_response( 'شماره ارجاع مالیاتی خالی است', 403 );

		// Check User id
		static::check_user_id('check');
		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		$result = self::get_inquiry_status($params);
		$errors = $result[0]->erorrs;

		$tableName = (int)$params['sandbox'] === 1 ?  self::$sandbox_DB_name : self::$main_DB_name ;
		if ($result[0]->status == 'PENDING') {
			$db_result = self::update_DB($tableName, $userId, $params['id'], 'send_status' , '-10');
			$message = "فاکتور شما در حال ثبت در سامانه است. لطفا ساعاتی دیگر مجدد استعلام بگیرید";
			return static::create_response( $message, 300 );
		}
		if ($result[0]->status == 'SUCCESS') {
			$db_result = self::update_DB($tableName, $userId, $params['id'], 'send_status' , '1');
			if ( $db_result === 1 ){
				$message = "فاکتور شما با موفقیت ثبت شده است. لطفا به کارپوشه اداره مالیات مراجعه کنید";
				return static::create_response($message, 200 );
			} elseif ($db_result === 0) {
				$message = "فاکتور شما با موفقیت ثبت شده است. اما ذخیره وضعیت در دیتابیس با خطا مواجه شد. لطفا دوباره استعلام بگیرید.";
				return static::create_response($message, 500 );
			}
		}
		if ($result[0]->status == 'FAILED') {
			$db_result = self::update_DB($tableName, $userId, $params['id'], 'send_status' , '-20');
			$message = json_encode($errors);
			return static::create_response($message, 403 );
		}

		return static::create_response( $result, 403 );
	}

	public static function get_inquiry_status($data) {
		$refNumber = isset($data['ref_number']) ? $data['ref_number'] : null;
		$data = array(
			"command" => "ref",
			"user_name"=> $data['company']['shenaseYekta'],
			"private_key"=> $data['company']['privateCode'],
			"is_sandbox"=> (int)$data['sandbox'],
			"ref_number"=> $refNumber,
		);

		$res = self::send_to_tax_portal($data);
		$status = $res->success;
		$result = $res->result;

		if ($res->erorr) return $res;
		return $result;
	}

	// Update DB
	public static function update_DB($db, $userId, $id, $column, $data) {
		global $wpdb;
		$tableName = $wpdb->prefix . $db;

		$MAMainUser = get_user_meta( $userId, 'MAMainUser', true );
		$mainUser = $MAMainUser === '' ? $userId : $MAMainUser;
		if ($column === 'nested') {
			$db_data = $wpdb->get_row( $wpdb->prepare( "SELECT nested FROM $tableName WHERE id = %d AND main_user_id = %d", array( $id , $mainUser ) ) );
			$nested = isset($db_data->nested) && $db_data->nested !== '' && self::json_validate($db_data->nested) ? json_decode($db_data->nested) : $db_data->nested;
			$time = $data->submit_date;
			if ( isset($db_data->nested) && $db_data->nested !== '' && self::json_validate($db_data->nested) ) {
				$nested->$time = $data;
				$data = $nested;
			} else {
				$object = new \stdClass();
				$object->$time = $data;
				$data = $object;
			}
			$data = json_encode($data);
		}

		if (str_contains($id, 'nested')) {
			$main_id = explode('-', $id);
			$row_id = (int)$main_id[1];
			$nested_id = (int)$main_id[2];
			$db_data = $wpdb->get_row( $wpdb->prepare( "SELECT nested FROM $tableName WHERE id = %d AND main_user_id = %d", array( $row_id , $mainUser ) ) );
			$nested = isset($db_data->nested) && $db_data->nested !== '' && self::json_validate($db_data->nested) ? json_decode($db_data->nested) : $db_data->nested;
			$info = $nested->$nested_id;
			$info->send_status = $data;
			$nested->$nested_id = $info;
			$data = json_encode($nested);

			$id = (int)$main_id[1];
			$column = 'nested';
		}

		$data_update = array($column => $data);
		$data_where = array('id' => $id, 'main_user_id' => $mainUser);
		$update = $wpdb->update($tableName , $data_update, $data_where);

		return $update;
	}

	// 6- eslahe bill


	// Get all Bill
	public static function get_bills($request) {
		$params	= $request->get_params();

		// Check User id
		static::check_user_id('check');
		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		global $wpdb;
        $pagenum = isset( $params['tableParams']['pagination']['current'] ) ? absint( $params['tableParams']['pagination']['current'] ) : 1;
        $limit = $params['tableParams']['pagination']['pageSize'];
        $offset = ($pagenum-1) * $limit;
		$db = $params['database'] === 'sandbox' ? self::$sandbox_DB_name : self::$main_DB_name;
		$tablename = $wpdb->prefix . $db;

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM `$tablename` WHERE main_user_id = $userId" );
        $num_of_pages = ceil( $total / $limit );

		if ( $limit == -1 ) {
			$qry="select * from $tablename WHERE main_user_id = $userId ORDER BY id DESC";
		} else {
			$qry="select * from $tablename WHERE main_user_id = $userId ORDER BY id DESC LIMIT $offset, $limit";
		}

        $result = $wpdb->get_results($qry, object);

		$data['total'] = $total;
		$data['result'] = $result;

		return static::create_response( $data, 200 );
	}

	// Get total Bill
	public static function get_total_bills($request) {
		$params	= $request->get_params();

		// Check User id
		static::check_user_id('check');
		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );

		global $wpdb;
		$db			= sanitize_text_field( $params['database'] );
		$db 		= $db === 'sandbox' ? self::$sandbox_DB_name : self::$main_DB_name ;
		$tablename	= $wpdb->prefix . $db;
		$total		= $wpdb->get_var( "SELECT COUNT(*) FROM `$tablename` WHERE main_user_id = $userId" );
		if ( $total ) {
			return static::create_response( $total, 200 );
		}

		return static::create_response( 'مشکلی پیش آمده است', 403 );
	}

	// Get Single Bill
	public static function get_bill($request) {
		$params	= $request->get_params();

		static::check_user_id('check');
		$userId = static::check_main_user_id( static::check_user_id( 'get' ) );
		$MAMainUser = get_user_meta( $userId, 'MAMainUser', true );
		$mainUser = $MAMainUser === '' ? $userId : $MAMainUser;

		global $wpdb;
		$tableName = $wpdb->prefix . self::$sandbox_DB_name;
		$singleId = sanitize_text_field( $params['singleId'] );
		if ( !str_contains($singleId, 'nested') ) {
			$db_data = $wpdb->get_row( $wpdb->prepare(
				"SELECT form_data , send_status ,irtaxid FROM " . $tableName . " WHERE `id` = %d AND `main_user_id` = %d",
				[ $singleId , $mainUser ]
			) );
		} else {
			$main_id = explode('-', $singleId);
			$row_id = (int)$main_id[1];
			$nested_id = (int)$main_id[2];

			// $db_data = $wpdb->get_row( $wpdb->prepare( "SELECT nested FROM $tableName WHERE id = %d AND main_user_id = %d", array( $row_id , $mainUser ) ) );
			$db_data = $wpdb->get_row( $wpdb->prepare(
				"SELECT nested FROM " . $tableName . " WHERE `id` = %d AND `main_user_id` = %d",
				[ $row_id , $mainUser ]
			) );
			$nested = isset($db_data->nested) && $db_data->nested !== '' && self::json_validate($db_data->nested) ? json_decode($db_data->nested) : $db_data->nested;
			$info = $nested->$nested_id;
			$db_data = $info;
		}
		return static::create_response( $db_data, 200 );
	}

	// Send to tax portal (curl)
	public static function send_to_tax_portal($data) {
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

		$result = self::json_validate($response['body']) ? json_decode($response['body']) : $response['body'];

		return $result;
	}

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
