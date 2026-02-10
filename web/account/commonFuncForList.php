<?

function DrawLeftMenus(){
	if(ArrayVal($_GET, 'showTabG') == 'Recently')
		return;
	openLeftBox('Display Options', 'menu');
?>
	<div class="bContent">
		<div class="bTopGrad bPad" style="padding-top: 5px;">

	<table border="0" cellpadding="0" cellspacing="0" class="display">
	<?
	if (SITE_MODE != SITE_MODE_BUSINESS) {
	?>
		<tr>
			<td style="padding-bottom: 5px;"><input type="checkbox" id="ungroupCheck"
		<?
		if(ArrayVal($_COOKIE, 'grouped') == 'false')
			echo " checked";
		?>
		onclick="ungroupClick()"></td>
			<td style="padding-bottom: 5px; padding-left: 7px;"><a href="#" onclick="document.getElementById('ungroupCheck').checked = !document.getElementById('ungroupCheck').checked; ungroupClick(); return false;" class="leftMenuLink">Ungroup Rewards</a></td>
		</tr>
		<?
	}
		$excel = $_GET;
		$excel['Excel'] = 1;
		$pdf = $_GET;
		$pdf['pdf'] = 1;
		$attrs = "";
		//echo "<pre>"; var_dump($_SESSION); echo ACCOUNT_LEVEL_AWPLUS."</pre>";
		if(isset($_SESSION['AccountLevel']) && $_SESSION['AccountLevel'] != ACCOUNT_LEVEL_AWPLUS)
			//$attrs = " onclick=\"showMessagePopup('error', 'Please upgrade', 'This feature is only available to AwardWallet Plus users. Please consider <a href=/user/pay.php>upgrading your account</a>.'); return false;\"";
		?>
		<!-- show coupons -->
		<!-- show inactive coupons -->
		<? if (!isBusinessMismanagement()){ ?>
		<tr>
			<td><div class="excel"></div></td><td style="padding-left: 7px;"><a href="/account/<?=(SITE_MODE == SITE_MODE_BUSINESS)?"overview.php":"list.php"?>?<?=ImplodeAssoc("=", "&", $excel, true)?>"<?=$attrs?> class="leftMenuLink">Download in Excel</a></td>
		</tr>
		<tr>
			<td><div class="pdf"></div></td><td style="padding-left: 7px;"><a href="http://<?=$_SERVER['HTTP_HOST']?>/account/<?=(SITE_MODE == SITE_MODE_BUSINESS)?"overview.php":"list.php"?>?<?=ImplodeAssoc("=", "&", $pdf, true)?>"<?=$attrs?> class="leftMenuLink">Download in PDF</a></td>
		</tr>
		<? } ?>
	</table>

		</div>
	</div>
	<?
	closeLeftBox();
}

function getPropsByKind(array $props){
	$result = [];
	foreach($props as $prop){
		$result[$prop['Kind']] = $prop['Val'];
	}
	return $result;
}

function getExportKindTitles(){
	global $arPropertiesKinds;
	$kindTitles = $arPropertiesKinds;
	unset($kindTitles[PROPERTY_KIND_OTHER]);
	unset($kindTitles[PROPERTY_KIND_EXPIRATION]);
	unset($kindTitles[PROPERTY_KIND_NUMBER]);
	return $kindTitles;
}

function excelColName($ascii){
	if($ascii > ord('Z'))
		return 'A' . chr($ascii - (ord('Z') - ord('A')));
	else
		return chr($ascii);
}

/**
 *
 * @global <type> $sPath
 * @global <type> $Connection
 * @global <type> $arProviderKind
 * @param <type> $rows
 * @return PHPExcel 
 */
