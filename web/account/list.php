<?
require( "../kernel/public.php" );
require_once($sPath . "/kernel/TAWTabs.php");
require_once("common.php");
require_once("commonFuncForList.php");

$sTitle = "AwardWallet.com";

if (!isset($_GET['Excel']) && !isset($_GET['pdf'])) {
    Redirect('/account/list');
}
$_GET['UserAgentID'] = 'All';

if (isBusinessMismanagement() && (isset($_GET['Excel']) || isset($_GET['pdf']))) {
	unset($_GET['Excel'], $_GET['pdf']);
}

# begin determining menus
require($sPath."/design/topMenu/main.php");
require($sPath."/design/leftMenu/award.php");
# end determining menus

if(SITE_MODE == SITE_MODE_BUSINESS){
	if (isGranted('USER_BOOKING_PARTNER') && !isGranted('USER_BOOKING_MANAGER'))
		Redirect('/awardBooking/queue');

	if(isset($_GET['ProviderID'])){
		$topMenu['Accounts']['selected'] = true;
	}
	else
		$topMenu['Members']['selected'] = true;
}

if(isset($_GET['StartCheck'])){
	if(intval($_GET['StartCheck']) <= 1){
		$_SESSION['StartCheck'] = 'server';
		if(isset($_GET['ProviderID']) && Lookup("Provider", "ProviderID", "CheckInBrowser", intval($_GET['ProviderID'])) != '0')
			$_SESSION['StartCheck'] = 'extension';
	}
	else
		$_SESSION['StartCheck'] = intval($_GET['StartCheck']);
	$ar = $_GET;
	unset($ar['StartCheck']);
	Redirect($_SERVER['SCRIPT_NAME']."?".ImplodeAssoc("=", "&", $ar, true));
}

$pProviderID = intval(ArrayVal( $_GET, "ProviderID", "0" ));
$nUserAgentID = ArrayVal( $_GET, "UserAgentID", "0" );
if(strcasecmp($nUserAgentID, 'All') === 0){ // it could be ?UserAgentI=all, so any case we will convert to "All"
	$nUserAgentID = 'All';
}else{
	$nUserAgentID = intval($nUserAgentID);
}
CheckUserAgentID($nUserAgentID);

if(SITE_MODE == SITE_MODE_BUSINESS && $nUserAgentID == "All" && empty($pProviderID))
	Redirect("overview.php");
			
GetAgentFilters( $_SESSION['UserID'], $nUserAgentID, $sUserAgentAccountFilter, $sUserAgentCouponFilter, false, false, SITE_MODE == SITE_MODE_PERSONAL);
$_SESSION['UserAgentID'] = $nUserAgentID;

$Interface->CssFiles[] = "/lib/3dParty/jquery/plugins/tips/tipTip.elite.css";
$Interface->ScriptFiles[] = "/lib/3dParty/jquery/plugins/tips/jquery.tipTip.js";

require( "../design/header.php" );

$arFilters = GetAccountFilters($nUserAgentID);
$tabs = GetAccountTabs($arFilters);

$aCount = 0;
$cCount = 0;
$customCount = 0;

$SQL = "SELECT
			COUNT(a.accountID) AS customCount
		FROM 
			Account a 
		WHERE
		    a.ProviderID is null
			AND $sUserAgentAccountFilter";
$q = new TQuery($SQL);
if(!$q->EOF){
    $customCount = $q->Fields["customCount"];
}
$q->Close();

$SQL = "SELECT
			COUNT(a.accountID) AS aCount
		FROM
			Account a
			JOIN Provider p ON p.ProviderID = a.ProviderID AND ".userProviderFilter()."
		WHERE
			a.State > 0 AND
			$sUserAgentAccountFilter";
$q = new TQuery($SQL);
if(!$q->EOF){
	$aCount = $q->Fields["aCount"];
}
$q->Close();

