<?

class TRegionSchema extends TBaseSchema
{
	function TRegionSchema(){
		global $regionKindOptions;
		parent::TBaseSchema();
		$this->TableName = "Region";
		$this->Fields = array(
            "Kind" => array(
                "Type" => "integer",
                "Required" => true,
                "Options" => array("" => "") + $regionKindOptions,
            ),
            "AwardChartID" => array(
                "Type" => "integer",
                "Options" => array("" => "") + SQLToArray("select AwardChartID, Name from AwardChart order by Name", "AwardChartID", "Name"),
            ),
            "AirCode" => array(
                "Caption" => "Airport",
                "Type" => "string",
                "RequiredGroup" => "name",
                "Options" => array("" => "") + SQLToArray("select AirCode, concat(AirCode, ', ', AirName) as Name from AirCode order by Name", "AirCode", "Name"),
            ),
			"Name" => array(
			    "Type" => "string",
				"Required" => false,
				"RequiredGroup" => "name",
				"Size" => 120,
				"InputAttributes" => "style='width: 300px;'",
                "Note" => "leave empty if you selected Airport, Country or State"
			),
            "CountryID"    => [
                "Caption" => "Country",
                "Type"    => "integer",
                "Options" => SQLToArray(
                    "select null as CountryID, null as Name
                    union
                    select `CountryID`, CONCAT(`Name`, ifnull(concat(' (', Code, ')'), '')) as `Name` from Country order by Name",
                    "CountryID",
                    "Name"
                ),
                "Required" => false,
                "RequiredGroup" => "name",
            ],
            "StateID"    => [
                "Caption" => "State",
                "Type"    => "integer",
                "Options" => SQLToArray(
                    "select null as StateID, null as Name
                    union
                    select s.StateID, CONCAT(c.Name, ': ', s.`Name`, ifnull(concat(' (', s.Code, ')'), '')) as `Name` from State s
                    join Country c on c.CountryID = s.CountryID
                    where c.HaveStates = 1
                    order by Name",
                    "StateID",
                    "Name"
                ),
                "Required" => false,
                "RequiredGroup" => "name",
            ],
			"UseForLongOrShortHaul" => [
			    "Type" => "boolean",
                "Default" => 0,
                "Required" => true,
            ],
			"UseForPromos" => [
			    "Type" => "boolean",
                "Default" => 0,
                "Required" => true,
            ],
			"Parents" => array(
			    "Type" => "string",
				"Required" => false,
				"Size" => 80,
				"Database" => false,
                "ExportCSV" => true,
			),
			"Include" => array(
			    "Type" => "string",
				"Required" => false,
				"Size" => 80,
				"Database" => false,
                "ExportCSV" => true,
			),
			"Exclude" => array(
			    "Type" => "string",
				"Required" => false,
				"Size" => 80,
				"Database" => false,
                "ExportCSV" => true,
			),
			"ShortHaulRegions" => array(
			    "Type" => "string",
				"Required" => false,
				"Size" => 80,
				"Database" => false,
                "ExportCSV" => true,
			),
		);
		$this->ListClass = "TAdminRegionList";
		$this->DefaultSort = "Name";
	}

	function GetRegionOptions(&$regionOptions, $skipId, $where = ""){
	    global $regionKindOptions;
		$sql = "select
			r.RegionID, coalesce(r.Name, rco.Name, rs.Name, concat(r.AirCode, ', ', ac.AirName)) as Name, r.Kind
		from
			Region r
            left outer join Country rco on rco.CountryID = r.CountryID
            left outer join State rs on rs.StateID = r.StateID
            left join AirCode ac on r.AirCode = ac.AirCode
        $where
		order by
			Name";

		$q = new TQuery($sql);

		while(!$q->EOF){
			if($skipId != $q->Fields['RegionID']){
				$regionOptions[$q->Fields['RegionID']] = $q->Fields['Name'] . " (" . $regionKindOptions[$q->Fields["Kind"]] . ")";
			}
			$q->Next();
		}
	}

