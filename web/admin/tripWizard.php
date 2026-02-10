<?php
require "../kernel/public.php";
require_once "../kernel/TForm.php";
require_once "../kernel/tariffFunctions.php";
AuthorizeUser();

$objForm = new TForm(array(
	"From" => array(
		"Type" => "string",
		"Size" => 3,
		"Required" => true,
	),
	"To" => array(
		"Type" => "string",
		"Size" => 3,
		"Required" => true,
	),
	"Date" => array(
		"Type" => "date",
		"Required" => true,
		"Value" => date(DATE_FORMAT),
	),
));
$objForm->OnCheck = "CheckSearchForm";
$sTitle = "Trip wizard test";
$objForm->SubmitButtonCaption = "Search tariffs";

require("../design/header.php");

if( $objForm->IsPost && $objForm->Check() ){
	$arPoints = FindMinimumPoints(
		$objForm->Fields["From"]["Value"],
		$objForm->Fields["To"]["Value"],
		StrToDate( $objForm->Fields["Date"]["Value"] ),
		SQLToSimpleArray("select distinct ProviderID from Account
		where UserID = {$_SESSION['UserID']} and ProviderID is not null",
			"ProviderID")
	);
}

echo $objForm->HTML();

require("../design/footer.php");

function CheckSearchForm(){
	global $objForm;
	if( SearchAirPort($objForm->Fields["From"]["Value"]) === FALSE )
		return "Source AirPort not found";
	if( SearchAirPort($objForm->Fields["To"]["Value"]) === FALSE )
		return "Target AirPort not found";
	return null;
}

function var_dump_pre( $v ){
	echo "<pre>";
	var_dump( $v );
	echo "</pre>";
}

function ShowTable( $arRows ){
	if( count( $arRows ) == 0 ){
		echo "table is empty<br>";
		return;
	}
	$arHeaders = array_keys( $arRows[0] );
	echo "<table border=1 cellpadding=2 cellspacing=0><tr>";
	foreach ( $arHeaders as $sCaption )
		echo "<td><b>$sCaption</b></td>";
	echo "</tr>";
	foreach ( $arRows as $arRow ){
		echo "<tr>";
		foreach ( $arRow as $sValue )
			echo "<td>$sValue</td>";
		echo "</tr>";
	}
	echo "</table>";
}

?>
