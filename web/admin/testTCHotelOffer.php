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
		"Value" => "",
		"Required" => false,
	),
    "xml" => array(
		"Type" => "string",
		"Value" => "",
        "HTML" => true,
        "InputType" => 'textarea',
		"Required" => false,
	),
	"env" => array(
		"Type" => "string",
		"Size" => 60,
		"Value" => "test",
		"Required" => true,
	),
));
$objForm->SubmitButtonCaption = "Process";

if($objForm->IsPost && $objForm->Check()){
	//echo "Sending XML..<br />";

	// generate test XML
    $doc = new DOMDocument('1.0', 'utf-8');

    $ident = $doc->createElement('HotelReservationOfferRQ');
    if (!empty($_POST['TransactionID']))
        $ident->setAttribute('AirTripID', $_POST['TransactionID']);
    else
        $ident->setAttribute('AirTripID', '7=439807=0');
    $ident->setAttribute('IATACode', 'LHR');

    ///security
    $security = $doc->createElement('Security');
    $username = $doc->createElement('UserName');
    $username->nodeValue = 'TravelConfirm';
    $password = $doc->createElement('Password');
    $password->nodeValue = 'df1494f11621dd71547a7fa464d5c040';
    $security->appendChild($username);
    $security->appendChild($password);
    $ident->appendChild($security);

    ///pixel
    $pixel = $doc->createElement('PixelUrl');
    $pixel->nodeValue = 'http://awardwallet.travelconfirm.com/skhp/Nj00NDkxOTk9NDcxNDQwPTEwMTMwODUy/pixel.gif';
    $ident->appendChild($pixel);

    ///scoosh url
    $url = $doc->createElement('SkooshUrl');
    $url->nodeValue = 'http://skoosh.com';
    $ident->appendChild($url);

    ///savings
    $savings = $doc->createElement('Savings');
    $savings->setAttribute('Amount', '62.75');
    $savings->setAttribute('CurrencyCode', 'USD');
    $ident->appendChild($savings);

    $HotelOffers = $doc->createElement('HotelOffers');
    $HotelOffer = $doc->createElement('HotelOffer');
    $HotelOffer->setAttribute('HotelName', 'Hilton test 1');
    $HotelOffer->setAttribute('HotelRating', 2.5);
    $HotelOffer->setAttribute('Amount', '123');
    $HotelOffer->setAttribute('BookingUrl', 'http://skoosh.com/book/ab/c/s');
    $HotelOffer->setAttribute('BARAmount', '321');
    $HotelOffer->setAttribute('CurrencyCode', 'USD');
    $HotelOffers->appendChild($HotelOffer);

    $BARProviders = $doc->createElement('BARProviders');
    $BARProvider1 = $doc->createElement('BARProvider');
    $BARProvider1->setAttribute('BARLogoUrl', 'http://awardwallet.travelconfirm.com/Web/Content/hotel/images/logos/hotels.com.gif');
    $BARProvider1->setAttribute('BARBookingUrl', '#');
    $BARProviders->appendChild($BARProvider1);
    $BARProvider2 = $doc->createElement('BARProvider');
    $BARProvider2->setAttribute('BARLogoUrl', 'http://awardwallet.travelconfirm.com/Web/Content/hotel/images/logos/expedia.com.gif');
    $BARProvider2->setAttribute('BARBookingUrl', '#');
    //$BARProviders->appendChild($BARProvider2);

    $BARProvider1 = $doc->createElement('BARProvider');
    $BARProvider1->setAttribute('BARLogoUrl', 'http://awardwallet.travelconfirm.com/Web/Content/hotel/images/logos/hotels.com.gif');
    $BARProvider1->setAttribute('BARBookingUrl', '#');
    $BARProviders->appendChild($BARProvider1);
    $BARProvider2 = $doc->createElement('BARProvider');
    $BARProvider2->setAttribute('BARLogoUrl', 'http://awardwallet.travelconfirm.com/Web/Content/hotel/images/logos/expedia.com.gif');
    $BARProvider2->setAttribute('BARBookingUrl', '#');
    $BARProviders->appendChild($BARProvider2);
    $HotelOffer->appendChild($BARProviders);

    $HotelOffer2 = $doc->createElement('HotelOffer');
    $HotelOffer2->setAttribute('HotelName', 'Hilton test 2');
    $HotelOffer2->setAttribute('HotelRating', 3.5);
    $HotelOffer2->setAttribute('Amount', '123');
    $HotelOffer2->setAttribute('BookingUrl', 'http://skoosh.com/book/ab/c/s');
    $HotelOffer2->setAttribute('BARAmount', '321');
    $HotelOffer2->setAttribute('BARBookingUrl', 'hotelscombined.com/key=');
    $HotelOffer2->setAttribute('CurrencyCode', 'USD');
    $HotelOffers->appendChild($HotelOffer2);


    $BARProviders = $doc->createElement('BARProviders');
    $BARProvider1 = $doc->createElement('BARProvider');
    $BARProvider1->setAttribute('BARLogoUrl', 'http://awardwallet.travelconfirm.com/Web/Content/hotel/images/logos/hotels.com.gif');
    $BARProvider1->setAttribute('BARBookingUrl', '#');
    $BARProviders->appendChild($BARProvider1);
    $BARProvider2 = $doc->createElement('BARProvider');
    $BARProvider2->setAttribute('BARLogoUrl', 'http://awardwallet.travelconfirm.com/Web/Content/hotel/images/logos/expedia.com.gif');
    $BARProvider2->setAttribute('BARBookingUrl', '#');
    $BARProviders->appendChild($BARProvider2);
    $HotelOffer2->appendChild($BARProviders);

    $ident->appendChild($HotelOffers);


    $doc->appendChild($ident);
    $xml = $doc->saveXML();
    if (!empty($_POST['xml']))
        $xml = $_POST['xml'];
/*
    header('Content-type: text/xml');
    echo $xml;
    exit;
*/




	try{
		$client = new AwardWalletClient(
			array(
				"trace"=> true,
				"exceptions"=> true,
				"cache_wsdl "=> WSDL_CACHE_NONE,
				"location" => 'http://'.$_SERVER['HTTP_HOST'].'/api/travelConfirm/offers.php?env=' . (empty($_POST['env']) ? 'test' : $_POST['env']),
			),
			'http://'.$_SERVER['HTTP_HOST'].'/api/travelConfirm/offers.php?wsdl'
		);
		$request = new HotelReservationOfferRQ($xml);
		$response = $client->HotelReservationOffer($request);
	}
	catch(SoapFault $e){
		echo "SoapFault exception: ".$e->getMessage()."<br>";
		echo "Response:<br>". StringSanitizer::encodeHtmlEntities($client->__getLastResponseHeaders().$client->__getLastResponse());
	}

	if(isset($response)){
        print_r($response);
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

            echo '<pre>'; print_r($xml); echo '</pre>';
		}
	}
}
echo $objForm->HTML();
?>
