<?
require "../kernel/public.php";
require_once "common.php";
require_once("renewCommon.php");

AuthorizeUser();
$nID = intval( $QS["ID"] );
GetAgentFilters( $_SESSION['UserID'], $_SESSION['UserAgentID'], $sUserAgentAccountFilter, $sUserAgentCouponFilter );
$q = new TQuery("select a.*, AccountID as ID from Account a where ( $sUserAgentAccountFilter ) and a.AccountID = $nID");
if ( $q->EOF )
	die("Access to this account is denied");
$qProv = new TQuery("select * from Provider where ProviderID = {$q->Fields["ProviderID"]}");
if($q->Fields["RenewProperties"] != "")
	$arProperties = unserialize($q->Fields["RenewProperties"]);
else
	$arProperties = array(
		"Program Name" => $qProv->Fields["DisplayName"],
		"Expiring Balance" => number_format_localized($q->Fields["Balance"], 0),
		"Expires on" => date(DATE_LONG_FORMAT, $Connection->SQLToDateTime($q->Fields["ExpirationDate"])),
	);
if($q->Fields['RenewNote'] != '')
	$qProv->Fields['RenewNote'] = $q->Fields['RenewNote'];
?>
<table cellpadding=5 cellspacing=0 border=0>
<?
foreach($arProperties as $sKey => $sValue) {
?>
    <tr>
        <td><?=$sKey?>:</td>
        <td style="font-weight: bold;"><?=$sValue?></td>
    <tr>
<?
}
?>
</table>
<br>
<?=ReplaceRenewMacros($qProv->Fields["RenewNote"], $q->Fields)?>
