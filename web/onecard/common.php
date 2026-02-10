<?

function getCountryRegions($countryId){
	$result = array();
	if($countryId > 0){
		$q = new TQuery("
		select
			r.RegionID,
			r.Name
		from
			Country c
			join Region r on r.Name = c.Name
		where
			c.CountryID = {$countryId}
			and r.Kind = ".REGION_KIND_COUNTRY);
		if(!$q->EOF){
			$result[$q->Fields['RegionID']] = $q->Fields['Name'];
			getChildRegions($q->Fields['RegionID'], $result);
			getParentRegions($q->Fields['RegionID'], $result);
		}
	}
	return $result;
}

function getChildRegions($regionId, &$result){
	$q = new TQuery("
	select
		sr.RegionID,
		sr.Name
	from
		RegionContent rc
		join Region sr on rc.SubRegionID = sr.RegionID
	where
		sr.Kind in (".REGION_KIND_COUNTRY.", ".REGION_KIND_REGION.")
		and rc.RegionID = $regionId");
	while(!$q->EOF){
		if(!isset($result[$q->Fields['RegionID']])){
			$result[$q->Fields['RegionID']] = $q->Fields['Name'];
			getChildRegions($q->Fields['RegionID'], $result);
		}
		$q->Next();
	}
}

function getParentRegions($regionId, &$result){
	$q = new TQuery("
	select
		pr.RegionID,
		pr.Name
	from
		RegionContent rc
		join Region pr on rc.RegionID = pr.RegionID
	where
		pr.Kind in (".REGION_KIND_COUNTRY.", ".REGION_KIND_REGION.")
		and rc.SubRegionID = $regionId");
	while(!$q->EOF){
		if(!isset($result[$q->Fields['RegionID']])){
			$result[$q->Fields['RegionID']] = $q->Fields['Name'];
			getParentRegions($q->Fields['RegionID'], $result);
		}
		$q->Next();
	}
}

function cursorToArray($accounts) {
	$res = array();
	while(!$accounts->EOF) {
		$res[$accounts->Fields['AccountID']] = $accounts->Fields['ShortName'];
		$accounts->Next();
	}
	return $res;
}

function showAccountNumber($accountID) {
	$q = new TQuery("select ap.Val from Account a
			inner join AccountProperty ap on ap.AccountID = a.AccountID
			inner join ProviderProperty pp on pp.ProviderPropertyID = ap.ProviderPropertyID
			where ap.SubAccountID IS NULL and a.AccountID = $accountID and (pp.Code = \"Number\" or pp.Kind = " . PROPERTY_KIND_NUMBER . ")");
	if(!$q->EOF)
		return $q->Fields['Val'];
	else {
		$q = new TQuery("select Login from Account where AccountID = $accountID");
		if(!$q->EOF) {
			$login = $q->Fields['Login'];
			if(preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/ims', $login)
			|| preg_match('/^fake\./ims', $login)) // it`s email, or fake login don`t show this
				return '';
			else
				return $login;
		}
	}
}

function showAccountLevel($accountID) {
	$statusQuery = new TQuery("select coalesce(el.Name, ap.Val) as Val from AccountProperty ap
				inner join ProviderProperty pp on ap.ProviderPropertyID = pp.ProviderPropertyID
				left outer join EliteLevel el on pp.ProviderID = el.ProviderID
				left outer join TextEliteLevel tel on el.EliteLevelID = tel.EliteLevelID and tel.ValueText = ap.Val
				where pp.Kind = ".PROPERTY_KIND_STATUS." and ap.AccountID = $accountID
				and (el.ProviderID is null or tel.ValueText is not null)");
	if(!$statusQuery->EOF)
		return $statusQuery->Fields['Val'];
	return "";
}

function getLevelPhones($level, $providerId){
	return SQLToArray("select ph.Phone, r.Name as Region, ph.PhoneFor, tel.ValueText as Level from
		EliteLevel el
		inner join ProviderPhone ph on el.ProviderID = ph.ProviderID and el.EliteLevelID = ph.EliteLevelID
		inner join TextEliteLevel tel on el.EliteLevelID = tel.EliteLevelID
		left outer join Region r on ph.RegionID = r.RegionID
		where tel.ValueText = \"$level\" and el.ProviderID = $providerId
		order by ph.DefaultPhone desc, r.Name, ph.Phone", "Phone", "Phone", true);
}

function getProviderPhones($providerId){
	return SQLToArray("select ph.Phone, r.Name as Region, ph.PhoneFor, null as Level from
		ProviderPhone ph
		left outer join Region r on ph.RegionID = r.RegionID
		where ph.ProviderID = $providerId and ph.EliteLevelID is null
		order by ph.DefaultPhone desc, r.Name, ph.Phone", "Phone", "Phone", true);
}
