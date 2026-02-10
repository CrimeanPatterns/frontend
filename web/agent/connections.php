<?
require( "../kernel/public.php" );
require_once( "../kernel/TForm.php" );

if(NDInterface::enabled())
	Redirect(getSymfonyContainer()->get("router")->generate("aw_user_connections"));

AuthorizeUser();

# begin determining menus
require($sPath."/design/topMenu/main.php");
require($sPath."/design/leftMenu/award.php");

$leftMenu['My Connections']['selected'] = true;
# end determining menus

$sTitle = "My connections";
$nID = intval( ArrayVal( $QS, "ID" ) );

$q = new TQuery("select ua.AgentID, ua.UserAgentID, ua.ShareDate,
	IF(u.AccountLevel=".ACCOUNT_LEVEL_BUSINESS.",u.Company,CONCAT(COALESCE(ua.FirstName, u.FirstName),' ', COALESCE(ua.LastName, u.LastName))) AS FullName,
	au.IsApproved, ua.ClientID,
	ua.Email, u.AccountLevel
from UserAgent ua
".(SITE_MODE == SITE_MODE_PERSONAL?"left outer ":"")."join Usr u on ua.AgentID = u.UserID
left outer join UserAgent au on ua.ClientID = au.AgentID and ua.AgentID = au.ClientID
where
((ua.ClientID = {$_SESSION['UserID']}) or ((ua.AgentID = {$_SESSION['UserID']})) and (ua.ClientID is null))
order by IsNull(ua.ClientID) DESC, FullName");
$qEmail = new TQuery("select * from InviteCode
					where UserID = {$_SESSION['UserID']}
					order by Email");

require( "$sPath/design/header.php" );

if ( $q->EOF && $qEmail->EOF ){
	if(SITE_MODE == SITE_MODE_BUSINESS)
		$Interface->DrawMessageBox("You have no pending members", "info");
	else {
		$Interface->DrawBeginBox('style="width: 550px; margin: 0 auto;"', "My Connections", true, null, false);
		?>
			<div style="padding: 20px;">
				<div class="question" style="padding-bottom: 20px;">At this point you have no connections or family member names added to your profile. You have
				two options, you can connect with another person on AwardWallet, or you can just create another
				name to better organize your rewards.</div>
				<div class="buttons" id="newAgentButtons">
				<?=$Interface->DrawButton("Connect with another person", "onclick=\"location.href = '/agent/addConnection.php?BackTo=".urlencode($_SERVER['REQUEST_URI'])."'\"", 210)?>
				<?=$Interface->DrawButton("Just add a new name", "onclick=\"location.href = '/agent/add.php?BackTo=".urlencode(urlPathAndQuery($_SERVER['REQUEST_URI']))."'\"", 180)?>
				</div>
			</div>
		<?
		$Interface->DrawEndBox();
	}
}
else{
    ?>
    <table cellspacing="0" cellpadding="0" class="roundedTable" style="width: 100%;">
		<tr class="afterTop">
			<td class="tabHeader" colspan="5">
				<div class="icon"><div class="left"></div></div>
				<div class="caption">My Connections</div>
			</td>
		 </tr>
		<tr class="head afterGroup">
			<td class="c1head">
				<div class="icon"><div class="inner"></div></div>
				<div class="caption">Connection</div>
			</td>
			<td class="leftDots noWrap">Connection Type *</td>
			<td class="leftDots">Status</td>
			<td class="leftDots">Email</td>
			<td class="leftDots"></td>
		 </tr>
    <?
	$lastRowKind = "Head";
    while(!$q->EOF){
		$classes = "after".$lastRowKind;
		if(($q->Position % 2) == 0){
			$classes .= " grayBg";
			$lastRowKind = "Gray";
		}
		else{
			$classes .= " whiteBg";
			$lastRowKind = "White";
		}
		$sDeleteLink = "<a class='checkLink' onclick=\"if(window.confirm('Are you sure you want to delete this connection?')) waitAjaxRequest('/members/deny/".$q->Fields["UserAgentID"]."'); return false\" href='#' title=Disconnect>";
        $sEditLink = '<a class="checkLink" href="'.getSymfonyContainer()->get('router')->generate('aw_user_connection_edit', ['userAgentId' => $q->Fields['UserAgentID']]).'">';
		if(!isset($q->Fields['ClientID'])) {
			$sEditLink = '<a class="checkLink" href="/agent/editFamilyMember.php?ID='.$q->Fields['UserAgentID'].'">';
			$sInviteLink = "<a class='checkLink' href='#' onclick='inviteFamilyMember(this, {$q->Fields['UserAgentID']}, \"{$q->Fields['Email']}\"); return false;'>";
			$sDeleteLink = "<a class='checkLink' onclick=\"if(window.confirm('Are you sure you want to delete this connection?')) waitAjaxRequest('/members/deny/".$q->Fields["UserAgentID"]."'); return false;\" href='#' title=Delete>";
		}
		$isLastAdminBusiness = false;
		if ($q->Fields['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS) {
            $admins = getBusinessAccountAdminIds($q->Fields['AgentID']);
            $isLastAdminBusiness = (sizeof($admins) <= 1 && in_array($_SESSION['UserID'], $admins));
		}
        ?>
        <tr class="<?=$classes?>">
			<td class="c1">
				<? if($q->Position == 1) { ?>
				<div class="icon"><div class="inner"></div></div>
				<? } ?>
				<div class="caption"><?=$q->Fields["FullName"]?></div>
			</td>
			<td class="pad leftDots"><?
				echo "Family member";
			?></td>
			<td class="pad leftDots"><?
			if(isset($q->Fields['ClientID'])){
				if($q->Fields["IsApproved"])
					echo "Approved";
				else
					echo "Waiting for approval";
			}
			else{
				if($q->Fields["ShareDate"] != ""){
					$inviteDate = $Connection->SQLToDateTime($q->Fields["ShareDate"]);
					if((time() - $inviteDate) <= (SECONDS_PER_DAY * 3))
						echo "Waiting for approval<br>
						<a href=# onclick='cancelInvitation({$q->Fields['UserAgentID']}); return false;'>Cancel</a>";
					else
						echo "Invitation expired.<br>
						Feel free to invite again";
				}
			}
			?></td>
			<td class="pad leftDots"><?
				if (isset($q->Fields['Email']))
					echo $q->Fields['Email'];
			?></td> 
            <td class="pad leftDots noWrap manage" style="text-align: center;">
				<?
				$editLinks = array();
				if(!isset($q->Fields['ClientID']))
					$editLinks[] = $sEditLink."Edit</a>";
				if($q->Fields['IsApproved'] == '0')
					$editLinks[] = "<a class='checkLink' href=# onclick=\"sendReminder({$q->Fields['AgentID']}, this.parentNode, ''); return false;\">Resend</a>";
				if($q->Fields['IsApproved'] == '1')
					$editLinks[] = $sEditLink."Edit</a>";
				if (!(isset($isLastAdminBusiness) && $isLastAdminBusiness))
					$editLinks[] = $sDeleteLink.(isset($q->Fields['ClientID'])?"Disconnect":"Delete")."</a>";
				if(!isset($q->Fields['ClientID']))
					$editLinks[] = $sInviteLink."Invite</a>";
				echo $Interface->getEditLinks($editLinks);
				?>
			</td>
        </tr>
        <?
        $q->Next();
    }
	while(!$qEmail->EOF){
		$sDeleteLink = "<a onclick=\"if(window.confirm('Are you sure you want to delete this connection?')) waitAjaxRequest('/user/cancel-email-invite/".$qEmail->Fields["InviteCodeID"]."'); return false;\" title=Delete href=\"#\">";
		$classes = "after".$lastRowKind;
		if(($q->Position % 2) == 0){
			$classes .= " grayBg";
			$lastRowKind = "Gray";
		}
		else{
			$classes .= " whiteBg";
			$lastRowKind = "White";
		}
		?>
        <tr class="<?=$classes?>">
			<td class="c1">
				<? if($q->Position == 1) { ?>
				<div class="icon"><div class="inner"></div></div>
				<? } ?>
				<div class="caption"><a href="mailto:<?=$qEmail->Fields["Email"]?>"><?=$qEmail->Fields["Email"]?></a></div>
			</td>
			<td class="pad leftDots"></td>
			<td class="pad leftDots">Waiting for approval</td>
			<td></td>
            <td class="pad leftDots noWrap manage" style="text-align: center">
				<?
				$editLinks = array(
					"<a class='checkLink' href=# onclick=\"sendEmailReminder({$qEmail->Fields['InviteCodeID']}, this.parentNode, ''); return false;\">Resend</a>",
					"{$sDeleteLink}Delete</a>"
				);
				echo $Interface->getEditLinks($editLinks);
				?>
			</td>
        </tr>
		<?
		$qEmail->Next();
	}
    ?>
		<tr class='after<?=$lastRowKind?>'>
			<td colspan=5 class="whiteBg topBorder" style="height: 1px;"></td>
		</tr>
    </table>
	<br>
	* By default, your award information is never shared. Connection only indicates that you can share something if you choose to do so.
	<br>
	<br>
	<? echo $Interface->DrawButton("Add new connection", "onclick=\"showPopupWindow(document.getElementById('newAgentPopup')); return false;\"", 240); ?>

    <?
}

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
require( "$sPath/design/footer.php" );

