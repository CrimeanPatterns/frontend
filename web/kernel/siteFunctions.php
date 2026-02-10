<?php

// -----------------------------------------------------------------------
// 		site function library
//		contains site-specific, often used functions
//		included to every page
//		move rarely-used functions to separate files
// 		Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com
// -----------------------------------------------------------------------
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\BonusConversion;
use AwardWallet\MainBundle\Globals\StandartViewCreator;

function drawLeftMenu($items, $counts = true)
{
    echo '<div class="bContent">
	<div class="bTopGrad bPad">';

    foreach ($items as $item) {
        $class = "item";

        if ($item['selected'] == true) {
            $class .= " selected";
        } else {
            $class .= " normal";
        }

        if (isset($item['actionPath'])) {
            $class .= " withact";
        } else {
            $class .= " woact";
        }

        if (isset($item['count'])) {
            $count = "(" . $item['count'] . ")";
        } else {
            $count = "&nbsp;";
        }

        $targetPath = isset($item['targetPath']) ? 'target="' . $item['targetPath'] . '"' : '';
        $targetActionPath = isset($item['targetActionPath']) ? 'target="' . $item['targetActionPath'] . '"' : '';

        echo "
			<div class=\"$class\">
				<a href=\"" . htmlspecialchars($item['path']) . "\"" . (isset($item['onclick']) ? " onclick=\"" . htmlspecialchars($item['onclick']) . "\"" : "") . " class=\"" . htmlspecialchars($class) . "\" " . $targetPath . ">";

        if ($counts) {
            echo "<div class=\"count\">{$count}</div>";
        }
        echo "<div class=\"caption\">{$item['caption']}</div>
				</a>\n";

        if (isset($item['actionPath'])) {
            echo "<a class=\"action\" href=\"" . htmlspecialchars($item['actionPath']) . "\" title=\"" . htmlspecialchars($item['actionCaption']) . "\" " . $targetActionPath . "><div class=\"icon\"></div>
			<div class=\"action\">
			add
			</div></a>";
        }
        echo "</div>\n";
    }
    echo '</div>
		</div>';
}

function openLeftBox($title, $classes)
{
    if (isset($title)) {
        echo "<div class=\"boxTitle\">
			<div class=\"head\"></div>
			<div class=\"center\">
				<div class=\"caption\">{$title}</div>
				<div class=\"mark\"></div>
			</div>
			<div class=\"foot\"></div>
		</div>\n";
        $classes .= " titled";
    } else {
        $classes .= " untitled";
    }
    echo '<div class="box ' . $classes . '">
	<div class="bHead"></div>';
}

function closeLeftBox()
{
    echo '<div class="bFoot"></div>
	</div>';
}

function makeDefaultSelection(&$items, $level = 0)
{
    global $forceMenuSelect, $Interface, $depth;
    $level++;

    if ($depth < $level) {
        $depth = $level;
    }

    foreach ($items as $key => $value) {
        if (!isset($forceMenuSelect)) {
            if ($Interface->comparePaths($value["path"])) {
                $items[$key]["selected"] = true;
            }
        }

        if (isset($value["subMenu"])) {
            makeDefaultSelection($items[$key]["subMenu"], $level);
        }
    }
}

