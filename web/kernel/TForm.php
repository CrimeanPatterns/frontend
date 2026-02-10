<?php

// -----------------------------------------------------------------------
// Forms class.
//		Contains form class, to handle input forms
//		interface methods: FormatHTML, FormatRowHTML
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

require_once( __DIR__."/../lib/classes/TBaseForm.php" );
require_once( __DIR__."/../lib/classes/TBaseFormEngConstants.php" );

class TForm extends TBaseForm
{
	var $showRequiredWarning = true;
	public $Footer;
	public $ButtonsAlign = "center";
	public $SuccessMessage;
	public $Warning;
	public $Message;

	function CompleteField($sFieldName, &$arField){
		parent::CompleteField($sFieldName, $arField);
		if( !isset( $arField["FullRow"] ) )
			$arField["FullRow"] = false;
        switch($arField["InputType"]){
            case 'htmleditor': {
                if(isset($arField['htmleditorConfigFile']))
                    $arField['Manager']->configFile = $arField['htmleditorConfigFile'];
				if(isset($arField['htmleditorCustomConfig']))
					$arField['Manager']->customConfig = $arField['htmleditorCustomConfig'];
            } break;
        }
	}

	public function DrawMessages(){
		global $Interface;
		$sHTML = '';
		if( isset( $this->Warning ) && isGranted("SITE_ND_SWITCH") ){
			$sHTML = "<tr class='html'><td colspan='4'><div class='warning-message silver'><i class='icon-warning-b'></i><p>".$this->Warning."</p></div></td></tr>\n$sHTML";
		}
		if( isset( $this->Message ) && isGranted("SITE_ND_SWITCH") ){
			$sHTML = "<tr class='html'><td colspan='4'><div class='success-message center'><i class='icon-green-check-b'></i><p>".$this->Message."</p></div></td></tr>\n$sHTML";
		}
		if( isset( $this->Error ) )
			if(isGranted("SITE_ND_SWITCH")){
				$sHTML = "<tr class='html'><td colspan='4'><div class='error-message-blk'><i class='icon-warning-small'></i><p>".$this->Error."</p></div></td></tr>\n$sHTML";
			}else{
				$sHTML = "<tr><td colspan='4' class='errorMessage'><div class=\"icon\"></div><div class=\"text\">".$this->Error."</div></td></tr>\n$sHTML";
			}
		if( isset( $this->SuccessMessage ) ) {
			ob_start();
			$Interface->DrawMessage($this->SuccessMessage, "success");
			$sHTML = "<tr><td colspan='4'>" . ob_get_clean() . "</td></tr>\n$sHTML";
		}
		return $sHTML;
	}

	// formats form html
	// override to implement your own formatting
	function FormatHTML( $sHTML, $bExistsRequired ){
		$sHTML = $this->DrawMessages(). $sHTML;
		$Result = "<form class=editor_form ".($this->AutoComplete?"":" autocomplete=\"off\"")." method={$this->Method}".(isset($this->Action)?" action=\"".htmlspecialchars($this->Action)."\"":"")." enctype=\"multipart/form-data\" name=\"".htmlspecialchars($this->FormName)."\" style='margin-bottom: 0px; margin-top: 0px;'";
		if( isset( $this->SubmitURL ) )
			$Result .= " action='".htmlspecialchars($this->SubmitURL)."'";
		if( $this->SubmitOnce )
			$Result .= " onsubmit='submitonce(this)'";
		if( isset( $this->SubmitURL ) )
			$Result .= " action='".htmlspecialchars($this->SubmitURL)."'";
        $formToken = GetFormToken();
		$Result .= ">
        <input type='hidden' name='FormToken' value='".htmlspecialchars($formToken)."'>
		<table cellspacing='0' cellpadding='5' border='0' class='formTable' id='formTable'>
			<col class=c0>
			<col class=c1>
			<col class=c2>
			<col class=c3>
			$sHTML";
		switch($this->ButtonsAlign){
			case "center":
				$Result .= "<tr><td colspan='4' class='formButtons'><table align='center' cellspacing='0' ".(isGranted("SITE_ND_SWITCH") ? "" : "")."><tr><td>" . $this->ButtonsHTML() . "</td></tr></table></td></tr>\n";
				break;
			case "left":
				$Result .= "<tr><td colspan='2'></td><td colspan='2' class='formButtons'><table align='left' cellspacing='0'><tr><td>" . $this->ButtonsHTML() . "</td></tr></table></td></tr>\n";
				break;
			default:
				DieTrace("unknown ButtonAlign: {$this->ButtonAlign}");
		}
		if( isset( $this->Footer ) ){
			$Result .= "<tr><td colspan='4' class='footer'>
			<div class='hLine' style='margin-bottom: 20px; margin-top: 15px;'></div>
			<table cellspacing='0' cellpadding='0'><tr><td class='icon'><div></div></td><td class='message'>" . $this->Footer . "</td></tr></table></td></tr>\n";
		}
		$Result .= "</table></form>";
		if($this->Title != "")
			$Result = '<div class="boxToll pageNote formHeader" style="padding-bottom: 20px;">
			<div class="top"><div class="left"></div></div>
			<div class="center pad"><div class="redHeader">'.$this->Title.'</div></div>
			<div class="bottom">
				<div class="left"></div>
			</div>
			</div>'.$Result;
		return $Result;
	}