$SQL = "SELECT (SELECT COUNT(c.ProviderCouponID)
		FROM ProviderCoupon c 
		WHERE $sUserAgentCouponFilter)
		+
		(SELECT COUNT(DISTINCT(a.accountID))
		FROM Account a, SubAccount sa, Provider p		
		WHERE a.accountID = sa.accountID
		AND p.ProviderID = a.ProviderID 
		AND ".userProviderFilter()."
		AND sa.Kind='C' AND ($sUserAgentAccountFilter)) AS cCount";
$q = new TQuery($SQL);
if(!$q->EOF)
	$cCount = $q->Fields["cCount"];
$q->Close();
$SQL = "SELECT a.AccountID, a.Login, a.Balance, p.DisplayName FROM Account a JOIN Provider p on a.ProviderID = p.ProviderID WHERE UserID = {$_SESSION["UserID"]} AND a.State = ".ACCOUNT_PENDING;
$qPending = new TQuery($SQL);
$cPending = 0;
foreach ($qPending as $fields) {
	$ID = $qPending->Fields["AccountID"];
	if (!empty($_COOKIE["skipPendingAll"])) {
		$cPending = 0;
		break;
	}
	if (empty($_COOKIE["skipPending" . $ID])) {
		$cPending++;
		if (!isset($pFields))
			$pFields = $qPending->Fields;
	}
}
# begin - only if there are programs or coupons added put stuff on the page...
if($aCount > 0 || $cCount > 0 || $customCount > 0){

// Offer part beginning
function checkOfferImpersonate(){
    if (!isGranted('ROLE_IMPERSONATED'))
        return false;
    $result = new TQuery("select Disabled from OfferImpersonate where Login = '".addslashes(\AwardWallet\MainBundle\Security\Utils::getImpersonator(getSymfonyContainer()->get("security.token_storage")->getToken()))."'");
    if (!$result->EOF)
        return true;
    else
        return false;
}

if ((!isset($_SESSION['ImpersonatedOfferShown']) || !isGranted('ROLE_IMPERSONATED'))
&& !(isGranted('ROLE_IMPERSONATED') && (checkOfferImpersonate() || isset($_COOKIE['disableOffer'])))
&& !(isset($_COOKIE['disableOffer']) && !isset($_GET['UserAgentID']) && !isGranted('SITE_BUSINESS_AREA'))
) {
    $ajaxName = '/offer/find/0';
    $Interface->FooterScripts[] = "showOffer();";
    ?>
<script type="text/javascript">
    function showOffer(){
        <?
        print "\$.get('$ajaxName',function(data,status){
	if (data != 'none'){
	if (data.indexOf('redirect') == 0){
	    var datasplit = data.split(' ');
        var ouid = datasplit[1];
        window.location = '/offer/show/'+ouid;
	}
	else{
        $(document.body).append(data);
        setTimeout(function(){ showPopupWindow(document.getElementById('offer'))},1000);
	}
	}
  });"
        ?>
    }
</script>
<?
}
// Offer part end

#end putting the right tabs in the right order on the page
// end checkong to see if the current user has any award programs or coupons added to the profile
$margin = 30;
$defaultSelect = "All";
if(!isset($tabs["All"])){
	$tabKeys = array_keys($tabs);
	$defaultSelect = $tabKeys[0];
}
if(!isset($_SESSION['showTabS']) && isset($_COOKIE['showTabC']))
	$_SESSION['showTabS'] = $_COOKIE['showTabC'];

$lastUpdateDates = array(); //last update dates

$awardTabs = new TAWTabs( $tabs, $defaultSelect );
$objList = new TAccountList();
echo "<div id='topTabs'>";
$awardTabs->AutoHideLine = $objList->Grouped;
$awardTabs->drawTabs2();
echo "</div>";

