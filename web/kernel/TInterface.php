<?php

// -----------------------------------------------------------------------
// Interface class.
//		Contains base interface class, to show site messages etc.
//		You should override class to build custom interface.
//		TInterface = class( TBaseInterface ) ..
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

class TInterface extends TBaseInterface
{
    /**
     * @var StandardSkin
     */
    public $Skin;
    public $HeaderData = [];
    public $NoExternals = false;

    public function Init()
    {
        parent::Init();
        $skin = "standard";

        if (isset($_SESSION['UserFields']['Skin']) && ($_SESSION['UserFields']['Skin'] != '')) {
            $skin = $_SESSION['UserFields']['Skin'];
        }

        if (isset($_SESSION['Skin'])) {
            $skin = $_SESSION['Skin'];
        }
        $className = ucfirst($skin) . "Skin";
        $this->Skin = new $className();
        $this->Skin->init();
    }

    // draw a simple notification message.
    // $sKind = "error", "info", "warning"
    public function DrawMessage($sText, $sKind)
    {
        echo "<div class='message {$sKind}Message'><table border='0' cellpadding='0' cellspacing='0'><tr><td><div class='icon'></div></td><td>{$sText}</td></tr></table></div>";
    }

    public function DrawMessageBar($text, $kind, $classes = "")
    {
        ?>
		<div class="boxToll boxBlue boxFaq qLess messageBar boxBlueClosed <?php echo $classes; ?>">
			<div class="top">
				<div class="left"></div>
				<div class="right"></div>
			</div>
			<div class="center">
				<div class="centerInner">
					<div class="text message <?php echo $kind; ?>Message"><div class='icon'></div><?php echo $text; ?></div>
				</div>
			</div>
			<div class="bottom">
				<div class="left"></div>
				<div class="right"></div>
			</div>
		</div>
		<div class="messageDownArrow <?php echo $classes; ?>Arrow"></div>
		<?php
    }

    // draws button
    // sType: submit, button; sName: input name; sTitle: button title;
    // sAttrs: additional tag attributes
    // sColor: color scheme, Red or Blue
    public function DrawButton($caption, $attr, $size = 0, $buttonAttr = null, $type = 'submit')
    {
        return '<div class="overallButton" ' . $attr . '>
		<table border="0" cellpadding="0" cellspacing="0"><tr>
		<td><div class="buttonLeft"></div></td>
		<td><input type="' . $type . '" ' . (isset($buttonAttr) ? " " . $buttonAttr : "") . ' class="buttonBg"' . ($size > 0 ? " style=\"width: {$size}px;\"" : "") . ' value="' . htmlspecialchars($caption) . '"></td>
		<td><div class="buttonRight"></div></td>
		</tr></table>
		</div>';
    }

    public function drawSectionDivider($title, $bReturn = false)
    {
        $s = '<table cellspacing="0" cellpadding="0" border="0" class="sectionDivider">
<tr>
	<td>' . $title . '</td>
</tr>
</table>';

        if (!$bReturn) {
            echo $s;

            return;
        } else {
            return $s;
        }
    }

    public function drawSectionDivider2($title)
    {
        ?>
<div style="width: 100%; border-bottom: #b3b3b3 1px solid; padding-bottom: 5px; font-size: 20px; color: #0b70b7;"><?php echo $title; ?></div>
<?php
    }