// load state options and attributes. returns state array
function LoadStateOptions($nCountryID, &$arOptionAttributes = null)
{
    $q = new TQuery("select s.*, a.Name as AreaName from State s
	left outer join Area a on s.AreaID = a.AreaID
	where s.CountryID = $nCountryID
	order by IsNull( s.AreaID ), a.Name, s.Name");
    $sArea = "";
    $arResult = [];

    while (!$q->EOF) {
        if ($sArea != $q->Fields["AreaName"]) {
            if ($q->Fields["AreaName"] != "") {
                $sArea = $q->Fields["AreaName"];
            } else {
                $sArea = "Other";
            }
            $arResult[" __" . $q->Fields["AreaID"]] = " ";
            $arResult[" _" . $q->Fields["AreaID"]] = "--- $sArea ---";

            if (isset($arOptionAttributes)) {
                $arOptionAttributes[" _" . $q->Fields["AreaID"]] = "style=\"{background-color: rgb(160, 40, 49); color: white}\"";
            }
        }
        $arResult[$q->Fields["StateID"]] = $q->Fields["Name"];
        $q->Next();
    }

    return $arResult;
}

function drawButton($caption, $attr, $size)
{
    return "<table cellspacing='0' cellpadding='0' border='0'>
<tr>
	<td><input type='submit' " . $attr . " class='button" . $size . "' value=\"" . htmlspecialchars($caption) . "\"></td>
	<td><img src='/images/arrow" . $size . ".gif' alt=''></td>
</tr>
</table>";
}

function MyConnectionsCount($sFilter = "")
{
    global $Connection;

    if (!isset($_SESSION['UserID'])) {
        return 0;
    }

    if (!isset($Connection) || !$Connection->Active) {
        return 0;
    }

    if (isset($GLOBALS['MyConnectionsCount'])) {
        return $GLOBALS['MyConnectionsCount'];
    }

    if (SITE_MODE == SITE_MODE_BUSINESS) {
        $q = new TQuery("select count( UserAgentID ) as Cnt
			from UserAgent
			where ( AgentID = {$_SESSION['UserID']} ) and ClientID is not null and IsApproved = 0 " . ($sFilter != "" ? " and $sFilter" : ""));

        return $GLOBALS['MyConnectionsCount'] = $q->Fields["Cnt"];
    } else {
        $q = new TQuery("select count( UserAgentID ) as Cnt
		from UserAgent
		where ( ClientID = {$_SESSION['UserID']} ) and AgentID is not null and IsApproved = 1" . ($sFilter != "" ? " and $sFilter" : ""));
        $qEmail = new TQuery("select count(*) as Cnt from InviteCode where UserID = {$_SESSION['UserID']}");
        $qFamily = new TQuery("select count( UserAgentID ) as Cnt
		from UserAgent
		 where ( AgentID = {$_SESSION['UserID']} ) and ClientID is null" . ($sFilter != "" ? " and $sFilter" : ""));

        return $GLOBALS['MyConnectionsCount'] = $q->Fields["Cnt"] + $qEmail->Fields["Cnt"] + $qFamily->Fields['Cnt'];
    }
}

function OtherUsersSQL($userAgentId = null)
{
    $sql = "select coalesce( u.FirstName, ua.FirstName ) as FirstName,
	coalesce( u.LastName, ua.LastName ) as LastName,
	" . SQL_USER_NAME . " as UserName,
	ua.UserAgentID, ua.ClientID, u.AccountLevel, u.Company
	from UserAgent ua
	left outer join UserAgent au on au.ClientID = ua.AgentID and au.AgentID = ua.ClientID
	left outer join Usr u on ua.ClientID = u.UserID
	where ua.AgentID = {$_SESSION['UserID']} and ua.IsApproved = 1
	and (au.IsApproved = 1 or au.IsApproved is null)
	";

    if (isset($userAgentId)) {
        $sql .= " and ua.UserAgentID = $userAgentId";
    }
    //	if(SITE_MODE == SITE_MODE_PERSONAL)
    //		$sql .= " and (c.AccountLevel is null or c.AccountLevel <> ".ACCOUNT_LEVEL_BUSINESS.")";
    $sql .= " order by UserName";

    return $sql;
}

function AddOthersMenu()
{
    global $othersMenu, $leftMenu;

    $symfonyContainer = getSymfonyContainer();
    $userRep = $symfonyContainer->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

    $currentUser = $userRep->find($_SESSION["UserID"]);
    $inBeta = $currentUser->getInbeta();

    $newInterfaceRoute = $symfonyContainer->get('router')->generate('aw_user_new_interface_switch');

    $q = new TQuery(OtherUsersSQL());
    $nMyCount = 0;

    if (isset($leftMenu["My Award Programs"])) {
        $qCnt = new TQuery("select count(*) as Cnt from Account a LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
		where UserID = {$_SESSION['UserID']} and UserAgentID is null AND " . userProviderFilter() . " AND a.State > 0
		union select count(*) as Cnt from ProviderCoupon where UserID = {$_SESSION['UserID']} and UserAgentID is null");
        $nCount = $qCnt->Fields["Cnt"];
        $qCnt->Next();
        $nCount += $qCnt->Fields["Cnt"];
        $nMyCount = $nCount;
        $leftMenu["My Award Programs"]["count"] = $nMyCount;
    }

    if (!$q->EOF && isset($leftMenu["My Award Programs"])) {
        $leftMenu = ["All Award Programs" => [
            "caption" => "All Award Programs",
            "path" => "/account/list.php?UserAgentID=All",
            "selected" => false,
        ]] + $leftMenu;
    }
    $nOtherCount = 0;

    while (!$q->EOF) {
        if ($q->Fields["ClientID"] != "") {
            $qShare = new TQuery(
                "
                SELECT
                    (select count(ash.AccountShareID) as Cnt from AccountShare ash
                     LEFT JOIN Account a ON ash.AccountID = a.AccountID
                     LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
                     where ash.UserAgentID = {$q->Fields['UserAgentID']} AND " . userProviderFilter() . "
                    )
                    +
                    (select count(pcsh.ProviderCouponShareID) as Cnt from ProviderCouponShare pcsh
                     LEFT JOIN ProviderCoupon pc ON pcsh.ProviderCouponID = pc.ProviderCouponID
                     where pcsh.UserAgentID = {$q->Fields['UserAgentID']}
                    )
                    AS Cnt"
            );
        } else {
            $qShare = new TQuery(
                "
                SELECT
                    (select count(a.AccountID) as Cnt from Account a
                     LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
				     where a.UserAgentID = {$q->Fields['UserAgentID']} AND " . userProviderFilter() . "
				    )
				    +
                    (SELECT count(pc.ProviderCouponID) as Cnt
                     FROM ProviderCoupon pc
                     WHERE pc.UserAgentID = {$q->Fields['UserAgentID']}
                    )
                    AS Cnt"
            );
        }

        if ($qShare->Fields['Cnt'] > 0) {
            $othersMenu["Client_" . $q->Fields['UserAgentID']] = [
                "caption" => getNameOwnerAccountByUserFields($q->Fields),
                "count" => $qShare->Fields['Cnt'],
                "actionPath" => "/account/add.php?UserAgentID={$q->Fields['UserAgentID']}",
                "actionCaption" => "Add a new loyalty program account for " . $q->Fields['FirstName'] . " " . $q->Fields['LastName'],
                "path" => "/account/list.php?UserAgentID={$q->Fields['UserAgentID']}",
                "selected" => false,
            ];
            $nOtherCount += $qShare->Fields['Cnt'];
        }
        $q->Next();
    }

    if (is_array($othersMenu) && count($othersMenu) > 0) {
        $othersMenu["Add New Person"] = $leftMenu["Add New Person"];
        unset($leftMenu["Add New Person"]);
    }

    if (isset($leftMenu["All Award Programs"])) {
        $nAllCount = $nMyCount + $nOtherCount;
        $leftMenu["All Award Programs"]["count"] = $nAllCount;
    }
    AddPendingMenu();

    if ($_SESSION['AccountLevel'] == ACCOUNT_LEVEL_AWPLUS) {
        GetAccountExpiration($_SESSION['UserID'], $dDate, $nLastPrice);

        if (time() >= strtotime("-1 month", $dDate)) {
            $leftMenu["pay"] = [
                "caption" => "Renew Membership",
                "path" => "/user/pay.php",
                "selected" => false,
            ];
        } else {
            $leftMenu["pay"] = [
                "caption" => "Donate Now",
                "path" => "/user/pay.php",
                "selected" => false,
            ];
        }
    }
    $leftMenu["coupon"] = [
        "caption" => "Upgrade Using a Coupon",
        "path" => "/user/useCoupon.php",
        "selected" => false,
    ];

    if ($_SESSION['AccountLevel'] != ACCOUNT_LEVEL_AWPLUS) {
        $leftMenu["upgrade account"] = [
            "caption" => "Upgrade Account",
            "path" => "/user/pay.php",
            "selected" => false,
        ];
    }

    if ($inBeta) {
        $leftMenu["new interface"] = [
            "caption" => "Switch to the new interface",
            "path" => $newInterfaceRoute,
            "selected" => false,
        ];
    }
}

function getAgentTripsCount($fields)
{
    global $Connection;

    if ($fields["ClientID"] == "") {
        $qFamily = new TQuery("select count(*) as Cnt from
		TravelPlan where UserAgentID = {$fields["UserAgentID"]}
		AND Hidden = 0
		and EndDate >= " . $Connection->DateTimeToSQL(time() - SECONDS_PER_DAY * TRIPS_PAST_DAYS));
        // $qShare->Fields["Cnt"] = intval($qShare->Fields["Cnt"]) + $qFamily->Fields["Cnt"];
        $othersCount = $qFamily->Fields["Cnt"];
    } else {
        $qShare = new TQuery("select /* atc */ count(tpsh.TravelPlanShareID) as Cnt
		from 		TravelPlanShare tpsh
		join TravelPlan tp on tpsh.TravelPlanID = tp.TravelPlanID AND tp.Hidden = 0
		where tpsh.UserAgentID = {$fields['UserAgentID']}
		and EndDate >= " . $Connection->DateTimeToSQL(time() - SECONDS_PER_DAY * TRIPS_PAST_DAYS));
        $othersCount = $qShare->Fields["Cnt"];
    }

    return $othersCount;
}

function AddTravelMenu()
{
    global $sharedTravelMenu, $leftMenu, $Connection, $manageTravelMenu, $topMenu;

    if ((SITE_MODE == SITE_MODE_PERSONAL) || (isset($topMenu['My Trips']['Simple']) && $topMenu['My Trips']['Simple'])) {
        $q = new TQuery("select coalesce( c.FirstName, ua.FirstName ) as FirstName,
		coalesce( c.LastName, ua.LastName ) as LastName, ua.UserAgentID, ua.ClientID, c.Company, c.AccountLevel, ua.AccessLevel
		from UserAgent ua
		left outer join Usr c on ua.ClientID = c.UserID
		where ua.AgentID = {$_SESSION['UserID']} /*and ua.ClientID is not null*/
		and ua.ClientID is null
		and ua.IsApproved = 1 order by FirstName, LastName");

        while (!$q->EOF) {
            if ($q->Fields['AccountLevel'] != ACCOUNT_LEVEL_BUSINESS) {
                $othersCount = getAgentTripsCount($q->Fields);
                $leftMenu["Client_" . $q->Fields['UserAgentID']] = [
                    "caption" => getNameOwnerAccountByUserFields($q->Fields),
                    "path" => "/trips/index.php?UserAgentID={$q->Fields['UserAgentID']}",
                    "selected" => false,
                    "count" => $othersCount,
                    "othersCount" => $othersCount,
                    "userName" => getNameOwnerAccountByUserFields($q->Fields),
                    "UserAgentID" => $q->Fields['UserAgentID'],
                    "ClientID" => $q->Fields['ClientID'],
                ];

                if (!isset($q->Fields['ClientID'])) {
                    $leftMenu["Client_" . $q->Fields['UserAgentID']]["actionPath"] = "/trips/retrieve.php?UserAgentID=" . $q->Fields['UserAgentID'];
                    $leftMenu["Client_" . $q->Fields['UserAgentID']]["actionCaption"] = "add+";
                }
            }
            $q->Next();
        }

        if (SITE_MODE == SITE_MODE_BUSINESS) {
            $leftMenu["Map"] = [
                "caption" => "Show Trips Map",
                "path" => "/trips/map.php",
                "selected" => false,
            ];
        }

        if (!$q->IsEmpty) {
            // move My Connection, Add Connections to bottom of menu
            if (SITE_MODE == SITE_MODE_PERSONAL) {
                foreach (["My Connections"] as $key) {
                    $tmp = $leftMenu[$key];
                    unset($leftMenu[$key]);
                    $leftMenu[$key] = $tmp;
                }
            }
            // move 3 links to manage menu
            $manageTravelMenu = [];

            foreach (["Previous Trip Plans", "Deleted Travel Plans", "Add Travel Plans", "CalendarAutoImport"] as $key) {
                $manageTravelMenu[$key] = $leftMenu[$key];
                unset($leftMenu[$key]);
            }
        }
    }
    AddPendingMenu();
}

function AddPendingMenu()
{
    global $leftMenu;
    $q = new TQuery("select count( UserAgentID ) as Cnt from UserAgent where ( ClientID = {$_SESSION['UserID']} ) and IsApproved = 0");

    if ($q->Fields['Cnt'] > 0) {
        $leftMenu['Pending requests'] = [
            "caption" => "Pending Connections ({$q->Fields['Cnt']})",
            "path" => "/agent/pending.php",
            "selected" => false,
        ];
    }
}

function updateTopMenu()
{
    global $topMenu, $Connection, $leftMenu;

    if (isset($_SESSION['UserID'])) {
        // balances
        if (class_exists('TQuery')) {
            $provFilter = userProviderFilter($_SESSION['UserID']);

            $q = new TQuery("
            SELECT COUNT(*) AS Cnt
			FROM   Account a
			       LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
			WHERE  a.UserID = {$_SESSION['UserID']}
			       AND " . userProviderFilter() . "
			       AND a.State > " . ACCOUNT_DISABLED . "

			UNION ALL

            SELECT COUNT(*) AS Cnt
			FROM   ProviderCoupon
			WHERE  UserID = {$_SESSION['UserID']}

			UNION ALL

			SELECT COUNT(ash.AccountShareID) AS Cnt
			FROM
				AccountShare ash
				LEFT JOIN Account a	ON ash.AccountID = a.AccountID
				LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
				LEFT JOIN UserAgent ua on ash.UserAgentID = ua.UserAgentID
				LEFT OUTER JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID
			WHERE
				ua.AgentID = {$_SESSION['UserID']}
				AND ua.IsApproved = 1
				AND ( au.IsApproved = 1
				OR au.IsApproved IS NULL )
				AND $provFilter

            UNION ALL

            SELECT COUNT(pcsh.ProviderCouponShareID) AS Cnt
			FROM
				ProviderCouponShare pcsh
				LEFT JOIN ProviderCoupon pc ON pcsh.ProviderCouponID = pc.ProviderCouponID
				JOIN UserAgent ua on pcsh.UserAgentID = ua.UserAgentID
				LEFT OUTER JOIN UserAgent au ON au.ClientID = ua.AgentID AND au.AgentID = ua.ClientID
			WHERE
				ua.AgentID = {$_SESSION['UserID']}
				AND ua.IsApproved = 1
				AND ( au.IsApproved = 1	OR au.IsApproved IS NULL )
            ");
            $nCount = 0;

            while (!$q->EOF) {
                $nCount += $q->Fields["Cnt"];
                $q->Next();
            }

            if (isset($topMenu["My Balances"])) {
                $topMenu["My Balances"]["count"] = $nCount;
            }

            // trips
            if (isset($topMenu["My Trips"])) {
                if (SITE_MODE == SITE_MODE_BUSINESS) {
                    $topMenu["My Trips"]["Simple"] = false;
                    $q = new TQuery("select count(*) as Cnt from UserAgent where AgentID = {$_SESSION['UserID']}");

                    if (($q->Fields['Cnt'] <= 20) && (UserTravelPlansCount($_SESSION['UserID']) <= 50)) {
                        $topMenu["My Trips"]["Simple"] = true;
                        $topMenu["My Trips"]["path"] = '/trips/';
                    }
                } else {
                    $topMenu["My Trips"]["count"] = UserTravelPlansCount($_SESSION['UserID']);
                }
            }

            // balances
            if (isset($topMenu["My Balances"])) {
                $q = new TQuery(OtherUsersSQL());

                if (!$q->EOF) {
                    $topMenu["My Balances"]["path"] = "/account/list.php?UserAgentID=All";
                }

                if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_PRODUCTION) {
                    $topMenu["My Balances"]["path"] = 'https://' . $_SERVER['HTTP_HOST'] . $topMenu["My Balances"]["path"];
                }
            }
        }
    }
}

function AjaxError($sMessage)
{
    $arResponse = [];
    $arResponse["Status"] = "Error";
    $arResponse["Message"] = $sMessage;
    header("Content-type: application/json");
    echo json_encode($arResponse);

    exit;
}

// record not registered user info for future ad compaigns
// $arValues = array( "Email" => "john@yahoo.com", "Name" => "John Smith", "Phone" => "4324324", "CityStateZip" => "34344 Lake shore" ), only email required. other parameters are optional
function RecordProspect($arValues)
{
    global $Connection;
    $sEmail = $arValues['Email'];
    $q = new TQuery("select * from Usr where Email = '" . addslashes($sEmail) . "'");

    if (!$q->EOF) {
        return false;
    }
    $q = new TQuery("select * from Prospect where Email = '" . addslashes($sEmail) . "'");
    $arParams = ["LastUseDate" => "now()"];

    if ($q->EOF) {
        $arExisting = [];
        $arParams["Uses"] = "1";
        $arParams["Email"] = "'" . addslashes($sEmail) . "'";
    } else {
        $arExisting = $q->Fields;
        $arParams["Uses"] = "Uses + 1";
    }

    foreach (["Name", "Phone", "Address", "CityStateZip"] as $sField) {
        if (isset($arValues[$sField]) && (ArrayVal($arExisting, $sField) == "")) {
            $arParams[$sField] = "'" . addslashes($arValues[$sField]) . "'";
        }
    }

    if ($q->EOF) {
        $Connection->Execute(InsertSQL("Prospect", $arParams));
    } else {
        $Connection->Execute(UpdateSQL("Prospect", ["ProspectID" => $q->Fields['ProspectID']], $arParams));
    }
}

function RedirectPrimaryPage()
{
    if (!isset($_SESSION['UserID'])) {
        DieTrace("User should be autorized");
    }
    $q = new TQuery("select * from Usr where UserID = {$_SESSION['UserID']}");

    if ($q->Fields["PrimaryFunctionality"] == TRACKING_AWARDS) {
        $qOther = new TQuery(OtherUsersSQL());

        if ($qOther->EOF) {
            Redirect("/account/list.php");
        } else {
            Redirect("/account/list.php?UserAgentID=All");
        }
    } else {
        Redirect("/trips/");
    }
}

function MyTravelPlansCount()
{
    global $Connection;
    $q = new TQuery("select count(tp.TravelPlanID) as Cnt from TravelPlan tp left outer join UserAgent ua on tp.UserAgentID = ua.UserAgentID where tp.Hidden = 0 AND tp.EndDate >= " . $Connection->DateTimeToSQL(time() - SECONDS_PER_DAY * TRIPS_PAST_DAYS) . " and tp.UserID = {$_SESSION['UserID']} and ua.UserAgentID is null");

    return $q->Fields["Cnt"];
}

function UserTravelPlansCount($userId)
{
    global $Connection;
    $nCount = 0;
    $qShare = new TQuery("select /* utc */ count(tpsh.TravelPlanShareID) as Cnt
	from 		TravelPlanShare tpsh
	join TravelPlan tp on tpsh.TravelPlanID = tp.TravelPlanID AND tp.Hidden = 0
	join UserAgent ua on tpsh.UserAgentID = ua.UserAgentID
	where ua.AgentID = {$userId} and ua.ClientID is not null and ua.IsApproved = 1
	and tp.EndDate >= " . $Connection->DateTimeToSQL(time() - SECONDS_PER_DAY * TRIPS_PAST_DAYS));
    $nCount += $qShare->Fields["Cnt"];
    $q = new TQuery("select count(*) as Cnt from TravelPlan where UserID = {$userId}
	AND Hidden = 0
	and EndDate >= " . $Connection->DateTimeToSQL(time() - SECONDS_PER_DAY * TRIPS_PAST_DAYS));

    return $nCount + $q->Fields['Cnt'];
}

function PlansCount($previous = 0, $deleted = 0)
{
    global $Connection;

    $previousSQL = $previous ? " EndDate < " . $Connection->DateTimeToSQL(time() - SECONDS_PER_DAY * TRIPS_PAST_DAYS) . " " : "";
    $deletedSQL = $deleted ? " Hidden = 1 " : " Hidden = 0 ";

    $userAgentID = isset($_GET['UserAgentID']) ? ($_GET['UserAgentID'] == 'My' ? 'My' : intval($_GET['UserAgentID'])) : null;

    if (($userAgentID == 'My') || ($userAgentID == null)) {
        $mySql = "
		SELECT
			COUNT(*) AS Cnt
		FROM
			TravelPlan
		WHERE
			UserID = {$_SESSION['UserID']}
			" . ($userAgentID == 'My' ? "AND UserAgentID IS NULL" : "") .
            (($previousSQL != '') ? " AND " . $previousSQL : '') .
            " AND " . $deletedSQL;
    } else {
        $mySql = "0";
    }

    if ($userAgentID != 'My') {
        $agentSql = "( SELECT COUNT(tpsh.TravelPlanID) AS Cnt
		FROM TravelPlan tp
		JOIN TravelPlanShare tpsh ON tpsh.TravelPlanID = tp.TravelPlanID
		JOIN UserAgent ua ON  tpsh.UserAgentID = ua.UserAgentID
		WHERE ua.AgentID = {$_SESSION['UserID']} AND ua.ClientID is not null
		AND " . $deletedSQL . (($previousSQL != '') ? " AND " . $previousSQL : '') .
        ($userAgentID > 0 ? " AND ua.UserAgentID = $userAgentID" : "") . " )" .
        ($userAgentID > 0 ? "
		+
		( SELECT COUNT(tp.TravelPlanID) AS Cnt
		FROM TravelPlan tp
		WHERE tp.UserID = {$_SESSION['UserID']} and tp.UserAgentID = $userAgentID" .
        " AND " . $deletedSQL .
        (($previousSQL != '') ? " AND " . $previousSQL : '') .
        " )" : "");
    } else {
        $agentSql = "0";
    }
    $q = new TQuery("SELECT ((" . $mySql . ") + (" . $agentSql . ")) Cnt");

    return $q->Fields["Cnt"];
}

function formatFullBalance($nBalance, $sProviderCode, $sBalanceFormat, $bChange = false)
{
    $formatter = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\BalanceFormatter::class);

    return $formatter->formatNumber($nBalance, true, $sBalanceFormat, 'n/a');
}

function number_format_localized($number, $decimals = 0)
{
    $localizer = getSymfonyContainer()->get(\AwardWallet\MainBundle\Globals\Localizer\LocalizeService::class);

    return $localizer->formatNumberWithFraction($number, $decimals);
}

function RecordSentEmail($sKind)
{
    global $Connection;
    $Connection->Execute("insert into EmailStat(StatDate, Kind, Messages)
	values(now(), '" . addslashes($sKind) . "', 1)
	on duplicate key update Messages = Messages + 1");
}

function getProgramMessage($fields, $plainText = false)
{
    global $Connection;
    $sMessage = "";
    $title = null;
    $lastUpdate = null;

    if ($fields['SuccessCheckDate'] != '') {
        $lastUpdate = date(DATE_TIME_FORMAT, $Connection->SQLToDateTime($fields['SuccessCheckDate']));
    }

    switch ($fields['ErrorCode']) {
        case ACCOUNT_CHECKED:
            $sMessage = "Last time account information was successfully retrieved from the {$fields['DisplayName']} web site on: <span style='font-weight:bold;'>" . $fields['UpdateDate'] . "</span>";

            break;

        case ACCOUNT_WARNING:
            if (!empty($fields['ErrorMessage']) && strtolower($fields['ErrorMessage']) != 'unknown error') {
                $title = $fields['ErrorMessage'];
            }
            $sMessage = "Last time your rewards info was retrieved from the {$fields['DisplayName']} web site on: <span style='font-weight:bold;'>" . $fields['UpdateDate'] . "</span>"; /* checked */

            break;

        case ACCOUNT_UNCHECKED:
            $sMessage = "Account information has not been retrieved from the {$fields['DisplayName']} web site yet. You have to click \"update\" in order for us to retrieve the account info.";

            break;

        case ACCOUNT_INVALID_PASSWORD:
            $title = "Invalid logon";

            if (isset($lastUpdate)) {
                $sMessage .= " Last time account information was successfully retrieved on <span align='center' style='font-weight:bold;'>" . $lastUpdate . "</span>";
            }

            break;

        case ACCOUNT_MISSING_PASSWORD:
            $title = "Missing Password";
            $sMessage .= " <div class='redSubHeader'>You opted to save the password for this award program locally, this computer / browser does not have it stored. In order to fix this problem please provide a password by editing this reward program. Going forward you should consider two things: (1) You can store your passwords in the AwardWallet.com database (2) You can backup and restore your locally stored password by using buttons in the left navigation section of the site.</div>";

            if (isset($lastUpdate)) {
                $sMessage .= "<br/>Last time account information was successfully retrieved on <span align='center' style='font-weight:bold;'>" . $lastUpdate . "</span>";
            }

            break;

        case ACCOUNT_LOCKOUT:
            $title = "Your account is locked out";
            $subTitle = ", because you or somebody else, attemted logging in with incorrect password too many times. You should unlock your account using the {$fields['DisplayName']} web site.";

            if (isset($lastUpdate)) {
                $sMessage .= " Last time account information was successfully retrieved on <span align='center' style='font-weight:bold;'>" . $lastUpdate . "</span>";
            }

            break;

        case ACCOUNT_PROVIDER_ERROR:
            $title = "Error occurred";

            if (isset($lastUpdate)) {
                $sMessage .= " Last time account information was successfully retrieved on <span align='center' style='font-weight:bold;'>" . $lastUpdate . "</span>";
            }

            break;

        case ACCOUNT_ENGINE_ERROR:
            $title = "Error occurred";

            if (isset($lastUpdate)) {
                $sMessage .= " Last time account information was successfully retrieved on: <span align='center' style='font-weight:bold;'>" . $lastUpdate . "</span>";
            }

            break;

        case ACCOUNT_PREVENT_LOCKOUT:
            $title = "Username or password is incorrect";
            $sMessage = "To prevent your account from being locked out by the provider <span class='orangeBold'>please change the password or the user name</span> you entered on AwardWallet.com as these credentials appear to be invalid.";

            if (isset($lastUpdate)) {
                $sMessage .= " Last time account information was successfully retrieved on: <span align='center' style='font-weight:bold;'>" . $lastUpdate . "</span>";
            }

            break;

        case ACCOUNT_QUESTION:
            $sMessage = "It looks like you are being prompted to <span class='orangeBold'>answer a security question</span> on the website which holds your account balance.  Please click the \"Update\" button to answer this question."; /* checked */

            break;
    }

    if (($fields['ErrorMessage'] != "") && ($fields['ErrorMessage'] != "Unknown engine error") && ($fields['ErrorCode'] != ACCOUNT_PREVENT_LOCKOUT)) {
        if (in_array($fields['ErrorCode'], [ACCOUNT_ENGINE_ERROR, ACCOUNT_PROVIDER_ERROR, ACCOUNT_INVALID_PASSWORD])) {
            $subTitle = ". " . $fields['DisplayName'] . " returned the following error: ";
            $sMessage .= "<div class='boxToll boxRed'><div class='left'></div><div class='right'></div><div class='center pad'>" . $fields['ErrorMessage'] . "</div><div class='bottom'><div class='left'></div><div class='right'></div></div></div>";
        }
    } elseif ($fields['ErrorCode'] == ACCOUNT_ENGINE_ERROR or $fields['ErrorCode'] == ACCOUNT_PROVIDER_ERROR) {
        if ($sMessage != "") {
            $sMessage .= "<br>";
        }
        $sMessage .= $fields['DisplayName'] . " returned error. Try logging into the {$fields['DisplayName']} website manually to check your balance. If it works please <a href=\"/contact\" target=_blank>send a note</a> to our web developer so that we can fix this problem on our end, otherwise please wait until the site becomes available again.";
    } elseif ($fields['ErrorCode'] == ACCOUNT_INVALID_PASSWORD) {
        if ($sMessage != "") {
            $sMessage .= "<br>";
        }
        $sMessage .= $fields['Site'] . " returned error. Invalid user name or password. Edit your account to change password.";
    }

    if (isset($title) && isset($subTitle)) {
        $title .= "<span class='subTitle'>" . $subTitle . "</span>";
    }

    if (isset($title)) {
        $sMessage = "<div class='redSubHeader'>{$title}</div>" . $sMessage;
    }

    if (in_array($fields['ErrorCode'], [ACCOUNT_ENGINE_ERROR, ACCOUNT_INVALID_PASSWORD, ACCOUNT_PROVIDER_ERROR, ACCOUNT_PREVENT_LOCKOUT]) && !$plainText) {
        if (!isset($fields['FAQ']) || ($fields['FAQ'] == '')) {
            $fields['FAQ'] = 9;
        }
        $sMessage .= "<div class='errorNote'>Please make sure you can: (1) successfully <a href=\"{$fields['Site']}\" target=\"_blank\">login to {$fields['DisplayName']} website</a> and (2) find the page where all of your balance information is listed.";
        $sMessage .= " Also please refer to the following <a href=\"/faqs.php#{$fields['FAQ']}\">FAQ article</a></div>";
    }

    if ($plainText) {
        $sMessage = strip_tags($sMessage);
        $sMessage = trim(str_ireplace("Error occurred.", "", $sMessage));
    }

    return $sMessage;
}

// return user date format
// variable below used for cache
$UserSettings = null;
function UserSettings($userId, $param)
{
    global $UserSettings;

    if (!isset($UserSettings) || !isset($UserSettings['UserID']) || ($UserSettings['UserID'] != $userId)) {
        $q = new TQuery("select UserID, DateFormat, ThousandsSeparator from Usr where UserID = $userId");
        $UserSettings = $q->Fields;
        $UserSettings = array_merge($UserSettings, DateFormats($UserSettings['DateFormat']));
    }

    return $UserSettings[$param];
}

// return date formats by mode
function DateFormats($dateFormat)
{
    switch ($dateFormat) {
        case DATEFORMAT_US:
            return [
                'datetime' => "F d, Y H:i:s",
                'date' => "m/d/Y",
                'dateshort' => "m/d/y",
                'time' => "h:ia",
                'datelong' => "F j, Y",
                'monthday' => "F j",
                'timelong' => "g:i A",
                'datetimelong' => "F j, Y g:i A",
                'datepicker' => "mm/dd/yy",
            ];

        case DATEFORMAT_EU:
            return [
                'datetime' => "d F, Y H:i:s",
                'date' => "d/m/Y",
                'dateshort' => "d/m/y",
                'time' => "H:i",
                'datelong' => "j F, Y",
                'monthday' => "j F",
                'timelong' => "G:i",
                'datetimelong' => "j F, Y G:i",
                'datepicker' => "dd/mm/yy",
            ];

        default:
            DieTrace("Unknown date format: $dateFormat");
    }
}

function aaPasswordValid($fields)
{
    $providerCode = ArrayVal($fields, 'ProviderCode', ArrayVal($fields, 'Code'));

    return
        $providerCode == 'aa'
        && (
            $fields['ErrorCode'] == ACCOUNT_CHECKED
            && !empty($fields['SuccessCheckDate'])
            && !empty($fields['PassChangeDate'])
            && $fields['SuccessCheckDate'] > $fields['PassChangeDate']
        )
        && $fields['UpdateDate'] >= '2014-04-16';
}

function GetAccountExpiration($nUserID, &$dDate, &$nLastPrice)
{
    global $Connection;
    $q = new TQuery("select c.CartID, c.PayDate, ci.TypeID
	from Cart c
	join CartItem ci on c.CartID = ci.CartID
	where c.UserID = {$nUserID} and c.PayDate is not null
	and (
	  ci.TypeID in (" . CART_ITEM_AWPLUS . ", " . CART_ITEM_AWPLUS_20 . ", " . CART_ITEM_AWPLUS_1 . ", " . CART_ITEM_AWPLUS_TRIAL . ")
	  or (
	    (
	        ci.TypeID = " . \AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription::TYPE . " 
	        or ci.TypeID = " . \AwardWallet\MainBundle\Entity\CartItem\AwPlusWeekSubscription::TYPE . " 
	    )
	    and ci.Price > 0
      )
	) 
	order by c.PayDate");
    $dDate = null;
    $nLastPrice = null;

    while (!$q->EOF) {
        EchoDebug("Payment", "Payment: {$q->Fields["PayDate"]}");
        $d = $Connection->SQLToDateTime($q->Fields["PayDate"]);

        switch ($q->Fields["TypeID"]) {
            case CART_ITEM_AWPLUS_20:
                $dateRange = "+20 year";

                break;

            case CART_ITEM_AWPLUS_1:
            case \AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription::TYPE:
                $dateRange = "+1 year";

                break;

            case \AwardWallet\MainBundle\Entity\CartItem\AwPlusWeekSubscription::TYPE:
                $dateRange = "+1 week";

                break;

            case CART_ITEM_AWPLUS:
                $dateRange = "+6 month";

                break;

            case CART_ITEM_AWPLUS_TRIAL:
                $dateRange = "+3 month";

                break;

            default:
                DieTrace("unknown cart item: " . $q->Fields["TypeID"]);
        }

        if (!isset($dDate)) {
            $dDate = strtotime($dateRange, $d);
        } elseif ($d < $dDate) {
            $dDate = strtotime($dateRange, $dDate);
        } else {
            $dDate = strtotime($dateRange, $d);
        }
        EchoDebug("Payment", "Expire date: " . date(DATE_FORMAT, $dDate));
        $qPrice = new TQuery("select sum(Price - Price * Discount / 100) as Price from CartItem where CartID = {$q->Fields['CartID']}");
        $nLastPrice = $qPrice->Fields["Price"];
        $q->Next();
    }

    if (!isset($dDate)) {
        $dDate = time();
        $nLastPrice = null;
        // DieTrace("No payments found for aw plus account: $nUserID");
    }
}

function StickToMainDomain()
{
    global $Interface, $HTTP_SESSION_START;

    if (stripos($_SERVER['HTTP_HOST'], 'www.') === 0) {
        if (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        $url = $protocol . substr($_SERVER['HTTP_HOST'], 4) . $_SERVER['REQUEST_URI'];
        header("Location: " . $url, true, 301);

        exit;
    }

    if (ConfigValue(CONFIG_HTTPS_ONLY) && empty($HTTP_SESSION_START)) {
        $Interface->forceHTTPS(true);
    }
}

function DrawAddressBookOptions($arOptions, $sSelected)
{
    foreach ($arOptions as $sKey => $sValue) {
        echo "<option value=\"{$sKey}\"";

        if (strval($sKey) == strval($sSelected)) {
            echo " selected";
        }
        echo " class=\"addressbookInvtOption\">{$sValue}</option>\n";
    }
}

/**
 * loads into $_SESSION variables FreeCoupons (count of free coupons) and FreeCouponCode
 * generates coupons, when applicable (aw plus payer).
 *
 * @return void
 */
function loadFreeCoupons()
{
    global $Connection;
    $q = new TQuery("select * from Coupon where Code like 'free-%' and UserID = {$_SESSION['UserID']}");

    if ($q->EOF) {
        $qPays = new TQuery("select 1 from Cart where PayDate is not null and UserID = {$_SESSION['UserID']} limit 1");

        if (!$qPays->EOF) {
            do {
                $code = "free-" . RandomStr(ord('a'), ord('z'), 6);
                $qCode = new TQuery("select 1 from Coupon where Code = '{$code}' limit 1");
            } while (!$qCode->EOF);
            $Connection->Execute("insert into Coupon(Name, Code, Discount, FirstTimeOnly, MaxUses, UserID)
			values('Free upgrade from " . addslashes($_SESSION['FirstName']) . ' ' . addslashes($_SESSION['LastName']) . "',
			'{$code}', 100, 1, 10, {$_SESSION['UserID']})");
            $count = 10;
        } else {
            $code = null;
            $count = 0;
        }
    } else {
        $code = $q->Fields['Code'];
        $qUses = new TQuery("select count(*) as Cnt from Cart where PayDate is not null and CouponID = {$q->Fields['CouponID']}");
        $count = max($q->Fields['MaxUses'] - $qUses->Fields['Cnt'], 0);
    }
    $_SESSION['FreeCoupons'] = $count;
    $_SESSION['FreeCouponCode'] = $code;
}

/**
 * check that user is not impersonated or check whether impersonated user has unexpired access to credentials.
 * dies or return null or error message.
 *
 * @param int $accountID
 */
function requirePasswordAccess($return = false, $accountID = null)
{
    $result = null;

    if (isGranted('ROLE_IMPERSONATED')) {
        $impersonator = \AwardWallet\MainBundle\Security\Utils::getImpersonator(getSymfonyContainer()->get("security.token_storage")->getToken());
        $result = "You are impersonated as <b><big>{$_SESSION['Login']}</big></b>. You can't use this feature. <a href='/security/logout?BackTo=" . urlencode($_SERVER['REQUEST_URI']) . "'>Logout</a>";

        if (isset($accountID)) {
            $accountID = intval($accountID);
            $q = new TQuery("SELECT *
							FROM PasswordVault pv
							JOIN PasswordVaultUser pvu on pvu.PasswordVaultID = pv.PasswordVaultID
							JOIN Usr u on u.UserID = pvu.UserID
							WHERE
								u.Login = '{$impersonator}' AND
								pv.ExpirationDate > NOW() AND
								pv.AccountID = {$accountID} AND
								pv.Approved = 1");

            if (!$q->EOF) {
                $result = null;
            }
        }
    }

    if (isset($result) && !$return) {
        if (ArrayVal($_SERVER, 'HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest' || ArrayVal($_SERVER, 'HTTP_CONTENT_TYPE') == 'application/json') {
            echo json_encode(["message" => $result, "title" => "Impersonated"]);
            header("Content-Type: application/json");
        } else {
            echo $result;
        }
        header('Impersonated: true', true, 403);

        exit;
    }

    return $result;
}

function CouponState($arFields)
{
    global $Connection;

    if (is_null($arFields['ExpirationDate'])) {
        return COUPON_NO_EXPIRATION;
    } elseif ($Connection->SQLToDateTime($arFields["ExpirationDate"]) <= time()) {
        return COUPON_EXPIRED;
    } elseif ($Connection->SQLToDateTime($arFields["ExpirationDate"]) <= time() + 60 * 60 * 24 * 30 * 3) {
        return COUPON_EXPIRES_SOON;
    } else {
        return COUPON_VALID;
    }
}
function CouponStateText($arFields)
{
    global $Connection;

    if ($Connection->SQLToDateTime($arFields["ExpirationDate"]) <= time()) {
        return "Coupon expired";
    } elseif ($Connection->SQLToDateTime($arFields["ExpirationDate"]) <= time() + 60 * 60 * 24 * 30 * 3) {
        return "Coupon expires soon";
    } else {
        return "Coupon valid";
    }
}
function CouponIcon($CouponCode)
{
    if ($CouponCode == COUPON_EXPIRED) {
        return "<div class=\"redBar\"></div>";
    }

    //	elseif($CouponCode==COUPON_EXPIRES_SOON)
    //		return "<img src='/images/infoState.gif' border='0' title='Expires in less than three months'>";
    //	elseif($CouponCode==COUPON_VALID)
    return "";
}

function getCouponTitle($errCode)
{
    switch ($errCode) {
        case COUPON_VALID:
            return "Valid";

        case COUPON_EXPIRES_SOON:
            return "Expires soon";

        case COUPON_EXPIRED:
            return "Expired";

        case COUPON_NO_EXPIRATION:
            return "No Expiration"; /* check */
    }
}

function getCouponIcon($errCode)
{
    switch ($errCode) {
        case COUPON_VALID:
            return "<img src='/images/success_big.gif' border='0' alt='The coupon is valid'>";

        case COUPON_EXPIRES_SOON:
            return "<img src='/images/info_big.gif' border='0' alt='The coupon expries soon'>";

        case COUPON_EXPIRED:
            return "<img src='/images/error_big.gif' border='0' alt='The coupon has expired'>";
    }
}

function getCouponMessage($errCode)
{
    switch ($errCode) {
        case COUPON_VALID:
            return "The coupon is valid.";

        case COUPON_EXPIRES_SOON:
            return "The coupon expries in less than three months";

        case COUPON_EXPIRED:
            return "The coupon has expired";

        case COUPON_NO_EXPIRATION:
            return "The coupon has no expiration"; /* check */
    }
}

function getProgramTitle($arFields)
{
    if ($arFields["State"] == ACCOUNT_DISABLED) {
        return "Disabled";
    } else {
        switch ($arFields["ErrorCode"]) {
            case ACCOUNT_UNCHECKED:
                return "Unchecked";

            case ACCOUNT_CHECKED:
                return "Success";

            case ACCOUNT_WARNING:
                return "Warning";

            case ACCOUNT_INVALID_PASSWORD:
                return "Invalid Logon";

            case ACCOUNT_MISSING_PASSWORD:
                return "Missing Password";

            case ACCOUNT_LOCKOUT:
                return "Account Locked Out";

            case ACCOUNT_PROVIDER_ERROR:
                return "Provider error";

            case ACCOUNT_ENGINE_ERROR:
                return "Internal error";
        }
    }
}

function getProgramIcon($arFields)
{
    if ($arFields["State"] == ACCOUNT_DISABLED) {
        return "<img src='/images/error_big.gif' border='0' alt='Disabled'>";
    } else {
        switch ($arFields["ErrorCode"]) {
            case ACCOUNT_UNCHECKED:
                return "<img src='/images/warning_big.gif' border='0' alt='Unchecked'>";

            case ACCOUNT_WARNING:
                return "<img src='/images/warning_big.gif' border='0' alt='Warning'>";

            case ACCOUNT_CHECKED:
                return "<img src='/images/success_big.gif' border='0' alt='OK'>";

            case ACCOUNT_INVALID_PASSWORD:
                return "<img src='/images/error_big.gif' border='0' alt='Invalid Logon'>";

            case ACCOUNT_MISSING_PASSWORD:
                return "<img src='/images/error_big.gif' border='0' alt='Missing Password'>";

            case ACCOUNT_LOCKOUT:
                return "<img src='/images/error_big.gif' border='0' alt='Account Locked Out'>";

            case ACCOUNT_PROVIDER_ERROR:
                return "<img src='/images/error_big.gif' border='0' alt='Provider error'>";

            case ACCOUNT_ENGINE_ERROR:
                return "<img src='/images/error_big.gif' border='0' alt='Internal error'>";

            case ACCOUNT_PREVENT_LOCKOUT:
                return "<img src='/images/error_big.gif' border='0' title='To prevent your account from being locked out, please change your password to the correct one'>"; /* checked */

            case ACCOUNT_QUESTION:
                return "<img src='/images/error_big.gif' border='0' title='It looks like you are being prompted to answer a security question on the website which holds your account balance.  Please click the Update button to answer this question.'>"; /* checked */
        }
    }
}

function getNameOwnerAccount($userID)
{
    $result = [];

    if (is_array($userID) && !sizeof($userID)) {
        return $result;
    }
    $row = SQLToArray("SELECT UserID, FirstName, LastName, Company, AccountLevel
					   FROM Usr
					   WHERE UserID IN (" . ((is_array($userID)) ? implode(',', $userID) : $userID) . ")", "FirstName", "Company", true);

    if (!sizeof($row)) {
        return $result;
    }

    foreach ($row as $k => $v) {
        if ($v['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS) {
            $result[$v['UserID']] = $v['Company'];
        } else {
            $result[$v['UserID']] = $v['FirstName'] . " " . $v['LastName'];
        }
    }

    return $result;
}

/**
 * return the correct name of User or Business Account by UserFields array.
 */
function getNameOwnerAccountByUserFields($userFields, $onlyFirstName = false)
{
    $userName = $userFields['FirstName'] . (($onlyFirstName && isset($userFields['LastName'])) ? '' : " " . $userFields['LastName']);

    if (isset($userFields['AccountLevel']) && $userFields['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS && isset($userFields['Company'])) {
        $userName = $userFields['Company'];
    }

    return $userName;
}

function isBusinessAccountAdmin($UserID, &$BusinessID = null)
{
    $sql = "
        SELECT
        	UserAgentID, ClientID
        FROM
        	UserAgent
        WHERE
        	AgentID = {$UserID}
        	AND AccessLevel in (" . ACCESS_ADMIN . ", " . ACCESS_BOOKING_MANAGER . ", " . ACCESS_BOOKING_VIEW_ONLY . ")
        	AND IsApproved = 1
    ";
    $q = new TQuery($sql);

    if (!$q->EOF) {
        $BusinessID = $q->Fields['ClientID'];

        return true;
    }

    return false;
}

/**
 * replace something looking like credit card number with xxxx xxxx xxxx 1234.
 *
 * @return bool - was replaced
 */
function hideCCNumber(&$value)
{
    $result = false;

    if (preg_match("/^\d{12}(\d{4})$/ims", $value, $match)) {
        $value = "xxxx xxxx xxxx " . $match[1];
        $result = true;
    }

    return $result;
}

function hideNumber($number, $showLast = 4)
{
    if (preg_match(EMAIL_REGEXP, $number)) {
        return $number;
    }

    if (preg_match("/(xxxx\s|\*)/ims", $number)) {
        return $number;
    }
    /*if (!preg_match("/^\d+$/", $number))
        return $number;*/

    $strlen = strlen($number);

    if ($strlen <= $showLast) {
        return $number;
    }
    $str = '';

    for ($i = 0; $i < $strlen; $i++) {
        if ($i < $strlen - $showLast) {
            $str .= '*';
        } else {
            $str .= $number[$i];
        }
    }

    return $str;
}

/**
 * Hide credit cards number and account id in credit type provider
 * Copied and modified from TRecentlyList.php.
 *
 * @return array
 */
function hideCreditCards(array $arFields, $kindProvider)
{
    foreach ($arFields as $key => $value) {
        if (is_array($value) || is_object($value) || preg_match('/.*ID$/', $key) || preg_match('/.*Balance$/', $key) || $kindProvider != PROVIDER_KIND_CREDITCARD) {
            continue;
        }

        if (!hideCCNumber($arFields[$key]) && (in_array($key, ['Account', 'Login']) && is_numeric($value) && preg_match("/(\d{1,4})$/ims", $value, $match))) {
            $value = preg_replace("/" . $match[1] . "$/ims", "", $value);
            $value = preg_replace("/./ims", "x", $value);
            $arFields[$key] = $value . $match[1];
        }
    }

    if (isset($arFields['Login']) && strpos($arFields['Login'], 'fake.') === 0) {
        $arFields['Login'] = 'n/a';
    }

    return $arFields;
}

/**
 * return Count of Administrators for Business Account.
 *
 * @return bool
 */
function countBusinessAccountAdmin($businessId)
{
    return sizeof(getBusinessAccountAdminIds($businessId));
}

/**
 * Return array of Administrators for Business Account.
 */
function getBusinessAccountAdminIds($businessId)
{
    $sql = "SELECT
                AgentID
            FROM
                UserAgent
            WHERE
                ClientID = {$businessId}
                AND AccessLevel = " . ACCESS_ADMIN . "
                AND IsApproved = 1";
    $adm = new TQuery($sql);
    $admins = [];

    while (!$adm->EOF) {
        $admins[] = $adm->Fields['AgentID'];
        $adm->Next();
    }

    return $admins;
}

function getBusinessAdminsDataByBusinessEmail($email)
{
    $sql = "
		SELECT 
			u.Email,
			concat( u.FirstName, ' ', u.LastName ) UserName,
			u.UserID
		FROM   Usr u
		       JOIN UserAgent ua
		       ON     ua.AgentID         = u.UserID
		              AND ua.AccessLevel = " . ACCESS_ADMIN . "
		              AND ua.IsApproved  = 1
		       JOIN Usr u2
		       ON     u2.UserID           = ua.ClientID
		              AND u2.AccountLevel = " . ACCOUNT_LEVEL_BUSINESS . "
		WHERE  u2.Email                   = '" . addslashes($email) . "'
	";
    $q = new TQuery($sql);
    $result = [];

    while (!$q->EOF) {
        $result[] = $q->Fields;
        $q->Next();
    }

    return $result;
}

function getBusinessAdminsEmailsByBusinessEmail($email)
{
    $fields = getBusinessAdminsDataByBusinessEmail($email);

    if (empty($fields)) {
        return $email;
    }
    $emails = [];

    foreach ($fields as $data) {
        $emails[] = $data['Email'];
    }
    $emails = array_unique($emails);

    return $emails;
}

/**
 * @param $UID - UserID or UserAgentID (if Family user)
 * @param $familyUser - default = false, true = if famyly user
 * @return float - total balance for User or Family User
 * */
function getTotalBalance($UID, $familyUser = false)
{
    $sql = "
		SELECT
			SUM(a.TotalBalance) AS Total
		FROM Account a
		LEFT JOIN Provider p USING(ProviderID)
		WHERE
			a.UserID = $UID
			AND a.UserAgentID IS NULL
			AND p.State >= " . PROVIDER_ENABLED . "
	";

    if ($familyUser) {
        $sql = "
			SELECT
				SUM(a.TotalBalance) AS Total
			FROM Account a
			LEFT JOIN Provider p USING(ProviderID)
			WHERE
				a.UserAgentID = $UID
				AND p.State >= " . PROVIDER_ENABLED . "
		";
    }
    $q = new TQuery($sql);

    if ($q->EOF) {
        return 0;
    }

    return $q->Fields['Total'];
}

/**
 * @return float - total balance about all Programs (share programs too) for User
 * */
function getGlobalTotalBalance()
{
    $sql = "SELECT 
				SUM(a.TotalBalance) AS TotalBalance 
			FROM 
				Account a 
			LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
			WHERE 
				UserID = {$_SESSION['UserID']}
				AND " . userProviderFilter() . " AND a.State > " . ACCOUNT_DISABLED . "
			UNION 
			SELECT 
				SUM(a.TotalBalance) AS TotalBalance 
			FROM 
				AccountShare ash 
			LEFT JOIN Account a ON ash.AccountID = a.AccountID 
			LEFT JOIN Provider p ON a.ProviderID = p.ProviderID 
			WHERE ash.UserAgentID IN 
				(SELECT 
					UserAgentID 
				FROM ((" . OtherUsersSQL() . "))ua) 
			AND (p.State >= 1 OR p.State IS NULL) ";
    $totals = 0;
    $q = new TQuery($sql);

    if (!$q->EOF) {
        while (!$q->EOF) {
            $totals += intval($q->Fields['TotalBalance']);
            $q->Next();
        }
    }

    return $totals;
}

/**
 * @deprecated Use \AwardWallet\MainBundle\Entity\Repositories\ElitelevelRepository::getEliteLevelFields
 * @param $status - EliteStatus from ProviderProperty Table (Kind = 3)
 * @return array() - fields from EliteLevel table
 * */
function getEliteLevelFields($ProviderID, $status = null)
{
    global $eliteLevelFieldsCache;

    // cache elite levels across all users for 1 minute
    if (isset($eliteLevelFieldsCache)) {
        $levels = $eliteLevelFieldsCache;
    } else {
        $cache = Cache::getInstance()->get('TextEliteLevel');

        if ($cache !== false && (time() - $cache['time']) < 60) {
            $levels = $cache['data'];
        } else {
            $sql = "
				SELECT
					el.*, tel.ValueText, ael.Name as AllianceName
				FROM
					EliteLevel el
					JOIN TextEliteLevel tel on el.EliteLevelID = tel.EliteLevelID
					LEFT OUTER JOIN AllianceEliteLevel ael
					ON el.AllianceEliteLevelID = ael.AllianceEliteLevelID
				ORDER BY el.Rank DESC
			";
            $q = new TQuery($sql);
            $levels = [];

            while (!$q->EOF) {
                if (!isset($levels[$q->Fields['ProviderID']])) {
                    $levels[$q->Fields['ProviderID']] = [];
                }
                $levels[$q->Fields['ProviderID']][$q->Fields['ValueText']] = $q->Fields;
                $q->Next();
            }
            Cache::getInstance()->set('TextEliteLevel', ['time' => time(), 'data' => $levels], 60);
        }
        $eliteLevelFieldsCache = $levels;
    }

    if (isset($status)) {
        if (isset($levels[$ProviderID]) && is_array($levels[$ProviderID])) {
            foreach ($levels[$ProviderID] as $key => $value) {
                if (strcasecmp($key, $status) == 0) {
                    return $value;
                }
            }
        }

        return null;
    } else {
        $result = [];

        foreach (ArrayVal($levels, $ProviderID, []) as $row) {
            $result[] = array_intersect_key(
                $row,
                ['Rank' => null, 'ValueText' => null, 'Name' => null, 'AllianceName' => null]
            );
        }

        return $result;
    }
}

/**
 * @param $Rank - EliteLevel property
 * @param $EliteLevelsCount - count of EliteLevels
 * @return float - Elitism in format from 0 to 1
 * */
function getElitism($Rank, $EliteLevelsCount)
{
    return $EliteLevelsCount == 0 ? 0 : round($Rank / $EliteLevelsCount, 2);
}

function getPagingParams(&$start, &$end, $onPage, $pageNum, $total)
{
    $prePages = 2;
    $pageCount = ceil($total / $onPage);
    $start = $pageNum * $onPage;
    $end = $start + $onPage - 1;

    if ($pageCount == 1) {
        return false;
    }

    if ($start < 0) {
        $start = 0;
    }

    if ($end > $total - 1) {
        $end = $total - 1;
    }

    $startPage = ($pageNum - $prePages < 0) ? 0 : $pageNum - $prePages;
    $endPage = ($pageNum + $prePages > $pageCount - 1) ? $pageCount - 1 : $pageNum + $prePages;

    $link = $_SERVER['SCRIPT_NAME'];
    $getParams = "";

    if (count($_GET) > 0) {
        foreach ($_GET as $k => $v) {
            if ($k != 'Page') {
                $getParams .= "&" . $k . "=" . $v;
            }
        }
    }

    $paging = "<div class='caption'>";

    if ($startPage > 0) {
        $paging .= " | <a href='" . $link . "?Page=0$getParams'>1</a>";
    }

    if ($startPage > 1) {
        $paging .= " | <span> ... </span>";
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($pageNum == $i) {
            $paging .= " | <b>" . ($i + 1) . "</b>";
        } else {
            $paging .= " | <a href='" . $link . "?Page=$i" . $getParams . "'>" . ($i + 1) . "</a>";
        }
    }

    if ($endPage < $pageCount - 2) {
        $paging .= " | <span> ... </span>";
    }

    if ($endPage < $pageCount - 1) {
        $paging .= " | <a href='" . $link . "?Page=" . ($pageCount - 1) . $getParams . "'>" . $pageCount . "</a>";
    }
    $paging .= "</div>";

    return $paging;
}

/**
 * @param int $travelPlanID
 * @param int $businessId UserID
 * @return bool
 */
function isTravelPlanSharedWithBusiness($travelPlanID, $businessId, $maxAccessLevelCheck = false)
{
    if (is_null($travelPlanID)) {
        return false;
    }
    $sql = "
		SELECT 1 AS Result
		FROM TravelPlanShare tps
			JOIN UserAgent ua ON ua.UserAgentID = tps.UserAgentID
			JOIN Usr u ON u.UserID = ua.AgentID
		WHERE
			TravelPlanID = " . $travelPlanID . "
			AND ua.AgentID = " . $businessId . "
			AND ua.IsApproved = 1
			" . (($maxAccessLevelCheck) ? "AND ua.AccessLevel IN (" . ACCESS_WRITE . ", " . ACCESS_ADMIN . ", " . ACCESS_BOOKING_MANAGER . ", " . ACCESS_BOOKING_VIEW_ONLY . ")" : "") . "
	";
    $q = new TQuery($sql);

    return (!$q->EOF) ? true : false;
}

function isAllowManageSharedTravelPlan($travelPlanID)
{
    return isset($_SESSION["UserID"]) && isTravelPlanSharedWithBusiness($travelPlanID, $_SESSION["UserID"], SITE_MODE == SITE_MODE_PERSONAL);
}

function isAllowAutologinSharedTravelPlan($travelPlanID)
{
    return (isset($_SESSION["UserID"]) && SITE_MODE == SITE_MODE_BUSINESS) && isTravelPlanSharedWithBusiness($travelPlanID, $_SESSION["UserID"], true);
}

function isMyTravelPlan($travelPlanID, $userId)
{
    $sql = "
		SELECT 1 AS Result
		FROM TravelPlan
		WHERE
	 		TravelPlanID = " . $travelPlanID . " AND
	 		UserID = " . $userId . "
	";
    $q = new TQuery($sql);

    return (!$q->EOF) ? true : false;
}

/**
 * return total number of OneCards for User.
 *
 * @param int userID
 * @return array(
 * 	Totals => int (default = 0),
 *  Used => int (default = 0),
 * 	Left => int (default = 0),
 * )
 * */
function getOneCardsCount($userID)
{
    $res = [
        "Total" => 0,
        "Used" => 0,
        "Left" => 0,
    ];

    if (!class_exists('TQuery')) {
        // forum
        if (isset($_SESSION['OneCardCache'])) {
            $res = $_SESSION['OneCardCache'];
        }
    } else {
        $sql = "
			 SELECT
				SUM(IF(ci.Cnt > 0,ci.Cnt,0)) Total
			FROM
				Usr u
			JOIN Cart c ON u.UserID = c.UserID AND c.PayDate IS NOT NULL
			LEFT JOIN CartItem ci ON ci.CartID = c.CartID AND ci.TypeID = " . CART_ITEM_ONE_CARD . "
			WHERE
				u.UserID = $userID
		";
        $q = new TQuery($sql);

        if (!$q->EOF) {
            $res['Total'] = $q->Fields['Total'];
        }

        $sql = "
			SELECT
				COUNT(*) Used
			FROM
				(
				select
					CartID, UserAgentID, count(OneCardID)
				from
					OneCard
				where
					UserID = $userID
					and State <> " . ONECARD_STATE_REFUNDED . "
				group by
					CartID, UserAgentID
				) cards
		";
        $q = new TQuery($sql);

        if (!$q->EOF) {
            $res['Used'] = $q->Fields['Used'];
        }

        $res['Left'] = $res['Total'] - $res['Used'];
        $_SESSION['OneCardCache'] = $res;
    }

    return $res;
}

function getUserName($userIDorFields)
{
    if (is_array($userIDorFields)) {
        if ($userIDorFields['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS) {
            return $userIDorFields['Company'];
        } else {
            return $userIDorFields['FirstName'] . ' ' . $userIDorFields['LastName'];
        }
    } else {
        $sql = "
			SELECT 
				IF(AccountLevel = '" . ACCOUNT_LEVEL_BUSINESS . "', Company, CONCAT_WS(' ', FirstName, LastName)) as Name
			FROM
				Usr
			WHERE
				UserID = $userIDorFields
		";
        $q = new TQuery($sql);

        if ($q->EOF) {
            return null;
        }

        return $q->Fields['Name'];
    }
}

function getUserAgent($userAgentID)
{
    $sql = "
	    SELECT
	        *,
	        COALESCE(NULLIF(TRIM(CONCAT(FirstName, ' ', LastName)), ''), Alias) as FullName
	    FROM UserAgent
	    WHERE UserAgentID = {$userAgentID}";
    $q = new TQuery($sql);

    if ($q->EOF) {
        return null;
    } else {
        return $q->Fields;
    }
}

function getBrowserKey()
{
    global $Connection;

    if (!isset($_SESSION['UserID'])) {
        return "";
    }

    if (!empty($_SESSION['UserFields']['BrowserKey'])) {
        return $_SESSION['UserFields']['BrowserKey'];
    }

    if (!class_exists('TQuery')) {
        return '';
    }
    $q = new TQuery("SELECT BrowserKey FROM Usr WHERE UserID = " . $_SESSION['UserID']);

    if (empty($q->Fields['BrowserKey'])) {
        $key = RandomStr(ord('a'), ord('z'), 64);
        $Connection->Execute(UpdateSQL('Usr', ['UserID' => $_SESSION['UserID']], ["BrowserKey" => "'" . $key . "'"]));
        $_SESSION['UserFields']['BrowserKey'] = $key;
    } else {
        $key = $q->Fields['BrowserKey'];
    }

    return $key;
}

function userProviderFilter($userId = null, $field = "p.State", ?string $includeFilter = null)
{
    $filter = "$field > 0 or $field is null";  // is null - for custom programs

    if (!isset($userId) && isset($_SESSION['UserID'])) {
        $userId = $_SESSION['UserID'];
    }

    if (isset($userId) && getSymfonyContainer()->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId)->getBetaapproved()) {
        $filter .= " or $field = " . PROVIDER_IN_BETA;
    }

    if ((isset($userId) && CheckUserGroupByUser($userId, 'Staff')) || ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG) {
        $filter .= " or $field = " . PROVIDER_TEST;
    }

    if ($includeFilter) {
        return sprintf('((%s) OR %s)', $filter, $includeFilter);
    }

    return "($filter)";
}

/**
 * converts php date format to javascript, suitable for formatDate js function
 * http://www.mattkruse.com/javascript/date/source.html.
 */
function javascriptDateFormat($format)
{
    $format = str_replace("Y", "yyyy", $format);
    $format = str_replace("m", "M", $format);

    return $format;
}

/**
 * return support Phone for current account.
 */
function getGeneralPhoneByAccountID($accountID)
{
    require_once "../onecard/common.php";

    require_once "TAccountInfo.php";

    if (empty($accountID)) {
        return null;
    }
    $q = new TQuery(
        "
		SELECT 
			a.Login2,
			a.AccountID as ID,
			a.ProviderID,
			p.Code as ProviderCode
		FROM 
			Account a, 
			Provider p
		WHERE 
			p.ProviderID = a.ProviderID
			AND a.AccountID = " . $accountID
    );
    $level = showAccountLevel($accountID);

    return TAccountInfo::getSupportPhone($q->Fields, $level, []);
}
/**
 * return time zone offset by UserID or null.
 */
function getTimeZoneOffset($userID = null)
{
    return null;  // :WARNING: refs #15606 - remove user timezone
}

function addBookingMenu(&$bookingCount)
{
    global $leftMenu;
    $leftMenu = array_merge($leftMenu, getSymfonyContainer()->get(StandartViewCreator::class)->addBookingMenu(isGranted('USER_BOOKING_REFERRAL'), true));
    /*$isBookerGroup = HaveUserGroup('Mileage Bookers');
    if ($isBookerGroup) {
        $bookingCountForBooker = getRepository(\AwardWallet\MainBundle\Entity\Bookingrequest::class)->getBookingRequestsCountByUserID($_SESSION['UserID'], '', true, true);
        $leftMenu["Award booker interface"] = array(
            "caption"	=> "Booking Requests Queue ({$bookingCountForBooker})",
            "path"      => "/booking/list.php?page=booker&noLeftNavigation=1",
            "selected"  => false
        );
    }*/
    $bookingCount = getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->getRequestsCountByUser(getCurrentSymfonyUser());
    /*$link = "/booking/list.php";
    if ($bookingCount == 1) {
        $q = new TQuery("SELECT BookingRequestID FROM BookingRequest WHERE UserID = ".$_SESSION['UserID']." LIMIT 1");
        if (!$q->EOF)
            $link = "/booking/view.php?ID=".$q->Fields['BookingRequestID'];
    }
    $leftMenu["Award booking requests"] = array(
        "caption"	=> "Award Ticket Booking ({$bookingCount})",
        "path"      => $link,
        "selected"  => false
    );*/
}

function gmtOffset($offset)
{
    $value = abs($offset / 3600);
    $hours = floor($value);
    $hours = strlen($hours) == 1 ? "0" . $hours : $hours;
    $minutes = $value - floor($value);
    $minutes = $minutes * 60 == 0 ? '00' : $minutes * 60;
    $sign = $offset < 0 ? "-" : ($offset == 0 ? "" : "+");

    return "(GMT" . (($hours == '00' && $minutes == '00') ? "" : $sign . $hours . ":" . $minutes) . ")";
}

function isSqlDateTimeFormat($str)
{
    return preg_match("/\d{4}\-\d{2}\-\d{2}/", $str);
}

function strToTimeForUser($str)
{
    if (isSqlDateTimeFormat($str)) {
        return strtotimeManualy($str, 'sql');
    }

    $dateFormat = (isset($_SESSION['UserFields']['DateFormat'])) ? $_SESSION['UserFields']['DateFormat'] : DATEFORMAT_US;

    return strtotimeManualy($str, $dateFormat);
}

function strtotimeManualy($str, $dateTimeFormat)
{
    switch ($dateTimeFormat) {
        case DATEFORMAT_EU:
            if (preg_match("/(\d{2})\/(\d{2})\/(\d{4})/", $str, $matches)) {
                $d = $matches[1];
                $m = $matches[2];
                $y = $matches[3];

                return mktime(null, null, null, $m, $d, $y);
            } else {
                return false;
                // var_dump_pre($str, 1);
                // DieTrace("Invalid string format", false);
            }

            break;

        case DATEFORMAT_US:
        case 'sql':
            return strtotime($str);

            break;
    }

    return null;
}

$symfonyContainer = null;
$globalVars = null;

/**
 * @return \Symfony\Component\DependencyInjection\ContainerInterface
 */
function getSymfonyContainer()
{
    global $symfonyContainer;

    if (!isset($symfonyContainer)) {
        require_once __DIR__ . "/../../app/liteSymfony/app.php";
    }

    return $symfonyContainer;
}

function schemaAccessAllowed($schema)
{
    $manager = getSymfonyContainer()->get("aw.security.role_manager");
    $schema = preg_replace("#[^a-z\d]+#ims", "_", $schema);
    $allowed = $manager->getAllowedSchemas();

    return in_array(strtolower($schema), $allowed);
}

/**
 * @return string
 */
function getSymfonyEnvironment()
{
    return getSymfonyContainer()->get('kernel')->getEnvironment();
}

/**
 * @return AwardWallet\MainBundle\Globals\GlobalVariables|null
 */
function getSymfonyGlobals()
{
    global $globalVars;

    if (!isset($globalVars)) {
        $globalVars = getSymfonyContainer()->get('aw.globals');
    }

    return $globalVars;
}

/**
 * @return \Doctrine\Common\Persistence\ObjectRepository
 */
function getRepository($repository)
{
    if (strpos($repository, 'AwardWallet\\') === 0) {
        return getSymfonyContainer()->get('doctrine')->getRepository($repository);
    }

    $className = 'AwardWallet\MainBundle\Entity\\' . $repository;

    return getSymfonyContainer()->get('doctrine')->getRepository($className);
}

function getSymfonyPasswordEncoder($user = null)
{
    $container = getSymfonyContainer();

    if (!isset($user)) {
        $user = new \AwardWallet\MainBundle\Entity\Usr();
    }

    return $container->get('security.encoder_factory')->getEncoder($user);
}

/**
 * @return mixed|\AwardWallet\MainBundle\Entity\Usr
 */
function getCurrentSymfonyUser()
{
    $tokenStorage = getSymfonyContainer()->get('security.token_storage');

    if (is_empty($tokenStorage->getToken())) {
        return null;
    }

    return $tokenStorage->getToken()->getUser();
}

function isGranted($role, $object = null)
{
    $tokenStorage = getSymfonyContainer()->get('security.token_storage');
    $authorizationChecker = getSymfonyContainer()->get('security.authorization_checker');

    return !is_empty($tokenStorage->getToken()) && $authorizationChecker->isGranted($role, $object);
}

function initRegionalSettings()
{
    global $UserSettings, $decimalPoints;

    if (isset($UserSettings) && is_array($UserSettings)) {
        return;
    }
    $dateFormat = DATEFORMAT_US;
    $thousandsSeparator = ",";

    if (isset($_SESSION['UserFields']['DateFormat'])) {
        $dateFormat = $_SESSION['UserFields']['DateFormat'];
    }

    if (isset($_SESSION['UserFields']['ThousandsSeparator'])) {
        $thousandsSeparator = $_SESSION['UserFields']['ThousandsSeparator'];
    }
    $UserSettings['DateFormat'] = $dateFormat;
    $UserSettings['ThousandsSeparator'] = $thousandsSeparator;
    $dateFormats = DateFormats($dateFormat);
    $UserSettings = array_merge($UserSettings, $dateFormats);

    if (isset($_SESSION['UserID'])) {
        $UserSettings['UserID'] = $_SESSION['UserID'];
    }

    if (!defined("DATE_TIME_FORMAT")) {
        define("DATE_TIME_FORMAT", $dateFormats['datetime']);
        define("DATE_FORMAT", $dateFormats['date']);
        define("TIME_FORMAT", $dateFormats['time']);
        define("MONTH_DAY_FORMAT", $dateFormats['monthday']);
        define("DATE_LONG_FORMAT", $dateFormats['datelong']);
        define("DATE_SHORT_FORMAT", $dateFormats['dateshort']);
        define("TIME_LONG_FORMAT", $dateFormats['timelong']);
    }
}

function isBusinessMismanagement()
{
    return SITE_MODE == SITE_MODE_BUSINESS
        && isset($_SESSION['UserFields']['Mismanagement'])
        && $_SESSION['UserFields']['Mismanagement'] == 1;
}

function suggestMobileVersion()
{
    if (isset($_SERVER['HTTP_HOST']) && preg_match("/^m\./ims", $_SERVER['HTTP_HOST'])) {
        // always redirect to mobile on m.awardwallet.com
        Redirect('/m/');
    } elseif (isset($_GET['mobile']) && $_GET['mobile'] == '0') {
        // disable mobile version on ([^m].)?awardwallet.com
        $days = 30; // cookie max-age in days
        setcookie('KeepDesktop', '1', time() + 3600 * 24 * $days, '/', $_SERVER['HTTP_HOST'], false);
    } elseif (
        !isset($_COOKIE['KeepDesktop']) // user do not switched to the desktop version with intention
        && (
            !isset($_SESSION)
            || !ArrayVal($_SESSION, 'KeepDesktop', false)
            || isset($_GET['appAvailable'])
        ) // no temporary switch to the desktop version in case when requested desktop functionality is absent in mobile version
        && (SITE_MODE == SITE_MODE_PERSONAL)
        && (SITE_BRAND == SITE_BRAND_NONE)
        && isset($_SERVER['HTTP_USER_AGENT'])
        && \AwardWallet\MainBundle\Globals\UserAgentUtils::isMobileBrowser($_SERVER['HTTP_USER_AGENT'])
    ) {
        $scriptUri = ArrayVal($_GET, 'BackTo', ArrayVal($_SERVER, 'REQUEST_URI'));
        $mobileAnalog = getMobileVersionAnalog($scriptUri);

        if ($mobileAnalog !== false) {
            if (
                isset($_GET['refCode'])
                || isset($_GET['invId'])
                || isset($_GET['softMobileRedirect'])
            ) {
                return;
            }

            $balancerProto = ArrayVal($_SERVER, 'HTTP_X_FORWARDED_PROTO', null);
            $scheme = (isset($balancerProto) && in_array($balancerProto, ['http', 'https'])) ? $balancerProto : $_SERVER['REQUEST_SCHEME'];
            $newUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $mobileAnalog;
            // redirect to mobile version domain.name

            Redirect($newUrl);
        } else {
            $_SESSION['KeepDesktop'] = true;
        }
    }
}

function getMobileVersionAnalog($scriptUri)
{
    // first match takes precedence
    $regExps = [
        '^/(\?[^?]+)?$' => '/m/$1',
    ];

    foreach ($regExps as $regExp => $replace) {
        if ($res = preg_replace("|{$regExp}|ims", $replace, $scriptUri)) {
            if ($res !== $scriptUri) {
                return $res;
            }
        }
    }

    return false;
}

$acCache = null;
$currentUserCache = null;
function getAC()
{
    global $acCache, $currentUserCache;

    if (isset($acCache)) {
        return $acCache;
    }
    $acCache = getSymfonyContainer()->get('aw.security.access.control');

    if (isset($_SESSION['UserID'])) {
        if (!isset($currentUserCache)) {
            $currentUserCache = getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($_SESSION['UserID']);
        }
        $acCache->setUser($currentUserCache);
    }

    return $acCache;
}

function sendBonusConversionMail(array $ids)
{
    $query = new TQuery("
        SELECT
            bc.BonusConversionID
        FROM BonusConversion bc
        JOIN Account a ON bc.AccountID = a.AccountID
        JOIN Provider p ON a.ProviderID = p.ProviderID
        JOIN Usr u ON u.UserID = a.UserID
        LEFT JOIN ProviderProperty pp ON pp.ProviderID = p.ProviderID
        LEFT JOIN AccountProperty ap ON
            ap.ProviderPropertyID = pp.ProviderPropertyID AND
            ap.AccountID = a.AccountID
        LEFT JOIN Currency c ON p.Currency = c.CurrencyID
        WHERE
            bc.Processed = 1 AND
            bc.BonusConversionID IN (" . implode(", ", $ids) . ") AND
            pp.Kind = " . PROPERTY_KIND_NUMBER);

    $mailer = getSymfonyContainer()->get("aw.email.mailer");
    $rep = getSymfonyContainer()->get("doctrine")->getRepository(\AwardWallet\MainBundle\Entity\BonusConversion::class);

    foreach ($query as $row) {
        /** @var \AwardWallet\MainBundle\Entity\BonusConversion $bc */
        $bc = $rep->find($row['BonusConversionID']);
        $user = $bc->getAccount()->getUserid();
        $template = new BonusConversion($user);
        $template->conversion = $bc;
        $message = $mailer->getMessageByTemplate($template);
        $message->setCc($mailer->getEmail('support'));
        $mailer->send($message);
        echo "<b>Mail sent to: " . $user->getEmail() . "</b><br/>";
    }
}

function canLoginToAccount($accountId)
{
    GetAgentFilters($_SESSION['UserID'], "All", $sUserAgentAccountFilter, $sUserAgentCouponFilter, true);
    $q = new TQuery("select 1 from Account a where ( $sUserAgentAccountFilter ) and a.AccountID = $accountId");

    return !$q->EOF;
}

function accountSharedWithBooker($accountId)
{
    $q = new TQuery("select
		1
	from
		AccountShare sh
		join UserAgent ua on  sh.UserAgentID = ua.UserAgentID
		join AbBookerInfo bi on bi.UserID = ua.AgentID
	where
		AccountID = $accountId");

    return !$q->EOF;
}

function filteredHost()
{
    return preg_replace('/^aw\d+\./i', '', $_SERVER["HTTP_HOST"]);
}

function setCSRFCookie()
{
    $token = getSymfonyContainer()->get("security.csrf.token_manager")->getToken("")->getValue();

    if (empty($_COOKIE['XSRF-TOKEN']) || $_COOKIE['XSRF-TOKEN'] != $token) {
        setcookie('XSRF-TOKEN', $token, null, '/', null, getSymfonyContainer()->getParameter('requires_channel') == 'https', false);
    }
}

function checkAjaxCSRF()
{
    $sessionToken = getSymfonyContainer()->get("security.csrf.token_manager")->getToken("")->getValue();
    $headersToken = ArrayVal($_SERVER, 'HTTP_X_XSRF_TOKEN');

    if ($headersToken != $sessionToken || empty($headersToken)) {
        ob_clean();
        echo json_encode(["error" => "CSRF", "CSRF" => $sessionToken]);
        header('Content-Type: application/json', true, 403);
        header('X-XSRF-FAILED: true', true);
        header("X-XSRF-TOKEN: {$sessionToken}", true);
        setCSRFCookie();

        exit;
    }
}

function checkFormCSRF()
{
    global $Interface;
    $sessionToken = getSymfonyContainer()->get("security.csrf.token_manager")->getToken("")->getValue();
    $receivedToken = ArrayVal($_POST, 'FormToken');

    if ($receivedToken != $sessionToken || empty($receivedToken)) {
        $Interface->DiePage(SESSION_HAS_EXPIRED);
    }
}

function pageWantsSession()
{
    global $bNoSession, $HTTP_SESSION_START;

    return !isset($bNoSession) && session_id() == "" && (!ConfigValue(CONFIG_HTTPS_ONLY) || ArrayVal($_SERVER, "HTTPS") == 'on' || (!empty($HTTP_SESSION_START) && isset($_COOKIE[ini_get('session.name')])));
}

function mysql_quote($s)
{
    return getSymfonyContainer()->get("database_connection")->quote($s);
}