?>
<form method="GET" name="accountFilterForm">
<? DrawHiddens(array_intersect_key($_GET, array("ProviderID" => null, "UserAgentID" => null))) ?>
<table cellspacing="0" border="0" id="tblAccounts" class="roundedTable" width="100%">
<? if($awardTabs->selectedTab != "Recently"){ ?>
<col class="cornerCol" id="cornerPlus">
<col class="plusCol" id="colPlus">
<col class="stateCol" id="colState">
<col id="colProgram">
<col id="colLogin">
<col class="statusCol" id="colStatus">
<col id="colBalance">
<col class="expireCol" id="colExpire">
<col style="width: 1px;">
<col class="manageCol" id="colManage">
<? } ?>
<?
	# Limit of number of accounts
	if (SITE_MODE == SITE_MODE_PERSONAL && !in_array($_SESSION['UserID'], $eliteUsers)) {
		$objList->MaxProgram = PERSONAL_INTERFACE_MAX_ACCOUNTS;
		$objList->limit = ' LIMIT '.(PERSONAL_INTERFACE_MAX_ACCOUNTS+1);
		if ($topMenu["My Balances"]["count"] > PERSONAL_INTERFACE_MAX_ACCOUNTS)
			$objList->ShowLimitMessage = '
				<div class="message InfoMessage"><div class="icon"></div>You have a total of '.$topMenu["My Balances"]["count"].' loyalty programs. Personal interface is intended only for a maximum of '.PERSONAL_INTERFACE_MAX_ACCOUNTS.' accounts. If you wish to view all of your accounts you can do one of two things:</div>
				<div style="padding: 3px 5px 5px 50px">
					<div>1. <a href="/agent/moveToBusiness.php">Transfer personal programs into business interface</a></div>
					<div>2. <a href="http://business.AwardWallet.com">View the entire account from the business interface</a></div>
				</div>
			';
	} else
		$objList->limit = '';
	$nRowCount = 0;
	foreach($awardTabs->Fields as $key => $value){
		if($value["selected"]){
            if($arFilters[$key]["ShowKinds"] && $objList->Grouped){
                foreach($arProviderKind as $nKind => $sCaption){
                    $objList->ShowListCaption = ($awardTabs->selectedTab == "All");
                    $objList->ListCategory($arFilters[$key]["Filter"]." and coalesce(p.Kind, a.Kind) = $nKind", $arFilters[$key]["CouponFilter"]." and c.Kind = $nKind", $sCaption, $nKind, $nKind);
                    if (isset($objList->MaxProgram)) {
                        # Get number of accounts
                        $num = 0;
                        foreach ($objList->Rows as $acc) {
                            if (isset($acc['TableName']))
                                $num++;
                        }
                        $objList->MaxProgram = PERSONAL_INTERFACE_MAX_ACCOUNTS - $num;
                        if ($objList->MaxProgram <= 0)
                            break;
                        $objList->limit = ' LIMIT '.($objList->MaxProgram+1);
                    }
                }
            }
            else
                $objList->ListCategory( $arFilters[$key]["Filter"], $arFilters[$key]["CouponFilter"], $value["caption"], 0, null);
            $nRowCount += $objList->ProgramCount;
            $Interface->FooterScripts[] = "alignAccountList();";
		}
	}
	if(SITE_MODE == SITE_MODE_BUSINESS){
		if((count($_GET) == 1) && isset($_GET['UserAgentID']) && ($_GET['UserAgentID'] == 'All'))
			Redirect("/account/overview.php");
	}
		if($awardTabs->selectedTab != "Recently"){
			$objList->DrawTotals();
			$objList->DrawPageNavigator();
			if(isset($_GET['Excel'])){
				ExportExcel($objList->Rows);
			}
			if(isset($_GET['pdf'])){
				ExportPDF($objList->Rows);
			}
		}
		echo "</table></form>";
		?>
		<div id="loadingTemplate" style="display: none;">
			<div>
				<table cellpadding="0" cellspacing="0" class="frame">
					<tr class="top">
						<td class="left"/>
						<td class="center">
							<div class="program">Loading</div>
						</td>
						<td class="right"></td>
					</tr>
					<tr class="middle"><td class="left"><div class="bg"></div></td><td class="center"><div style="padding: 20px 20px">Please wait..</div></td><td class="right"><div class="bg"></div></td></tr>
					<tr class="bottom"><td class="left"></td><td class="center"></td><td class="right"></td></tr>
				</table>
			</div>
		</div>
		<?
	if(!isset($_GET['showTabG']) && isset($_SESSION['showTabS']) && ($_SESSION['showTabS'] != 'All') && ($nRowCount == 0)){
		$ar = $_GET;
		$ar['showTabG'] = 'All';
		Redirect("?".ImplodeAssoc("=", "&", $ar, true));
	}
	setcookie("showTabC", $awardTabs->selectedTab, time() + SECONDS_PER_DAY*90, "/account/list.php");

