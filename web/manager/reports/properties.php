<?
$schema = "PropertiesHealth";
require "../start.php";
drawHeader("Properties Health");

$q = new TQuery("select
	p.Code as Provider,
	pp.Code as Property,
	pp.Name as Name,
	pp.ProviderPropertyID,
	coalesce(ppr.Accounts, 0) as Received,
	pstat.CheckCount,
	round(coalesce(ppr.Accounts, 0) / pstat.CheckCount * 100, 2) as Health
from
	ProviderProperty pp
	join Provider p on pp.ProviderID = p.ProviderID
	left outer join (
		select ap.ProviderPropertyID,
		count(a.AccountID) as Accounts
		from Account a
		join AccountProperty ap on a.AccountID = ap.AccountID
		where a.UpdateDate > adddate(now(), interval -1 day)
		and a.ErrorCode in(1, 9)
		group by ap.ProviderPropertyID
	) ppr on ppr.ProviderPropertyID = pp.ProviderPropertyID
	join (
		select a.ProviderID, count(a.AccountID) as CheckCount
		from Account a
		where a.UpdateDate > adddate(now(), interval -1 day)
		and a.ErrorCode in(1, 9)
		group by a.ProviderID
	) pstat on p.ProviderID = pstat.ProviderID
where pp.Required = 1
order by Health");
ShowQuery($q, "Received properties in last 24h", "");

function ShowQuery($q, $title, $style){
	echo "<table class='detailsTable' cellpadding='3' style='$style'>";
	$headerStyle = "font-weight: bold; text-align: center; background-color: #dddddd;";
	if($q->EOF){
		echo "<tr><td style='{$headerStyle}'>{$title}</td></tr>";
		echo "<tr><td>No data</td></tr>";
	}
	else{
		echo "<tr><td colspan='6' style='{$headerStyle}'>{$title}</td></tr>";
		echo "<tr>
			<td style='font-weight: bold;'>Provider</td>
			<td style='font-weight: bold;'>Property</td>
			<td style='font-weight: bold;'>Name</td>
			<td style='font-weight: bold;'>Received</td>
			<td style='font-weight: bold;'>Check Count</td>
			<td style='font-weight: bold;'>Health</td>
		</tr>";
		while(!$q->EOF){
			echo "<tr>
				<td>{$q->Fields["Provider"]}</td>
				<td><a href=\"missingProperties.php?ID={$q->Fields['ProviderPropertyID']}\" title=\"Last checked accounts with missing properties\">{$q->Fields["Property"]}</a></td>
				<td>{$q->Fields["Name"]}</td>
				<td>{$q->Fields["Received"]}</td>
				<td>{$q->Fields["CheckCount"]}</td>
				<td>{$q->Fields["Health"]}%</td>
			</tr>";
			$q->Next();
		}
	}
	echo "</table>";
}

drawFooter();
