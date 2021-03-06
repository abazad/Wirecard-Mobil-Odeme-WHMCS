<?php
function threepaymobil_config() {
	global $CONFIG;

	$configarray = array(
		'FriendlyName' => array('Type' => 'System', 'Value' => 'Mobil Ödeme'), 
		'UserCode' => array('FriendlyName' => 'UserCode', 'Type' => 'text', 'Size' => '40'), 
		'Pin' => array('FriendlyName' => 'Pin', 'Type' => 'text', 'Size' => '40'), 
		'ProductCategory' => array('FriendlyName' => 'ProductCategory', 'Type' => 'text', 'Size' => '40', 'Default' => '3'),
		'TurkcellServiceId' => array('FriendlyName' => 'TurkcellServiceId', 'Type' => 'text', 'Size' => '40', 'Default' => '1'),
		'MobilKomisyon' => array('FriendlyName' => 'Mobil Ödeme Komisyon %', 'Type' => 'text', 'Size' => '40'),
		'UstOdemeLimiti' => array('FriendlyName' => 'Üst Ödeme Limiti', 'Type' => 'text', 'Size' => '40')
	);
	return $configarray;
}

function threepaymobil_link($params) {
	global $CONFIG;
	
	
	$RequestGsmOperator = '0';
	$Extra = '';
	$Amount = $params['amount'] * $params['MobilKomisyon'] / 100;
	$Amount = $params['amount'] + $Amount;
	$token = array( 'UserCode' => $params['UserCode'], 'Pin' => $params['Pin'] );
	$SuccessfulPageUrl = $params['systemurl'] . '/viewinvoice.php?id=' . $params['invoiceid'] . '&paymentsuccess=true';
	$ErrorPageUrl = $params['systemurl'] . '/viewinvoice.php?id=' . $params['invoiceid'] . '&paymentfailed=true';
	
	
	$return = "";
	$input = array('MPAY' => $params['invoiceid'], 'Content' => $params['description'],'SendOrderResult' => 'true', 'PaymentTypeId' => '1', 'ReceivedSMSObjectId' => '00000000-0000-0000-0000-000000000000', 'ProductList' => array('MSaleProduct' => array('ProductId' => '0', 'ProductCategory' => $params['ProductCategory'], 'ProductDescription' => $params['description'], 'Price' => $Amount, 'Unit' => '1')), 'SendNotificationSMS' => 'True', 'OnSuccessfulSMS' => 'Odeme basariyla tamamlandi.', 'OnErrorSMS' => 'Odeme tamamlanirken hata olustu.', 'RequestGsmOperator' => $RequestGsmOperator, 'RequestGsmType' => '0', 'Url' => $_SERVER['HTTP_HOST'], 'SuccessfulPageUrl' => $SuccessfulPageUrl, 'ErrorPageUrl' => $ErrorPageUrl, 'Country' => '', 'Currency' => '', 'Extra' => $Extra, 'TurkcellServiceId' => $params['TurkcellServiceId']);
	$data = array('token' => $token, 'input' => $input);

	if ($Amount <= $params['UstOdemeLimiti']) {
		if (isset($_POST['threepay']) || isset($_GET['otoyon'])) {
			$connect = new SoapClient('https://www.wirecard.com.tr/vas/MSaleService.asmx?WSDL');
			$result = $connect->SaleWithTicket($data);
			if ($result->SaleWithTicketResult->StatusCode == '0') {
				header('Location: '.$result->SaleWithTicketResult->RedirectUrl);
				$return = '<form action="'.$result->SaleWithTicketResult->RedirectUrl.'" id="otoyon" method="post"></form><script type="text/javascript">otoyon.submit();</script>';
			}else
				$return .= '<font color="red">Bir hata oluştu. Code : '.$result->SaleWithTicketResult->ErrorCode.' Message : '.$result->SaleWithTicketResult->ErrorMessage.'</font>';
		}else{
			$return .= '<form action="viewinvoice.php?id='.$params['invoiceid'].'&otoyon" method="post">
				<input type="hidden" name="id" value="'.$params['invoiceid'].'">
				<input type="submit" name="threepay" value="'.$params['langpaynow'].'"> <br />
				<br />
				'.$params['name'].' ödemelerinde %'.$params['MobilKomisyon'].' komisyon kesilmektedir.
				<br />
				Toplam ödeme tutarınız : <font color="green"><b>'.$Amount.'</b> '.$params['currency'].'</font> dir.
				</form>';
				//</form>'.print_r($params,true);
		}
	}else
		$return .= '<font color="red">Bu ödeme yoluyla en fazla '.$params['UstOdemeLimiti'].' '.$params['currency'].' tutarındaki faturalar ödenebilir.</font>';
	return $return;
}

?>