?>
<? if($awardTabs->selectedTab != "Recently"){ ?>
<br>
<div class="notPrintable" align="center">
<?
if(($objList->atLeatOneAccountAdded > 0) && !isset($QS["printView"]) && ( $objList->CheckCount > 0 )){
	$onClick = "javascript:checkAll(); return false;";
	echo $Interface->DrawButton("Update all accounts in this view", 'onclick="'.$onClick.'"');
	$lastUpdMsg = getLastUpdateMessage($lastUpdateDates);
	?>
	<a class="button" href="#" onclick="<?=$onClick?>">
	<div class='button' id="checkAllTop">
		<div class='head'></div>
		<div class='caption' style="width: 80px; text-align: center;">Update all</div>
		<div class='foot'></div>
	</div>
	</a>
	<? if($lastUpdMsg['msg']) { ?>
	<a class="lastUpdateTime" title="<?=$lastUpdMsg['title']?>">Last Updated: <?=$lastUpdMsg['msg']?> ago</a>
	<? } ?>
	<?
	if(isset($_SESSION['StartCheck'])){
		switch($_SESSION['StartCheck']){
			case 'extension':
				$Interface->FooterScripts[] = "browserExt.requireValidExtension(); browserExt.pushOnReady(checkAll);";
				break;
			case 'server':
				$Interface->FooterScripts[] = "checkAll();";
				break;
			default:
				$Interface->FooterScripts[] = "checkAccountId({$_SESSION['StartCheck']});";
		}
		unset($_SESSION['StartCheck']);
	}
}

if($nUserAgentID != 0){
	$userAgentFields = getUserAgent($nUserAgentID);
	$isLastAdminBusiness = false;
	if (SITE_MODE == SITE_MODE_BUSINESS){
        $admins = getBusinessAccountAdminIds($_SESSION['UserID']);
        $isLastAdminBusiness = (sizeof($admins) <= 1 && in_array($userAgentFields['ClientID'], $admins));
	}
	
	if(empty($userAgentFields['ClientID'])){
		$typeOfUserAgent = "family"; 
		$editLink = "/agent/editFamilyMember.php?ID=$nUserAgentID&Source=A"; 
		$deleteLink =  "/members/deny/$nUserAgentID";
		$deleteText = "Delete this user from my profile";
	} else {
		$typeOfUserAgent = "connected";
		$q = new TQuery("SELECT UserAgentID FROM UserAgent WHERE ClientID = {$_SESSION['UserID']} AND AgentID = {$userAgentFields['ClientID']}"); 
		if (SITE_MODE == SITE_MODE_BUSINESS)
			$editLink = "/agent/editBusinessConnection.php?ID={$q->Fields['UserAgentID']}&Source=A";
		else
			$editLink = getSymfonyContainer()->get('router')->generate('aw_user_connection_edit', ['userAgentId' => $q->Fields['UserAgentID'], 'Source' => 'A']);// "/agent/editConnection.php?ID={$q->Fields['UserAgentID']}&Source=A";
		$deleteLink =  "/members/deny/{$q->Fields['UserAgentID']}";
		$deleteText = "Disconnect this user from my profile";
	}
	echo $Interface->DrawButton("Edit this user", 'onclick="window.location.href=\''.$editLink.'\'"');
	if(!$isLastAdminBusiness)
		echo $Interface->DrawButton($deleteText, 'onclick=" if( confirm(\'Are you sure you want to delete this connection?\') ) waitAjaxRequest(\''.$deleteLink.'\'); return false; "');
	if($typeOfUserAgent == 'family')
		echo $Interface->DrawButton("Invite this user", 'onclick="inviteFamilyMember(this, '.$nUserAgentID.', \''.$userAgentFields['Email'].'\'); return false;"');
}

if(!isset($QS["printView"]) && preg_match('/^Tab(\d+)$/ims', $awardTabs->selectedTab, $arMatches)){
	echo ' '.$Interface->DrawButton("Edit view", "onclick=\"location.href='/account/view.php?ID={$arMatches[1]}'\"", 120);
	echo ' '.$Interface->DrawButton("Delete view", "onclick=\"if(window.confirm('Delete this view?')) location.href='/account/deleteView.php?ID={$arMatches[1]}'\"", 120);
}
?>
	<div style='clear: both; height: 1px'></div>
</div>
<? } ?>
<?php

}# end - only if there are programs or coupons added put stuff on the page...
#else show notice that they need to add programs
elseif ($cPending == 0) {
# begin - This page is displayed only when no programs are added to the profile
if(( $nUserAgentID == 0 ) || ( trim( $nUserAgentID ) == "All" )){
$Interface->DrawBeginBox('id="funcPopup"', 'Add award programs', true, 'popupWindow');
?>
	<div style="padding-top: 5px; margin: 5px 20px auto 20px;">
		<div class="question">At this point you have not added any award programs to your profile, please proceed to add your first award program to AwardWallet.</div>
		<div class="buttons" id="funcButtons">
		<?=$Interface->DrawButton("Add a New Award Program", "onclick='document.location.href=\"/account/add.php\"'", 220)?>
		</div>
	</div>
<?
$Interface->DrawEndBox();
$Interface->FooterScripts[] = "showPopupWindow(document.getElementById('funcPopup'), true);";
}
else
	$Interface->DrawMessageBox("This user has not yet shared any award program information for you to view.");
# end - This page is dysplayed only when no programs are added to the profile
}

