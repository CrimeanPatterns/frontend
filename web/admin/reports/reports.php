<?php
// -----------------------------------------------------------------------
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com
// -----------------------------------------------------------------------
require( "../../kernel/public.php" );
$bSecuredPage = False;
require( "$sPath/lib/admin/design/header.php" );
require_once( "../../kernel/TAccountList.php" );

$now = getdate();
$arrow = "<img src='/images/arrow.gif' border='0' style='margin-bottom: -1px'>";
if(isset($_GET["sort"])){
	switch($_GET["sort"]){
		case "carrier":
			$carrierSort = $arrow;
			$orderby = "order by ProviderName, Balance DESC";
		break;
		case "program":
			$programSort = $arrow;
			$orderby = "order by ProgramName, Balance DESC";
		break;
		case "balance":
			$balanceSort = $arrow;
			$orderby = "order by Balance DESC";
		break;
		default:
			$carrierSort = $arrow;
			$orderby = "order by ProviderName, Balance DESC";
	}
}
else{
	$carrierSort = $arrow;
	$orderby = "order by ProviderName, Balance DESC";
}
$bSecuredPage = False;

if(isset($_POST["avDate"]))
	$dispDate = $_POST["avDate"];
else{
	$dispDate = $now["year"] . "-" . $now["mon"] . "-" . $now["mday"];
	$_POST["avDate"] = $dispDate;
}
?>
<style TYPE="text/css">
      <!--
      td{
              font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
              font-size: 8pt;
              color: Black;
              font-weight : normal;
              border: 0px solid Black;
      }
	  body{
              font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
              font-size: 8pt;
              color: Black;
              font-weight : normal;
              border: 0px solid Black;
      }
		#tbBorder td{
			border-top: 1px solid Black;
			border-right: 1px solid Black;
		}
      -->