    public function DrawInviteBox($invitedCount, $acceptedCount)
    {
        global $Connection;

        // require RefCode
        if (isset($_SESSION['UserID']) && !isset($_SESSION['UserFields']['RefCode'])) {
            $_SESSION['UserFields']['RefCode'] = RandomStr(ord('a'), ord('z'), 10);
            $Connection->Execute("update Usr set RefCode = '" . addslashes($_SESSION['UserFields']['RefCode']) . "' where UserID = {$_SESSION['UserID']}");
        }
        $stars = floor($acceptedCount / 5);
        $remainingCount = 5 - ($acceptedCount - $stars * 5);

        if ($remainingCount == 0) {
            $remainingCount = 5;
        }
        $coupons = $this->GetCoupons("Invite-" . $_SESSION['UserID'] . '-%');
        $stars = count($coupons);

        if ($stars == 0) {
            $upgradeMessage = "At the moment you don’t have any free upgrades; we will give you a free 6 month upgrade to AwardWallet Plus for every 5 invited users who join our service.";
        } else {
            if ($stars == 1) {
                $upgradeMessage = "Congratulations, you’ve earned a six month AwardWallet Plus upgrade. "
                . "You can use the following coupon code yourself or give it as a gift to your friend:<br/><br/>"
                . "{$coupons[0]['Code']} - <a href='/user/useCoupon.php?Code={$coupons[0]['Code']}'>use coupon</a>";
            } else {
                $upgradeMessage = "Congratulations, you’ve earned six month AwardWallet Plus upgrades. "
                . "You can use the following coupon codes yourself or give them as gifts to your friends:<br/><br/>";

                foreach ($coupons as $coupon) {
                    $upgradeMessage .= "{$coupon['Code']} - <a href='/user/useCoupon.php?Code={$coupon['Code']}'>use coupon</a><br/>";
                }
            }
        }
        ?>
		<div id='upgradeMessage' style="display: none;">
			<?php echo $upgradeMessage; ?>
		</div>
		<?// begin adding direct link?>
		<div class="addressbookInvtLbl emailInvt">Invite using a direct link:</div>

		<table class='inputFrame' cellpadding='0' cellspacing='0'><tr><td class='ifLeft'></td><td class='ifCenter inviteBox'><input class="borderLess inviteBox" type="text" readonly value="http://AwardWallet.com/?refCode=<?php echo $_SESSION['UserFields']['RefCode']; ?>" onclick="this.focus(); this.select();"/></td><td class='ifRight'></td></tr></table>
		<?// end adding direct link?>
		<div class="addressbookInvtLbl" style="padding: 10px 15px 0px 15px;">Or</div>

		<div class="emailInvt" style="padding-top: 10px;">
			<form name="inviteFrm" id="inviteFrm" action="/lib/processInviteForm.php" method="post" onsubmit="return sendInvite(this)" style="margin-top: 0px; margin-bottom: 0px;">
				<input type="hidden" name="CSRF" value="<?php echo GetFormToken(); ?>"/>
				<div class="emailInvtInput">
					<input id='emailInvtInput' type="Text" class="emailInvtInput" name="inviteEmail" value="Type your friend's email here..." onclick='clearEmailFeild(this)'>
				</div>
				<a id="emailInvtLink" onclick="sendInvite(document.inviteFrm); return false;" href="#" title="Invite"><div class="inviteEmailBtn"></div></a>
			</form>
		</div>

		<?php
        loadFreeCoupons();

        if ($_SESSION['FreeCoupons'] > 0) {?>
		<div style="padding: 15px 5px 5px 15px;">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="3" style="padding-bottom: 5px;">Free upgrade coupon for the first time</td>
				</tr>
				<tr>
					<td style="width: 1px; padding-right: 10px;">users:</td>
					<td style="width: 1px; padding-right: 5px;">
						<div class="overallReferral">
							<table class="overRefTable" cellpadding="0" cellspacing="0"><tr>
								<td><div class="referralLeft"></div></td>
								<td><div class="referralCenter" style="cursor: default;"><div class="referralUnderline"><?php echo $_SESSION['FreeCouponCode']; ?></div></div></td>
								<td><div class="referralRight"></div></td>
							</tr></table>
						</div>
					</td>
					<td>
						 – <?php echo $_SESSION['FreeCoupons']; ?> left
					</td>
				</tr>
			</table>
		</div>
		<?php } ?>

		<?if (defined('FACEBOOK_KEY') && !isset($_COOKIE['DisableFB'])) {?>
		<div class="socialInvt" style="height: auto; padding-bottom: 5px; padding-top: 10px;">
			<a href="https://www.facebook.com/AwardWallet" target="_blank"><div class="facebookIcon"></div></a>
			<div class="socialLink" style="padding-right: 0;"><iframe src="http<?php if (isset($_SERVER['HTTPS'])) {
			    echo 's';
			} ?>://www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2FAwardWallet&width=200&layout=button_count&action=like&size=small&show_faces=false&share=true&height=21&appId=75330755697" width="200" height="21" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true"></iframe></div>
			<div class="socialLink"></div>
			<div class="clear"></div>
		</div>
		<?}?>
		<div class="socialInvt" style="padding-top: 0px;">
			<div class="twitterIcon"></div>
			<div class="socialLink"><a target="_blank" class="leftMenuLink" href="http://twitter.com/home?status=<?php echo urlencode("I am using AwardWallet to track my rewards. I highly recommend it: http://AwardWallet.com/?r=" . $_SESSION['UserFields']['RefCode']); ?>&ref=4">Invite Twitter followers</a></div>
			<div class="socialLink"><a target="_blank" class="leftMenuLink" href="http://twitter.com/AwardWallet">Follow us on Twitter</a></div>
		</div>
		<table class="stats" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td class="statsNumber"><div class="statsNumber" id="invitesAccepted"><?php echo $acceptedCount; ?></div></td>
				<td valign="top"><div class="dash"></div></td>
				<td class="statsLable">people accepted</td>
			</tr>
			<tr>
				<td class="statsNumber"><div class="statsNumber availableColor" id='availableUpgrades'><?php echo $stars; ?></div></td>
				<td valign="top"><div class="dash availableColorDash"></div></td>
				<td class="statsLable"><a class='leftMenuLink' href="#" onclick="showMessagePopup('scroll', 'Available upgrades', document.getElementById('upgradeMessage').innerHTML); return false;">available upgrade<?php echo s($stars); ?></a></td>
			</tr>
			<tr>
				<td class="statsNumber" colspan="3">
					<div class="overallReferral">
						<table class="overRefTable" cellpadding="0" cellspacing="0"><tr>
							<td><div class="referralLeft"></div></td>
							<td><a href="/agent/invites.php" class="leftMenuLink" style="text-decoration: none;"><div class="referralCenter"><div class="referralUnderline"><?php echo getSymfonyContainer()->get('aw.referral_income_manager')->getTotalReferralBonusBalanceByUser($_SESSION['UserID']); ?></div></div></a></td>
							<td><div class="referralRight"></div></td>
						</tr></table>
					</div>
					<div class="statsLable"><a href="/agent/invites.php" class="leftMenuLink">referral bonus points</a></div>
				</td>
			</tr>
		</table>
		<?php
    }

    // draw notification message in a box in the middle of the page
    public function DrawMessageBox($sMessage, $sKind = "info", $boxWidth = 400)
    {
        ?>
<table cellspacing="0" cellpadding="0" border="0" align="center" style="width: 100%;">
<tr>
	<td valign="bottom" align="center">
<?php
if ($this->IsAdminInteface()) {
    TBaseInterface::DrawBeginBox($boxWidth);
} else {
    $this->DrawBeginBox("style='width: {$boxWidth}px'", null, false);
}
        ?>
<table cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 20px; margin-left: 20px; margin-right: 20px;">
<tr>
	<td width="50" align="left"><img src="/lib/images/<?php echo strtolower($sKind); ?>_big.gif" border="0" alt="" style="margin-right: 20px;"></td>
	<td class="<?php echo $sKind; ?>Frm"><?php echo $sMessage; ?></td>
</tr>
</table>
<?php
if ($this->IsAdminInteface()) {
    TBaseInterface::DrawEndBox();
} else {
    $this->DrawEndBox();
}
        ?>
	</td>
</tr>
</table>
<?php
    }

    public function DrawBeginBox($boxWidth = 400, $header = null, $closable = true, $classes = null, $closeButton = true)
    {
        if (is_integer($boxWidth)) { // old style call
            $boxWidth = "style='width: {$boxWidth}px;'";
        }
        ?>
		<div <?php echo $boxWidth; ?> class="roundedBox<?php if (isset($classes)) {
		    echo " " . $classes;
		} ?>">
		<div>
		<?// begin award details?>
		<table cellpadding="0" cellspacing="0" class="frame<?php
		if (!isset($header)) {
		    echo " headerLess";
		}

		if (!$closable) {
		    echo " notClosable";
		}
        ?>">
			<tr class="top">
				<td class="left"></td>
				<td class="center">
					<?php
                    if ($closeButton) {
                        echo "<a class='close' href='#' onclick='cancelPopup(); return false;'></a>";
                    } else {
                        echo "<div style='margin-top: 12px; line-height: 21px; height: 21px;'>&nbsp</div>";
                    }
                    echo "<div class='clear'></div><div class='header'>$header</div>";
        ?>
				</td>
				<td class="right"></td>
			</tr>
			<tr class="middle"><td class="left"><div class="bg"></div></td><td class="center">
		<?php
    }

    public function DrawEndBox()
    {
        ?>
				<div class='clear'></div>
			</td><td class="right"><div class="bg"></div></td></tr>
			<tr class="bottom"><td class="left"></td><td class="center"></td><td class="right"></td></tr>
		</table>
		<?// end award details?>
		</div>
		</div>
		<?php
    }

    public function RequireUserAuth()
    {
        StickToMainDomain();

        if (!isset($_SESSION['UserID'])) {
            $this->forceHTTPS(true);
        }
        parent::RequireUserAuth();
    }

    public function getHTTPSHost()
    {
        if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_PRODUCTION) {
            if (isset($_SERVER['HTTP_HOST'])) {
                return str_ireplace("www.", "", $_SERVER['HTTP_HOST']);
            } else {
                return SITE_NAME;
            }
        } else {
            return $_SERVER['SERVER_NAME'];
        }
    }

    public function RestoreCookies($cookies)
    {
        parent::RestoreCookies($cookies);

        foreach ($cookies as $key => $value) {
            if (preg_match('/^(AP|Account_\d+_Code|PasswordSaved|SavePwd|PwdHash)$/ims', $key)) {
                setcookie($key, $value, time() + SECONDS_PER_DAY * 90, "/", "", true);
            }
        }
    }

    /**
     * converts array of links to formatted html.
     *
     * @return string
     */
    public function getEditLinks($editLinks, $tower = false)
    {
        if (empty($editLinks)) {
            return "";
        }

        if (!$tower) {
            return "
            <table cellpadding='0' cellspacing='0'><tr><td><div class='left'></div></td><td>" . implode("</td><td><div class='right'></div></td><td><div class='left'></div></td><td>", $editLinks) . "</td><td><div class='right'></div></td></tr></table>";
        } else {
            return "
            <table cellpadding='0' cellspacing='0'><tr><td><div class='left'></div></td><td>" . implode("</td><td><div class='right'></div></td><tr></table><table cellpadding='0' cellspacing='0' style='margin-top:5px;'><tr><td><div class='left'></div></td><td>", $editLinks) . "</td><td><div class='right'></div></td></tr></table>";
        }
    }

    public function getEditIconLinks($editLinks)
    {
        return "<div class='iconLinksBlock " . (count($editLinks) < 3 ? "Short" . count($editLinks) : "") . "'>" . implode("", $editLinks) . "</div>";
    }

    public function jsonError(string $error)
    {
        echo \json_encode(['error' => $error]);

        exit(0);
    }

    public function getTitleIconLink($title)
    {
        return "
			<b class='title'>
				<b class='l'></b>
				<b class='c'>$title<b class='corn'></b></b>
				<b class='r'></b>
			</b>
		";
    }

    public function drawAddUserIDsBox()
    {
        $this->DrawBeginBox('id="askUserIDBox" style="display: none; min-width: 370px; height: 240px; position: absolute; z-index: 50;"', 'Add UserIDs');
        echo "<div style=\"padding: 0px;\">";
        echo "<div style=\"margin-bottom: 10px;\">";
        echo "<p id='providerName' style='font-weight: bold;'></p>";
        echo "<p id='note'>Here you may add User IDs that you would like to be in the Mailer list</p>";
        echo "<p>Format: <b id=\"regExp\"></b></p>";
        echo "<p id='example'>Example: 7, 349414, 61266, 428036</p>";
        echo "<p id='justLink' style=\"display: none\"></p>";
        echo "<input type=\"text\" id=\"userIDs\" style=\"margin-top: 15px; width: 290px;\" class=\"inputTxt\">";
        echo "<input type=\"hidden\" id=\"newState\">";
        echo "<input type=\"hidden\" id=\"providerID\">";
        echo "</div>";
        echo "</div>";
        echo $this->DrawButton("Add to mailer list", 'style="float:left;" onclick="changeProviderState(\'addUsers\'); return false;"');
        echo $this->DrawButton("Just send emails", 'style="float:right; background: #125da3; border-color: #125da3; color: #fff!important;" onclick="changeProviderState(\'noThanks\'); return false;"');
        echo "<div style=\"clear: both;\">&nbsp;</div>";
        //		echo $this->DrawButton("Dont send emails, just change state", 'style="float:left; width: 290px;" onclick="changeProviderState(\'dontSendEmails\'); return false;"');
        echo "<a href=\"#\" onclick='changeProviderState(\"dontSendEmails\"); return false;'>Dont send emails, just change state</a>";
        echo "<div style=\"clear: both;\">&nbsp;</div>";
        echo "<a href=\"#\" onclick='changeProviderState(\"justLink\"); return false;'>Dont send emails, just make a link</a>";
        $this->DrawEndBox();
    }

    public function getInput($name, $value = '', $attrs = '')
    {
        return '
		<table cellspacing="0" cellpadding="0" class="inputFrame">
			<tr>
				<td class="ifLeft"></td>
				<td class="ifCenter"><input type="text" value="' . $value . '" name="' . $name . '" class="inputTxt" ' . $attrs . '></td>
				<td class="ifRight"></td>
			</tr>
		</table>';

        // size="20" maxlength="80" id="fldStateID" style="width: 300px;"
    }

    public function DiePageAccount()
    {
        $this->DiePage("You are currently logged in as {$_SESSION['FirstName']} {$_SESSION['LastName']},
		however you are attempting to access someone else's account.<br><br>
		You can either <a href=\"/security/logout?BackTo=" . urlencode($_SERVER['REQUEST_URI']) . "\">log out and log in as that user</a>, or you can link your accounts using our <a href=\"/agent/connections.php?Source=A\">Connections</a> feature.");
    }
}