if ($cPending > 0) {
	if ($cPending > 1)
		$title = sprintf("New loyalty accounts found. %d pending accounts remaining", $cPending);
	else
		$title = "New loyalty account found";
	$Interface->DrawBeginBox('id="pendingAccount" style="position: absolute; z-index: 50; width: 650px;"', $title);
	if (false)
		$info = "While scanning your mailbox we found a new %s account which we added to your profile.";
	else
		$info = "You have a new %s account that was added into your profile automatically.";
	if (isset($_SESSION["UserFields"]) && $_SESSION["UserFields"]["DateFormat"] == DATEFORMAT_EU)
		$dateformat = "d/m/Y";
	else
		$dateformat = "m/d/Y";
	?>

	<div style="padding-top: 5px; margin: 5px 20px auto 20px;">
		<div>
			<p><?= sprintf($info, $pFields["DisplayName"]) ?></p>

			<p>This account is not yet fully set up, please set it up now or delete it:</p>
			<table cellspacing="0" style="margin-bottom: 10px;">
				<tr style="background-color: #fff;">
					<td style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #ccc; border-right: 1px dotted #ccc;">
						<b>Account #</b>
					</td>
					<td style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #ccc;">
						<?= $pFields["Login"] ?>
					</td>
				</tr>
				<? if (true) { ?>
					<tr style="background-color: #f8f8f8;">
					<td style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #ccc; border-right: 1px dotted #ccc;">
						<b>Balance</b>
					</td>
					<td style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #ccc;">
						<?= number_format_localized($pFields["Balance"], 0) ?>
					</td>
				</tr>
				<?
				} else {
					?>
					<tr style="background-color: #f8f8f8;">
						<td style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #ccc; border-right: 1px dotted #ccc;">
							<b>Retrieved from:</b>
						</td>
						<td style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #ccc;">
							<?= $qScanned->Fields["Email"] ?>
						</td>
					</tr>
					<tr style="background-color: #fff;">
						<td style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #ccc; border-right: 1px dotted #ccc;">
							<b>Date of email:</b>
						</td>
						<td style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #ccc;">
							<?= date($dateformat, strtotime($qScanned->Fields["EmailDate"])) ?>
						</td>
					</tr>
				<?
				}
				?>
				<tr <?= ($cPending < 3) ? "style=\"display: none;\"" : "" ?>>
					<td colspan="2" style="padding: 8px 10px; text-align: left;">
						<input type="checkbox" id="pendingApply">&nbspApply to all <?= $cPending ?> pending accounts
					</td>
				</tr>
			</table>
		</div>
		<?= $Interface->DrawButton("Delete", 'onclick="deletePendingAccount(' . $pFields["AccountID"] . ', document.getElementById(\'pendingApply\').checked)"') ?>
		<?= $Interface->DrawButton("Set Up", 'onclick="document.location.href=\'/account/edit.php?ID=' . $pFields["AccountID"] . '\'; return false;"') ?>
		<?= $Interface->DrawButton("Skip", 'onclick="skipPendingAccount(' . $pFields["AccountID"] . ', document.getElementById(\'pendingApply\').checked)"') ?>
	</div>
	<?
	$Interface->DrawEndBox();
	$Interface->FooterScripts[] = "showPopupWindow(document.getElementById('pendingAccount'));";
}
if($nUserAgentID != 0 && isset($typeOfUserAgent) && $typeOfUserAgent == 'family'){
	$Interface->DrawBeginBox('id="askEmailBox" style="display: none; width: 380px; height: 260px; position: absolute; z-index: 50;"', 'Email');
?>
		<div style="padding: 0px;">
			<div style="margin-bottom: 10px;">
				<input type="text" id="email" style="margin-top: 15px; width: 333px;" class="inputTxt">
				<input type="hidden" id="userAgentId">
			</div>
			<? echo $Interface->DrawButton("Invite", 'onclick="sendFamilyInvite(); return false;"'); ?>
			<? echo $Interface->DrawButton("Cancel", 'onclick="cancelInvite(); return false;"'); ?>
		</div>
<?
	$Interface->DrawEndBox();
}

