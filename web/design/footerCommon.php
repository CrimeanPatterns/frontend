<?php
global $cPage, $Interface, $leftMenu, $othersMenu, $sPath, $eliteUsers, $page, $bNoSession;

if (isset($_SESSION['invrId'])) {
    echo '<!-- invrId: ' . $_SESSION['invrId'] . ' -->';
}

?>

<!-- browser ext params -->
<input type="hidden" id="extCommand" value="init"/>
<input type="hidden" id="extParams" value=""/>
<input type="button" id="extButton" style="display: none;"/>
<input type="button" id="extListenButton" style="display: none;" onclick="if(typeof(browserExt) == 'undefined') setTimeout(document.getElementById('extListenButton').onclick, 100); else browserExt.receiveCommand()"/>
<input type="hidden" id="extBrowserKey" value="<?php echo htmlspecialchars(getBrowserKey()); ?>"/>
<input type="hidden" id="extUserId" value="<?php if (isset($_SESSION['UserID'])) {
    echo $_SESSION['UserID'];
} ?>"/>

<script type="text/javascript">
var debugMode = <?php echo ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG ? "true" : "false"; ?>;
var dateFormat = '<?php echo javascriptDateFormat(DATE_FORMAT); ?>';
var thousandsSeparator = '<?php echo getSymfonyContainer()->get(\AwardWallet\MainBundle\Globals\Localizer\LocalizeService::class)->getThousandsSeparator(); ?>';
<?php if (function_exists('ArrayVal')) { ?>
var disableExtension = <?php
    if (isset($_COOKIE['DBE'])) {
        echo '1';
    } else {
        echo '0';
    }
    ?>;
<?php } ?>
</script>

