<?

class EliteLevelProgressList extends TBaseList{

	function __construct($table, $fields, $defaultSort) {
		parent::__construct($table, $fields, $defaultSort);
		$this->EmptyListMessage = "Please select provider from dropdown above";
	}

	function FormatFields($output = "html"){
		$p = Lookup("ProviderProperty", "ProviderPropertyID", "ProviderID", $this->Query->Fields["ProviderPropertyID"]);
		parent::FormatFields($output);
		if($output == "html"){
			$this->Query->Fields["Provider"] = Lookup("Provider", "ProviderID", "DisplayName", $p);
			$elpID = $this->Query->Fields["EliteLevelProgressID"];
			$lvls = SQLToArray("select elv.Value,
									   el.Name
								from EliteLevelValue elv,
									 EliteLevel el
								where elv.EliteLevelProgressID = {$elpID}
								  and el.EliteLevelID = elv.EliteLevelID
							 order by elv.Value",
					"Name", "Value");
			$cell = "";
			foreach($lvls as $name => $val)
				$cell.="{$val} : {$name}<br>";
			$this->Query->Fields["Values"] = $cell;
		}
	}

	function GetFilterFields() {
		$fields = parent::GetFilterFields();
		$fields["Provider"] = array(
			"Type" => "integer",
			"Options" => array("" => "all providers") +
				SQLToArray("select ProviderID, DisplayName from Provider", "ProviderID", "DisplayName"),
			"InputAttributes" => "style='width: 70px; font-family: Arial, Helvetica, sans-serif; font-weight: normal;'",
			"InputType" => "select",
			"FilterType" => "where",
		);
		return $fields;
	}

	function GetFieldFilter($sField, $arField){
		if ($sField == 'Provider') {
			$sFilters = " and ProviderPropertyID in (select ProviderPropertyID from ProviderProperty where ProviderID = ".$arField["SQLValue"].")";
		}
		else
			$sFilters = parent::GetFieldFilter($sField, $arField);
		return $sFilters;
	}

	function GetFilters($filterType = "where") {
		$filters = parent::GetFilters($filterType);
		if ((!isset($_GET["Provider"]) || $_GET["Provider"] == "")) {
			if ($filters != "")
				$filters.=" and";
			$filters.=" 1 = 0";
		}
		return $filters;
	}

}
