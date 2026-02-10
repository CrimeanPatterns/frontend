<?

use AwardWallet\MainBundle\Security\StringSanitizer;

require_once( "start.php" );
require_once( "$sPath/kernel/TForm.php" );
require_once( "$sPath/lib/classes/TFileFieldManager.php" );
require_once( "$sPath/lib/classes/TBaseImporter.php" );

$objSchema = getSymfonyContainer()->get(\AwardWallet\Manager\SchemaFactory::class)->getSchema($_GET["Schema"]);
$objSchema->Admin = True;
drawHeader("Import " . $objSchema->TableName);
if(isset($objSchema->Description) && $objSchema->Description != "")
	print $objSchema->DrawDescription();
	
define( 'MATCH_KEYFIELD', 0 );
define( 'MATCH_1FIELD', 1 );
define( 'MATCH_2FIELDS', 2 );

switch (ArrayVal($_POST, 'Step', 'Upload' ) ){
	case "Upload":
		$objFileManager = new TFileFieldManager();
		$objFileManager->Extensions = array("txt", "csv");
		$objFileManager->Dir = "/images/uploaded/temp";
		$objFileManager->ShowUploadButton = false;
        $objFileManager->MaxSize = 64 * 1024 * 1024;
		$objForm = new TBaseForm( array(
			"CSVFile" => array(
				"Type" => "string",
				"Caption" => "Tab-delimited File",
				"Required" => True,
				"Manager" => $objFileManager,
			),
			"Step" => array(
				"Type" => "string",
				"InputType" => "html",
				"IncludeCaption" => false,
				"HTML" => "<input type=hidden name=Step value=Upload>",
			),
			"Match" => array(
				"Type" => "integer",
				"Caption" => "Match rows",
				"Required" => true,
				"InputType" => "radio",
				"Options" => array( 
					MATCH_KEYFIELD => "Match using key field ($objSchema->KeyField)",
					MATCH_1FIELD => "Match using first column",
					MATCH_2FIELDS => "Match using first two columns",
				),
				"Value" => MATCH_KEYFIELD,
			),
		) );
		$objForm->SubmitButtonCaption = "Parse";
		if( $objForm->IsPost && $objForm->Check() ){
			echo "<h2>Processing file " . basename( $objFileManager->OriginalFilename ) . "</h2>";
			echo "<form method=post>";
            echo "<input type='hidden' name='FormToken' value='" . GetFormToken() . "'>";
			echo "<input type=hidden name=Step value=Import>";
			$list = getSymfonyContainer()->get(\AwardWallet\Manager\ListFactory::class)->get($_GET['Schema']);
			$objImporter = new TBaseImporter();
			$objImporter->Import($objSchema, $list, $objForm->Fields["Match"]["Value"], LoadFile( $sPath . $objFileManager->FileURL() ), strtolower(FileExtension($objFileManager->FileURL())));
			echo "</form><hr><a href=\"import.php?Schema={$objSchema->Name}\">Import another file</a>";
		}
		if( !$objForm->IsPost || isset( $objForm->Error ) ){
?>
Please select a tab delimited file to import. You can <a href="export.php?Schema=<?= StringSanitizer::encodeHtmlEntities($_GET["Schema"])?>">export a sample tab delimited file here</a> if you wish. <br>
The first row in this file must contain column names as they appear in the database, so please do not change the names of the headings. You may however remove some columns that you do not wish to work with.  
<br>
<br>
<?
			echo $objForm->HTML();
		}
		break;
	case "Import":
		echo "<h2>Applying changes</h2>";
        $dbConnection = getSymfonyContainer()->get('database_connection');
		$nChangeCount = intval( $_POST["ChangeCount"] );
		$nApplied = 0;
		$arKeyFields = explode(",",$_POST["KeyFields"]);
		$arFieldNames = explode( ",", $_POST["Fields"] );
		for( $n = 0; $n < $nChangeCount; $n++ ){
			if( ArrayVal( $_POST, "Change{$n}" ) == "1" ){
				$nKeyValue = intval( $_POST["ID$n"] );
				$arValues = array();
				foreach ( $arFieldNames as $sField ){
                    $sValue = ArrayVal( $_POST, "Change{$n}{$sField}New" );
                    $sField = $dbConnection->quoteIdentifier($sField);
                    if( $sValue != "" )
						$arValues[$sField] = $dbConnection->quote($sValue);
				}
				if( !isset( $_POST["Change{$n}{$arKeyFields[0]}Old"] ) )
					$sSQL = "insert into {$objSchema->TableName}( ".implode(",", array_keys($arValues))." ) values( ".implode(",",$arValues)." )";
				else{
					$arKeyValues = array();
					foreach ( $arKeyFields as $sField ) {
                        $sValue = $dbConnection->quote($_POST["Change{$n}{$sField}Old"]);
                        $sField = $dbConnection->quoteIdentifier($sField);
                        $arKeyValues[$sField] = $sValue;
                    }

					$sSQL = "update {$objSchema->TableName} set ".ImplodeAssoc( " = ", ", ", $arValues )." where " . ImplodeAssoc( "=", " and ", $arKeyValues );
				}
				echo $sSQL." - ";
				if( !$Connection->Execute( $sSQL, false ) ){
					echo "failed<br>\n";
					echo "<div class=formError>Error: ".$Connection->GetLastError()."</div>";
				}
				else 
					echo "ok<br>\n";
			}
		}
		echo "<br><br>Finished.";
		break;
}

drawFooter();

echo "<br><br><a href=\"list.php?Schema=".urlencode($QS["Schema"])."\">Back to list</a>";

?>