	function GetFormFields(){
		$fields = parent::GetFormFields();

		// parent
		$objParentManager = new TTableLinksFieldManager();
		$objParentManager->TableName = "RegionContent";
		$objParentManager->KeyField = "SubRegionID";
		$objParentManager->UniqueFields = array("RegionID");
		$objParentManager->SQLParams = array("Exclude" => "0");

		$id = intval($_GET['ID']);
		if($id > 0)
			$skipId = $id;
		else
			$skipId = null;

        $regionOptions = array("" => "None");
		$this->GetRegionOptions($regionOptions, $skipId);

		$objParentManager->Fields = array(
			"RegionID" => array(
				"Caption" => "Region",
				"Type" => "integer",
				"Options" => $regionOptions,
				"InputAttributes" => "style='width: 500px;'",
			),
		);
		if(isset($_GET['ID'])){
			$id = intval($_GET['ID']);
			if($id > 0)
				unset($objParentManager->Fields['RegionID']['Options'][$id]);
		    else
			    if(isset($_GET['Parent'])){
				    $parents = explode(".", $_GET['Parent']);
			        if(count($parents) > 0){
				        $parentId = intval(array_pop($parents));
			            $q = new TQuery("select RegionID from Region where RegionID = $parentId");
			            if(!$q->EOF){
				            $objParentManager->SelectedOptions[] = array("RegionID" => $parentId);
			            }
			        }
			    }
		}
		$objParentManager->CanEdit = true;
		$fields["Parents"]["Manager"] = $objParentManager;

		// childs
		$objIncludeManager = new TTableLinksFieldManager();
		$objIncludeManager->TableName = "RegionContent";
		$objIncludeManager->UniqueFields = array("SubRegionID");
		$objIncludeManager->SQLParams = array("Exclude" => "0");
		$objIncludeManager->Fields = array(
			"SubRegionID" => array(
				"Caption" => "Region",
				"Type" => "integer",
				"Options" => $regionOptions,
				"InputAttributes" => "style='width: 500px;'",
			),
		);
		if(isset($_GET['ID'])){
			$id = intval($_GET['ID']);
			if($id > 0)
				unset($objIncludeManager->Fields['SubRegionID']['Options'][$id]);
		}
		$objIncludeManager->CanEdit = true;
        $fields["Include"]["Manager"] = $objIncludeManager;
		
		// exclude
		$objExcludeManager = new TTableLinksFieldManager();
		$objExcludeManager->TableName = "RegionContent";
		$objExcludeManager->UniqueFields = array("SubRegionID");
		$objExcludeManager->SQLParams = array("Exclude" => "1");
		$objExcludeManager->Fields = array(
			"SubRegionID" => array(
				"Caption" => "Region",
				"Type" => "integer",
				"Options" => $regionOptions,
				"InputAttributes" => "style='width: 500px;'",
			),
		);
		if(isset($_GET['ID'])){
			$id = intval($_GET['ID']);
			if($id > 0)
				unset($objExcludeManager->Fields['SubRegionID']['Options'][$id]);
		}
		$objExcludeManager->CanEdit = true;
		$fields["Exclude"]["Manager"] = $objExcludeManager;
		
		// Short Haul Regions
        $regionOptions = array("" => "None");
		$this->GetRegionOptions($regionOptions, $skipId, "where r.UseForLongOrShortHaul = 1");
		$shortHaulManager = new TTableLinksFieldManager();
		$shortHaulManager->TableName = "RegionContent";
		$shortHaulManager->UniqueFields = array("SubRegionID");
		$shortHaulManager->SQLParams = array("Exclude" => "2");
		$shortHaulManager->Fields = array(
			"SubRegionID" => array(
				"Caption" => "Region",
				"Type" => "integer",
				"Options" => $regionOptions,
				"InputAttributes" => "style='width: 500px;'",
			),
		);
		if(isset($_GET['ID'])){
			$id = intval($_GET['ID']);
			if($id > 0)
				unset($shortHaulManager->Fields['SubRegionID']['Options'][$id]);
		}
		$shortHaulManager->CanEdit = true;
		$fields["ShortHaulRegions"]["Manager"] = $shortHaulManager;

		return $fields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->OnCheck = array($this, "CheckForm", $form);
		$form->Uniques[] = [
		    'AllowNulls' => true,
            'Fields' => ['CountryID'],
            'ErrorMessage' => 'Record with this Country already exists',
        ];
	}