	// returns input html with title
	// override to implement your own formatting
	// $sInput - input html, example: "<input type=text name=Field1>"
	// will be formatted with title
	function FormatRowHTML( $sFieldName, $arField, $sInput )
	{
		$sCaption = $arField["Caption"];
		if( $arField["Required"] && ( $arField["InputType"] != "checkbox" ) )
			$sCaption = "<table cellpadding='0' cellspacing='0'><tr><td>$sCaption</td><td class='asterisk'>*</td></tr></table>";
        $sNote = "";

		if( isset( $arField["Note"] ) ) {
            $sNote .= "<div class=fieldhint id='fld{$sFieldName}Hint'>" . $arField["Note"] . "</div>";
        }

		$classes = $arField['InputType'];
		if(isset($arField['Error']))
			$classes .= " error";
		switch( $arField["InputType"] )
		{
			case "checkbox":
				$s = "<tr class='{$classes}' id='tr$sFieldName'>
				<td class='row rowLeft'><div></div></td>
				<td class='row'></td>\n  <td class='input row'><table border='0' cellpadding='0' cellspacing='0'><tr><td class='checkboxLeft'> $sInput </td><td class='checkboxRight'><label for=\"fld{$sFieldName}\">$sCaption</label> $sNote</td></tr></table></td>
				<td class='row rowRight'><div></div></td>
				</tr>\n";
				break;
            case "hidden":
                $s = "$sInput\n";
                break;
			default:
				if( $sCaption == "" )
					$sCaption = "&nbsp;";
				if($arField['NoWrap'])
					$sWrap = " nowrap";
				else
					$sWrap = "";
				$s = "<tr class='{$classes}' id='tr$sFieldName'>
				<td class='row rowLeft'><div></div></td>
				<td class='caption{$sWrap} row'><a name='tr$sFieldName'></a><label for=\"fld{$sFieldName}\">$sCaption</label></td>\n  <td class='input row'>$sInput$sNote</td>
				<td class='row rowRight'><div></div></td>
				</tr>\n";
				break;
		}

		if($arField['FullRow']){
			$s = "<tr class='{$classes}' id='tr$sFieldName'><td class='row rowLeft'><div></div></td><td class='input row' colspan='2' style='padding-left:0; padding-right:0;'>$sInput</td><td class='row rowRight'><div></div></td></tr>";
		}
		return $s;
	}

	function DrawButton($title, $attributes){
		global $Interface;
		return $Interface->DrawButton($title, $attributes);
	}

