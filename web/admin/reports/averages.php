<?
require( "../../kernel/public.php" );
$bSecuredPage = False;
$sTitle = "Averages";
require( "$sPath/lib/admin/design/header.php" );

$averages = array();

$q = new TQuery("select
   round(sum(case when a.Balance >= 0 then a.Balance else 0 end)/(count(distinct a.UserID) + count(distinct a.UserAgentID) - 1)) as PointsPerUser,
   round(count(a.AccountID)/(count(distinct a.UserID) + count(distinct a.UserAgentID) - 1), 1) as ProgramsPerUser,
   count(distinct a.UserID ) + count(distinct a.UserAgentID) - 1 as UserCount,
   round(sum(case when a.Balance > 0 then 1 else 0 end)/(count(distinct a.UserID) + count(distinct a.UserAgentID) - 1), 1) as NotEmptyProgramsPerUser,
   round(sum(case when p.Kind = ".PROVIDER_KIND_AIRLINE." then 1 else 0 end)/(count(distinct a.UserID) + count(distinct a.UserAgentID) - 1), 1) as AirProgramsPerUser,
   round(sum(case when p.Kind = ".PROVIDER_KIND_AIRLINE." and a.Balance > 0 then a.Balance else 0 end)/(count(distinct a.UserID) + count(distinct a.UserAgentID) - 1), 1) as AirPointsPerUser,
   round(sum(case when p.Kind = ".PROVIDER_KIND_HOTEL." then 1 else 0 end)/(count(distinct a.UserID) + count(distinct a.UserAgentID) - 1), 1) as HotelProgramsPerUser,
   round(sum(case when p.Kind = ".PROVIDER_KIND_HOTEL." and a.Balance > 0 then a.Balance else 0 end)/(count(distinct a.UserID) + count(distinct a.UserAgentID) - 1), 1) as HotelPointsPerUser
from
	Account a
	left outer join Provider p on a.ProviderID = p.ProviderID");
$averages["1. Average number of cumulative points by user"] = number_format($q->Fields['PointsPerUser'], 0, ".", " ");
$averages["2. Average number of loyalty programs by user including those without points"] = number_format($q->Fields['ProgramsPerUser'], 1, ".", " ");
$averages["3. Average number of loyalty programs by user with points"] = number_format($q->Fields['NotEmptyProgramsPerUser'], 1, ".", " ");
$averages["8. Average number of airline programs per user"] = number_format($q->Fields['AirProgramsPerUser'], 1, ".", " ");
$averages["9. Average number of airline points per user"] = number_format($q->Fields['AirPointsPerUser'], 0, ".", " ");
$averages["10. Average number of hotel programs per user"] = number_format($q->Fields['HotelProgramsPerUser'], 1, ".", " ");
$averages["11. Average number of hotel points per user"] = number_format($q->Fields['HotelPointsPerUser'], 0, ".", " ");
$userCount = $q->Fields['UserCount'];

$q = new TQuery("select
	round(sum(case when HotelPrograms/TotalPrograms > 0.7 then 1 else 0 end) / (count(distinct UserID) + count(distinct UserAgentID) - 1) * 100, 1) as HotelUsers
from
	(
		select
			a.UserID,
			a.UserAgentID,
			sum(case when p.Kind = ".PROVIDER_KIND_HOTEL." then 1 else 0 end) as HotelPrograms,
			count(a.AccountID) as TotalPrograms
		from
			Account a
			join Provider p on a.ProviderID = p.ProviderID
		where
			a.Balance > 0
		group by
			a.UserID, a.UserAgentID
	) as us
");
$averages["10. % of users whose top loyalty program is > 70% of hotel points"] = $q->Fields['HotelUsers'].'%';

$q = new TQuery("select ab.AccountID, ab.SubAccountID, ab.Balance, p.Kind from AccountBalance ab
join Account a on a.AccountID = ab.AccountID
join Provider p on a.ProviderID = p.ProviderID
where ab.UpdateDate > adddate(now(), interval -1 year)
order by ab.AccountID, ab.SubAccountID, ab.AccountBalanceID");
$lastAccountId = null;
$lastBalance = null;
$lastSubAccountId = null;
$redemptions = 0;
$airRedemptions = 0;
$hotelRedemptions = 0;
while(!$q->EOF){
	if(isset($lastBalance) && isset($lastAccountId) && ($lastSubAccountId == $q->Fields['SubAccountID'])
	&& (($lastBalance - $q->Fields['Balance']) > 2)){
		$redemptions++;
		switch($q->Fields['Kind']){
			case PROVIDER_KIND_AIRLINE:
		        $airRedemptions++;
		        break;
			case PROVIDER_KIND_HOTEL:
		        $hotelRedemptions++;
		        break;
		}
	}
	if($q->Fields["Balance"] > 0)
		$lastBalance = $q->Fields["Balance"];
	else
		$lastBalance = null;
	$lastAccountId = $q->Fields['AccountID'];
	$lastSubAccountId = $q->Fields['SubAccountID'];
	$q->Next();
}
$averages["11. Average number of redemptions per year in total"] = number_format($redemptions / $userCount, 1, ".", " ");
$averages["12. Average number of airline redemptions per year"] = number_format($airRedemptions / $userCount, 1, ".", " ");
$averages["13. Average number of hotel redemptions per year"] = number_format($hotelRedemptions / $userCount, 1, ".", " ");

echo "<table cellpadding='5' cellspacing='0' class='detailsTable'>";
foreach($averages as $key => $value)
	echo "<tr><td>$key</td><td>$value</td></tr>";
echo "</table>";

require( "$sPath/lib/admin/design/footer.php" );
?>