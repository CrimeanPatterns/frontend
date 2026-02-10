<?
class TAccountForm  {

	public static function getLoginFieldsDefs($providerFields, $showReveal, $id, $checkInBrowser, $showSavePassword, $loginAttrs){
		global $SAVE_PASSWORD;
		$arFormFields = array();
		$arFormFields["ChromeAutoFillCatcher"] = [
			'Database' => false,
			'Type' => 'string',
			'InputType' => 'html',
			'IncludeCaption' => false,
			'HTML' => '<tr style="display: none"><td colspan="4"><input name="lcatch" type="text"/><input type="password" name="pcatch"/></td></tr>',
		];
		$arFormFields["Login"] = array(
				"Caption" => "Login",
				"Note" => $providerFields["LoginCaption"],
				"Type" => "string",
				"Size" => $providerFields["LoginMaxSize"],
				"MinSize" => $providerFields["LoginMinSize"],
				"Cols" => $providerFields["LoginMaxSize"]+2,
				"InputAttributes" => " onfocus=\"this.removeAttribute('readonly'); \" readonly autocomplete='off' style='width: 280px; cursor:text;' $loginAttrs",
				"NoWrap" => false,
				"HTML" => true,
				"Database" => ($checkInBrowser != CHECK_IN_CLIENT),
				"Required" => True );
		if( $providerFields["Login2Caption"] != "" ){
			$arFormFields["Login2"] = array(
				"Caption" => $providerFields["Login2Caption"],
				"Type" => "string",
				"Size" => $providerFields["Login2MaxSize"],
				"MinSize" => $providerFields["Login2MinSize"],
				"Cols" => $providerFields["Login2MaxSize"]+2,
				"InputAttributes" => " autocomplete='off' style='width: 280px;' $loginAttrs",
				"Database" => ($checkInBrowser != CHECK_IN_CLIENT),
				"HTML" => true,
				"Required" => (int)$providerFields['Login2Required'] > 0 );
			if(preg_match('/^([^\(]+)\(([^\)]+)\)$/ims', $arFormFields["Login"]["Note"], $arMatches)){
				$arFormFields["Login"]["Caption"] = $arMatches[1];
				$arFormFields["Login"]["Note"] = $arMatches[2];
			}
			else{
				$arFormFields["Login"]["Caption"] = $arFormFields["Login"]["Note"];
				unset($arFormFields["Login"]["Note"]);
			}
		}
		if($providerFields['PasswordRequired'] == '1'){
			if($providerFields["PasswordCaption"] != "Password")
				$note = $providerFields["PasswordCaption"];
			else
				$note = "";
			if($note != "")
				$note .= " | ";
			if(isGranted('ROLE_IMPERSONATED'))
				$note .= "<a href=# onclick=\"requestPassword( 'fldPass', this, $id ); return false;\">Request password</a>";
			elseif($showReveal)
				$note .= "<a href=# onclick=\"revealPassword( 'fldPass', this, $id ); return false;\">Reveal password</a>";
			$arFormFields["Pass"] = array(
				 "Caption" => "Password",
				 "Note" => $note,
				 "Type" => "string",
				 "InputType" => "password",
				 "InputAttributes" => " onfocus=\"this.removeAttribute('readonly')\" readonly autocomplete=\"off\" style='width: 280px; cursor:text;'",
				 "MinSize" => $providerFields["PasswordMinSize"],
				 "Size" => max($providerFields["PasswordMaxSize"], 8),
				 "Cols" => $providerFields["PasswordMaxSize"]+2,
				 "Database" => false,
				 "HTML" => true,
                 "Required" => True );
			if($checkInBrowser != CHECK_IN_CLIENT && $showSavePassword && $providerFields['Code'] != 'aa')
				$arFormFields["SavePassword"] = array(
					 "Caption" => "Save password",
					 "Note" => "You may optionally choose to store your award program passwords locally on this computer, if you do so and switch computers you will need to re-enter the passwords again",
					 "Type" => "integer",
					 "InputType" => "select",
					 "Options" => $SAVE_PASSWORD,
					 "Required" => True,
					 "InputAttributes" => "onchange='savePasswordChanged(this)' style='width: 288px;'",
					 "Value" => ArrayVal($_SESSION['UserFields'],'SavePassword',SAVE_PASSWORD_DATABASE) );
		}
		return $arFormFields;
	}

	public static function GetUserAgentIDDef($nUserAgentID){
		$arField = array(
			'Caption' => 'Account Owner',
			'Type' => 'integer',
			'Options' => array(
				'' => getNameOwnerAccountByUserFields($_SESSION['UserFields']),
			),
			"InputAttributes" => "onchange='clientChanged(this);' style='width: 288px;'",
			"Note" => "Please choose whose loyalty program this is.<br>You can <a href=\"#\" onclick=\"showPopupWindow(document.getElementById('newAgentPopup')); return false;\">add a new person</a> if necessary.",
		);

		$qAgents = self::getOwnersQuery();
		while(!$qAgents->EOF){
			$arField['Options'][$qAgents->Fields['UserAgentID']] = getNameOwnerAccountByUserFields($qAgents->Fields);
			$qAgents->Next();
		}
		$arField['Options']["+"] = "Add a new person";
		if($nUserAgentID > 0)
			$arField['Value'] = $nUserAgentID;
		if( isset( $_SESSION['LastUserAgentID'] ) ){
			$arField['Value'] = $_SESSION['LastUserAgentID'];
			unset( $_SESSION['LastUserAgentID'] );
		}
		return $arField;
	}

	private static function getOwnersQuery(){
        $filter = "";
        if (SITE_MODE != SITE_MODE_BUSINESS) {
            $filter = " and c.AccountLevel <> ".ACCOUNT_LEVEL_BUSINESS."";
        }
		$qAgents = new TQuery("select
			ua.UserAgentID,
			ua.ClientID,
			coalesce( c.FirstName, ua.FirstName ) as FirstName,
			coalesce( c.LastName, ua.LastName ) as LastName,
			c.AccountLevel,
			c.Company
		from UserAgent ua left outer join Usr c on ua.ClientID = c.UserID
			where ua.IsApproved = 1 and
			( ( ua.AgentID = {$_SESSION['UserID']} and ua.ClientID is null )
			or ( ua.AgentID = {$_SESSION['UserID']} and ua.ClientID is not null and ua.AccessLevel in (".ACCESS_WRITE.", ".ACCESS_ADMIN.", ".ACCESS_BOOKING_MANAGER.", ".ACCESS_BOOKING_VIEW_ONLY.") $filter) )
		order by case when c.AccountLevel = ".ACCOUNT_LEVEL_BUSINESS." then c.Company else concat(coalesce( c.FirstName, ua.FirstName ), coalesce( c.LastName, ua.LastName )) end");
		return $qAgents;
	}

}
