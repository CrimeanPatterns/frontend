<?php

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;

require_once __DIR__ . "/../lib/schema/BaseUser.php";

require_once __DIR__ . "/../kernel/TSchemaManager.php";

class TUserSchema extends TBaseUserSchema
{
    public $UserAgentID;
    public $AcceptUserID;

    public static $UsersArray = [];

    public function __construct()
    {
        global $Interface, $decimalPoints;
        parent::__construct();
        unset($this->Fields["MidName"]);
        unset($this->Fields["BDay"]);
        unset($this->Fields["Phone1"]);
        unset($this->Fields["Phone2"]);
        unset($this->Fields["Address1"]);
        unset($this->Fields["Address2"]);
        unset($this->Fields["UserPhoto"]);
        unset($this->Fields["IsNewsSubscriber"]);
        unset($this->Fields["IsPartnersSubscriber"]);

        $newDesignEnable = isGranted("SITE_ND_SWITCH");

        if (isset($this->Fields["Email"], $_SESSION['UserID']) && $_SESSION['UserID'] == $this->userID && isGranted("SITE_ND_SWITCH")) {
            $q = new TQuery("select Email from Usr where UserID = {$this->userID}");
            $this->Fields["Email"]["Required"] = false;
            $this->Fields["Email"]["Type"] = "html";
            $this->Fields["Email"]["HTML"] = "<span id=\"fldEmail\" data-user-email=\"" . $q->Fields["Email"] . "\">" . $q->Fields["Email"] . "</span><br>" .
                "<a href='" . getSymfonyContainer()->get('router')->generate('aw_user_change_email') . "'>Click here to change</a>" .
                "<input type='hidden' name='Email' value='" . $q->Fields["Email"] . "'/>";
            $this->Fields["Email"]["Database"] = false;
            $this->Fields["Email"]["Note"] = "";
        }

        $this->Fields["AutoGatherPlans"] = [
            "Type" => "boolean",
            "Caption" => "Automatically gather my travel plans",
            "Value" => "1",
        ];
        $this->Fields["EmailNewPlans"] = [
            "Type" => "boolean",
            "Caption" => "Email me my new travel plans",
            "Value" => "1",
        ];
        $this->Fields["EmailPlansChanges"] = [
            "Type" => "boolean",
            "Caption" => "Email me when my travel plans change",       /* checked */
            "Value" => "1",
        ];
        //		if( ! $newDesignEnable) {
        //			$this->Fields["EmailTCSubscribe"] = array(
        //				"Type"    => "boolean",
        //				"Caption" => "Hotel booking offer emails",
        //				"Value"   => "1",
        //			);
        //		};
        $this->Fields["EmailFamilyMemberAlert"] = [
            "Type" => "boolean",
            "Caption" => "Send notifications that are intended for connected members to me also",
            "Value" => "1",
        ];
        $this->Fields["EmailInviteeReg"] = [
            "Type" => "boolean",
            "Caption" => "Email me when people that I invite register on AwardWallet",
            "Value" => "1",
        ];
        $this->Fields["EmailRewards"] = [
            "Type" => "integer",
            "Caption" => "Rewards activity",
            "Value" => "1",
            "InputAttributes" => "style=\"width: 307px;\"",
            "Options" => [
                3 => "Once a day",
                1 => "Once a week",
                2 => "Once a month",
                0 => "Never",
            ],
            "Note" => "Only if something changed",
        ];
        $this->Fields["CheckinReminder"] = [
            "Type" => "integer",
            "Caption" => "Flight check-in",
            "Value" => "1",
            "InputAttributes" => "style=\"width: 307px;\"",
            "Options" => [
                0 => "Off",
                1 => "24 hours before departure",
            ],
        ];
        $this->Fields["EmailProductUpdates"] = [
            "Type" => "boolean",
            "Caption" => "Occasional, important product updates",
            "Value" => "1",
        ];
        $this->Fields["EmailOffers"] = [
            "Type" => "boolean",
            "Caption" => "Lucrative offers",
            "Value" => "1",
        ];
        $this->Fields["DateFormat"] = [
            "Type" => "integer",
            "Value" => "1",
            "Required" => true,
            "InputAttributes" => "style=\"width: 307px;\"",
            "Options" => [
                DATEFORMAT_US => "mm/dd/yyyy",
                DATEFORMAT_EU => "dd/mm/yyyy",
            ],
        ];
        $options = [];

        foreach ($decimalPoints as $separator => $point) {
            $options[$separator] = number_format(12000, 2, $point, $separator);
        }
        $this->Fields["ThousandsSeparator"] = [
            "Type" => "string",
            "Caption" => "Number Format",
            "Value" => ",",
            "Required" => true,
            "InputAttributes" => "style=\"width: 307px;\"",
            "Options" => $options,
        ];
        ArrayInsert($this->Fields, "AutoGatherPlans", false, [
            "HomeAirport" => [
                "Type" => "string",
                "Size" => 3,
                "Required" => false,
                "Note" => "This will help us organizing your travel plans", /* checked */
                "RegExp" => "/^[a-zA-Z0-9]{3}$/i",
            ],
        ]);
        ArrayInsert($this->Fields, 'Pass', true, [
            'SavePassword' => [
                "Caption" => "By default",
                "Type" => "integer",
                "Value" => "0",
                "Required" => true,
                "InputAttributes" => "style='width:308px;'",
                "Options" => [
                    SAVE_PASSWORD_DATABASE => "Store passwords in the database",
                    SAVE_PASSWORD_LOCALLY => "Store passwords locally",
                ],
            ],
        ]);

        if (SITE_MODE != SITE_MODE_BUSINESS && !empty($_SESSION['UserFields']) && array_key_exists('GoogleAuthSecret', $_SESSION['UserFields'])) {
            if ((null === $_SESSION['UserFields']['GoogleAuthSecret']) && (null === $_SESSION['UserFields']['GoogleAuthRecoveryCode'])) {
                $twoFactorHtml = "<a href=\"#\" onclick=\"showCheckPass(function () {setupTwoFactorAuthPopupWindow(); showPopupWindow(document.getElementById('TwoFactorAuthPopup'), false);}); return false; \">Setup</a>";
            } else {
                $twoFactorHtml = 'Enabled. Click <a href="#" onclick="showCheckPass(cancelTwoFactorAuth)">here</a> to turn it off';
            }

            if (isset($twoFactorHtml)) {
                ArrayInsert($this->Fields, 'Pass', true, [
                    "2FactorAuth" => [
                        "Caption" => "Two-factor Authentication",
                        "Type" => "html",
                        "HTML" => $twoFactorHtml,
                    ],
                ]);
            }
        }

        $this->Fields['StateID']['Required'] = false;
    }

