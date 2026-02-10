<?

require_once(__DIR__ . "/ProviderPhone.php");

class TDealSchema extends TBaseSchema
{
	function TDealSchema(){
		global $arProviderKind, $arProviderState, $arDeepLinking;
		parent::TBaseSchema();
		$this->TableName = "Deal";
		$this->ListClass = "DealList";
		$this->Fields = array(
            "DealID" => array(
                "Caption" => "id",
                "Type" => "integer",
                "InputAttributes" => " readonly",
                "filterWidth" => 30),
            "ProviderID" => array(
                "Caption" => "Provider",
                "Type" => "integer",
                "Required" => True,
                "Options" => SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
                "InplaceEdit" => False,
                "InputAttributes" => "onChange='$(\"#fldAutologinProviderID\").val($(\"#fldProviderID\").val())'"
            ),
            "Title" => array(
                "Type" => "string",
                "Size" => 200,
                "Cols" => 40,
                "Required" => True ),
            "Link" => array(
                "Caption" => "Regestration Link",
                "Type" => "string",
                "Size" => 2048,
                "Cols" => 80,
            ),
            "DealsLink" => array(
                "Caption" => "Deals Link",
                "Type" => "string",
                "Size" => 2048,
                "Cols" => 80
            ),
            "AffiliateLink" => array(
                "Caption" => "Affiliate Link",
                "Type" => "string",
                "Size" => 2048,
                "Cols" => 80
            ),
            "AutologinProviderID" => array(
                "Caption" => "Deal Provider Autologin",
                "Type" => "integer",
                "Required" => True,
                "Options" => SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
                "InplaceEdit" => False,
            ),
            "TimesClicked" => array(
                "Type" => "integer",
                "Cols" => 10,
                "filterWidth" => 30,
                "InputAttributes" => " readonly",
                "Value" => 0,
                ),
            "Description" => array(
                "Type" => "string",
                "Size" => 2000,
                "InputType" => "htmleditor",
                "HTML" => true,
                "Required" => False ),
            "DealRegionID" => array(
                "Caption" => "Deal Region",
                "Type" => "integer",
                "Database" => false,
            ),
            "BeginDate" => array(
                "Type" => "date",
                "Required" => True,
            ),
            "EndDate" => array(
                "Type" => "date",
                "Required" => True,
            ),
            "ButtonCaption" =>array(
              "Type" => 'string',
              "Required" => True,
              "Value" => 'Register'
            ),
            "Source" =>array(
              "Type" => 'string',
              "Required" => True
            ),
		);
	}
	
	function GetListFields(){
		$arFields = parent::GetListFields();
		unset($arFields["Description"]);
		unset($arFields["DealRegionID"]);
		unset($arFields["DealsLink"]);
        unset($arFields["AffiliateLink"]);
		unset($arFields["Link"]);
		unset($arFields["ButtonCaption"]);
		unset($arFields["AutologinProviderID"]);
		$arFields["Active"] = array (
			"Type" => "integer",
			"Caption" => "Active",
			"InplaceEdit" => false,
			"Database" => false,
		);

		return $arFields;
	}

	public static function GetCategoryExplorer()
	{
		$objCategoryExplorer = new TCategoryExplorer();
		$objCategoryExplorer->Table = "Region";
		$objCategoryExplorer->ItemField = "RegionID";
		$objCategoryExplorer->NameExpression = "coalesce(Region.Name, rco.Name, rs.Name)";
		$objCategoryExplorer->Joins = "left outer join Country rco on rco.CountryID = Region.CountryID
		left outer join State rs on rs.StateID = Region.StateID";
		$objCategoryExplorer->ParentField = "SubRegionID";
		$objCategoryExplorer->Init();
		return $objCategoryExplorer;
	}

	function GetFormFields()
	{
		$arFields = $this->Fields;
		// get regions
		$objCategoryExplorer = $this->GetCategoryExplorer();		
		// regions
		$objCategoryManager = new TRegionLinksFieldManager();
		$objCategoryManager->TableName = "DealRegion";
		//$objCategoryManager->selectParents = true;
		$objCategoryManager->CategoryExplorer = $objCategoryExplorer;
		$arFields["DealRegionID"]["Manager"] = $objCategoryManager;
		
		$objManager = new TTableLinksFieldManager();
		$objManager->TableName = "DealRelatedProvider";
		$objManager->Fields = array(			
			"ProviderID" => array(
				"Caption" => "Provider",
				"Type" => "integer",
				"Required" => True,
				"Options" => SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
			),
		);
		
		ArrayInsert($arFields, 'DealRegionID', true, array('RelatedProviderID'=>array("Caption" => "Related Providers", 'Manager'=>$objManager) ));
		
		return $arFields;
	}
    
    function TuneForm(\TBaseForm $form){
        if ( $this->id == 0 ) {
			$form->SQLParams = array( "CreateDate" => "now()" );
		}
    }
		
}
?>