	function GetListFields(){
		$fields = parent::GetListFields();
		unset($fields['Zip']);
		unset($fields['URL']);
		unset($fields['AddressText']);
		return $fields;
	}

	function TuneList(&$list){
		parent::TuneList($list);
		$list->ShowBack = true;
		$list->TopButtons = true;
        $list->showCopy = true;
	}

	function CheckForm(TBaseForm $objForm){
		if(!empty($objForm->Fields['Parents']['Manager']->SelectedOptions)){
			foreach($objForm->Fields['Parents']['Manager']->SelectedOptions as $region){
				if(isset($region['RegionID']) && isset($_GET['ID'])){
					$ID = intval($_GET['ID']);
					$pID = intval($region['RegionID']);
					$sql = "
					SELECT rc.RegionContentID, r.Name
					FROM RegionContent rc
					JOIN Region r ON rc.SubRegionID = r.RegionID
					WHERE rc.SubRegionID = $pID AND rc.RegionID = $ID AND rc.Exclude = 0
					";
					$q = new TQuery($sql);
					if(!$q->EOF){
						return "Region <strong>{$q->Fields['Name']} already childern of this region.</strong>";
					}
				}
			}			
		}

		$filledNamesCount = 0;
		if (!empty($objForm->Fields["Name"]["Value"])) {
		    if (in_array($objForm->Fields["Kind"]['Value'], [REGION_KIND_STATE, REGION_KIND_COUNTRY])) {
		        return "You could not set Name when Kind is set to State or Country. Please fill State ot Country fields";
            }
		    $filledNamesCount++;
        }
		if (!empty($objForm->Fields["CountryID"]["Value"])) {
            if ($objForm->Fields["Kind"]['Value'] != REGION_KIND_COUNTRY) {
                return "You could not set Country field when Kind is not set to Country";
            }
		    $filledNamesCount++;
        }
		if (!empty($objForm->Fields["StateID"]["Value"])) {
            if ($objForm->Fields["Kind"]['Value'] != REGION_KIND_STATE) {
                return "You could not set State field when Kind is not set to State";
            }
		    $filledNamesCount++;
        }
		if ($filledNamesCount > 1) {
		    return "Fill only one of fields: Name, Country, State";
        }

		if ($objForm->Fields["Kind"]["Value"] == REGION_KIND_AIRLINE_REGION && empty($objForm->Fields["AwardChartID"]["Value"])) {
		    return "You should fill 'Award Chart' when Kind is 'Airline Region'";
        }

		if ($objForm->Fields["Kind"]["Value"] != REGION_KIND_AIRLINE_REGION && !empty($objForm->Fields["AwardChartID"]["Value"])) {
		    return "The 'Award Chart' field should be blank when Kind is not 'Airline Region'";
        }

		if ($objForm->Fields["Kind"]["Value"] == REGION_KIND_AIRPORT && empty($objForm->Fields["AirCode"]["Value"])) {
		    return "You should fill 'Airport' when Kind is 'Airport'";
        }

		if ($objForm->Fields["Kind"]["Value"] != REGION_KIND_AIRPORT && !empty($objForm->Fields["AirCode"]["Value"])) {
		    return "The 'Airport' field should be blank when Kind is not 'Airport'";
        }

		return null;
	}

}
?>
