<?php
$bNoSession = true;
require "../kernel/public.php";
require_once "../kernel/tariffFunctions.php";
require_once "$sPath/lib/classes/TBaseFormEngConstants.php";

$sTitle = "Test accurateit.com callback";

require "$sPath/lib/admin/design/header.php";

$objForm = new TBaseForm(array(
	"URL" => array(
		"Type" => "string",
		"Size" => 60,
		"Value" => "https://accurateit.com/callback.asp",
		"Required" => true,
		"Caption" => "URL",
		"HTML" => true,
	),
	"XML" => array(
		"Caption" => "XML",
		"Type" => "string",
		"Size" => 64000,
		"Required" => true,
		"InputAttributes" => "style='width: 800px; height: 600px;'",
		"HTML" => true,
	),
));
$objForm->SubmitButtonCaption = "Send to accurateit.com";
session_write_close();

if($objForm->IsPost && $objForm->Check()){
	echo "Sending XML..<br>";
	$rConn = curl_init();
	curl_setopt( $rConn, CURLOPT_HEADER, true );
	curl_setopt( $rConn, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $rConn, CURLOPT_FAILONERROR, false );
	curl_setopt( $rConn, CURLOPT_TIMEOUT, 30 );
	curl_setopt( $rConn, CURLOPT_CONNECTTIMEOUT, 30 );
	curl_setopt( $rConn, CURLOPT_BINARYTRANSFER, true );
	curl_setopt( $rConn, CURLOPT_FOLLOWLOCATION, false );
	curl_setopt( $rConn, CURLOPT_URL, $objForm->Fields['URL']['Value'] );
   	curl_setopt( $rConn, CURLOPT_POST, true );
    curl_setopt( $rConn, CURLOPT_POSTFIELDS, $objForm->Fields['XML']['Value'] );
	$arHeaders = array("Authorization: Basic VhfdsjfdisoF=");
	curl_setopt( $rConn, CURLOPT_HTTPHEADER, $arHeaders );
	$sResponse = curl_exec( $rConn );
	if($sResponse === false){
		echo "CURL error: ".curl_error($rConn)."<br>";
	}
	else{
		echo "Response: <br><pre>".htmlspecialchars($sResponse)."</pre><br>";
	}
}

echo $objForm->HTML();

require "$sPath/lib/admin/design/footer.php";
?>
