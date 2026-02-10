<?
$schema = "profitPerUser";
require "../start.php";
drawHeader("Profit per user");

require_once( "$sPath/manager/reports/common.php" );
$bSecuredPage = False;
$sTitle = "Payments";
$chartValuesY = array();
$chartValuesX = array();
$dataLables = array();
$axisY = array();
$j = 0;
$userChunck = 20000;
$userCountRS = new TQuery("SELECT COUNT(*) AS TotalUsers FROM Usr");
$thousands = floor($userCountRS->Fields["TotalUsers"]/$userChunck);
if($thousands > 20)
    $thousands = 20;
for($i=1;$i<=$thousands;$i++){
    $limitCeil = (($i-1)*$userChunck-1);
    $limitFloor = ($i*$userChunck-1);
    if($i==1)
        $limitCeil = 0;
    $userIDFloorRS = new TQuery("SELECT UserID FROM Usr ORDER BY UserID DESC LIMIT $limitFloor, 1");
    $userIDCeilRS = new TQuery("SELECT UserID FROM Usr ORDER BY UserID DESC LIMIT $limitCeil, 1");
    $incomeAr = getProfit(null, null, "AND c.UserID > {$userIDFloorRS->Fields["UserID"]} AND c.UserID <= {$userIDCeilRS->Fields["UserID"]}");

    $chartValuesY[] = ($incomeAr["nTotalProfit"]/$userChunck);
#	$chartValuesX[] = "{$userIDFloorRS->Fields["UserID"]}-{$userIDCeilRS->Fields["UserID"]}";
    $chartValuesX[] = "{$userIDCeilRS->Fields["UserID"]}";
	$dataLables[] = "t".round((($incomeAr["nTotalProfit"]/$userChunck)*100), 0)."c,001bc0,0,".($thousands-$i).",15";
    $j++;
	if(!isset($schema))
		print "$i. ({$userIDCeilRS->Fields["UserID"]} - {$userIDFloorRS->Fields["UserID"]}) <strong>{$incomeAr["nTotalProfit"]}</strong> = {$incomeAr["nTotalRevenue"]} - {$incomeAr["nTotalFee"]}<br>";
}
$chartValuesY = array_reverse($chartValuesY);
$chartValuesX = array_reverse($chartValuesX);
#$dataLables = array_reverse($dataLables);
for($i=0;$i<=max($chartValuesY);$i=$i+10)
	$axisY[] = $i;
$maxVal = max($chartValuesY);
$step = number_format(10 / $maxVal, 2, '.', '');
$url = "http://chart.apis.google.com/chart?cht=bvg&chs=950x300&chbh=30,30,15&chd=t:".implode(",", $chartValuesY)."&chds=0,$maxVal&chco=c6d9fd&chxt=x,y&chxl=0:|".implode("|", $chartValuesX)."|1:|".implode("|", $axisY)."&chg=0,$step&chm=".implode("|", $dataLables)."&chtt=Profit%20per%20one%20user%20in%20$userChunck%20user%20chunks";
?>
<img src="<?=$url?>">
<br>

drawFooter();