if(isset($objList) && ($objList->NoticeAutoLogin || $objList->NoticeBrowserAutoLogin)){
	echo "<br/><br/><br/>";
	if($objList->NoticeAutoLogin)
		print "<a name='AutoLogin'></a><div class='notPrintable'>* Programs which are marked with a star (*) do not have auto login feature available</div>";
	if($objList->NoticeBrowserAutoLogin)
		print "<a name='browserAutoLogin'></a><div class='notPrintable'>** Programs which are marked with (**) do not have auto login feature available without a browser extension.
		<br/>If you wish to install the extension please <a href='/extension/'>click here</a>.</div>";
}

if(isset($awardTabs) && ($awardTabs->selectedTab == "Recently")){
}
require( "commonForList.php" );

// BEGIN: Tracking pixels for e-miles
if (!empty($objList->Totals) && $_SESSION['UserFields']['CameFrom'] == 145 && !empty($_SESSION['UserFields']['ReferralID'])){
	echo "<img style='width: 1px; height: 1px; border: none;' src='https://www.e-miles.com/autocredit.do?pc=6EWP5DK4KYUAY7Z&ftouch=".urlencode($_SESSION['ReferralID'])."&cs=1&id=awardwallet'>";
	$Connection->Execute("update Usr set ReferralID = null where UserID = ".$_SESSION['UserID']);
}
// END: Tracking pixels for $_GET["ref"]

require( "../design/footer.php" );
