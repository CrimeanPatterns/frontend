<?
$schema = "adStats";
require "../start.php";
drawHeader("Ad stats");

$bSecuredPage = False;
$sTitle = "Ad stats";

?>
<style>
table.stats{
	border-collapse: collapse;
}
table.stats td{
	border: 1px solid gray;
	padding: 3px;
}
table.stats tr.head td, table.stats tr.total td{
	background-color: yellow;
	font-weight: bold;
}
</style>
<?

$objForm = new TForm(array(
	"StartDate" => array(
		"Type" => "date",
		"Value" => date(DATE_FORMAT, strtotime("-1 month")),
	),
	"EndDate" => array(
		"Type" => "date",
		"Value" => date(DATE_FORMAT, time()),
	),
));
$objForm->SubmitButtonCaption = "Calculate ads volume";

if($objForm->IsPost && $objForm->Check()){
	echo $objForm->HTML();
	$objForm->CalcSQLValues();
	echo "<div>Sent ads within {$objForm->Fields["StartDate"]["Value"]} <= Sent Date <= {$objForm->Fields["EndDate"]["Value"]}</div><br>";
	$q = new TQuery("select
		a.Name,
		sum(s.Messages) as Messages
	from
		AdStat s
		join SocialAd a on s.SocialAdID = a.SocialAdID
	where
		s.StatDate >= ".$objForm->Fields["StartDate"]["SQLValue"]."
		and s.StatDate <= ".$objForm->Fields["EndDate"]["SQLValue"]."
	group by
		a.Name
	order by
		sum(s.Messages) desc");
	if($q->EOF)
		$Interface->DrawMessage("There are no emails for this dates", "warning");
	else{
		echo "<table class=stats cellpadding=0 cellspacing=0 border=0>";
		echo "<tr class=head>";
		foreach($q->Fields as $sField => $sValue)
			echo "<td>$sField</td>";
		$nFieldCount = count($q->Fields);
		echo "</tr>";
		$nTotal = 0;
		while(!$q->EOF){
			echo "<tr>";
			foreach($q->Fields as $sField => $sValue)
				echo "<td>$sValue</td>";
			$nTotal += $q->Fields["Messages"];
			echo "</tr>";
			$q->Next();
		}
		echo "<tr class=total><td>&nbsp;</td>";
		echo "<td>$nTotal</td>";
		echo "</tr>";
		echo "</table>";
	}
}
else
	echo $objForm->HTML();

drawFooter();
