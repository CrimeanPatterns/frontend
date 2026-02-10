<?
require_once "TForm.php";

class TOneOptionForm extends TForm{

	function FormatRowHTML( $sFieldName, $arField, $sInput ){
		return "<tr><td colspan='4' style='font-weight: bold; color: #363636; padding: 30px 0 20px 0;'>{$arField['Caption']}</td></tr><tr class='' id='tr$sFieldName'>
				<td colspan='4' style='padding: 0;'>$sInput</td>
				</tr>";
	}

	function InputHTML( $sFieldName, $arField = NULL, $bIncludeCaption = False ){
		if( !isset( $arField ) )
			$arField = $this->Fields[$sFieldName];
		if( isset( $arField["Value"] ) )
			$sValue = $arField["Value"];
		else
			$sValue = "";
		$sAttributes =  $arField["InputAttributes"];
		if( stripos( $sAttributes, "id=" ) === false )
			$sAttributes .= " id=\"fld{$sFieldName}\"";
		$s = "";
		foreach( $arField["Options"] as $sKey => $sValue )
		{
			if( isset( $arField["OptionAttributes"] ) && isset( $arField["OptionAttributes"][$sKey] ) )
				$sAttributes = " " . $arField["OptionAttributes"][$sKey];
			else
				$sAttributes = "";
			$s .= "<table cellspacing='0' cellpadding='0' border='0' id='noBorder' style='width: 100%;'><tr><td class='dashBottom' style='width: 1px; padding: 10px 20px 10px 0; vertical-align: middle;'>
			<input $sAttributes type='radio' id='fld".htmlspecialchars("{$sFieldName}_{$sKey}")."' name='".htmlspecialchars($sFieldName)."' $sAttributes value=\"".htmlspecialchars($sKey)."\"";
			if( ( isset( $arField["Value"] ) && ( $arField["Value"] == $sKey ) )
			|| ( !isset( $arField["Value"] ) && ( $sKey == "" ) ) )
				$s .= " checked='checked'";
			$s .= "/></td><td class='dashBottom' style='vertical-align: middle; padding: 13px 0;'><label for='fld".htmlspecialchars("{$sFieldName}_{$sKey}")."'>".$sValue."{$arField["RadioGlue"]}</label></td></tr></table>\n";
		}
		if( $bIncludeCaption )
			$s = $this->FormatRowHTML( $sFieldName, $arField, $s );
		return $s;
	}

}