</style>
<script language="JavaScript" type="text/javascript">
var now = new Date();
var earlierdate = new Date(2004,10,20);
function timeDifference(laterdate,earlierdate) {
    var difference = laterdate.getTime() - earlierdate.getTime();
    var daysDifference = Math.floor(difference/1000/60/60/24);
    document.write('<tr><td height="23">Total days passed:</td><td width="10">&nbsp;</td><td style="font-weight: bold;">'+daysDifference+'</td></tr>');
}
</script>
<link rel="stylesheet" type="text/css" href="/design/mainStyle.css">
<script language="JavaScript" src="/design/awardWallet.js"></script>
<?
if(isset($_GET["userID"])){
	$currentUser = intval( $_GET['userID'] );
?>
<form action="/admin/reports.php" method="get" style="margin-bottom: 0px;">
User ID: <a href="/admin/reports.php?userID=<?=$currentUser-1?>"><<</a> <input type="Text" size="5" value="<?=$currentUser?>" name="userID"> <a href="/admin/reports.php?userID=<?=$currentUser+1?>">>></a><br>
<input type="Submit" value="Check user">
</form>
<?
}
if(isset($_GET["view"]) && $_GET["view"] == 2){
?>
<form action="/admin/reports.php?view=2" method="post" style="margin-bottom: 0px; margin-top: 0px;">
Accounts checked since <input type="Text" name="avDate" value="<?=$dispDate?>" style="width: 65px;"> <input type="Submit" value="go"></form>
<?
}
?>
<br>
<?php
if(isset($_GET["userID"])){
	ListCategory( PROVIDER_KIND_AIRLINE, "Airlines" );
	ListCategory( PROVIDER_KIND_HOTEL, "Hotels" );
	ListCategory( PROVIDER_KIND_CAR_RENTAL, "Car rental" );
	ListCategory( PROVIDER_KIND_TRAIN, "Trains");
	ListCategory( PROVIDER_KIND_CRUISES, "Cruises");
	ListCategory( PROVIDER_KIND_CREDITCARD, "Credit Cards");
	ListCategory( PROVIDER_KIND_OTHER, "Other Award Programs" );

	$q = new TQuery( "SELECT *, DATE_FORMAT(CreationDateTime,'%M %D, %Y (%H:%i:%S)') AS CreationDateTime, DATE_FORMAT(LastLogonDateTime,'%M %D, %Y (%H:%i:%S)') AS LastLogonDateTime, DATE_FORMAT(UpdateDate,'%M %D, %Y (%H:%i:%S)') AS UpdateDate FROM Usr Where UserID = " . addslashes(ArrayVal($_GET, 'userID', 0)), $Connection );
	if( !$q->EOF ){
?>
<table cellspacing="0" cellpadding="5" border="0" style="border-left: Black solid 1px; border-bottom: Black solid 1px;" id="tbBorder">
<tr>
	<td>User ID:</td>
	<td><?=$q->Fields["UserID"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>Login:</td>
	<td><?=$q->Fields["Login"]?>&nbsp;</td>
</tr>
<tr>
	<td>Pass:</td>
	<td><?=$q->Fields["Pass"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>Prefix:</td>
	<td><?=$q->Fields["Prefix"]?>&nbsp;</td>
</tr>
<tr>
	<td>First Name:</td>
	<td><?=$q->Fields["FirstName"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>Mid Name:</td>
	<td><?=$q->Fields["MidName"]?>&nbsp;</td>
</tr>
<tr>
	<td>Last Name:</td>
	<td><?=$q->Fields["LastName"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>Suffix:</td>
	<td><?=$q->Fields["Suffix"]?>&nbsp;</td>
</tr>
<tr>
	<td>Email:</td>
	<td><?=$q->Fields["Email"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>Age:</td>
	<td><?=$q->Fields["Age"]?>&nbsp;</td>
</tr>
<tr>
	<td>Address1:</td>
	<td><?=$q->Fields["Address1"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>Address2:</td>
	<td><?=$q->Fields["Address2"]?>&nbsp;</td>
</tr>
<tr>
	<td>City:</td>
	<td><?=$q->Fields["City"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>State:</td>
	<td><?=$q->Fields["State"]?>&nbsp;</td>
</tr>
<tr>
	<td>Zip:</td>
	<td><?=$q->Fields["Zip"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>Country:</td>
	<td><?=$q->Fields["Country"]?>&nbsp;</td>
</tr>
<tr>
	<td>Title:</td>
	<td><?=$q->Fields["Title"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>Company:</td>
	<td><?=$q->Fields["Company"]?>&nbsp;</td>
</tr>
<tr>
	<td>Phone1:</td>
	<td><?=$q->Fields["Phone1"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>IsNewsSubscriber:</td>
	<td><strong><?=$q->Fields["IsNewsSubscriber"]?></strong>&nbsp;</td>
</tr>
<tr>
	<td>CreationDateTime:</td>
	<td><?=$q->Fields["CreationDateTime"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>IsEnabled:</td>
	<td><?=$q->Fields["IsEnabled"]?>&nbsp;</td>
</tr>
<tr>
	<td>LastLogonDateTime:</td>
	<td><?=$q->Fields["LastLogonDateTime"]?>&nbsp;</td>
</tr>
<tr bgcolor='#DFDFDF'>
	<td>UpdateDate:</td>
	<td><?=$q->Fields["UpdateDate"]?>&nbsp;</td>
</tr>
</table>


<?
	}
}
if(isset($_GET["view"])){
	if($_GET["view"] == 1){
?>

<table cellspacing="0" cellpadding="7" border="0" width="100%">
<tr bgcolor="#000000">
	<td height="20" style="font-weight: bold; color: white;">ID</td>
	<td style="font-weight: bold; color: white;">User</td>
	<td style="font-weight: bold; color: white;">Err</td>
	<td style="font-weight: bold; color: white;">Error Message</td>
	<td style="font-weight: bold; color: white;">Balance</td>
	<td style="font-weight: bold; color: white;">Login 2</td>
	<td style="font-weight: bold; color: white;">Login</td>
	<td style="font-weight: bold; color: white;">Password</td>
	<td style="font-weight: bold; color: white;">Provider</td>
</tr>


<?
		$counter = 0;
		$q = new TQuery( "SELECT a.AccountID, u.FirstName, u.LastName, a.UserID, a.ErrorCode, a.ErrorMessage, a.Balance, a.Login2, a.Login, a.Pass, p.DisplayName, p.LoginURL FROM Account a INNER JOIN Usr u ON u.UserID = a.UserID INNER JOIN Provider p ON a.ProviderID = p.ProviderID Where u.CreationDateTime > '2004-11-20' AND a.ErrorCode > 1", $Connection );
		while( !$q->EOF ){
			$counter++;
			if($counter%2 == 1)
				$bgcolor = " bgcolor='#DFDFDF'";
			else
				$bgcolor =  "";
			print "<tr{$bgcolor}>
	<td height='20'>{$q->Fields["AccountID"]}</td>
	<td><a href='/admin/reports.php?userID={$q->Fields["UserID"]}'>{$q->Fields["FirstName"]} {$q->Fields["LastName"]}</a></td>
	<td>{$q->Fields["ErrorCode"]}</td>
	<td>{$q->Fields["ErrorMessage"]}</td>
	<td>{$q->Fields["Balance"]}</td>
	<td>{$q->Fields["Login2"]}</td>
	<td>{$q->Fields["Login"]}</td>
	<td>{$q->Fields["Pass"]}</td>
	<td><a target='_blank' href='{$q->Fields["LoginURL"]}'>{$q->Fields["DisplayName"]}</a></td>
</tr>";
			$q->Next();
		}
		print "</table>";
	}

	if($_GET["view"] == 2){
?>

<table cellspacing="0" cellpadding="7" border="0" width="100%">
<tr bgcolor="#000000">
	<td height="20" style="font-weight: bold; color: white;">ID</td>
	<td style="font-weight: bold; color: white;">User</td>
	<td style="font-weight: bold; color: white;">Err</td>
	<td style="font-weight: bold; color: white;">Error Message</td>
	<td style="font-weight: bold; color: white;">Balance</td>
	<td style="font-weight: bold; color: white;">Login 2</td>
	<td style="font-weight: bold; color: white;">Login</td>
	<td style="font-weight: bold; color: white;">Password</td>
	<td style="font-weight: bold; color: white;">Provider</td>
	<td style="font-weight: bold; color: white;">Last Updated</td>
</tr>


<?
		$counter = 0;
		$fromDate = $_POST["avDate"] . " 00:00:00";
		$today = "{$now["year"]}-{$now["mon"]}-{$now["mday"]} 00:00:00";
		$q = new TQuery( "SELECT a.AccountID, u.FirstName, u.LastName, a.UserID, a.ErrorCode, a.ErrorMessage, a.Balance, a.Login2, a.Login, a.Pass, p.DisplayName, p.LoginURL, a.UpdateDate, a.CreationDate FROM Account a INNER JOIN Usr u ON u.UserID = a.UserID INNER JOIN Provider p ON a.ProviderID = p.ProviderID Where u.CreationDateTime > '2004-11-20' AND (a.UpdateDate > '".addslashes($fromDate)."' OR (a.UpdateDate IS NULL AND a.CreationDate > '".addslashes($fromDate)."')) ORDER BY a.UpdateDate DESC", $Connection );
		while( !$q->EOF ){
			$uDate = $q->Fields["UpdateDate"];
			if($q->Fields["UpdateDate"]=="")
				$uDate = $q->Fields["CreationDate"];
			$counter++;
			if($counter%2 == 1)
				$bgcolor = " bgcolor='#DFDFDF'";
			else
				$bgcolor =  "";
			print "<tr{$bgcolor}>
	<td height='20'>{$q->Fields["AccountID"]}</td>
	<td><a href='/admin/reports.php?userID={$q->Fields["UserID"]}'>{$q->Fields["FirstName"]} {$q->Fields["LastName"]}</a></td>
	<td>{$q->Fields["ErrorCode"]}</td>
	<td>{$q->Fields["ErrorMessage"]}</td>
	<td>{$q->Fields["Balance"]}</td>
	<td>{$q->Fields["Login2"]}</td>
	<td>{$q->Fields["Login"]}</td>
	<td>{$q->Fields["Pass"]}</td>
	<td><a target='_blank' href='{$q->Fields["LoginURL"]}'>{$q->Fields["DisplayName"]}</a></td>
	<td>$uDate</td>
</tr>";
			$q->Next();
		}
		print "</table>";
	}
	if($_GET["view"] == 3){
?>

<table cellspacing="0" cellpadding="7" border="0">
<tr bgcolor="#000000">
	<td style="font-weight: bold; color: white;">Provider</td>
	<td style="font-weight: bold; color: white;">Maximum Balance</td>
	<td style="font-weight: bold; color: white;">Times added</td>
</tr>


<?
		$counter = 0;
		$q = new TQuery( "SELECT p.DisplayName, p.loginURL, MAX(a.Balance) AS maxPoints, COUNT(a.Balance) as timesAdded
FROM Provider p
LEFT OUTER JOIN Account a ON a.ProviderID = p.ProviderID
GROUP BY p.DisplayName, p.loginURL
ORDER BY timesAdded, maxPoints", $Connection );
		while( !$q->EOF ){
			$counter++;
			if($counter%2 == 1)
				$bgcolor = " bgcolor='#DFDFDF'";
			else
				$bgcolor =  "";
			print "<tr{$bgcolor}>
	<td><a target='_blank' href='{$q->Fields["loginURL"]}'>{$q->Fields["DisplayName"]}</a></td>
	<td height='20'>". number_format($q->Fields["maxPoints"]) . "</td>
	<td height='20'>{$q->Fields["timesAdded"]}</td>
</tr>";
			$q->Next();
		}
		print "</table>";
	}
	if($_GET["view"] == 4){
?>
SELECT p.DisplayName, p.loginURL, MAX(a.Balance) AS maxPoints, COUNT(a.Balance) as timesAdded<br>
FROM Provider p<br>
LEFT OUTER JOIN Account a ON a.ProviderID = p.ProviderID<br>
GROUP BY p.DisplayName, p.loginURL<br>
ORDER BY timesAdded, maxPoints<br>
<br><br>
<table cellspacing="0" cellpadding="5" border="0" style="border-left: Black solid 1px; border-bottom: Black solid 1px;" id="tbBorder">
<tr>
	<td>User</td>
	<td>Registered on</td>
	<td>Last logged in on</td>
	<td># of programs</td>
</tr>
<?
		$counter = 0;
		$q = new TQuery( "SELECT u.UserID, u.FirstName, u.LastName, DATE_FORMAT(u.CreationDateTime,'%M %D, %Y (%H:%i:%S)') AS CreationDateTime, DATE_FORMAT(u.LastLogonDateTime,'%M %D, %Y (%H:%i:%S)') AS LastLogonDateTime, count(a.AccountID) as programCount
FROM Usr u
INNER JOIN Account a ON a.UserID = u.UserID
WHERE u.CreationDateTime <> u.LastLogonDateTime AND u.UserID NOT IN(5, 7, 9, 12, 47, 58, 81)
GROUP BY UserID, FirstName, LastName, DATE_FORMAT(u.CreationDateTime,'%M %D, %Y (%H:%i:%S)'), DATE_FORMAT(u.LastLogonDateTime,'%M %D, %Y (%H:%i:%S)')
ORDER BY LastLogonDateTime", $Connection );
		while( !$q->EOF ){
			$counter++;
			if($counter%2 == 1)
				$bgcolor = " bgcolor='#DFDFDF'";
			else
				$bgcolor =  "";
			print "<tr{$bgcolor}>
	<td height='20'><a href='/admin/reports.php?userID={$q->Fields["UserID"]}'>{$q->Fields["FirstName"]} {$q->Fields["LastName"]}</a></td>
	<td>{$q->Fields["CreationDateTime"]}</td>
	<td>{$q->Fields["LastLogonDateTime"]}</td>
	<td>{$q->Fields["programCount"]}</td>
</tr>";
			$q->Next();
		}
		print "</table>";
		print "<br>Total number of users that return to the site is: <strong>$counter</strong>";
	}
	if(intval($_GET["view"]) == 5){
?>
<table cellspacing="0" cellpadding="5" border="0" style="border-left: Black solid 1px; border-bottom: Black solid 1px;" id="tbBorder">
<tr>
	<td bgcolor="black" style="color: white;">Award Programs:</td>
</tr>
<?
		$counter = 0;
		$q = new TQuery( "select distinct p.DisplayName
from Provider p, Account a
left outer join Trip t on a.AccountID = t.AccountID
left outer join Rental r on a.AccountID = r.AccountID
left outer join Reservation v on a.AccountID = v.AccountID
where p.ProviderID = a.ProviderID
and ( t.AccountID is not null or r.AccountID is not null or v.AccountID is not null )
order by p.DisplayName" );
		while( !$q->EOF ){
			$counter++;
			if($counter%2 == 1)
				$bgcolor = " bgcolor='#DFDFDF'";
			else
				$bgcolor =  "";
			print "<tr{$bgcolor}>
	<td height='20'>{$q->Fields["DisplayName"]}</td></tr>";
			$q->Next();
		}
		print "</table>";
	}
}

function ListCategory( $nKind, $sListCaption )
{
  global $Connection, $carrierSort, $programSort, $balanceSort, $orderby;
  		$carrierWidth = 600;
  $q = new TQuery( "select CONVERT('Account' USING utf8) as TableName, a.AccountID as ID, a.Login, a.Pass, a.Balance, a.ErrorCode, a.ErrorMessage, a.State, a.Pass, p.Code as ProviderCode, coalesce( p.Name, 'Custom' ) as ProviderName, p.LoginURL, coalesce( p.ProgramName, a.ProgramName ) as ProgramName, CONVERT(REPEAT( 'x', 80 ) USING utf8) as Description, CONVERT(REPEAT( 'x', 128 ) USING utf8) as Value, a.ExpirationDate, a.comment, a.ProviderID from Account a left outer join Provider p on a.ProviderID = p.ProviderID where a.UserID = '".addslashes(ArrayVal($_GET, 'userID', 0))."' and ( p.Kind = $nKind or p.Kind is null )
  union select CONVERT('Coupon' USING utf8) as TableName, c.ProviderCouponID as ID, CONVERT(NULL USING utf8) as Login, CONVERT(NULL USING utf8) as Pass, CONVERT(0 USING utf8) as Balance, CONVERT(NULL USING utf8) as ErrorCode, CONVERT(NULL USING utf8) as ErrorMessage, CONVERT(NULL USING utf8) as State, CONVERT(NULL USING utf8) as Pass, p.Code as ProviderCode, p.Name as ProviderName, p.LoginURL, p.ProgramName, c.Description as Description, c.Value, c.ExpirationDate as ExpirationDate, CONVERT(NULL USING utf8) as comment, c.ProviderID from ProviderCoupon c, Provider p where c.ProviderID = p.ProviderID and c.UserID = '".addslashes(ArrayVal($_GET, 'userID', 0))."' and p.Kind = $nKind
  $orderby" );
  if( !$q->EOF )
  {
  $carrierTitle = "Carrier";
	if($nKind == PROVIDER_KIND_HOTEL)
		$carrierTitle = "Brands";
	elseif($nKind == PROVIDER_KIND_CAR_RENTAL)
		$carrierTitle = "Company";
	elseif($nKind == PROVIDER_KIND_CREDITCARD)
		$carrierTitle = "Credit Card";
	elseif($nKind == PROVIDER_KIND_OTHER)
		$carrierTitle = "Company";
    echo "<div style=\"font-size: 22px; font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; letter-spacing: 4px; margin-bottom: -8px;\">$sListCaption</div><hr>";
    echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\" style=\"border-top-width: 1px;\">
    <tr bgcolor=\"#3D327E\">
        <td width=\"5\" style=\"border-left-width: 1px;\" height='20'>&nbsp;</td>
        <td style=\"color: White;\"><!--a href=\"#\" class=\"myWhite\">Status</a-->&nbsp;</td>
        <td width=\"5\">&nbsp;</td>
        <td width=\"5\" style=\"border-left-width: 1px;\">&nbsp;</td>

        <td style=\"color: White;\"><a href=\"/account/list.php?sort=carrier\" class=\"myWhite\">$carrierTitle $carrierSort</a></td>
        <td width=\"5\">&nbsp;</td>
        <td width=\"5\" style=\"border-left-width: 1px;\">&nbsp;</td>

        <td style=\"color: White;\"><a href=\"/account/list.php?sort=program\" class=\"myWhite\">Award Program \ Description $programSort</a> </td>
        <td width=\"5\">&nbsp;</td>
        <td width=\"5\" style=\"border-left-width: 1px;\">&nbsp;</td>
        <td style=\"color: White;\"><a href=\"/account/list.php?sort=balance\" class=\"myWhite\">Balance $balanceSort</a></td>
        <td width=\"5\">&nbsp;</td>
        <td width=\"5\" style=\"border-left-width: 1px;\">&nbsp;</td>
        <td width=\"5\"><a href=#>Expiration Date</a></td>
        <td width=\"5\" style=\"border-right-width: 1px;\">&nbsp;</td>
    </tr>";
    while( !$q->EOF )
    {
      $sRowStyle = "";
      if( ( $q->Position % 2 ) == 0 )
        $sRowStyle = " bgcolor=\"#E1DCFC\"";
      if( $q->Fields["TableName"] == "Account" )
      {
	  	$balance = number_format($q->Fields["Balance"], 2, '.', ',');
		if(substr($balance, -2) == "00")
			$balance = substr($balance, 0, strlen($balance)-3);
#echo "<script>alert('".$q->Fields["ProviderName"]." - ".$q->Fields["ErrorCode"]."')</script>";
        if( ( $q->Fields["ErrorCode"] != ACCOUNT_CHECKED ) && ( $q->Fields["ErrorCode"] != ACCOUNT_WARNING ) && ( $q->Fields["ErrorCode"] != ACCOUNT_UNCHECKED ) )
          $sRowStyle = " bgcolor=\"#FFA3A3\"";
        $sExpirationDate = "&nbsp;";
        if( $q->Fields['ExpirationDate'] != '' ){
        	$d = $Connection->SQLToDateTime($q->Fields["ExpirationDate"]);
        	$sExpirationDate = date( DATE_FORMAT, $d );
          	$nExpires = ( time() - $d ) / SECONDS_PER_DAY;
          	if( ( $nExpires > -90 ) && ( $nExpires <= 0 ) )
          		$sExpirationDate = "<table id='tblNoBorder' cellspacing='0' cellpadding='0' border='0'><tr><td width='18'><a href='#' onclick=\"showExpirationWarning('The miles / points on this award program are due to expire on ".date(DATE_LONG_FORMAT, $d)."', 'warning'); return false;\"><img src='/images/warning.gif' border='0' style='margin-right: 4px;' title='Expiration warning'></a></td><td>" . $sExpirationDate . "</td></tr></table>";
          	if( $nExpires <= -90 )
          		$sExpirationDate = "<table id='tblNoBorder' cellspacing='0' cellpadding='0' border='0'><tr><td width='18'>&nbsp;<a href='#' onclick=\"showExpirationWarning('The miles / points on this award program are due to expire on ".date(DATE_LONG_FORMAT, $d)."', 'success'); return false;\"><img src='/images/success.gif' border='0' style='margin-right: 4px;' title='Expiration date'></a></td><td>" . $sExpirationDate . "</td></tr></table>";
          	if( $nExpires > 0 )
          		$sExpirationDate = "<table id='tblNoBorder' cellspacing='0' cellpadding='0' border='0'><tr><td width='18'><a href='#' onclick=\"showExpirationWarning('Some miles / points on this award program might have expired on ".date(DATE_LONG_FORMAT, $d)."', 'error'); return false;\"><img src='/images/error.gif' border='0' style='margin-right: 4px;' title='Miles have expired'></a></td><td>" . $sExpirationDate . "</td></tr></table>";
        }
        echo "<tr{$sRowStyle} height='25'>
          <td style=\"border-left-width: 1px;\">&nbsp;</td>
          <td height=\"20\" width='24' align='center'><a href='javascript:showStatus(".$q->Fields["ID"].", \"program\", ".$q->Fields["ErrorCode"].")'>" . AccountState( $q->Fields ) . "</a></td>
          <td>&nbsp;</td>
          <td style=\"border-left-width: 1px;\">&nbsp;</td>
          <td height=\"20\" width='$carrierWidth'>{$q->Fields["ProviderName"]}</td>
          <td>&nbsp;</td>
          <td style=\"border-left-width: 1px;\">&nbsp;</td>
          <td><a href=\"".$q->Fields["LoginURL"]."\" target=_blank>{$q->Fields["ProgramName"]}</a> <span style='font-weight: normal;'>[account: {$q->Fields["Login"]}, password: {$q->Fields["Pass"]}]  {$q->Fields["comment"]}</span></td>
          <td>&nbsp;</td>
          <td style=\"border-left-width: 1px;\">&nbsp;</td>
          <td width='65'>" . ( $q->Fields["ErrorCode"] == ACCOUNT_UNCHECKED ? "" : $balance ) . "</td>
          <td>&nbsp;</td>
          <td style=\"border-left-width: 1px;\">&nbsp;</td>
          <td>{$sExpirationDate}</td>
          <td style=\"border-right-width: 1px;\">&nbsp;</td>
        </tr>";
       //if( ( $q->Fields["ErrorCode"] != ACCOUNT_CHECKED ) && ( $q->Fields["ErrorCode"] != ACCOUNT_UNCHECKED ) )
       //echo "<tr bgcolor=\"#FFA3A3\"><td colspan=23 height=20 align=center style=\"border-left-width: 1px; border-top-width: 1px; border-bottom-width: 1px; border-right-width: 1px;\">" . ErrorMessage( $q->Fields["ErrorCode"], $q->Fields["ErrorMessage"] ) . "</td></tr>";
      }
      else
      {
        $sCouponState = CouponState( $q->Fields );
        if( $sCouponState == "3" )
          $sRowStyle = " bgcolor=\"#FFA3A3\"";
        echo "<tr{$sRowStyle}>
          <td style=\"border-left-width: 1px;\">&nbsp;</td>
          <td height=\"20\" align='center'><a href='javascript:showStatus(".$q->Fields["ID"].", \"coupon\", $sCouponState)'>".CouponIcon($sCouponState)."</a></td>
          <td>&nbsp;</td>
          <td style=\"border-left-width: 1px;\">&nbsp;</td>
          <td height=\"20\">{$q->Fields["ProviderName"]}</td>
          <td>&nbsp;</td>
          <td style=\"border-left-width: 1px;\">&nbsp;</td>
          <td>
				<table cellspacing='0' cellpadding='0' border='0'>
				<tr>
					<td style='font-weight: normal;'>Coupon Description:</td>
					<td width='10'>&nbsp;</td>
					<td>{$q->Fields["Description"]}</td>
				</tr>
				<tr>
					<td style='font-weight: normal;'>Coupon Value:</td>
					<td width='10'>&nbsp;</td>
					<td>{$q->Fields["Value"]}</td>
				</tr>
				<tr>
					<td style='font-weight: normal;'>Coupon Expiration:</td>
					<td width='10'>&nbsp;</td>
					<td>" . (is_null($q->Fields['ExpirationDate'])) ? '' : date( DATE_FORMAT, $Connection->SQLToDateTime($q->Fields["ExpirationDate"])) . "</td>
				</tr>
				</table>
		  </td>
          <td>&nbsp;</td>
          <td style=\"border-left-width: 1px;\">&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td style=\"border-left-width: 1px;\">&nbsp;</td>
          <td>&nbsp;</td>
          <td style=\"border-right-width: 1px;\">&nbsp;</td>
        </tr>";
       //if( ( $q->Fields["ErrorCode"] != ACCOUNT_CHECKED ) && ( $q->Fields["ErrorCode"] != ACCOUNT_UNCHECKED ) )
       //echo "<tr bgcolor=\"#FFA3A3\"><td colspan=23 height=20 align=center style=\"border-left-width: 1px; border-top-width: 1px; border-bottom-width: 1px; border-right-width: 1px;\">" . ErrorMessage( $q->Fields["ErrorCode"], $q->Fields["ErrorMessage"] ) . "</td></tr>";
      }
      $q->Next();
    }
    echo "<tr>
        <td colspan=\"23\" style=\"border-top-width: 1px;\">
        &nbsp;
        </td>
    </tr>
    </table>";
  }
}

?>

<script language="JavaScript" type="text/javascript">
function showStatus(id, mode, errorCode){
	alert("sorry, can't do that from this view");
}
function checkAll(){
	alert("sorry, can't do that from this view");
}
</script>

<?
require("$sPath/lib/admin/design/footer.php");
?>
