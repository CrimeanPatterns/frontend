<?
$schema = "qaac";
require "../../start.php";
drawHeader("Account totals");
// $q = new TQuery("select count(*) as c from Usr where Usr.UserID in (select UserID from Account a join Provider p on a.ProviderID = p.ProviderID where p.Kind = 1)");
// $totalalu = $q->Fields["c"];
$q = new TQuery("SELECT COUNT(*) as c FROM (SELECT DISTINCT UserID, UserAgentID FROM Account a join Provider p on a.ProviderID = p.ProviderID where p.Kind = 1) as x");
$totalalu = number_format($q->Fields["c"], 0, '.', ',');
$q = new TQuery("SELECT COUNT(*) as c FROM (SELECT DISTINCT UserID, UserAgentID FROM Account a join Provider p on a.ProviderID = p.ProviderID where p.Code = 'aa') as x");
$totalaau = number_format($q->Fields["c"], 0, '.', ',');
$q = new TQuery("SELECT COUNT(*) as c FROM (SELECT DISTINCT UserID, UserAgentID FROM Account a join Provider p on a.ProviderID = p.ProviderID where p.Code = 'dividendmiles') as x");
$totalusu = number_format($q->Fields["c"], 0, '.', ',');
$q = new TQuery("select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where p.Code = 'aa'");
$totalaaa = number_format($q->Fields["c"], 0, '.', ',');
$q = new TQuery("select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where p.Code = 'dividendmiles'");
$totalusa = number_format($q->Fields["c"], 0, '.', ',');
$q = new TQuery("select count(*) as c from Account a join Provider p on a.ProviderID = p.ProviderID where p.Kind = 1");
$totalala = number_format($q->Fields["c"], 0, '.', ',');
?>
<table border="1">
    <tr><td>&nbsp;</td><td>All Airline Loyalty Programs</td><td>AAdvantage</td><td>US Airways</td></tr>
    <tr><td>Total Members</td><td><?=$totalalu?></td><td><?=$totalaau?></td><td><?=$totalusu?></td></tr>
    <tr><td>Total Accounts</td><td><?=$totalala?></td><td><?=$totalaaa?></td><td><?=$totalusa?></td></tr>
</table>
<?
drawFooter();
?>
