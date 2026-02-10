<?php

// required for /api

require_once($sPath.'/lib/3dParty/barcodegen/class/BCGFont.php');
require_once($sPath.'/lib/3dParty/barcodegen/class/BCGColor.php');
require_once($sPath.'/lib/3dParty/barcodegen/class/BCGDrawing.php');

function createBarcode($format, $number, $scale = 1){
	global $sPath;
	$font = new BCGFont(getSymfonyContainer()->getParameter("kernel.root_dir") . '/../data/fonts/arial.ttf', 18);
	switch($format){
		case "code39":
			require_once($sPath.'/lib/3dParty/barcodegen/class/BCGcode39.barcode.php');
			$code = new BCGcode39();
			break;
		case "upca":
			require_once($sPath.'/lib/3dParty/barcodegen/class/BCGupca.barcode.php');
			$code = new BCGupca();
			break;
		case "ean13":
			require_once($sPath.'/lib/3dParty/barcodegen/class/BCGean13.barcode.php');
			$code = new BCGean13();
			break;
		case "code128":
			require_once($sPath.'/lib/3dParty/barcodegen/class/BCGcode128.barcode.php');
			$code = new BCGcode128();
			break;
		case "interleaved25":
			require_once($sPath.'/lib/3dParty/barcodegen/class/BCGi25.barcode.php');
			$code = new BCGi25();
			break;
		default:
			die("Unknown format");
	}
	$code->setScale($scale); // Resolution
	$code->setThickness(30); // Thickness
	// The arguments are R, G, B for color.
	$color_black = new BCGColor(0,0,0);
	$color_white = new BCGColor(255,255,255);
	$code->setForegroundColor($color_black); // Color of bars
	$code->setBackgroundColor($color_white); // Color of spaces
	$code->setFont($font); // Font (or 0)
	$code->parse($number); // Text
	$drawing = new BCGDrawing('', $color_white);
	$drawing->setBarcode($code);
	$drawing->draw();
	return $drawing;
}

function createAccountBarCode($fields, $format = null, $number = null, $scale = 1){
	global $sPath;
	if(!isset($number)){
		$number = $fields['Login'];
		$qNumber = new TQuery("select ap.Val as Value from AccountProperty ap
			join ProviderProperty pp on ap.ProviderPropertyID = pp.ProviderPropertyID
			where ap.AccountID = {$fields['AccountID']}
			and pp.Code = 'Number'");
		if(!$qNumber->EOF)
			$number = $qNumber->Fields['Value'];
	}
	if(!isset($format))
		$format = $fields['BarCode'];
	if($format == 'custom'){
		require_once("$sPath/engine/".strtolower($fields['ProviderCode'])."/functions.php");
		call_user_func(array("TAccountChecker".ucfirst($fields['ProviderCode']), "FormatBarCode"), $number, $format, $fields);
	}
	return createBarcode($format, $number, $scale);
}

?>