	function InputHTML( $sFieldName, $arField = NULL, $bIncludeCaption = False ){
		global $UserSettings;
		if(!isset($arField))
			$arField = $this->Fields[$sFieldName];
		$s = parent::InputHTML($sFieldName, $arField, false);

		global $Config, $Interface;
		if( !isset( $arField ) )
			$arField = $this->Fields[$sFieldName];
		if( isset( $arField["Value"] ) )
			$sValue = $arField["Value"];
		else
			$sValue = "";
		$sAttributes =  $arField["InputAttributes"];
        $sTimeAttributes = $arField["TimeInputAttributes"];
		if( stripos( $sAttributes, "id=" ) === false )
			$sAttributes .= " id=\"fld{$sFieldName}\"";

		switch( $arField["InputType"] ){
			case "text":
			case "password":
			case "select":
				$s = "<table class='inputFrame' cellpadding='0' cellspacing='0'><tr><td class='ifLeft'></td><td class='ifCenter'>$s</td><td class='ifRight'>";

                if (!empty($arField['Widgets'])) {
                    $s .= " <span style='padding-left: 10px;'>" . implode(" ", $arField['Widgets']) . "</span>";
                }

				$s .= "</td></tr></table>";
				break;
			case "textarea":
				$s = '<div class="boxToll">
					<div class="left"></div>
					<div class="right"></div>
					<div class="center pad">
						'.$s.'
					</div>
					<div class="bottom">
						<div class="left"></div>
						<div class="right"></div>
					</div>
				</div>';
				break;
            case "hidden":
                $s = "<input type='hidden' name='".htmlspecialchars($sFieldName)."' value=\"" . htmlspecialchars( $sValue ) . "\"/>";
                break;
			case "date":
				if( isset( $arField["Size"] ) )
					$sAttributes .= " maxlength=" . $arField["Size"];
				if( isset( $arField["Cols"] ) )
					$sAttributes .= " size=" . $arField["Cols"];
				if( !isset( $arField['ReadOnly'] ) ){
					$dateFormat = !empty($UserSettings['datepicker']) ? "dateFormat: '{$UserSettings['datepicker']}'," : '';
                    //$dateFormat = 'dateFormat: "' . getDateTimeFormat(true)['full'] . '",';
					$Interface->FooterScripts[] = "
						dateOptions = {
							name: '_$sFieldName',
							options: {
								/*$dateFormat*/
								showOn: 'both',
								changeMonth: true,
								changeYear: true,
								buttonImage: '/lib/images/calendar3.gif',
								buttonImageOnly: true,
								autoSize: true,
								onClose : function(date, inst) {
								    var d = $.datepicker.formatDate('mm/dd/yy', $(inst.input[0]).datepicker('getDate'));
								    $('input[name=\"_$sFieldName\"]').attr('lang', date);
								    return $('input[name=\"$sFieldName\"]').attr('lang', date).val(d);
								}
								".(isset($arField['DatepickerOptions'])?",".$arField['DatepickerOptions']:"")."								
							}
						}
						allDatepickers.push(dateOptions);

                        if ('undefined' == typeof(region)) {
                            var region = $('a.language[data-region]', 'aside.user-blk').data('region');
                            if (region && region.length) {
                                require(['lib/customizer'], function (customizer) {
                                    var lang = customizer.getLocaleByCountry(region, {mode : 'jqDatepicker'});
                                    if (null !== lang) {
                                        require(['/assets/common/vendors/jqueryui/ui/i18n/jquery.ui.datepicker-' + lang + '.js'], function(o){
                                            dateOptions.options.dateFormat = $.datepicker.regional[lang].dateFormat;
                                            $.datepicker.setDefaults($.datepicker.regional[lang]);
                                        });
                                    }
                                });
                            }
                        }
					";
				}
				else
					$s = "";
                $_date = $sValue;
				$s = "<table border='0' cellpadding='0' cellspacing='0' class='noBorder' id='scw{$sFieldName}Table'>
				<tr>
				<td class='scwDate1'><div class='datepicker'><input type='text' name='".htmlspecialchars($sFieldName)."' value='".htmlspecialchars($sValue)."' style='display:none!important'><input class='inputTxt inputDate' type='text'
				name='_".htmlspecialchars($sFieldName)."' $sAttributes value=\"" . htmlspecialchars( $_date ) . "\"/></div></td>";
                
				if(isset($arField["IncludeTime"]))
					$s .= "<td class='scwDate3'>Time:&nbsp;</td><td class='scwDate4'><input class='inputTxt inputTime' type='text' size='7' id='fld{$sFieldName}Time' name='{$sFieldName}Time' {$sTimeAttributes} value=\"".htmlspecialchars($arField["TimeValue"])."\"/><div class='helpTime'>hh:mm or hh:mm pm</div></td>";
				$s .= "</tr></table>";
				$s = preg_replace("/(<input[^>]+>)/ims", "<table class='inputFrame' cellpadding='0' cellspacing='0'><tr><td class='ifLeft'></td><td class='ifCenter'>\$1</td><td class='ifRight'></td></tr></table>", $s);
				break;
            case "datetime":
				if( isset( $arField["Size"] ) )
					$sAttributes .= " maxlength=" . $arField["Size"];
				if( isset( $arField["Cols"] ) )
					$sAttributes .= " size=" . $arField["Cols"];
                $_date = $sValue;
                if (!empty($sValue)) {
                    $dateFormat = 'Y-m-d H:i:s';
                    $date = date_create_from_format($dateFormat, $_date);
                    if (false !== $date) {
                        $_date = date_format($date);
                    }
                }
				$s = "<table border='0' cellpadding='0' cellspacing='0' class='noBorder' id='scw{$sFieldName}Table'>
				<tr>
				<td class='scwDate1'><div class='datepicker'><input type='text' name='".htmlspecialchars($sFieldName)."' value='".htmlspecialchars($sValue)."' style='display:none!important'><input class='inputTxt inputDate' type='text'
				name='_".htmlspecialchars($sFieldName)."' $sAttributes value=\"" . htmlspecialchars( $_date ) . "\"/></div></td>";

				$s .= "</tr></table>";
				$s = preg_replace("/(<input[^>]+>)/ims", "<table class='inputFrame' cellpadding='0' cellspacing='0'><tr><td class='ifLeft'></td><td class='ifCenter'>\$1</td><td class='ifRight'></td></tr></table>", $s);
				break;
		}
		if( $bIncludeCaption )
			$s = $this->FormatRowHTML( $sFieldName, $arField, $s );
		return $s;
	}


