<?
$schema = "qaac";
require "../start.php";
drawHeader("Quarterly AA Compensation");

$startingPoint = strtotime("00:00:00 1.08.2013");
/*
$endingPoint = time();
$monthCounter = $startingPoint;
while ($monthCounter <= $endingPoint){
    $options[] = date('F', $monthCounter).' '.date('Y', $monthCounter);
    $monthCounter = strtotime('+1 month', $monthCounter);
}

print_r($options);
*/

$q = new TQuery("
    select count(*) as aac
    from Account a, Provider p
    where a.ProviderID = p.ProviderID and p.Code = 'aa'");
if (!$q->EOF)
    $aac = $q->Fields['aac'];
else
    die('DB error, could not count AA accounts number');
$q = new TQuery("
    select (((select count(*)
    from Account a, Provider p
    where a.ProviderID = p.ProviderID and p.Category = 1 and p.Kind = 1) * 100)
    +
    ((select count(*)
    from Account a, Provider p
    where a.ProviderID = p.ProviderID and p.Category = 2 and p.Kind = 1) * 10)
    +
    ((select count(*)
    from Account a, Provider p
    where a.ProviderID = p.ProviderID and p.Category = 3 and p.Kind = 1) * 1))
    as Total");
if (!$q->EOF)
    $Total = $q->Fields['Total'];
else
    die('DB error, could not count total accounts share');
$aaas = round(($aac * 100) / $Total, 2);

$omonth = date("F", time());
$oyear = date("Y", time());
$months = "";
for ($q = 1; $q <= 4; $q++) {
    $months.='<option value="'.($q + 12).'">Q'.$q.'</option>';
}
for ($m = 1; $m <= 12; $m++) {
    $f = date('F', mktime(0,0,0,$m));
    $months.='<option value="'.$m.'"';
    if ($omonth == $f)
        $months.=' selected';
    $months.='>'.$f.'</option>';
}
if (isset($_POST["month"]) && isset($_POST["year"])){
    $m = intval($_POST["month"]);
    $y = intval($_POST["year"]);
    if (($m < 1) || ($m > 16))
        echo "Wrong input data<br />";
    else{
        if ($m <= 12)
            $e = date("F", mktime(0, 0, 0, $m, 10));
        else
            $e = 'Q'.($m - 12);
        $e.= ' '.$y;
        $revenue = 0;
        $aacomp = ($revenue) * ($aaas) * 0.35;
?>
        <table border="1">
            <tr>
                <td>AW Website Revenue in <?=$e?>:</td>
                <td><?=$revenue?></td>
            </tr>
            <tr>
                <td>AA Market Share in <?=$e?>:</td>
                <td><?=$aaas?></td>
            </tr>
        </table>
<?
    }
}

?>
<form method="post">
    <table>
        <tr>
            <td><strong>Month / Quarter</strong></td>
            <td><strong>Year</strong></td>
        </tr>
        <tr>
            <td><select name="month"><?=$months?></select></td>
            <td><input name="year" value="<?=$oyear?>" size="4"></td>
        </tr>
    </table>
    <input type="SUBMIT" value="Report">
</form>
<?
echo "<strong>Additional information:</strong><br />";
echo "AA accounts total = $aac<br />";
echo "Total AwardWallet account weight = $Total<br />";
echo "AA account share = ".($aaas*100)."%";
drawFooter();
?>
