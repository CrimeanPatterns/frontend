<?

class DealList extends TBaseList{

	function FormatFields($output = "html"){
		parent::FormatFields($output);
		if($output == "html"){
			$this->Query->Fields["Active"] = (strtotime($this->Query->Fields["EndDate"]) >= strtotime("today")) ? "Active" : "Old";
		}
	}

	function GetFilterFields() {
		$fields = parent::GetFilterFields();
		$fields["Active"] = array(
			"Type" => "integer",
			"Options" => array(
				"" => "All",
				"0" => "Old",
				"1" => "Active",
			),
			"InputType" => "select",
			"FilterType" => "where",
			"Database" => false,
		);
		return $fields;
	}

	function GetFieldFilter($sField, $arField){
		if ($sField == 'Active') {
			switch ($arField["Value"]) {
				case 0:
					$sFilters = " and EndDate < CURDATE()";
					break;
				case 1:
					$sFilters = " and EndDate >= CURDATE()";
					break;
				default:
					$sFilters = "";
					break;
			}
		}
		else
			$sFilters = parent::GetFieldFilter($sField, $arField);
		return $sFilters;
	}
}
