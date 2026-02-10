<?php

use AwardWallet\MainBundle\Security\StringSanitizer;

$bNoSession = true;
require "../kernel/public.php";
require_once "$sPath/lib/classes/TBaseFormEngConstants.php";
require_once '../api/travelConfirm/AwardWalletClient.php';

$sTitle = "Test client TC API";

require "$sPath/lib/admin/design/header.php";

$objForm = new TBaseForm(array(
	"TransactionID" => array(
		"Type" => "string",
		"Size" => 60,
		"Value" => "11111",
		"Required" => true,
	),
));
$objForm->SubmitButtonCaption = "Process";

if($objForm->IsPost && $objForm->Check()){
	echo "Sending XML..<br />";

	// generate test XML
/*
	$doc = new DOMDocument('1.0', 'utf-8');
	$ident = $doc->createElement('HotelReservationUpdateRQ');
	$ident->setAttribute('TransactionIdentifier', $objForm->Fields["TransactionID"]["Value"]);
	$rUpd = $doc->createElement('ReserationUpdate');
	$savings = $doc->createElement('Savings');
	$savings->setAttribute('Amount', '999');
	$savings->setAttribute('CurrencyCode', 'EUR');
	$rebook = $doc->createElement('RebookingDetailsPage');
	$rebook->setAttribute('Url', 'www.siggard.ru');
	$rUpd->appendChild($savings);
	$rUpd->appendChild($rebook);
	$ident->appendChild($rUpd);
	$doc->appendChild($ident);
	$xml = $doc->saveXML();
*/

    $doc = new DOMDocument('1.0', 'utf-8');

    $ident = $doc->createElement('HotelReservationUpdateRQ');

    $security = $doc->createElement('Security');
    $username = $doc->createElement('UserName');
    $username->nodeValue = 'TravelConfirm';
    $password = $doc->createElement('Password');
    $password->nodeValue = 'df1494f11621dd71547a7fa464d5c040';
    $security->appendChild($username);
    $security->appendChild($password);
    $ident->appendChild($security);

    $ident->setAttribute('TransactionIdentifier', $objForm->Fields["TransactionID"]["Value"]);

    $ident->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
    $ident->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

    $rUpd = $doc->createElement('ReservationUpdate');
    $savings = $doc->createElement('Savings');
    $savings->setAttribute('Amount', '10');
    $savings->setAttribute('CurrencyCode', 'USD');
    $rebook = $doc->createElement('RebookingDetailsPage');
    $rebook->setAttribute('Url', 'http://www.travelconfirm.com/web/book/hotel/53c9502a-a8b0-4a22-9987-a5d6e26b7635');
    //$rUpd->setAttribute('Type', 'NONREFUNDABLE');
    $rebook2 = $doc->createElement('UnsubscribePage');
    $rebook2->setAttribute('Url', 'http://www.travelconfirm.com/you-unsubscribe-url');
    $rUpd->appendChild($savings);
    $rUpd->appendChild($rebook);
    $rUpd->appendChild($rebook2);
    $ident->appendChild($rUpd);
    $doc->appendChild($ident);
    $xml = $doc->saveXML();


 /*
    $doc = new DOMDocument('1.0', 'utf-8');

    $ident = $doc->createElement('CarRentalUpdateRQ');

    $security = $doc->createElement('Security');
    $username = $doc->createElement('UserName');
    $username->nodeValue = 'TravelConfirm';
    $password = $doc->createElement('Password');
    $password->nodeValue = 'df1494f11621dd71547a7fa464d5c040';
    $security->appendChild($username);
    $security->appendChild($password);
    $ident->appendChild($security);

    $ident->setAttribute('TransactionIdentifier', $objForm->Fields["TransactionID"]["Value"]);

    $ident->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
    $ident->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');

    $rUpd = $doc->createElement('ReservationUpdate');
    $savings = $doc->createElement('Savings');
    $savings->setAttribute('Amount', '10');
    $savings->setAttribute('CurrencyCode', 'USD');
    $rebook = $doc->createElement('RebookingDetailsPage');
    $rebook->setAttribute('Url', 'http://www.travelconfirm.com/web/book/hotel/53c9502a-a8b0-4a22-9987-a5d6e26b7635');
    //$rUpd->setAttribute('Type', 'NONREFUNDABLE');
    $rebook2 = $doc->createElement('UnsubscribePage');
    $rebook2->setAttribute('Url', 'http://www.travelconfirm.com/you-unsubscribe-url');
    $rUpd->appendChild($savings);
    $rUpd->appendChild($rebook);
    $rUpd->appendChild($rebook2);
    $ident->appendChild($rUpd);
    $doc->appendChild($ident);
    $xml = $doc->saveXML();


  */
    //echo '<pre>'.htmlentities($xml).'</pre>';

	try{
		$client = new AwardWalletClient(
			array(
				"trace"=> true,
				"exceptions"=> true,
				"cache_wsdl "=> WSDL_CACHE_NONE,
				"location" => 'http://'.$_SERVER['HTTP_HOST'].'/api/travelConfirm/offers.php'
			),
			'http://'.$_SERVER['HTTP_HOST'].'/api/travelConfirm/offers.php?wsdl'
		);
		$request = new HotelReservationUpdateRQ($xml);
		$response = $client->HotelReservationUpdate($request);
	}
	catch(SoapFault $e){
		echo "SoapFault exception: ".$e->getMessage()."<br>";
		echo "Response:<br>".StringSanitizer::encodeHtmlEntities($client->__getLastResponseHeaders().$client->__getLastResponse());
	}

	if(isset($response)){
		$xml = simplexml_load_string($response->response);
		if(!$xml){
			echo 'no XML!!!!!';
			echo '<pre>'; var_dump($response); echo '</pre>';
		}
		else{
			$x['TransactionID'] = $xml["TransactionIdentifier"];
			if(isset($xml->Errors)){
				$x['result'] = $xml->Errors->Error['Type'];
				$x['message'] = $xml->Errors->Error['ShortText'];
			}
			else if(isset($xml->Success)){
				$x['result'] = 'SUCCESS';
			};

			echo "<pre>"; print_r($x); echo "</pre><hr>";
            //echo '<pre>'; var_dump($xml); echo '</pre>';
		}
	}
}
echo $objForm->HTML();
?>
