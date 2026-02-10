<?

function FindMinimumPoints( $sFrom, $sTo, $dDate, $arProviders, $sClass, $bDisplay = false ){
	global $Connection;
	// search airports
	$arFrom = SearchAirPort($sFrom);
	$arTo = SearchAirPort($sTo);
	if($bDisplay){
		echo "From airport:<br>";
		var_dump_pre($arFrom);
		echo "To airport:<br>";
		var_dump_pre($arTo);
		echo "<hr>";
		echo "Date ranges:<br>";
	}
	// search date ranges
	$sDate = $Connection->DateTimeToSQL($dDate);
	$arDateRanges = SQLToArray("Select DateRangeID, Name from DateRange
	where StartDate <= $sDate and EndDate >= $sDate", "DateRangeID", "Name");
	if($bDisplay){
		var_dump_pre( $arDateRanges );
		echo "<hr>";
		// search regions
		echo "From region:<br>";
	}
	$arFromRegions = SearchRegions($arFrom);
	if( count( $arFromRegions ) == 0 ){
		if($bDisplay)
			echo "Can't find source region<br>";
		return null;
	}
	if($bDisplay)
		echo "To region:<br>";
	$arToRegions = SearchRegions($arTo);
	if( count( $arToRegions ) == 0 ){
		if($bDisplay)
			echo "Can't find destination region<br>";
		return null;
	}
	if($bDisplay)
		echo "<hr>";
	// search tariffs
	$sSQL = "select
		p.Name as ProviderName,
		sr.Name as SrcRegionName,
		dr.Name as DstRegionName,
		d.Name as DateRangeName,
		t.PriceEconomy,
		t.PriceBusiness,
		t.PriceFirst
	from
		AirTariff t
		left outer join DateRange d on t.DateRangeID = d.DateRangeID,
		Region sr,
		Region dr,
		Provider p
	where
		t.SrcRegionID = sr.RegionID
		and t.DstRegionID = dr.RegionID
		and t.ProviderID = p.ProviderID
		and t.ProviderID in ( ".implode(", ", $arProviders)." )
		and t.SrcRegionID in ( ".implode(", ", array_keys($arFromRegions))." )
		and t.DstRegionID in ( ".implode(", ", array_keys($arToRegions))." )";
	if( count( $arDateRanges ) > 0 )
		$sSQL .= " and ( t.DateRangeID is null
		or t.DateRangeID in ( ".implode(", ", array_keys($arDateRanges))." ) )";
	$sSQL .= " order by t.Price{$sClass}";
	$arTariffs = SQLToArray( $sSQL, "ProviderName", "SrcRegionName", True );
	if($bDisplay){
		echo "Tariffs:<br>";
		ShowTable( $arTariffs );
	}
	if(count($arTariffs) > 0)
		return $arTariffs[0]["Price{$sClass}"];
	else
		return null;
}

function SearchRegions( $arAirCode ){
	$arRegions = SQLToArray("select distinct rc.ParentID, p.Name
	from
		RegionContent rc, Region p
	where
		rc.ParentID = p.RegionID
		and rc.AirPortCode = '".addslashes($arAirCode['AirCode'])."'",
	"ParentID", "Name");
//	if( count( $arRegions ) == 0 )
		$arRegions = $arRegions + SQLToArray("select distinct rc.ParentID, p.Name
	from
		RegionContent rc, Region p
	where
		rc.ParentID = p.RegionID
		and rc.CountryCode = '".addslashes($arAirCode['AirCountryCode'])."'
		and rc.StateCode = '".addslashes($arAirCode['State'])."'
		and rc.AirPortCode is null",
	"ParentID", "Name");
//	if( count( $arRegions ) == 0 )
		$arRegions = $arRegions + SQLToArray("select distinct rc.ParentID, p.Name
	from
		RegionContent rc, Region p
	where
		rc.ParentID = p.RegionID
		and rc.CountryCode = '".addslashes($arAirCode['AirCountryCode'])."'
		and rc.StateCode is null
		and rc.AirPortCode is null",
	"ParentID", "Name");
	if(isset($_GET['DebugTariff'])){
		echo "Direct regions:<br>";
		var_dump_pre( $arRegions );
	}
	$arRegions = DecodeRegions($arRegions);
	if(isset($_GET['DebugTariff'])){
		echo "All regions:<br>";
		var_dump_pre( $arRegions );
	}
	return $arRegions;
}

function DecodeRegions($arRegions){
	if( count( $arRegions ) == 0 )
		return $arRegions;
	$arParentRegions = SQLToArray("select distinct rc.ParentID, p.Name
	from
		RegionContent rc, Region p
	where
		rc.ParentID = p.RegionID
		and rc.RegionID in ( ".implode(", ", array_keys($arRegions))." )", "ParentID", "Name");
	$arNewRegions = $arRegions + $arParentRegions;
	if( count( $arNewRegions ) > count( $arRegions ) )
		$arNewRegions = DecodeRegions( $arNewRegions );
	return $arNewRegions;
}

function SearchAirPort($sCode){
	$q = new TQuery("select * from AirCode where AirCode = '".addslashes($sCode)."'");
	if( $q->EOF )
		return false;
	return $q->Fields;
}

?>