function phpExcelObjectForExport($rows){
	global $sPath, $Connection, $arProviderKind;
	
	require_once "$sPath/lib/3dParty/PHPExcel.php";

	$objPHPExcel = new PHPExcel();
	$objPHPExcel->getProperties()->setCreator("AwardWallet.com")
				     ->setLastModifiedBy("AwardWallet.com")
				     ->setTitle("AwardWallet.com - Accounts")
				     ->setSubject("Accounts")
				     ->setDescription("Visit awardwallet.com for more information")
				     ->setKeywords("awardwallet")
				     ->setCategory("rewards");
	$y = 1;
	$objDrawing = new PHPExcel_Worksheet_Drawing();
	$objDrawing->setName('Logo');
	$objDrawing->setDescription('AwardWallet');
	$objDrawing->setPath($sPath.'/images/export_logo.png');
	$objDrawing->setHeight(50);
	$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
	$objDrawing->setCoordinates('A1');
	$objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(50);
//	$y++;
	$sheet = $objPHPExcel->setActiveSheetIndex(0);
	$sheet->setCellValue('A'.$y, 'Account ID')
			->setCellValue('B'.$y, 'Type')
			->setCellValue('C'.$y, 'Account Owner')
			->setCellValue('D'.$y, 'Award Program')
			->setCellValue('E'.$y, 'Account Number')
			->setCellValue('F'.$y, 'Login Name')
			->setCellValue('G'.$y, 'Balance')
			->setCellValue('H'.$y, 'Last Change')
			->setCellValue('I'.$y, 'Expiration')
			->setCellValue('J'.$y, 'Comments');
	$index = ord('K');
	$kindTitles = getExportKindTitles();
	foreach($kindTitles as $kind => $caption) {
		$sheet->setCellValue(excelColName($index) . $y, $caption);
		$index++;
	}
	$objPHPExcel->getActiveSheet()->getStyle('A'.$y.':'.excelColName($index).$y)->applyFromArray(
		array(
			'font' => array(
				'bold' => true,
			),
		)
	);

	$sheet->getColumnDimension('A')->setWidth(20);
	$sheet->getColumnDimension('B')->setWidth(20);
	$sheet->getColumnDimension('C')->setWidth(30);
	$sheet->getColumnDimension('D')->setWidth(20);
	$sheet->getColumnDimension('E')->setWidth(25);
	$sheet->getColumnDimension('F')->setWidth(20);
	$sheet->getColumnDimension('G')->setWidth(15);
	$sheet->getColumnDimension('H')->setWidth(15);
	$sheet->getColumnDimension('I')->setWidth(80);
	$sheet->getColumnDimension('J')->setWidth(80);
	$index = ord('K');
	foreach($kindTitles as $kind => $caption) {
		$sheet->getColumnDimension(excelColName($index))->setWidth(15);
		$index++;
	}
	$y++;
    $arProviderKind['SubAccount'] = '';
	foreach($rows as $row){
		$props = getPropsByKind(ArrayVal($row, 'Properties', []));
		if(!isset($row['FormattedBalance']))
			$row['FormattedBalance'] = $row['Balance'];
		$expirationDate = $row['ExpirationDate'];
		if($row['ExpirationDate'] != '')
			$row['ExpirationDate'] = date(DATE_FORMAT, $Connection->SQLToDateTime($row['ExpirationDate']));
		$isNA = is_null($row['Balance']);
		if(is_null($row['Balance']) || is_null($row['LastBalance']))
			$lastChange = '';
		else
			$lastChange = $row['Balance'] - $row['LastBalance'];
		if (!$isNA && array_key_exists('TableName', $row) && 'Coupon' == $row['TableName']) {
		    $isNA = true;
		    $lastChange = '';
        }
		$sheet
					->setCellValue('A'.$y, $row['ID'])
					->setCellValue('B'.$y, ArrayVal($arProviderKind, $row['Kind'], "Custom"))
					->setCellValue('C'.$y, htmlspecialchars_decode($row['Kind'] == 'SubAccount' ? "" : $row['UserName']))
					->setCellValue('D'.$y, htmlspecialchars_decode($row['DisplayName']))
					->setCellValue('E'.$y, $row['Kind'] == 'SubAccount' ? "" : "".ArrayVal($row, 'Number')." ")
					->setCellValue('F'.$y, $row['Kind'] == 'SubAccount' ? "" : htmlspecialchars_decode($row['Login']))
					->setCellValue('G'.$y, $row['FormattedBalance'])
					->setCellValue('H'.$y, $lastChange)
					->setCellValue('I'.$y, $row['ExpirationDate'])
					->setCellValue('J'.$y, ArrayVal($row, 'comment'));
		$index = ord('K');
		foreach($kindTitles as $kind => $caption) {
			$sheet->setCellValue(excelColName($index).$y, ArrayVal($props, $kind));
			$sheet->getCell(excelColName($index).$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
			$index++;
		}
		$sheet->getCell('A'.$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->getCell('B'.$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->getCell('C'.$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->getCell('D'.$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->getCell('E'.$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->getCell('F'.$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->getCell('G'.$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->getCell('H'.$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->getCell('I'.$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->getCell('J'.$y)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);

//		$numberFormat = '[GREEN]+# ##0.00;[RED]-# ##0.00;0.00';
		//Set last change
        if (0 !== (int) $lastChange) {
            $cell = $objPHPExcel->getActiveSheet()->getCell('H' . $y);
            $cell->setValueExplicit($lastChange, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $objPHPExcel->getActiveSheet()->getStyle($cell->getCoordinate())->getNumberFormat()->setFormatCode("+##0.00;-##0.00;0.00");
        }

        $objPHPExcel->getActiveSheet()->getStyle('G' . $y)->applyFromArray([
            'alignment' => [
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
            ],
        ]);
		//Set balance
		if (!$isNA) {
			$cell = $objPHPExcel->getActiveSheet()->getCell('G'.$y);
			$cell->setValueExplicit($row['Balance'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$objPHPExcel->getActiveSheet()->getStyle( $cell->getCoordinate() )->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
		}
		//Convert dates
		$saveTimeZone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$cell = $objPHPExcel->getActiveSheet()->getCell('I'.$y);
		if (($expirationDate = strtotime($expirationDate)) !== false && ($expirationDate = PHPExcel_Shared_Date::PHPToExcel($expirationDate)) !== false) {
			$cell->setValueExplicit($expirationDate, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$objPHPExcel->getActiveSheet()->getStyle( $cell->getCoordinate() )->getNumberFormat()->setFormatCode('mm\/dd\/yyyy');
		}
		date_default_timezone_set($saveTimeZone);

		if($lastChange > 0)
			$objPHPExcel->getActiveSheet()->getStyle('H'.$y)->applyFromArray(array(
				'font' => array(
					'color' => array('rgb' => '4dbfa2')
				)
			));
		if($lastChange < 0)
			$objPHPExcel->getActiveSheet()->getStyle('H'.$y)->applyFromArray(array(
				'font' => array(
					'color' => array('rgb' => '4684c4')
				)
			));
		if(in_array(ArrayVal($row, 'ExpirationState'), array('soon')))
			$objPHPExcel->getActiveSheet()->getStyle('I'.$y)->applyFromArray(array(
				'font' => array(
					'color' => array('rgb' => 'ee9101')
				)
			));
		if(in_array(ArrayVal($row, 'ExpirationState'), array('expired')))
			$objPHPExcel->getActiveSheet()->getStyle('I'.$y)->applyFromArray(array(
				'font' => array(
					'color' => array('rgb' => 'e60405')
				)
			));
		if(in_array(ArrayVal($row, 'ExpirationState'), array('far')))
			$objPHPExcel->getActiveSheet()->getStyle('I'.$y)->applyFromArray(array(
				'font' => array(
					'color' => array('rgb' => '00971c')
				)
			));
		$y++;
	}
	$y--;
	$objPHPExcel->getActiveSheet()->setTitle("AwardWallet.com - Accounts");
	$objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&G&C&HAwardWallet.com - Accounts for '.$_SESSION['FirstName'].' '.$_SESSION['LastName']);
//	$objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&[Picture]');
	$objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objPHPExcel->getProperties()->getTitle() . '&RPage &P of &N');
	//$objDrawing = new PHPExcel_Worksheet_HeaderFooterDrawing();
	//$objDrawing->setName('AwardWallet logo');
	//$objDrawing->setPath($sPath.'/images/logo.jpg');
	//$objDrawing->setHeight(31);
	//$objPHPExcel->getActiveSheet()->getHeaderFooter()->addImage($objDrawing, PHPExcel_Worksheet_HeaderFooter::IMAGE_HEADER_LEFT);
	$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
	$objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	$objPHPExcel->getActiveSheet()->getPageSetup()->setPrintArea("A1:".excelColName(ord('K') + count($kindTitles) - 1)."{$y}");
	$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
	$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
	$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToPage(0);
	$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight(0);

	$objPHPExcel->setActiveSheetIndex(0);

	return $objPHPExcel;
}

function ExportExcel($rows){
	global $sPath, $Connection, $arProviderKind;
	ob_clean();
	AuthorizeUser();
	$objPHPExcel = phpExcelObjectForExport($rows);
	require_once "$sPath/lib/3dParty/PHPExcel/IOFactory.php";
	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="AwardWallet.com - Accounts for '.$_SESSION['FirstName'].' '.$_SESSION['LastName'].'.xls"');
//	header('Cache-Control: max-age=0');
    header_remove('Pragma');

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('php://output');
	exit();
}

function ExportPDF($rows){
	global $sPath, $Connection, $arProviderKind;
	ob_clean();
	
	include_once("$sPath/lib/3dParty/ufpdftable/lib/pdftable.inc.php");
	define('FPDF_FONTPATH', "$sPath/lib/3dParty/ufpdftable/font/");
	AuthorizeUser();
	
	error_reporting(0);
	
	$pdf = new PDFTable('L','pt');
	$pdf->Open();
	$pdf->SetTitle("AwardWallet.com - Accounts");
	$pdf->SetAuthor('AwardWallet.com');
	$pdf->SetCreator('AwardWallet.com');
	$pdf->setSubject("Accounts");
	$pdf->AddFont('arial', '', 'arial.php');
	$pdf->AddFont('arial', 'b', 'ariblk.php');
	$pdf->AddPage();
	$pdf->SetFont('arial', '', 11);
	
	$pdf->Image("$sPath/images/export_logo.png",279,25, null, 48);
	$pdf->SetY(100);
	$kindTitles = [PROPERTY_KIND_STATUS => "Status"];
	$table = "	
	<table width=100%' border=1>
		<tr> 			
			<td style='b'>Type</td>
			<td style='b' align='center'>Account Owner</td>
			<td style='b'>Award Program</td>
			<td style='b'>Account Number</td>			
			<td style='b' align='center'>Login Name</td>			
			<td style='b'>Balance</td>			
			<td style='b'>Last Change</td>			
			<td style='b'>Expiration</td>
			<td style='b'>".implode("</td><td style='b'>", $kindTitles)."</td>
		</tr>
	";
	

	foreach($rows as $row){
		$props = getPropsByKind(ArrayVal($row, 'Properties', []));

		if(!isset($row['FormattedBalance']))
			$row['FormattedBalance'] = $row['Balance'];
		if($row['ExpirationDate'] != '')
			$row['ExpirationDate'] = date(DATE_FORMAT, $Connection->SQLToDateTime($row['ExpirationDate']));
		
		$Color = $exColor = $lcColor = '000000';
		
		if(preg_match("/^\+/ims", ArrayVal($row, 'LastChange')))
			$lcColor = '4dbfa2';
		if(preg_match("/^\-/ims", ArrayVal($row, 'LastChange')))
			$lcColor = '4684c4';
			
		if(in_array(ArrayVal($row, 'ExpirationState'), array('soon')))
			$exColor = 'ee9101';
		if(in_array(ArrayVal($row, 'ExpirationState'), array('expired')))
			$exColor = 'e60405';
		if(in_array(ArrayVal($row, 'ExpirationState'), array('far')))
			$exColor = '00971c';

        $arProviderKind['SubAccount'] = '';
        if($row['Kind'] == 'SubAccount'){
            $table .= "
                <tr>
                    <td color='#".$Color."' colspan='2'></td>
                    <td color='#".$Color."' colspan='3'>".htmlspecialchars_decode($row['DisplayName'])."</td>
                    <td color='#".$Color."'>".$row['FormattedBalance']."</td>
                    <td color='#".$lcColor."'>".ArrayVal($row, 'LastChange')."</td>
                    <td color='#".$exColor."'>".$row['ExpirationDate']."</td>";
			foreach($kindTitles as $kind => $title)
            	$table .= "<td color='#".$exColor."'>".ArrayVal($props, $kind)."</td>";
			$table .= "</tr>";
        } else {
            $table .= "
                <tr>
                    <td color='#".$Color."'>".ArrayVal($arProviderKind, $row['Kind'], "Custom")."</td>
                    <td color='#".$Color."'>".htmlspecialchars_decode($row['UserName'])."</td>
                    <td color='#".$Color."'>".htmlspecialchars_decode($row['DisplayName'])."</td>
                    <td color='#".$Color."'>".ArrayVal($row, 'Number')."</td>
                    <td color='#".$Color."'>".str_replace('<', '&lt;', htmlspecialchars_decode($row['Login']))."</td>
                    <td color='#".$Color."'>".$row['FormattedBalance']."</td>
                    <td color='#".$lcColor."'>".ArrayVal($row, 'LastChange')."</td>
                    <td color='#".$exColor."'>".$row['ExpirationDate']."</td>";
					foreach($kindTitles as $kind => $title)
						$table .= "<td color='#".$exColor."'>".ArrayVal($props, $kind)."</td>";
					$table .= "</tr>";
        }

		
	}
	$table .= "		
	</table>	
	";
	//echo $table; die();
	$pdf->htmltable($table);
	
	$pdf->Close();
	
	ob_clean();
	header('Content-Type: application/pdf');
	header('Content-Disposition: attachment;filename="AwardWallet.com - Accounts for '.$_SESSION['FirstName'].' '.$_SESSION['LastName'].'.pdf"');
	header('Cache-Control: max-age=0');
	$pdf->Output('AwardWallet.com - Accounts for '.$_SESSION['FirstName'].' '.$_SESSION['LastName'].'.pdf', 'I');
	exit();
}