	// return prev, next, update buttons html
	function ButtonsHTML()
	{
		global $Interface;
		$result = "";
		if(isset($this->PrevPage))
			$result .= $Interface->DrawButton("Back", "", 0, "name='prevButton' onclick=\"this.form.DisableFormScriptChecks.value = '1'; this.form.NewFormPage.value = '{$this->PrevPage}'; return CheckForm( document.forms['{$this->FormName}'] );\"");
		if(isset($this->NextPage))
			$result .= $Interface->DrawButton("Continue", "", 0, "name='nextButton' value=\"Continue".($this->ShowPageOnButtons?" to {$this->Pages[$this->NextPage]} Page":"")."\" onclick=\"this.form.NewFormPage.value = '{$this->NextPage}'; return CheckForm( document.forms['{$this->FormName}'] )\"");
		if( !isset( $this->Pages ) || ( $this->ActivePage == $this->LastPage ) )
			$result .= $Interface->DrawButton($this->SubmitButtonCaption, "name='submitButtonTrigger' onclick=\"var form = document.forms['{$this->FormName}']; if( CheckForm( form ) ) { form.submitButton.value='submit'; return true; } else return false;\"");
		if(get_class($this) == 'TForm')
			$result .= '<div style="clear: both; height: 1px;"></div>';
		//"<input class='button' type='submit' name='submitButtonTrigger' value=\"{$this->SubmitButtonCaption}\" onclick=\"if( CheckForm( document.forms['{$this->FormName}'] ) ) { this.form.submitButton.value='submit'; return true; } else return false;\"/>";
		return $result;
	}

	function CheckScripts(){
		global $Interface;
		$Interface->FooterScripts[] = "attachFormEvents('{$this->FormName}');";
		$Interface->FooterScripts[] = "submitonce(document.forms['{$this->FormName}'], true);";
		return parent::CheckScripts();
	}

    function SQLValue($sFieldName, $arField = NULL){
        if( !isset( $arField ) )
            $arField = &$this->Fields[$sFieldName];
        if(isset($arField['Value'])){
            $sValue = $arField["Value"];
            switch($arField["Type"]){
                case "string":
                    if(isset($arField["Encoding"])){
                        switch ($arField["Encoding"]) {
                            case "symfonyPasswordEncoding":
                                return "'" . addslashes( getSymfonyPasswordEncoder()->encodePassword($sValue, null) ) . "'";
                        }
                    }
            }
        }
        return parent::SQLValue($sFieldName, $arField);
    }

	// update form to table row. $arValues - additional parameters
	function Update($arValues = array())
	{
		global $Connection;

		$state = $Connection->getEntityState($this->TableName, $this->ID);
		parent::Update($arValues);
		$Connection->sendUpdateEvent($state);
	}

	function Insert($arValues = array()){
		global $Connection;

		parent::Insert($arValues);
		$Connection->sendInsertEvent($this->TableName);
	}

    function getDateTimeFormat($time = false)
    {
        return getDateTimeFormat($time)['full'];
    }

}