    public function GetFormFields()
    {
        global $arAccountLevel, $Interface;
        $arFields = parent::GetFormFields();

        if (isset($arFields['StateID'])) {
            $arFields['StateID']['Manager']->Required = false;
            $arFields['StateID']['InputAttributes'] = 'style="width: 300px;"';
        }

        $newDesignEnable = isGranted("SITE_ND_SWITCH");
        $payLink = $newDesignEnable ? getSymfonyContainer()->get('router')->generate('aw_users_pay') : '/user/pay.php';

        if ((SITE_MODE == SITE_MODE_BUSINESS) && isset($_SESSION['UserID'])) {
            $arFields["Login"]["Caption"] = "Email alias for travel plans"; /* review */
            $loginField = $arFields["Login"];
            unset($arFields['Login']);
            ArrayInsert($arFields, "HomeAirport", false, ["Login" => $loginField]);
            unset($arFields["FirstName"]);
            unset($arFields["LastName"]);
            unset($arFields["Pass"]);
            unset($arFields["Email"]);

            unset($arFields["EmailNewPlans"]);
            unset($arFields["EmailPlansChanges"]);
            unset($arFields["EmailTCSubscribe"]);
            unset($arFields["EmailFamilyMemberAlert"]);
        }

        if (isset($_SESSION['UserID'])) {
            $sCaption = $arAccountLevel[$_SESSION['AccountLevel']];

            if ($_SESSION['AccountLevel'] == ACCOUNT_LEVEL_FREE) {
                $sCaption .= " (<a href='" . $payLink . "'>Upgrade to AwardWallet Plus</a>)";
            }

            if ($_SESSION['AccountLevel'] == ACCOUNT_LEVEL_AWPLUS) {
                GetAccountExpiration($_SESSION['UserID'], $dDate, $nLastPrice);
                $sCaption .= " (expires: " . date("M j, Y", $dDate) . ")";

                if (strtotime(MAX_AWPLUS_PERIOD) > $dDate) {
                    $sCaption .= "<br><a href='" . $payLink . "'>Extend AwardWallet Plus until " . date(DATE_FORMAT, strtotime("+6 month", $dDate)) . "</a>";
                } else {
                    $sCaption .= "<br><a href='" . $payLink . "'>Donate</a>";
                }
            }

            if (SITE_MODE == SITE_MODE_PERSONAL) {
                if (!empty($_SESSION['UserFields']['PayPalRecurringProfileID'])) {
                    $sCaption .= "<br><br>Recurring payment is turned on.<br/>
				Your card will be charged \$" . $_SESSION['UserFields']['RecurringPaymentAmount'] . " every 6 months<br/>
				Please <a href='#' onclick=\"
				showMessagePopup('info', 'Recurring payment', 'Would you like to cancel recurring payment?');
				document.getElementById('messageCancelButton').style.display = 'block';
				document.getElementById('messageOKButton').onclick = function(){
					showMessagePopup('info', 'Updating..', 'Cancelling your subscription, please wait...', true);
					$.ajax({
						url: '/user/cancelRecurring.php',
						type: 'POST',
						success: function(){
							document.location.href = '/user/edit.php?cancelled';
						}
					});
				};
				return false;
				\">click here to turn it off</a>.
				";
                } else {
                    $sCaption .= "<br><br>
				Recurring payment is turned off.<br/>
				Please <a href='" . $payLink . "?Recurring=1'>click here to turn it on</a>
				";
                }
            }
            $arFields = [
                "AccountLevel" => [
                    "Type" => "html",
                    "Caption" => "Account Type",
                    "Database" => false,
                    "HTML" => $sCaption,
                ]] + $arFields;
        } else {
            unset($arFields["PrimaryFunctionality"]);
            unset($arFields["EmailNewPlans"]);
            unset($arFields["EmailTCSubscribe"]);
            unset($arFields["EmailRewardsHeader"]);
            unset($arFields["EmailRewards"]);
            unset($arFields["ThousandsSeparator"]);
            unset($arFields["DateFormat"]);
            unset($arFields["HomeAirport"]);
        }

        $finalFields = [];

        foreach ($arFields as $key => $value) {
            if (isset($value["InputAttributes"])) {
                $value["InputAttributes"] = str_ireplace("322px", "307px", $value["InputAttributes"]);
            }
            $finalFields = $finalFields + [$key => $value];

            if ($key == "Zip") {
                $finalFields = $finalFields + ["Company" => [
                    "Type" => "string",
                    "InputAttributes" => "style=\"width: 300px;\"",
                    "Size" => 80,
                    "Cols" => 20,
                ],
                ];
            }
        }
        $finalFields["MiscHeader"] = [
            "Type" => "html",
            "Database" => false,
            "IncludeCaption" => false,
            "HTML" => "<tr><td colspan='4' class='sectionHeader'>" . $Interface->drawSectionDivider("Miscellaneous:", true) . "</td></tr>",
        ];

        if (isset($_SESSION['UserID'])) {
            $q = new TQuery("select
			sum(case when a.SavePassword = " . SAVE_PASSWORD_DATABASE . " then 1 else 0 end) as DB,
			count(*) as Total
			from Account a
			join Provider p on a.ProviderID = p.ProviderID
			where a.UserID = {$_SESSION['UserID']} and a.ProviderID is not null and p.PasswordRequired = 1");
            $database = $q->Fields['DB'];
            $local = $q->Fields['Total'] - $database;
            $s = "";
            $popup = "Store all existing passwords:";

            if ($database > 0) {
                $s .= "<div style='line-height: 22px;'><strong>{$database}</strong> password" . s($database) . ($database > 1 ? " are " : " is ") . " stored in a secure and encrypted AwardWallet database.</div>
				<table border='0' cellpadding='6' cellspacing='0'><tr><td valign='top'><img src=\"/lib/images/success.gif\"/ style=\"margin-top: 5px;\"></td><td style='color: #8a8a8a'>AwardWallet is automatically monitoring your reward programs for changes and expirations.</td></tr></table>";
                $popup .= "<br><br><img src=/lib/images/bulletBlue4.gif style='position: relative; top: -4px; margin-right: 5px;'> Locally on this computer";
            }

            if ($local > 0) {
                if ($s != "") {
                    $s .= "";
                }
                $s .= "<div style='line-height: 22px;'><strong>{$local}</strong> password" . s($local) . ($local > 1 ? " are " : " is ") . " stored locally on your computer.</div>
				<table border='0' cellpadding='6' cellspacing='0'><tr><td valign='top'><img src=\"/lib/images/warning.gif\" style=\"margin-top: 5px;\"/></td><td style='color: #8a8a8a'>AwardWallet is not able to automatically monitor reward programs
                  for which passwords are stored locally. Also if you clear your cookies
                  or simply use another computer to check your balances these passwords
                  would have to be re-entered.</td></tr></table>";

                // Backup and restore local passwords
                // location.replace - don't shows in browser history
                // Check that user contains local passwords
                $q = new TQuery("select a.AccountID, a.Pass, a.SavePassword, p.DisplayName, a.Login
					from Account a
					join Provider p on a.ProviderID = p.ProviderID
					where a.SavePassword = " . SAVE_PASSWORD_LOCALLY . " and a.UserID = {$_SESSION['UserID']} and a.ProviderID is not null");

                if (!$q->EOF) {/* askUserPassword function */
                    /* checked */
                    $s .= $Interface->DrawButton('Backup', 'id="mainBackupButton"', 0, 'onclick="askUserPassword(\'Backup local passwords\', \'Get backup\', function() { location.href = \'/account/backupPasswords.php\'; }, \'mainBackupButton\' ); return false;"');
                    $s .= $Interface->DrawButton('Restore', 'id="restorePwdButton"', 0, 'onclick="return false;"', 'submit', 'btn-silver');
                }
                $s .= '</div>';

                $s .= '<br /><br />';
                $popup .= "<br><br><img src=/lib/images/bulletBlue4.gif style='position: relative; top: -4px; margin-right: 5px;'> In a secure and encrypted AwardWallet database";
            }

            if (!$newDesignEnable) {
                $s .= "<br /><a href=\"#\" onclick=\"showMessagePopup('info', 'Change where all of my passwords are stored', '" . addslashes(
                    $popup
                ) . "'); document.getElementById('messageOKButton').value = 'Cancel';return false;\">Change where all of my passwords are stored</a>";
            }

            if (($local > 0) || ($database > 0)) {
                if (!$newDesignEnable) {
                    $finalFields["PasswordOptions"] = [
                        "Caption" => "Password options<a name='passwordOptions'></a>",
                        "IncludeCaption" => true,
                        "Database" => false,
                        "Type" => "html",
                        "HTML" => $s,
                    ];
                }
            }
        }

        if (isset($_SESSION['UserID'])) {
            if (!$newDesignEnable) {
                $finalFields["Skin"] = [
                    "Caption" => "View",
                    "Type" => "string",
                    "Options" => [
                        "" => "Regular",
                        "compact" => "Compact",
                    ],
                ];
            }
            $finalFields["DisableBrowserExtension"] = [
                "Type" => "boolean",
                "Caption" => "Disable AwardWallet extension in this browser",
                "Value" => ArrayVal($_COOKIE, 'DBE', '0'),
                "Database" => false,
                "Note" => "Please note that by disabling AwardWallet extension you lose many of the AwardWallet features. If you change your browser or clear your cookies you will need to check this checkbox again.",
            ];
        }

        if (!isset($finalFields['AdOptions']) && !isset($finalFields["PasswordOptions"]) && !isset($finalFields["Skin"])
            && !isset($finalFields["DisableBrowserExtension"]) && !isset($finalFields["UserEmail"])) {
            unset($finalFields["MiscHeader"]);
        }

        if (isGranted("SITE_ND_SWITCH")) {
            $picManager = new AvatarPictureFieldManager();
        } else {
            $picManager = new TPictureFieldManager();
        }

        $picManager->Dir = "/images/uploaded/user";
        $picManager->thumbHeight = 64;
        $picManager->thumbWidth = 64;
        $picManager->KeepOriginal = true;
        $picManager->CreateMedium = true;

        if (SITE_MODE == SITE_MODE_BUSINESS) {
            $field = "Company";
        } else {
            $field = "Email";
        }
        ArrayInsert($finalFields, $field, true, [
            "Picture" => [
                "Type" => "string",
                "Manager" => $picManager,
                "Caption" => SITE_MODE == SITE_MODE_BUSINESS ? "Logo" : "Picture",
            ],
        ]);

        return $finalFields;
    }

    public function UpdateInviterScore($nUserID)
    {
        global $Connection, $Interface;
        $acceptedCount = $this->getLiveReferralsCount($nUserID);
        $totalStars = floor($acceptedCount / 5);
        $actualStars = $Interface->FreeCouponCount("Invite-{$nUserID}-%");
        $new = $totalStars - $actualStars;

        while ($totalStars > $actualStars) {
            $sCode = "Invite-{$nUserID}-" . RandomStr(ord('A'), ord('Z'), 5);
            $Connection->Execute("insert into Coupon(Code, Name, MaxUses, Discount, FirstTimeOnly)
			values('{$sCode}', 'Invite bonus', 1, 100, 0)");
            $totalStars--;
        }

        return $new > 0 ? $new : 0;
    }

    public function CheckAgree(&$objForm)
    {
        requirePasswordAccess();

        if (isset($objForm->Fields['Email']) && !empty($objForm->Fields['Email']['Value'] != $objForm->Fields['Email']['OldValue'])) {
            $objForm->SQLParams["ResetPasswordCode"] = "null";
        }

        return parent::CheckAgree($objForm);
    }

    public function TuneForm(TBaseForm $form)
    {
        parent::TuneForm($form);

        if ($form->ID == 0) {
            $form->SQLParams["RefCode"] = "'" . RandomStr(ord('a'), ord('z'), 10) . "'";

            if (isset($_SESSION['ReferralID'])) {
                $form->SQLParams['ReferralID'] = "'" . addslashes($_SESSION['ReferralID']) . "'";
            }
        } else {
            if (isset($form->Fields["Email"], $_SESSION['UserID']) && $_SESSION['UserID'] == $this->userID && isGranted("SITE_ND_SWITCH")) {
                foreach ($form->Uniques as $k => $u) {
                    if ($u['Fields'] == ["Email"]) {
                        unset($form->Uniques[$k]);
                    }
                }
            }

            if (SITE_MODE == SITE_MODE_BUSINESS) {
                $form->Uniques = [
                    [
                        "Fields" => ["Login"],
                        "ErrorMessage" => "User with this email alias already exists. Please choose another email alias.", /* review */
                    ],
                    [
                        "Fields" => ["Company"],
                        "ErrorMessage" => "Company name already taken. Please choose another company name.",
                    ],
                ];

                if (isGranted("SITE_ND_SWITCH")) {
                    $form->SuccessURL = "/account/overview.php";
                } else {
                    $form->SuccessURL = "/account/list";
                }
            }
        }
    }

    public function emailNotify(&$objForm)
    {
        $q = new TQuery("select u.Referer, sa.Description as SiteAdDescription, u.CameFrom
			from Usr u left outer join SiteAd sa on u.CameFrom = sa.SiteAdID
			where u.UserID = {$objForm->ID}");
        mailTo(
            SECURITY_EMAIL,
            "New account was registered at " . SITE_NAME,
            "Referer: " . ArrayVal($q->Fields, "Referer") . "
Came from: " . ArrayVal($q->Fields, "SiteAdDescription") . " ({$q->Fields['CameFrom']})
Login: {$objForm->Fields["Login"]["Value"]}
Email: {$objForm->Fields["Email"]["Value"]}
ID: $objForm->ID
IP: " . $_SERVER["REMOTE_ADDR"],
            EMAIL_HEADERS
        );

        $q = new TQuery("SELECT UserID FROM Usr WHERE Login = '" . addslashes($objForm->Fields["Login"]["Value"]) . "'");

        if ($q->EOF) {
            DieTrace('UserID was not found for registration notification purposes', false); /* checked */
        } else {
            $uid = $q->Fields['UserID'];
            /** @var \AwardWallet\MainBundle\Entity\Usr $user */
            $user = getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($uid);
            $mailer = getSymfonyContainer()->get('aw.email.mailer');
            $template = new \AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\WelcomeToAw($user);
            $message = $mailer->getMessageByTemplate($template);
            $mailer->send($message, [
                Mailer::OPTION_SKIP_DONOTSEND => true,
            ]);
        }
    }

    public static function isAdminBusinessAccount($userID)
    {
        if (!isset(self::$UsersArray[$userID]['isAdminBusinessAccount'])) {
            $q = new TQuery("select 1 from UserAgent ua, Usr u
				where ua.AgentID = {$userID} and ua.ClientID = u.UserID and u.AccountLevel = " . ACCOUNT_LEVEL_BUSINESS . " AND ua.AccessLevel = " . ACCESS_ADMIN);
            self::$UsersArray[$userID]['isAdminBusinessAccount'] = !$q->EOF;

            return self::$UsersArray[$userID]['isAdminBusinessAccount'];
        }

        return self::$UsersArray[$userID]['isAdminBusinessAccount'];
    }

    public static function getTextErrorAboutLimitUsers($isPopUp = true, $forAdminBusinessAccount = false)
    {
        global $Interface;
        $html = '';

        if ($isPopUp) {
            ob_start();
            $Interface->DrawBeginBox('id="usersLimit"', 'Personal Account Limit'/* checked */, false, 'popupWindow'); ?>
			<div style="padding: 20px;">
				<div class="question">
					AwardWallet.com is a personal interface for managing loyalty programs and is not intended for business use. There is another business interface that we offer: <a href="http://business.AwardWallet.com" target="_blank">http://business.AwardWallet.com</a>.
            Your account looks like a business account based on the fact that you have added a total
            of <?php echo MyConnectionsCount(); ?> users to your profile. <?php /* checked */ ?>
					<?php if (!$forAdminBusinessAccount) {?>
                Please convert this personal account to a business account. <?php /* checked */ ?>
						</div>
						<div class="buttons" id="funcButtons">
                            <?php echo $Interface->DrawButton("Convert to Business Account", "onclick='document.location.href=\"/agent/convertToBusiness.php?Start=1\"'", 220); ?><?php /* checked */ ?>
						</div>
					<?php } else {?>
                Please login to your business account. <?php /* checked */ ?>
						</div>
						<div class="buttons" id="funcButtons">
                            <?php echo $Interface->DrawButton("Login to Business Account", "onclick='document.location.href=\"http://business.{$_SERVER['HTTP_HOST']}/?Login=1\"'", 220); ?><?php /* checked */ ?>
						</div>
					<?php } ?>
			</div>
			<?php
            $Interface->DrawEndBox();
            $html = ob_get_contents();
            ob_end_clean();
        } else {
            $pattern = "AwardWallet.com is a personal interface for managing loyalty programs and is not intended for business use. There is another business interface that we have: <a href='http://business.AwardWallet.com' target='_blank'>http://business.AwardWallet.com</a>. Your account looks like a business account based on the fact that you have added a total of %d users to your profile."; /* checked */

            if (!$forAdminBusinessAccount) {
                $pattern .= " Please <a href='/agent/convertToBusiness.php?Start=1'>Convert this personal account to a business account</a>."; /* checked */
            } else {
                $pattern .= " Please <a href='http://business.{$_SERVER['HTTP_HOST']}/?Login=1'>login to your business account</a>."; /* checked */
            }
            $html = sprintf($pattern, MyConnectionsCount());
        }

        return $html;
    }

    public function GetListFields()
    {
        $arFields = parent::GetListFields();
        unset($arFields['EmailInviteeReg']);

        return $arFields;
    }

    public static function trialAccount($userId)
    {
        $q = new TQuery("select
			ci.TypeID
		from
			Cart c
			join CartItem ci on c.CartID = ci.CartID
		where
			c.PayDate is not null
			and c.UserID = $userId
		order by
			c.PayDate desc
		limit 1");

        return !$q->EOF && $q->Fields['TypeID'] == CART_ITEM_AWPLUS_TRIAL;
    }

    /**
     * returns last used user address, in order:
     * 1. shipping
     * 2. billing
     * 3. profile info
     */
    public static function getLastAddress($userId)
    {
        // scan last orders
        foreach (['Ship', 'Bill'] as $prefix) {
            $q = new TQuery("select
				co.Name as CountryName,
				co.Code as CountryCode,
				st.Name as StateName,
				st.Code as StateCode,
				c.{$prefix}FirstName as FirstName,
				c.{$prefix}LastName as LastName,
				c.{$prefix}Address1 as Address1,
				c.{$prefix}Address2 as Address2,
				c.{$prefix}City as City,
				c.{$prefix}Zip as Zip,
				c.{$prefix}CountryID as CountryID,
				c.{$prefix}StateID as StateID
			from
				Cart c
				join Country co on c.{$prefix}CountryID = co.CountryID
				join State st on c.{$prefix}StateID = st.StateID
			where
				c.UserID = {$userId}
				and PayDate is not null
				and {$prefix}CountryID is not null
			order by
				c.PayDate desc
			limit 1");

            if (!$q->EOF) {
                return $q->Fields;
            }
        }
        // scan account info
        $q = new TQuery("select
			co.Name as CountryName,
			co.Code as CountryCode,
			st.Name as StateName,
			st.Code as StateCode,
			u.FirstName,
			u.LastName,
			u.Address1,
			u.Address2,
			u.City,
			u.Zip,
			u.CountryID,
			u.StateID
		from
			Usr u
			left outer join Country co on u.CountryID = co.CountryID
			left outer join State st on u.StateID = st.StateID
		where
			u.UserID = {$userId}");

        return $q->Fields;
    }

    protected function getLiveReferralsCount($inviterID)
    {
        $q = new TQuery("
            SELECT COUNT(InvitesID) AS accepted
            FROM Invites
            WHERE
                InviterID = {$inviterID} AND
                /* don't count unapproved invites and deleted referrals */
                Approved = 1 AND
                InviteeID IS NOT NULL");

        return (int) $q->Fields["accepted"];
    }

    protected function ClearFakeUsers($UserRegistrationIP)
    {
        global $Connection;
        $sql = "
			SELECT
				COUNT(u.UserID) count,
				i.InviterID,
				up.Login as InviterLogin
			FROM 
				Usr u
			LEFT JOIN Account a ON u.UserID = a.UserID
			LEFT JOIN Invites i ON u.UserID = i.InviteeID
			JOIN Usr up on i.InviterID = up.UserID
			WHERE 
				u.RegistrationIP=$UserRegistrationIP
				AND u.CameFrom = 4
				AND u.CreationDateTime > ADDDATE(NOW(), INTERVAL -1 HOUR)
				AND i.InviterID IS NOT NULL
			GROUP BY 
				i.InviterID
		";
        $countUsers = new TQuery($sql);

        while (!$countUsers->EOF) {
            if ($countUsers->Fields['count'] > 2) {
                $IDs = "";
                $numberOfFakeAccounts = 0;
                $sql = "
					SELECT
						u.UserID,
						COUNT(a.AccountID) AccountsNum
					FROM 
						Usr u
					LEFT JOIN Account a ON u.UserID = a.UserID
					WHERE 
						RegistrationIP=$UserRegistrationIP
						AND CameFrom = 4
						AND CreationDateTime > ADDDATE(NOW(), INTERVAL -1 HOUR)
					GROUP BY UserID					
				";
                $fakeUsers = new TQuery($sql);

                while (!$fakeUsers->EOF) {
                    $TManager = new TSchemaManager();

                    if (intval($fakeUsers->Fields['AccountsNum']) == 0) {
                        $numberOfFakeAccounts++;
                        $IDs .= $fakeUsers->Fields['UserID'] . ",";
                    }
                    $fakeUsers->Next();
                }
                $IDs = trim($IDs, ',');

                if (!empty($IDs) && $numberOfFakeAccounts > 2) {
                    mailTo(
                        ConfigValue(CONFIG_ERROR_EMAIL),
                        "Fake IDs should be deleted" . ((ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG) ? " (from test server)" : ""),
                        "Potentially fake IDs:<br/>" . $IDs . "<br/>
						Registered from IP: $UserRegistrationIP<br/>
						InviterID: {$countUsers->Fields['InviterID']}<br/>
						InviterLogin: {$countUsers->Fields['InviterLogin']}<br/>
						<a href='//{$_SERVER['HTTP_HOST']}/manager/reports/fakeAccounts.php?Inviter={$countUsers->Fields['InviterID']}#{$countUsers->Fields['InviterID']}'>Delete All</a>",
                        str_replace("text/plain", "text/html", EMAIL_HEADERS)
                    );
                }
            }
            $countUsers->Next();
        }
    }
}
?>
