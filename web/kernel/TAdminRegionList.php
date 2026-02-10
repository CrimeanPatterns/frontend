<?php
class TAdminRegionList extends TBaseList {

	function GetEditLinks(){
		$arFields = &$this->Query->Fields;
		$s = parent::GetEditLinks();
		//$s .= "| <a href=\"list.php?Schema=RegionContent&ParentID={$arFields["RegionID"]}\">Contents</a>";
		return $s;
	}

	function FormatFields($output = "html"){
		$arFields = &$this->Query->Fields;
		parent::FormatFields($output);

		$arFields['Parents'] = implode(", ", SQLToSimpleArray(
			"select
				" . ($output === "html" ? "coalesce(r.Name, rco.Name, rs.Name)" : "rc.RegionID") . " as ParentRegion
			from 
                RegionContent rc
                left outer join Region r on rc.RegionID = r.RegionID
                left outer join Country rco on rco.CountryID = r.CountryID
                left outer join State rs on rs.StateID = r.StateID
			where rc.SubRegionID = {$arFields['RegionID']} and rc.Exclude = 0 order by r.Name", "ParentRegion"));
		$arFields['Include'] = implode(", ", SQLToSimpleArray(
			"select
				" . ($output === "html" ? "coalesce(r.Name, rco.Name, rs.Name)" : "rc.SubRegionID") . " as SubRegion
			from 
                RegionContent rc
			    left outer join Region r on rc.SubRegionID = r.RegionID
                left outer join Country rco on rco.CountryID = r.CountryID
                left outer join State rs on rs.StateID = r.StateID
			where rc.RegionID = {$arFields['RegionID']} and rc.Exclude = 0 order by r.Name", "SubRegion"));
		$arFields['Exclude'] = implode(", ", SQLToSimpleArray(
			"select
				" . ($output === "html" ? "coalesce(r.Name, rco.Name, rs.Name)" : "rc.SubRegionID") . " as SubRegion
			from 
			    RegionContent rc
			    left outer join Region r on rc.SubRegionID = r.RegionID
                left outer join Country rco on rco.CountryID = r.CountryID
                left outer join State rs on rs.StateID = r.StateID
			where rc.RegionID = {$arFields['RegionID']} and rc.Exclude = 1 order by r.Name", "SubRegion"));
		$arFields['ShortHaulRegions'] = implode(", ", SQLToSimpleArray(
			"select
				" . ($output === "html" ? "coalesce(r.Name, rco.Name, rs.Name)" : "rc.SubRegionID") . " as SubRegion
			from 
			    RegionContent rc
			    left outer join Region r on rc.SubRegionID = r.RegionID
                left outer join Country rco on rco.CountryID = r.CountryID
                left outer join State rs on rs.StateID = r.StateID
			where rc.RegionID = {$arFields['RegionID']} and rc.Exclude = 2 order by r.Name", "SubRegion"));
		$ar = array(
			'Schema' => $_GET['Schema']
		);
		if(!isset($_GET['Parent']))
			$ar['Parent'] = "None.".$arFields['RegionID'];
		else
			$ar['Parent'] = $_GET['Parent'].'.'.$arFields['RegionID'];

		if ($output === "html") {
            $arFields['Name'] = "<a href=\"?" . ImplodeAssoc("=", "&", $ar, true) . "\">{$arFields['Name']}</a>";
        }
	}

	// get sql filters
	function GetFilters($filterType = "where")
	{
		$filters = parent::GetFilters($filterType);
		if(isset($_GET['Parent'])){
			if($filters != "")
				$filters .= " and ";
			if($_GET['Parent'] == 'None'){
				$filters .= "RegionID not in (select SubRegionID from RegionContent where Exclude = 0)";
			}
			else{
				$parents = explode(".", $_GET['Parent']);
				$filters .= "RegionID in (select SubRegionID from RegionContent where RegionID = ".intval(array_pop($parents))." and Exclude = 0)";
			}
		}
		return $filters;
	}

	function DrawHeader(){
		$parents = explode(".", ArrayVal($_GET, 'Parent', 'None'));
		echo "<div style='text-align: left; padding-bottom: 10px;'>";
		$links = array();
		$ar = $_GET;
		foreach($parents as $index => $parent){
			if($parent == "None")
				$name = "Root";
			else{
				$q = new TQuery("select Name from Region where RegionID = ".intval($parent));
				$name = $q->Fields["Name"];
			}
			$ar['Parent'] = implode(".", array_slice($parents, 0, $index + 1));
			$links[] = "<a href=\"?".ImplodeAssoc("=", "&", $ar, true)."\">{$name}</a>";
		}
		echo implode(" &gt; ", $links);
		echo "</div>";
		parent::DrawHeader();
	}

}

?>
