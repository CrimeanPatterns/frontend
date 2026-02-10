<?
$schema = "Offer";
require "../start.php";
$minRange = '1 year';
drawHeader("Travelling Activity History");
while(ob_get_level() > 0)
    ob_end_flush();
$q = new TQuery("select ProviderID, Code, Name, DisplayName from Provider where Kind = 1");
?>
    Activity - Records per Day for all appropriate Accounts of selected Provider
    <table border="1">
		<tr>
			<td>Airline ID</td>
			<td>Airline</td>
			<td># of Accounts</td>
			<td>Average registration date</td>
			<td>Average records per month Before registration</td>
			<td>Average records per month After registration</td>
			<td>Total number of records analyzed Before registration</td>
			<td>Total number of records analyzed After registration</td>
			<td>Total average date range in days (before and after registration)</td>
		</tr>
		<?
while (!$q->EOF){
    $pid = $q->Fields["ProviderID"];
    $pname = $q->Fields["Name"];
    $q2 = new TQuery("select
    	AccountID,
    	CreationDate
	from
		Account A
	where
		A.CreationDate is not null
		and A.CreationDate <> '0000-00-00 00:00:00'
		and A.ProviderID = $pid
		and exists(select 1 from AccountHistory AH where AH.AccountID = A.AccountID and AH.PostingDate < A.CreationDate - interval $minRange and PostingDate > '2000-01-01' limit 1)
		and exists(select 1 from AccountHistory AH where AH.AccountID = A.AccountID and AH.PostingDate > A.CreationDate + interval $minRange limit 1)
	limit 100000");
    $acn = 0;
    $totalBefore = 0;
    $actBefore = 0;
    $totalAfter = 0;
    $actAfter = 0;
    $averageDif = 0;
	$regDateSum = "0";
    while(!$q2->EOF){
        $aId = $q2->Fields["AccountID"];
        $crDate = $q2->Fields["CreationDate"];
		$regDateSum = bcadd($regDateSum, $Connection->SQLToDateTime($crDate));
        $q3 = new TQuery("select min(PostingDate) as m from AccountHistory AH where AH.AccountID = $aId");
        $minDate = $q3->Fields['m'];
        $q4 = new TQuery("select max(PostingDate) as m from AccountHistory AH where AH.AccountID = $aId");
        $maxDate = $q4->Fields['m'];
        // echo "cr=$crDate min=$minDate max=$maxDate<br/>";
        $q5 = new TQuery("select
        	count(*) as c,
        	least(abs(DATEDIFF('$minDate', '$crDate')), abs(DATEDIFF('$maxDate', '$crDate'))) as dif
		from
			AccountHistory AH
		where
			AH.AccountID = $aId
			and AH.PostingDate < '$crDate'
			and AH.PostingDate > '$crDate' - interval least(abs(DATEDIFF('$minDate', '$crDate')), abs(DATEDIFF('$maxDate', '$crDate'))) day");
        $totalBefore += $q5->Fields['c'];
        $actBefore += $q5->Fields['c']/$q5->Fields['dif'];
        $q6 = new TQuery("select
        	count(*) as c,
        	least(abs(DATEDIFF('$minDate', '$crDate')), abs(DATEDIFF('$maxDate', '$crDate'))) as dif
		from
			AccountHistory AH
		where
			AH.AccountID = $aId
			and AH.PostingDate > '$crDate'
			and AH.PostingDate < '$crDate' + interval least(abs(DATEDIFF('$minDate', '$crDate')), abs(DATEDIFF('$maxDate', '$crDate'))) day");
        $totalAfter += $q6->Fields['c'];
        $actAfter += $q6->Fields['c']/$q6->Fields['dif'];
        $acn++;
        $averageDif += $q6->Fields['dif'];
        $q2->next();
    }
    if ($acn > 0){
        if ($actBefore >= $actAfter)
            $rise = false;
        else
            $rise = true;
        $averageDif = number_format($averageDif / $acn, 0);
        $actBefore = number_format($actBefore * 30 / $acn, 4);
        $actAfter = number_format($actAfter * 30 / $acn, 4);
        if (!$rise){
            $actBefore = '<font color = "#008000">'.$actBefore.'</font>';
            $actAfter = '<font color = "#ff0000">'.$actAfter.'</font>';
        }
        else{
            $actBefore = '<font color = "#ff0000">'.$actBefore.'</font>';
            $actAfter = '<font color = "#008000">'.$actAfter.'</font>';
        }
		$avgRegDate = date("Y-m-d", round(bcdiv($regDateSum, $acn)));
        echo "<tr><td>$pid</td><td>$pname</td><td>$acn</td><td>$avgRegDate</td><td>$actBefore</td><td>$actAfter</td><td>$totalBefore</td><td>$totalAfter</td><td>$averageDif</td></tr>";
    }
    flush();
    $q->next();
}
?>
    </table>
<?
drawFooter();
?>