<div id="fader" onclick="faderClick()">
</div>
<?php if ($cPage != "forum pages") { ?>
<div id="reviewPopup" style="display: none; width: 400px; height: 30px; overflow: hidden;" class="popupWindow"><div id="reviewPopupText"></div></div>

<?php $Interface->DrawBeginBox('id="termsPopup" style="height: 700px; width: 800px; position: absolute; z-index: 60; display: none;"', "Terms of Use"); ?>
	<div style="padding: 20px 0px 10px 0px;" id="termsText">
	</div>
<?php $Interface->DrawEndBox(); ?>

<?php $Interface->DrawBeginBox('id="framePopup" style="top: 0px; left: 0px;height: 100px; position: absolute; z-index: 50; width: 100px; display: none;"', "<div id='frameHeader'></div>"); ?>
	<div style="padding: 20px 0px 10px 0px;" id="popupFrameContainer">
	</div>
<?php $Interface->DrawEndBox(); ?>

<?php $Interface->DrawBeginBox('id="messagePopup" style="height: 200px; position: absolute; z-index: 50; width: 540px; display: none;"', "<div id='messageHeader'></div>"); ?>
	<div style='padding-top: 10px; margin: 5px 20px auto 20px;' id="messagePopupBody">
		<div id="messageText">Question</div>
		<div style="margin-top: 10px;" id="messageButtons">
		<?php echo $Interface->DrawButton("OK", "onclick='cancelPopup()' id=messageOKButton", 120); ?>
		<?php echo $Interface->DrawButton("Cancel", "onclick='cancelPopup()' style='display: none;' id=messageCancelButton", 120); ?>
		</div>
		<div class="clear"></div>
		<div id="messageProgress" class="progress" style="display: none;">
		Deleting..
		</div>
	</div>
<?php $Interface->DrawEndBox(); ?>

<?php if (isset($leftMenu["Add New Person"]) || isset($othersMenu["Add New Person"]) || isset($leftMenu["My Connections"])) { ?>
<?php $Interface->DrawBeginBox('id="newAgentPopup" style="width: 550px;"', "Select connection type", true, "popupWindow"); ?>
	<div style="padding-top: 5px; margin: 5px 20px auto 20px;">
		<div class="question">You have two options, you can connect with another person on AwardWallet, or you can just create another name to better organize your rewards.</div>
		<div class="buttons" id="newAgentButtons">
		<?php echo $Interface->DrawButton("Connect with another person", "onclick=\"location.href = '/agent/addConnection.php?BackTo=" . urlencode($_SERVER['REQUEST_URI']) . "'\"", 210); ?>
		<?php echo $Interface->DrawButton("Just add a new name", "onclick=\"location.href = '/agent/add.php?BackTo=" . urlencode(urlPathAndQuery($_SERVER['REQUEST_URI'])) . "'\"", 180); ?>
		</div>
	</div>
<?php $Interface->DrawEndBox(); ?>
<?php } ?>

<?php if (SITE_MODE == SITE_MODE_PERSONAL
        && MyConnectionsCount() >= PERSONAL_INTERFACE_MAX_USERS
        && isset($_SESSION['UserID'])
        && !in_array($_SESSION['UserID'], $eliteUsers)) {?>
	<?php require_once __DIR__ . "/../schema/User.php"; ?>
	<?php echo TUserSchema::getTextErrorAboutLimitUsers(true, TUserSchema::isAdminBusinessAccount($_SESSION['UserID'])); ?>
<?php }?>

<?php
if (isset($_SESSION['UserID']) && $_SESSION['UserFields']['EmailVerified'] == EMAIL_NDR && $_SESSION['AccountLevel'] != ACCOUNT_LEVEL_BUSINESS) {
    $Interface->DrawBeginBox('id="emailNDR" style="width: 540px;"', "Invalid Email Provided", true, "popupWindow"); ?>
	<div style="padding-top: 5px; margin: 5px 20px auto 20px;">
		<div class="question">
			Dear <?php echo $_SESSION['FirstName']; ?>, we tried sending you an email but it returned back to us as "non-deliverable", either you provided us with an invalid email address or your mail server does not accept emails from awardwallet.com. Please, update your email address below, or if your address is typed in correctly, just click "Update Email Address" without changing it. If your mail server keeps denying emails from us we will show this message to you again.<br /><br />
			<div style="float:left; width:70px; padding:10px 0;">Email:</div>
			<div style="float:left;"><?php echo $Interface->getInput('newEmail', $_SESSION['Email'], " style='width:300px' "); ?></div>
			<div style="clear:both;"></div>
		</div>
		<div class="buttons">
			<?php echo $Interface->DrawButton("Update Email Address", "onclick=\"uncheckNdr(this)\"", 210); ?>
			<div id="newEmailStat" style='float:left; padding:0; display:none; width:220px;'>
				<table width="100%" style="border-collapse: collapse; border-spacing:0;">
					<tr>
						<td height="35"></td>
					</tr>
				</table>
			</div>
		</div>
	</div>
<?php
    $Interface->DrawEndBox();
    $Interface->FooterScripts[] = 'showPopupWindow(document.getElementById(\'emailNDR\'), true);';
}

    $Interface->FooterScripts[] = 'activateDatepickers("active");';
    ?>
<?php } ?>
<?php // TODO Temporary solution to the conflict in datepicker and another scripts in new Booking?>
    <?php if (file_exists("$sPath/cache/scripts-" . FILE_VERSION . ".js")) { ?>
    <script type="text/javascript" language="JavaScript" src="/cache/scripts-<?php echo FILE_VERSION; ?>.js"></script>
    <?php } else { ?>
    <script type="text/javascript" language="JavaScript" src="/assets/common/vendors/jquery/dist/jquery.min.js?v=<?php echo FILE_VERSION; ?>"></script>
    <script type="text/javascript" language="JavaScript" src="/lib/3dParty/jquery/plugins/jquery.json-2.2.min.js?v=<?php echo FILE_VERSION; ?>"></script>
    <?php if (strpos($_SERVER['REQUEST_URI'], 'awardBooking') == false) {?>
        <script type="text/javascript" language="JavaScript" src="/assets/common/vendors/jqueryui/ui/jquery-ui.custom.js?v=<?php echo FILE_VERSION; ?>"></script>
        <script type="text/javascript" language="JavaScript" src="/assets/common/vendors/jquery.cookie/jquery.cookie.js?v=<?php echo FILE_VERSION; ?>"></script>
        <script type="text/javascript" language="JavaScript" src="/assets/common/js/jquery-handlers-old.js?v=<?php echo FILE_VERSION; ?>"></script>
    <?php } ?>
    <script type="text/javascript" language="JavaScript" src="/design/awardWallet.js?v=<?php echo FILE_VERSION; ?>"></script>
    <script type="text/javascript" language="JavaScript" src="/lib/scripts.js?v=<?php echo FILE_VERSION; ?>"></script>
    <script type="text/javascript" language="JavaScript" src="/kernel/browserExt.js?v=<?php echo FILE_VERSION; ?>"></script>
    <script type="text/javascript" src="/design/iepngfix_tilebg.js?v=<?php echo FILE_VERSION; ?>"></script>
    <?php if (isGranted('ROLE_IMPERSONATED')) { ?>
    <script type="text/javascript" language="JavaScript" src="/design/manager.js?v=<?php echo FILE_VERSION; ?>"></script>
<?php } ?>


<?php
if (isset($Interface->Skin)) {
    $Interface->Skin->drawScripts();
}
    }

    if (isset($Interface) && sizeof($Interface->ScriptFiles)) {
        foreach ($Interface->ScriptFiles as $file) {
            echo "<script src=\"$file\" type=\"text/javascript\"></script>\n";
        }
    }

    echo "<script>
\$(document).ready(function(){
	var top = getCookie('scrollTop');
	if(top > 0){
		$(window).scrollTop(top);
		setCookie('scrollTop', 0, new Date(), '/');
	}
	" . implode("\n", $Interface->FooterScripts) . "\n
});
</script>";

if (count($Interface->onLoadScripts) > 0) {
    echo "<script>
	\$(window).load(function() {
		" . implode("\n", $Interface->onLoadScripts) . "\n
	});
	</script>";
}

if (strpos($_SERVER['HTTP_HOST'], '.local') === false && !$Interface->NoExternals) {
    ?>
<script type="text/javascript">

(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
 (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
 m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
 })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

 ga('create', 'UA-74302-3', 'auto');
 ga('send', 'pageview');

</script>
<?php
    global $sGoogleAnalyticsTracker, $sPath;

    if (isset($sGoogleAnalyticsTracker)) {
        require $sPath . $sGoogleAnalyticsTracker;
    }
}
?>
