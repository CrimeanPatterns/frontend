<?
require( "../kernel/public.php" );
require( $sPath."/kernel/TForm.php" );

# begin determining menus
require($sPath."/design/topMenu/main.php");
require($sPath."/design/leftMenu/award.php");
# end determining menus
$sTitle = "Pending requests";

if(NDInterface::enabled())
	Redirect(getSymfonyContainer()->get("router")->generate("aw_user_connections"));

require("$sPath/design/header.php");

$q = new TQuery("select a.FirstName, a.LastName, a.Company, a.AccountLevel, ua.UserAgentID, ua.AgentID, ua.AccessLevel from UserAgent ua, Usr a where ua.AgentID = a.UserID and ua.ClientID = {$_SESSION['UserID']} and ua.IsApproved = 0 order by a.FirstName, a.LastName");

if( $q->EOF )
	$Interface->DrawMessageBox("There are no pending requests", "info");
else{
?>
<table cellspacing="0" cellpadding="0" class="roundedTable" style="width: 100%;">
	<tr class="afterTop">
		<td class="tabHeader" colspan="4">
			<div class="icon"><div class="left"></div></div>
			<div class="caption">Pending requests</div>
		</td>
	 </tr>
	<tr class="head afterGroup">
		<td class="c1head">
			<div class="icon"><div class="inner"></div></div>
			<div class="caption">Name</div>
		</td>
		<td class="leftDots">Access Level *</td>
		<td class="leftDots">&nbsp</td>
	 </tr>

<?
$lastRowKind = "Head";
while ( !$q->EOF ) {
	$classes = "after".$lastRowKind;
	if(($q->Position % 2) == 0){
		$classes .= " grayBg";
		$lastRowKind = "Gray";
	}
	else{
		$classes .= " whiteBg";
		$lastRowKind = "White";
	}

    $sAccessLevel = $arAgentAccessLevelsAll[$q->Fields['AccessLevel']];
          
    $UserName = $q->Fields['FirstName'].' '.$q->Fields['LastName'];   
    if ($q->Fields['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS)
        $UserName = $q->Fields['Company'];
	?>
	<tr class="<?=$classes?>">
		<td class="c1">
			<? if($q->Position == 1) { ?>
			<div class="icon"><div class="inner"></div></div>
			<? } ?>
			<div class="caption"><?=$UserName?></div>
		</td>
		<td class="pad leftDots"><?=$sAccessLevel?></td>
        <td class="pad leftDots noWrap manage" style="text-align: center;">
        	<table cellspacing="0" cellpadding="0">
        		<tr>
					<td><div class="left"></div></td>
					<td><a class="checkLink" href="#" onclick="waitAjaxRequest('/agent/approve.php?AgentID=<?=$q->Fields['AgentID']?>'); return false;">Approve</a></td>
					<td><div class="right"></div></td>
					<td><div class="left"></div></td>
					<td><a class="checkLink" href="#" onclick="waitAjaxRequest('/members/deny/<?=$q->Fields['UserAgentID']?>'); return false;">Deny</a></td>
					<td><div class="right"></div></td>
				</tr>
        	</table>
		</td>
    </tr>
<?	
	$q->Next();
}
?>
<tr class='after<?=$lastRowKind?>'>
	<td colspan="4" class="whiteBg topBorder" style="height: 1px;"></td>
</tr>
</table>
<br>
* By default, your award information is never shared. Connection only indicates that you can share something if you choose to do so.

<?	
}

require("$sPath/design/footer.php");

?>
