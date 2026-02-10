<?
$schema = "reportTotals";
require("../start.php");
require_once __DIR__ . "/common.php";
drawHeader("Totals");
$info = getTotals();
?>
<table cellspacing="0" cellpadding="5" border="0" style="border: Black solid 1px;">
    <tr bgcolor="#DFDFDF">
        <td height="23" colspan="3" align="center" style="font-weight: bold;">Since Saturday November 20-th, 2004:</td>
    </tr>
    <tr>
        <td height="23">Active users:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['Active'])?></td>
    </tr>
    <tr>
        <td height="23">Active users including added persons</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['ActiveTotal'])?></td>
    </tr>
    <tr>
        <td height="23">Total new users:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['TotalUsers'])?></td>
    </tr>
    <tr>
        <td height="23">Users logged in, in the last 24 hours:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['lastLogged'])?></td>
    </tr>
    <tr>
        <td height="23">Total award programs added:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['TotalAccounts'])?></td>
    </tr>
    <tr>
        <td height="23">Disabled accounts:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['DisabledAccounts'])?></td>
    </tr>
    <tr>
        <td height="23">Average award programs per user:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=$info['AveragePrograms']?></td>
    </tr>
    <tr>
        <td height="23">Number of trips:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['AllTrips'])?></td>
    </tr>
    <tr>
        <td height="23">Future air trips:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['Trips'])?></td>
    </tr>
    <tr>
        <td height="23">Future hotel reservations:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['Reservations'])?></td>
    </tr>
    <tr>
        <td height="23">Future car rentals:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['Rentals'])?></td>
    </tr>
    <tr>
        <td height="23">Number of currently paying users:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['PayingUsersNow'])?></td>
    </tr>
    <tr>
        <td height="23">Total AW Plus Profit:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=$info['TotalProfit']?></td>
    </tr>
    <tr bgcolor="#DFDFDF">
        <td height="23">AW Plus Profit this month:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=$info['MonthProfit']?></td>
    </tr>
    <tr>
        <td height="23">AW Plus users:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['AWPlus']) . " (" . round($info['AWPlus'] / $info['TotalUsers'] * 100, 1) . '%)'?></td>
    </tr>
    <tr>
        <td height="23">Total number of users who payed:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['EverPaid']) . " (" . round($info['EverPaid'] / $info['TotalUsers'] * 100, 1) . '%)'?></td>
    </tr>
    <tr>
        <td height="23">Active subscribers:</td>
        <td width="10">&nbsp;</td>
        <td style="font-weight: bold;"><?=number_format($info['Subscribers']) . " (" . round($info['Subscribers'] / $info['TotalUsers'] * 100, 1) . '%)'?></td>
    </tr>
</table>
<img src="<?=registrationsLastMonthGraph()?>">
<br>
<img src="<?=monthlyRegistrationsGraph()?>">
<br>
<?
drawFooter();
?>
