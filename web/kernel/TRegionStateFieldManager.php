<?php

// -----------------------------------------------------------------------
// Table Links Field manager class.
//		Contains class, to handle sub-tables
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

class TRegionStateFieldManager extends TAbstractFieldManager
{
	var $CountryField;
	var $DBFieldName;

	// initialize field
	function CompleteField()
	{
		if( !is_array($this->CountryField) ){
			if(!isset( $this->Form->Fields[$this->CountryField] ))
				DieTrace( "CountryField not set or does not exist in form" );
			$this->CountryField = &$this->Form->Fields[$this->CountryField];
		}
		$this->Field["CheckScripts"] = True;
		$this->Field["MultiSelect"] = False;
		$this->Field["Rows"] = 1;
		$this->CheckCountry();
		if( !isset( $this->DBFieldName ) )
			$this->DBFieldName = $this->FieldName;
	}

	// check field. return NULL or error message. called only when field is checked.
	function Check( &$arData )
	{
		if( isset( $this->Field["OnGetRequired"] ) )
		{
			if( is_array( $this->Field['OnGetRequired'] ) )
				$bRequired = CallUserFunc( array_merge( $this->Field['OnGetRequired'], array( $this->FieldName, &$this->Field ) ) );
			else
				$bRequired = CallUserFunc( array( $this->Field['OnGetRequired'], $this->FieldName, &$this->Field ) );
		}
		else
			$bRequired = $this->Field["Required"];
		if( $bRequired && !isset( $this->Field["Value"] ) )
			return "Value required";
		if( isset( $this->Field["Value"] ) && ( $this->Field["InputType"] == "select" ) && !isset( $this->Field["Options"][$this->Field["Value"]] ) )
			return "Invalid option";
		return NULL;
	}

	// load state options, set input attributes depending on country
	function CheckCountry()
	{
		if( !isset( $this->CountryField["Value"] ) )
			$sCountryCode = "";
		else
		{
			$sCountryCode = $this->CountryField["Value"];
			if( !isset( $this->CountryField["Options"][$sCountryCode] ) )
				$sCountryCode = "";
		}
		if( $sCountryCode <> "" )
		{
			// country have states, select state from dropdown list
			$arStateOptionAttributes = array();
			$arStateOptions = $this->LoadStateOptions( $sCountryCode, $arStateOptionAttributes );
			$this->Field["Options"] = $arStateOptions;
			$this->Field["OptionAttributes"] = $arStateOptionAttributes;
		}
		else{
			$this->Field["Options"] = array("" => "None");
		}
	}

	// load state options and attributes. returns state array
	static function LoadStateOptions( $sCountryCode, &$arOptionAttributes = NULL )
	{
		$arResult = array("" => "None")
		+ SQLToArray("select distinct State, StateName as Name
				from AirCode where State is not null and State <> ''
				and StateName is not null and StateName <> ''
				and CountryCode = '".addslashes($sCountryCode)."'
				and CountryCode is not null and CountryCode <> '+' and CountryCode <> ''
				order by StateName", "State", "Name");
		return $arResult;
	}

	// set field values, from database
	// warning: field names comes in lowercase
	function SetFieldValue( $arValues )
	{
		$sValue = $arValues[strtolower( $this->DBFieldName )];
		$this->CheckCountry();
		if( isset( $sValue ) )
		{
			$this->Field["Value"] = $sValue;
		}
	}

	// load post data to field. called on every post.
	function LoadPostData( &$arData )
	{
		$this->CheckCountry();
		parent::LoadPostData( $arData );
	}

/*	// get addional sql parameters, for update or insert call.
	function GetSQLParams( &$arFields, $bInsert )
	{
		global $Connection;
		$sStateCode = $this->Field["Value"];
		if( !isset( $sStateCode ) )
			$sStateCode = "null";
		$arFields[$this->FieldName] = $sStateCode;
	}*/

}
