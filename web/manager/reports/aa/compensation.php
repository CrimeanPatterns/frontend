<?
$schema = "qaac";
require "../../start.php";
require_once __DIR__ . "/../common.php";
drawHeader("Quarterly AA Compensation");

$startingPoint = '2013-08-01';
$paymentTypes = array(
    PAYMENTTYPE_ANDROIDMARKET ,
    PAYMENTTYPE_APPSTORE,
    PAYMENTTYPE_CREDITCARD ,
    PAYMENTTYPE_PAYPAL,
);

$curMonth = date("n");
$curYear = date("Y");
$curQuarter = floor(($curMonth - 1) / 3);
$xm = 17;

if (isset($_GET["month"]) && isset($_GET["year"])) {

    $m = $xm = (int)$_GET["month"];
    $y = (int)$_GET["year"];

    if (($m < 1) || ($m > 16)) { // 13..16 - quarters
        die("Wrong input data: month");
    }

    if ($m <= 12) {
        // single month selected
        $periodTitle = date("F", mktime(0, 0, 0, $m, 10));
        $startYear = $endYear = $y;
        $startMonth = $endMonth = $m;
        $curMonth = $m;
        $curYear = $y;
    }
    else{
        // quarter (13..16) selected
        $curYear = $y;
        $curQuarter = $m - 12;
        $periodTitle = 'Q' . $curQuarter;
        $m = 3 * ($m - 12) - 2;
        $startYear = $endYear = $y;
        $startMonth = $m;
        $endMonth = $m + 2;
    }

    $periodTitle.= ' '.$y;

    //TODO: No AdIncome finally calculated this month
    $revenue = 0;
    $aaComp = 0;
    $noAdData = false;
    $shar = '';
    $ocFee = 0;
    for ($mm = $startMonth; $mm <= $endMonth; $mm++) {
        echo "Calculating profit for {$mm}/{$y}";
        $ar = getProfit(mktime(0, 0, 0, $mm, 1, $curYear), mktime(0, 0, 0, $mm + 1, 1, $curYear));
        $r = $ar["nTotalProfit"];
        echo ", paid carts ({$ar['nTransactions']}): " . number_format($ar["nTotalProfit"], 2);
        $q = new TQuery("select Income from AdIncome where PayDate >= '$startingPoint' and year(PayDate) >= '$startYear' and month(PayDate) >= '$mm' and month(PayDate) <= '$mm' and year(PayDate) <= '$endYear'");
        if ((!$q->EOF)  && (strtotime("$y-$mm-01") >= strtotime('2013-08-01'))) {
            $r += $q->Fields['Income'];
            echo ", ad income: " . number_format($q->Fields['Income'], 2);
        }
        if ($q->EOF && !(($mm <= 7) && ($y <= 2013)))
            $noAdData = true;
        $revenue += $r;
        $yyn = $y;
        $mmn = $mm+1;
        if ($mmn > 12){
            $mmn = 1;
            $yyn++;
        }
        calcOneCards("'$y-$mm-01'", "'$yyn-$mmn-01'", $oneCardsCount, $oneCardsFee);
        echo ", onecards fee: " . number_format($oneCardsFee, 2);
        $revenue -= $oneCardsFee;
        $q = new TQuery("select Share from AAShare where CountDate >= '$startingPoint' and year(CountDate) >= '$y' and month(CountDate) >= '$mm' and month(CountDate) <= '$mm' and year(CountDate) <= '$y'");
        if ((!$q->EOF) && ($q->Fields['Share'] != 0))
            $aaas = $q->Fields['Share'];
        else $aaas = 0;
        $ee = date("F", mktime(0, 0, 0, $mm, 10)).' '.$y;
        $shar .= "<tr><td>AA Market Share in $ee:</td><td>".($aaas*100)."%</td></tr>";
        echo ", aa market share: {$aaas}";
        $acmp = ($r) * ($aaas) * 0.35;
        echo ", aa compensation: " . number_format($acmp, 2);
        if (($acmp < 1500) && (strtotime("$y-$mm-01") >= strtotime('2013-08-01')))
            $acmp = 1500;
        echo ", aa compensation corrected: " . number_format($acmp, 2). "<br/>";
        $aaComp += $acmp;
    }
    $revenue = '$'.number_format($revenue - $ocFee, 2, '.', ',');
    $aaComp = '$'.number_format($aaComp, 2, '.', ',');
    if ($noAdData){
        $revenue .= ' + unknown advertising revenue';
        $aaComp = 'Not yet known without advertising revenue totals';
    }
?>
    <table border="1">
        <tr>
            <td>AW Website Revenue in <?=$periodTitle?>:</td>
            <td><?=$revenue?></td>
        </tr>
        <?=$shar?>
        <tr>
            <td>AA Compensation in <?=$periodTitle?>:</td>
            <td><?=$aaComp?></td>
        </tr>
    </table>
<?
}
if ($curQuarter == 0 && $xm == 17){
    $curYear--;
    $curQuarter = 4;
}
$months = "";
for ($q = 1; $q <= 4; $q++) {
    $months.='<option value="'.($q + 12).'"';
    if (($curQuarter == $q) && ($xm > 12))
        $months.=' selected';
    $months.= '>Q'.$q.'</option>';
}
for ($m = 1; $m <= 12; $m++) {
    $f = date('F', mktime(0,0,0,$m));
    $months.='<option value="'.$m.'"';
    if (($curMonth == $m) && ($xm <= 12))
        $months.=' selected';
    $months.='>'.$f.'</option>';
}
?>
<form method="get">
    <table>
        <tr>
            <td><strong>Month / Quarter</strong></td>
            <td><strong>Year</strong></td>
        </tr>
        <tr>
            <td><select name="month"><?=$months?></select></td>
            <td><input name="year" value="<?=$curYear?>" size="4"></td>
        </tr>
    </table>
    <input type="SUBMIT" value="Report">
</form>
<?
drawFooter();
?>
