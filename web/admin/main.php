<?php
// -----------------------------------------------------------------------
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com
// -----------------------------------------------------------------------
require_once( "$sPath/manager/reports/common.php" );
//$q = new TQuery("select  /*! STRAIGHT_JOIN */ count(*) as BackLog from Account a, Provider p, Usr u  where a.ProviderID = p.ProviderID and a.UserID = u.UserID and a.ErrorCode in( 8, 1, 9, 4, 6, 5, 0 ) and p.State = 1 and p.CanCheck = 1 and a.UpdateDate < DATE_SUB(now(), INTERVAL 1 day) and a.SavePassword = 1 and a.Pass <> '' ");
//$backLog = $q->Fields["BackLog"];
$info = getTotals();
?>
<script language="JavaScript" type="text/javascript">
var now = new Date();
var earlierdate = new Date(2004,10,20);
function timeDifference(laterdate,earlierdate) {
    var difference = laterdate.getTime() - earlierdate.getTime();
    var daysDifference = Math.floor(difference/1000/60/60/24);
    document.write('<tr><td height="23">Total days passed:</td><td width="10">&nbsp;</td><td style="font-weight: bold;">'+daysDifference+'</td></tr>');
}
</script>
<table cellspacing="0" cellpadding="5" border="0" style="border: Black solid 1px;">
<tr bgcolor="#000000">
	<td height="23" colspan="3" align="center" style="font-weight: bold; color: white;">Since Saturday November 20-th, 2004:</td>
</tr>
<tr bgcolor="#DFDFDF">
	<td height="23">Total new users: </td>
	<td width="10">&nbsp;</td>
	<td style="font-weight: bold;"><?=$info['TotalUsers']?></td>
</tr>
<script>
timeDifference(now,earlierdate);
</script>
<tr bgcolor="#DFDFDF">
	<td height="23">Total award programs added: </td>
	<td width="10">&nbsp;</td>
	<td style="font-weight: bold;"><?=$info['TotalAccounts']?></td>
</tr>
<tr>
	<td height="23">Average award programs per user:</td>
	<td width="10">&nbsp;</td>
	<td style="font-weight: bold;"><?=$info['AveragePrograms']?></td>
</tr>
<tr bgcolor="#DFDFDF">
	<td height="23">Number of programs checked today:</td>
	<td width="10">&nbsp;</td>
	<td style="font-weight: bold;"><?=$info['CheckedSinceToday']?></td>
</tr>
<tr>
	<td height="23">Number of users who checked balances today:</td>
	<td width="10">&nbsp;</td>
	<td style="font-weight: bold;"><?=$info['UsersSinceToday']?></td>
</tr>
<tr bgcolor="#DFDFDF">
	<td height="23">Number of returning users:</td>
	<td width="10">&nbsp;</td>
	<td style="font-weight: bold;"><?=$info['ReturningUsers']?></td>
</tr>
<tr>
	<td height="23">Number of trips:</td>
	<td width="10">&nbsp;</td>
	<td style="font-weight: bold;"><?=$info['Trips']?></td>
</tr>
<tr>
	<td height="23">Total number of users who payed:</td>
	<td width="10">&nbsp;</td>
	<td style="font-weight: bold;"><?=$info['TotalPayingUsers']?></td>
</tr>
<tr bgcolor="#DFDFDF">
	<td height="23">Number of currently paying users:</td>
	<td width="10">&nbsp;</td>
	<td style="font-weight: bold;"><?=$info['PayingUsersNow']?></td>
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
<!--tr>
	<td height="23">Backlog of accounts to be checked:</td>
	<td width="10">&nbsp;</td>
	<td style="font-weight: bold;"><?//=$backLog?></td>
</tr-->
</table>
<img src="<?=registrationsLastMonthGraph()?>">
<br>
<img src="<?=monthlyRegistrationsGraph()?>">
