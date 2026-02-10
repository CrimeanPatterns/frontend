<?php

require_once __DIR__ . "/../lib/classes/http_class.php";

require_once __DIR__ . "/../trips/common.php";

require_once __DIR__ . "/../lib/htmlMimeMail5/htmlMimeMail5.php";

function GetAccountFilters($nUserAgentID)
{
    $arFilters = [];
    $filterSuffix = "";
    $accountSuffix = "";
    $couponSuffix = "";

    if (isset($_GET['ProviderID'])) {
        $filterSuffix .= "&ProviderID=" . urlencode($_GET['ProviderID']);
        $accountSuffix = " and a.ProviderID = " . intval($_GET['ProviderID']);
        $couponSuffix = " and 0 = 1";
    }
    $arFilters["All"] = [
        "Caption" => "All",
        "Filter" => " and (" . userProviderFilter() . "){$accountSuffix}",
        "CouponFilter" => "{$couponSuffix}",
        "ShowKinds" => true,
        "URLParams" => "&UserAgentID={$nUserAgentID}{$filterSuffix}",
    ];
    /*$q = new TQuery("select * from AView where UserID = {$_SESSION['UserID']} order by Name");
      while(!$q->EOF){
        $arFilters["Tab{$q->Fields["AViewID"]}"] = array(
            "Caption" => $q->Fields["Name"],
            "Filter" => " and (a.AccountID in (select ID from AViewAccount where AViewID = {$q->Fields['AViewID']} and Kind = 'A')) and (p.State is null or p.State >= ".PROVIDER_ENABLED.")",
            "CouponFilter" => " and (c.ProviderCouponID in (select ID from AViewAccount where AViewID = {$q->Fields['AViewID']} and Kind = 'C'))",
            "ShowKinds" => false,
            "URLParams" => "&UserAgentID=All",
        );
        $q->Next();
      }*/
    $arFilters["Recently"] = [
        "Caption" => "Recently changed",
        "Filter" => "{$accountSuffix}",
        "CouponFilter" => "and (0 = 1)",
        "ShowKinds" => false,
        "URLParams" => "&UserAgentID={$nUserAgentID}{$filterSuffix}",
    ];
    $arFilters["Active"] = [
        "Caption" => "Active accounts",
        "Filter" => " 
		and (
			(
				(
					(
						(a.Balance > 0 or p.CanCheckBalance = 0)
						or (
							select sum(if(Balance > 0,1,0))
							from SubAccount where AccountID = a.AccountID
						) > 0  
					)
					and (" . userProviderFilter() . "){$accountSuffix}
				) 
				AND a.IsActiveTab != " . ACTIVE_ACCOUNT_REMOVE . "
			) 			
			OR a.IsActiveTab = " . ACTIVE_ACCOUNT_ADD . "
		)",
        "CouponFilter" => "{$couponSuffix}",
        "ShowKinds" => false,
        "URLParams" => "&UserAgentID=$nUserAgentID&sort=balance{$filterSuffix}",
    ];

    if (ArrayVal($_GET, 'Coupons') == '1') {
        $CouponFilter = " and a.AccountID IN (SELECT DISTINCT(a.accountID)
		FROM Account a, SubAccount sa
		WHERE a.accountID = sa.accountID
		AND sa.Kind='C')";

        foreach ($arFilters as $key => $value) {
            $arFilters[$key]["Filter"] = $CouponFilter;
        }
    }

    return $arFilters;
}

function GetAccountTabs($arFilters)
{
    $tabs = [];

    foreach ($arFilters as $sCode => $arFilter) {
        $tabs[$sCode] = [
            "caption" => $arFilter["Caption"],
            "path" => "/account/list.php?showTabG=" . $sCode . $arFilter["URLParams"],
            "selected" => false,
        ];
    }

    return $tabs;
}

function PasswordEncrypt($text)
{
    $iv_size = mcrypt_get_iv_size(MCRYPT_3DES, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $key = "barbara12";

    return base64_encode(mcrypt_encrypt(MCRYPT_3DES, $key, $text, MCRYPT_MODE_ECB, $iv));
}

function Request($sHost, $sRequest, &$nError)
{
    $nError = 0;
    $sError = "";
    $rSocket = fsockopen("ssl://" . $sHost, 443, $nError, $sError, 30);

    if (!$rSocket) {
        return $sError;
    }
    fputs($rSocket, $sRequest);
    $sBody = "";

    while (!feof($rSocket)) {
        $s = fgets($rSocket, 4096);
        //    echo $s . "<br>";
        $sBody .= $s;
    }
    fclose($rSocket);

    return $sBody;
}

function LoadCookiePassword(&$arFields, $bSetError = true)
{
    global $Connection;

    if ($arFields["SavePassword"] == SAVE_PASSWORD_LOCALLY) {
        $id = ArrayVal($arFields, 'AccountID', ArrayVal($arFields, 'ID', 0));
        $lpm = getSymfonyContainer()->get("aw.manager.local_passwords_manager");

        if (!$lpm->hasPassword($id)) {
            if (aaPasswordValid($arFields)) {
                return true;
            } else {
                if ($bSetError) {
                    $Connection->Execute("update Account set ErrorCode = " . ACCOUNT_MISSING_PASSWORD . ", ErrorMessage = '" . addslashes(ACCOUNT_MISSING_PASSWORD_MESSAGE) . "' where AccountID = {$id}");
                }

                return false;
            }
        } else {
            $arFields["Pass"] = $lpm->getPassword($id);
        }
    }

    return true;
}

function CheckUserAgentID($nUserAgentID)
{
    if (intval($nUserAgentID) > 0) {
        $nUserAgentID = intval($nUserAgentID);
    } elseif (!in_array($nUserAgentID, ["All"])) {
        $nUserAgentID = "0";
    }
}

function GetAgentFilters($nUserID, $nUserAgentID, &$sUserAgentAccountFilter, &$sUserAgentCouponFilter, $bWantCheck = false, $bWantEdit = false, $bSetMenu = false)
{
    global $leftMenu, $othersMenu, $forceMenuSelect;

    if (isset($_SESSION['Business'])) {
        $bSetMenu = false;
    }

    if ($bSetMenu) {
        $forceMenuSelect = true;
    }

    if ($nUserAgentID === "All") {
        if ($bSetMenu && isset($leftMenu["All Award Programs"])) {
            $leftMenu["All Award Programs"]["selected"] = true;
        }
        $qAgent = new TQuery("select ua.*, coalesce( c.FirstName, ua.FirstName ) as FirstName, coalesce( c.LastName, ua.LastName ) as LastName from UserAgent ua left outer join Usr c on c.UserID = ua.ClientID where ua.IsApproved = 1 and ua.AgentID = {$nUserID}");
        $sUserAgentAccountFilter = "a.UserID = {$nUserID}";

        if (!$qAgent->EOF) {
            $sUserAgentAccountFilter .= " or ";

            while (!$qAgent->EOF) {
                if ($qAgent->Fields['ClientID'] > 0) {
                    if ((!$bWantCheck || ($qAgent->Fields['AccessLevel'] >= ACCESS_READ_ALL))
                    && (!$bWantEdit || ($qAgent->Fields['AccessLevel'] >= ACCESS_WRITE))) {
                        $sUserAgentAccountFilter .= "( ( a.UserID = {$qAgent->Fields['ClientID']} and a.AccountID in ( select ash.AccountID from AccountShare ash, Account a where ash.AccountID = a.AccountID and a.UserID = {$qAgent->Fields['ClientID']} and ash.UserAgentID = {$qAgent->Fields['UserAgentID']} ) ) or ( a.UserAgentID = {$qAgent->Fields['UserAgentID']} and a.UserID = {$nUserID} ) )";
                    } else {
                        $sUserAgentAccountFilter .= "0 = 1";
                    }
                } else {
                    $sUserAgentAccountFilter .= "a.UserAgentID = {$qAgent->Fields['UserAgentID']}";
                }

                $qAgent->Next();

                if (!$qAgent->EOF) {
                    $sUserAgentAccountFilter .= " or ";
                }
            }
        }
        $sUserAgentCouponFilter = "c.UserID = {$nUserID}";
    } else {
        $nUserAgentID = intval($nUserAgentID);

        if ($nUserAgentID > 0) {
            $qAgent = new TQuery("select ua.*, coalesce( c.FirstName, ua.FirstName ) as FirstName, coalesce( c.LastName, ua.LastName ) as LastName from UserAgent ua left outer join Usr c on c.UserID = ua.ClientID where ua.UserAgentID = {$nUserAgentID} and ua.IsApproved = 1 and ua.AgentID = {$nUserID}");

            if ($qAgent->EOF) {
                Redirect("/account/list.php");
            }

            if ($bSetMenu && isset($othersMenu["Client_" . $qAgent->Fields["UserAgentID"]])) {
                $othersMenu["Client_" . $qAgent->Fields["UserAgentID"]]["selected"] = true;
            }
        } elseif ($bSetMenu) {
            $leftMenu["My Award Programs"]["selected"] = true;
        }

        if ($nUserAgentID > 0) {
            if ($qAgent->Fields['ClientID'] > 0) {
                if ((!$bWantCheck || ($qAgent->Fields['AccessLevel'] >= ACCESS_READ_ALL))
                && (!$bWantEdit || ($qAgent->Fields['AccessLevel'] >= ACCESS_WRITE))) {
                    $sUserAgentAccountFilter = "( ( a.UserID = {$qAgent->Fields['ClientID']} and a.AccountID in ( select ash.AccountID from AccountShare ash, Account a where ash.AccountID = a.AccountID and a.UserID = {$qAgent->Fields['ClientID']} and ash.UserAgentID = {$qAgent->Fields['UserAgentID']} ) ) or ( a.UserAgentID = {$qAgent->Fields['UserAgentID']} and a.UserID = {$nUserID} ) )";
                } else {
                    $sUserAgentAccountFilter = "0 = 1";
                }
            } else {
                $sUserAgentAccountFilter = "a.UserAgentID = {$qAgent->Fields['UserAgentID']}";
            }

            $sUserAgentCouponFilter = 0;
        } else {
            $sUserAgentAccountFilter = "a.UserID = {$nUserID} and a.UserAgentID is null";
            $sUserAgentCouponFilter = "c.UserID = {$nUserID}";
        }
    }
    $sUserAgentCouponFilter = str_replace("a.", "c.", $sUserAgentAccountFilter);
    $sUserAgentCouponFilter = str_replace("Account", "ProviderCoupon", $sUserAgentCouponFilter);
    $sUserAgentCouponFilter = str_replace("ProviderCoupon a", "ProviderCoupon c", $sUserAgentCouponFilter);
}

function GetBalanceHistoryQuery($accountToCheck, $numberOfBalancesToShow, $subAccountId = null)
{
    $countNumberOfBalancesRS = new TQuery("select count(*) as BalanceCount FROM AccountBalance WHERE AccountID = $accountToCheck and SubAccountID " . (isset($subAccountId) ? " = $subAccountId" : "is null"));

    if (!$countNumberOfBalancesRS->EOF) {
        $balanceCount = $countNumberOfBalancesRS->Fields['BalanceCount'];
    }
    $startLimit = $balanceCount - $numberOfBalancesToShow;

    if ($startLimit < 0) {
        $startLimit = 0;
    }

    return "select UpdateDate, FORMAT(Balance,0) AS BalanceFormatted, trim(trailing '.' from trim(trailing '0' from round(Balance, 7))) as Balance FROM AccountBalance WHERE AccountID = $accountToCheck and SubAccountID " . (isset($subAccountId) ? " = $subAccountId" : "is null") . " ORDER BY UpdateDate LIMIT $startLimit,$numberOfBalancesToShow;";
}

function GetBalanceGraphImage($accountToCheck, $numberOfBalancesToShow, $delayedLoad, $id, $userId, $subAccountId = null, $availWidth = null)
{
    global $Connection;

    if (isset($availWidth) && ($availWidth > 140)) {
        $numberOfBalancesToShow = floor(($availWidth - 40) / 50);
    }
    $objRS = new TQuery(GetBalanceHistoryQuery($accountToCheck, $numberOfBalancesToShow, $subAccountId));

    $chartValuesY = [];
    $chartValuesX = [];
    $dataLables = [];
    $axisY = [];
    $axisX = [1, 2, 3, 4, 5];
    $i = 0;

    while (!$objRS->EOF) {
        $chartValuesY[] = $objRS->Fields['Balance'];
        //	if($i%5 == 0)
        $chartValuesX[] = date(UserSettings($userId, 'dateshort'), $Connection->SQLToDateTime($objRS->Fields['UpdateDate']));
        //	else
        //		$chartValuesX[] = "";
        $caption = str_replace(".00", "", number_format($objRS->Fields['Balance'], 2, ".", " "));

        if (strlen($caption) > 5) {
            $fontSize = 9;
        } else {
            $fontSize = 15;
        }
        $dataLables[] = "t" . $caption . ",001bc0,0,$i," . $fontSize;
        $i++;
        $objRS->Next();
    }
    $width = 50 * $i + 40;

    if (count($chartValuesY) > 1) {
        $maxVal = max($chartValuesY);
        $minVal = min($chartValuesY);

        if ($maxVal != $minVal) {
            $extra = round(($maxVal - $minVal) * 0.2);
        } else {
            $extra = $maxVal * 0.2;
        }

        if ($extra == 0) {
            $extra = 1;
        }
        $minVal = $minVal - $extra;
        // Mip begin
        /*if($minVal < 0)
          $minVal = 0;*/
        // Mip end
        $maxVal = $maxVal + $extra;
        $range = $maxVal - $minVal;
        $step = round($range / 4);
        $roundStep = $step;
        $rounder = 10;
        $lastRoundStep = $roundStep;

        while ($rounder < $step) {
            $roundStep = round($step / $rounder) * $rounder;

            if ($roundStep == 0) {
                $roundStep = $lastRoundStep;

                break;
            }

            if (($range / $roundStep) > 4.5) {
                break;
            }
            $rounder = $rounder * 10;
            $lastRoundStep = $roundStep;
        }

        if ($roundStep == 0) {
            $roundStep = 1;
        }
        $minVal = floor($minVal / $roundStep) * $roundStep;
        $maxVal = ceil($maxVal / $roundStep) * $roundStep;

        for ($y = $minVal; $y <= $maxVal; $y += $roundStep) {
            $axisY[] = number_format($y, 0, '.', ' ');
        }
        $step = 100 / (count($axisY) - 1);
        $url = "/lib/chart.php?cht=bvg&chs=" . $width . "x150&chbh=25,25,25&chd=t:" . implode(",", $chartValuesY) . "&chds=" . intval($minVal) . "," . intval($maxVal) . "&chco=006eb7&chxt=x,y&chxl=0:|" . implode("|", $chartValuesX) . "|1:|" . implode("|", $axisY) . "&chg=0,$step&chm=" . urlencode(implode("|", $dataLables));

        if ($delayedLoad) {
            $src = "realSrc='" . htmlspecialchars($url) . "' src='/lib/images/pixel.gif'";
        } else {
            $src = "src='" . htmlspecialchars($url) . "'";
        }

        if (isset($id)) {
            $idAttr = "id='" . urlencode($id) . "'";
        } else {
            $idAttr = "";
        }

        return "<img alt='Balance history chart' {$src} {$idAttr} width='" . $width . "' height='150'>"; /* checked by AV */
    } else {
        return null;
    }
}

function AccountsSQL($nUserID, $sUserAgentAccountFilter, $sUserAgentCouponFilter, $sFilter, $sCouponFilter, $nUserAgentID)
{
    $oneAccount = preg_match("#a\.AccountID = \d+#ims", $sFilter);
    $sSQL = "select /*fields(*/ CONVERT('Account' USING utf8) as TableName, a.AccountID as ID, a.Login, a.Login2, a.Login3,
	trim(trailing '.' from trim(trailing '0' from round(a.Balance, 10))) as Balance,
	trim(trailing '.' from trim(trailing '0' from round(a.TotalBalance, 10))) as TotalBalance,
	a.ErrorCode, a.ErrorMessage, a.State, a.Pass, p.Code as ProviderCode,
	coalesce( p.ShortName, a.ProgramName ) as ProviderName, p.Name as FullProviderName, coalesce( p.LoginURL, a.LoginURL ) as LoginURL,
	coalesce( p.ProgramName, a.ProgramName ) as ProgramName, CONVERT(REPEAT( 'x', 128 ) USING utf8) as Description,
	CONVERT(REPEAT( 'x', 128 ) USING utf8) as Value,
	a.ExpirationDate, p.ProviderID, p.AutoLogin,
	p.CanCheck, p.AllianceID, al.Alias as AllianceAlias, a.comment,
	" . SQL_USER_NAME . " as UserName,
	u.UserID, ash.AccessLevel, p.CanCheckBalance, DATE_FORMAT(DATE(a.UpdateDate),'%M %e, %Y') as UpdateDate, a.UpdateDate as RawUpdateDate, p.Site,
	coalesce( p.DisplayName, a.ProgramName ) as DisplayName, a.ExpirationAutoSet, p.ExpirationDateNote,
	a.LastChangeDate, a.ChangeCount, trim(trailing '.' from trim(trailing '0' from round(a.LastBalance, 7))) as LastBalance, u.AccountLevel, p.TradeMin,
	a.Goal, p.BalanceFormat, a.ExpirationWarning, coalesce(p.Kind, a.Kind, 1) as Kind, p.FAQ, p.CanCheckExpiration, p.ExpirationAlwaysKnown, a.Balance as RawBalance, p.AAADiscount,
	a.SavePassword, p.ExpirationUnknownNote, a.SubAccounts, ua.Comment as AgentComment, p.CustomDisplayName, p.BarCode, ash.UserAgentID as ShareUserAgentID, p.MobileAutoLogin,
	a.SuccessCheckDate,	u.PictureVer as UserPictureVer, u.PictureExt as UserPictureExt, ua.PictureVer as UserAgentPictureVer, ua.PictureExt as UserAgentPictureExt,
	p.EliteLevelsCount,
	a.UserAgentID,
	p.Currency,
	a.Question,
	a.DontTrackExpiration,
	coalesce(p.CheckInBrowser, 0) as CheckInBrowser,
	p.DeepLinking,
	a.LastCheckItDate, a.LastCheckHistoryDate, a.HistoryVersion, a.QueueDate, p.State as ProviderState, p.Engine AS ProviderEngine, a.PassChangeDate, a.ModifyDate,
	p.CanCheckItinerary
	/*)fields*/
	from Account a " . (!$oneAccount ? "use index (idx_Account_UserID, idx_Account_UserAgentID)" : "") . "
	left outer join Provider p on a.ProviderID = p.ProviderID
	left outer join Alliance al on p.AllianceID = al.AllianceID
	left outer join UserAgent ua on a.UserAgentID = ua.UserAgentID
	left outer join (
		select AccountShare.UserAgentID, AccountID, AgentID, AccessLevel
		from UserAgent, AccountShare where UserAgent.UserAgentID = AccountShare.UserAgentID
		" . (isset($nUserID) ? " and UserAgent.AgentID = {$nUserID}" : "") . "
	) ash on a.AccountID = ash.AccountID
	/*joins*/,
	Usr u
	where a.State > 0 and a.UserID = u.UserID and ( $sUserAgentAccountFilter )
	$sFilter ";

    if ($sUserAgentCouponFilter != "0 = 1") {
        $sSQL .= "union select
		CONVERT('Coupon' USING utf8) as TableName,
		c.ProviderCouponID as ID, CONVERT(NULL USING utf8) as Login, null as Login2, null as Login3, 0 as Balance,
		0 as TotalBalance,
		CONVERT(NULL USING utf8) as ErrorCode, CONVERT(NULL USING utf8) as ErrorMessage,
		CONVERT(NULL USING utf8) as State, CONVERT(NULL USING utf8) as Pass, null as ProviderCode,
		c.ProgramName as ProviderName, c.ProgramName as FullProviderName, null as LoginURL, c.ProgramName, c.Description as Description, c.Value,
		c.ExpirationDate as ExpirationDate, null as ProviderID, 0 as AutoLogin, 0 as CanCheck, 0 as AllianceID, null as AlllianceAlias,
		CONVERT(NULL USING utf8) as comment,
		" . SQL_USER_NAME . " as UserName,
		u.UserID, ash.AccessLevel as AccessLevel, 0 as CanCheckBalance, null as UpdateDate, null as RawUpdateDate, null as Site,
		c.ProgramName as DisplayName, " . EXPIRATION_UNKNOWN . " as ExpirationAutoSet, null as ExpirationDateNote,
		null as LastChangeDate, null as ChangeCount, null as LastBalance, u.AccountLevel,
		null as TradeMin, null as Goal, null as BalanceFormat, null as ExpirationWarning, c.Kind as Kind, null as FAQ, 0 as CanCheckExpiration, 0 as ExpirationAlwaysKnown,
		0 as RawBalance, 0 as AAADiscount, null as SavePassword, null as ExpirationUnknownNote, 0 as SubAccounts, null as AgentComment, 0 as CustomDisplayName,
		null as BarCode, ash.UserAgentID as ShareUserAgentID, null as MobileAutoLogin, null as SuccessCheckDate,
		u.PictureVer as UserPictureVer, u.PictureExt as UserPictureExt, ua.PictureVer as UserAgentPictureVer, ua.PictureExt as UserAgentPictureExt, null as EliteLevelsCount, c.UserAgentID,
		null as Currency,
		null as Question,
		null as DontTrackExpiration,
		null as CheckInBrowser,
		0 DeepLinking,
		null as LastCheckItDate, null as LastCheckHistoryDate, null as HistoryVersion, null as QueueDate, null as ProviderState, null AS ProviderEngine, null as PassChangeDate, null as ModifyDate,
		0 as CanCheckItinerary
		from ProviderCoupon c
		left outer join UserAgent ua on c.UserAgentID = ua.UserAgentID
		left outer join (
			select csh.UserAgentID, csh.ProviderCouponID, ua.AgentID, ua.AccessLevel
			from UserAgent ua, ProviderCouponShare csh where ua.UserAgentID = csh.UserAgentID
			" . (isset($nUserID) ? " and ua.AgentID = {$nUserID}" : "") . "
		) ash on c.ProviderCouponID = ash.ProviderCouponID,
		Usr u
		where
		c.UserID = u.UserID
		and ( $sUserAgentCouponFilter )
		$sCouponFilter";
    }

    return $sSQL;
}

function ReplaceSQLFields($sql, $fields)
{
    return preg_replace("/\/\*fields\*\(.*\/\*\)fields\*\//ims", $fields, $sql);
}

function DeleteAccount($nID)
{
    global $Connection;
    $dispatcher = getSymfonyContainer()->get('event_dispatcher');
    $userID = Lookup('Account', 'AccountID', 'UserID', $nID);
    $Connection->Delete("Account", $nID);
}

function loadAccountAvatar(&$arFields)
{
    $useAgent = false;

    if ($arFields['UserID'] == $_SESSION['UserID']) {
        if ($arFields['UserAgentID'] != '') {
            $useAgent = true;
        }
    } elseif (isset($arFields['UserAgentPictureVer'])) {
        $useAgent = true;
    }

    if ($useAgent) {
        $arFields['PictureVer'] = $arFields['UserAgentPictureVer'];
        $arFields['PictureExt'] = $arFields['UserAgentPictureExt'];
        $arFields['PictureID'] = $arFields['UserAgentID'];
        $arFields['PictureDir'] = 'userAgent';
    } else {
        $arFields['PictureVer'] = $arFields['UserPictureVer'];
        $arFields['PictureExt'] = $arFields['UserPictureExt'];
        $arFields['PictureID'] = $arFields['UserID'];
        $arFields['PictureDir'] = 'user';
    }
}

function getLastUpdateMessage($arrUpdDates)
{
    $result = [
        "msg" => false,
        "title" => false,
    ];
    $arrDates = [];
    $maxDate = 0;
    $minDate = time();

    foreach ($arrUpdDates as $date) {
        $currDate = strtotime($date);

        if ($currDate > $maxDate) {
            $maxDate = $currDate;
        }

        if ($currDate < $minDate) {
            $minDate = $currDate;
        }
    }

    if (!empty($arrUpdDates)) {
        $tail = time() - $maxDate;

        if ($tail < 0) {
            $tail = 0;
        }

        if ($tail < 3600) {
            $num = floor($tail / 60);
            $result['msg'] = " {$num} minute" . (($num > 1 || $num == 0) ? 's' : '');
        } elseif ($tail >= 3600 && $tail < 3600 * 24) {
            $num = floor($tail / 3600);
            $result['msg'] = " {$num} hour" . (($num > 1 || $num == 0) ? 's' : '');
        } elseif ($tail >= 3600 * 24) {
            $num = floor($tail / (3600 * 24));
            $result['msg'] = " {$num} day" . (($num > 1 || $num == 0) ? 's' : '');
        }
        $offset = getTimeZoneOffset();

        if (!isset($offset)) {
            $offset = 0;
        }

        $result['title'] = gmdate("d F Y h:i A", $maxDate + $offset);
    }

    return $result;
}

function getAccountPromotions($userID, $accountID, $limit)
{
    global $Interface;

    $sql = "SELECT p.DisplayName,
        	d.DealID,
        	d.Title,
        	IF(dm.Readed IS NULL,0,dm.Readed) MarkRead,
        	IF(dm.Applied IS NULL,0,dm.Applied) MarkApplied,
        	IF(dm.Follow IS NULL,0,dm.Follow) MarkFollow,
        	IF(dm.Manual IS NULL,0,dm.Manual) MarkManual,
            IF(d.BeginDate > DATE_ADD(NOW(), INTERVAL -7 DAY) OR
               d.CreateDate > DATE_ADD(NOW(), INTERVAL -7 DAY), 1, 0) IsNew,
            dr.RegionNames,
            dr.RegionIDs
        FROM Deal d
        	LEFT JOIN Provider p ON p.ProviderID = d.ProviderID
        	LEFT JOIN DealMark dm ON dm.DealID = d.DealID
                AND dm.UserID = " . $userID . "
            LEFT JOIN (
                SELECT DealID,
                    (SUM(Follow)+SUM(Applied)) TotalFollowApplied,
                    (SUM(Follow)+SUM(Manual)) TotalFollowManual
                FROM DealMark
                GROUP BY DealID
            ) dmTotals ON dmTotals.DealID = d.DealID
            LEFT JOIN (
				SELECT
            		GROUP_CONCAT(DISTINCT Region.Name) as RegionNames,
                	GROUP_CONCAT(DISTINCT Region.RegionID) as RegionIDs,
                    DealID
                FROM DealRegion
                JOIN Region on Region.RegionID = DealRegion.RegionID
                GROUP BY DealID) dr ON dr.DealID = d.DealID
			LEFT JOIN Account a on a.ProviderID = d.ProviderID
            WHERE (NOW() >= d.BeginDate AND NOW() <= d.EndDate)
				AND a.AccountID = " . $accountID . "
			HAVING
				MarkRead = 0
				AND MarkManual = 0
        ORDER BY
        	isNew DESC,
        	dmTotals.TotalFollowManual DESC,
            d.TimesClicked DESC,
            d.CreateDate DESC,
            p.DisplayName
		limit " . $limit;

    $q = new TQuery($sql);

    if (!$q->IsEmpty) {
        $list = '';

        while (!$q->EOF) {
            // show full regions list for deals affected by US(id = 2) and Canada(id = 3)
            $promoRegion = [];

            if (isset($q->Fields['RegionNames']) && isset($q->Fields['RegionIDs'])) {
                $detailedList = [2 => "United States", 3 => "Canada"]; // United States and Canada
                /*
                $arrAssoc = array_combine(explode(',', $q->Fields['RegionIDs']), explode(',', $q->Fields['RegionNames']));
                $detailedList = array(2 => "United States", 3 => "Canada"); // United States and Canada
                foreach ($arrAssoc as $regionID => $regionName) {
                    if (array_key_exists($regionID, $detailedList)) {
                        $promoRegion[$regionID] = $regionName;
                        continue;
                    }
                    $parentRegions = array();
                    getAllParentRegions($regionID, $parentRegions);
                    if (!empty($parentRegions) && is_empty(array_intersect_key($parentRegions, $arrAssoc)) && array_intersect_key($parentRegions, $detailedList))
                        $promoRegion[] = $regionName;
                }*/
                $promoRegion = array_intersect_key($detailedList, explode(',', $q->Fields['RegionIDs']));
                $promoRegion = implode(', ', $promoRegion);
            }
            $dealID = $q->Fields['DealID'];
            $list .=
            "<div class='listItem " . (((bool) $q->Fields['IsNew']) ? 'listItemNew ' : '') . (((bool) $q->Fields['MarkFollow']) ? 'listItemFollow ' : '') . (((bool) $q->Fields['MarkApplied']) ? 'listItemApply ' : '') . "'>
				<div class='title'>
				<a href='/promos#{$dealID}'>" . $q->Fields['Title'] . "</a>
				" . ((!empty($promoRegion)) ? "<span class='regionName'>(" . $promoRegion . ")</span>" : "") . "
				</div>
				<b class='new'> </b>
                <b class='follow'></b>
                <b class='apply'></b>
			  </div>";
            $q->Next();
        }

        return $list;
    } else {
        return null;
    }
}
function getAllParentRegions($regionId, &$result)
{
    $q = new TQuery("
	select
		pr.RegionID,
		pr.Name
	from
		RegionContent rc
		join Region pr on rc.RegionID = pr.RegionID
	where
		pr.Kind <> " . REGION_KIND_CONTINENT . "
		and rc.SubRegionID = $regionId");

    while (!$q->EOF) {
        if (!isset($result[$q->Fields['RegionID']])) {
            $result[$q->Fields['RegionID']] = $q->Fields['Name'];
            getAllParentRegions($q->Fields['RegionID'], $result);
        }
        $q->Next();
    }
}

function getContinentsDeals()
{
    $sql = "
        SELECT r.RegionID,
            r.Kind,
            GROUP_CONCAT(rc.RegionID) as ParentsID
        FROM Region r
        LEFT JOIN RegionContent rc ON rc.SubRegionID = r.RegionID AND rc.Exclude = 0
        JOIN (
        	SELECT dr.RegionID
        	FROM Deal d
        	LEFT JOIN DealRegion dr ON d.DealID = dr.DealID
        	WHERE dr.RegionID IS NOT NULL AND (NOW() >= d.BeginDate AND NOW() <= d.EndDate) GROUP BY dr.RegionID
        ) dr ON dr.RegionID = r.RegionID
        GROUP BY r.RegionID
        ORDER BY (
            CASE
                WHEN r.Kind = 7 THEN 0 ELSE 1
            END
        )
        ";
    $cache = Cache::getInstance()->get('regionContinents');
    $regionsArr = [];
    $q = new TQuery($sql);

    while (!$q->EOF) {
        if ($cache !== false && isset($cache[$q->Fields['RegionID']])) {
            $regionsArr[$q->Fields['RegionID']] = $cache[$q->Fields['RegionID']];
        } else {
            if ($q->Fields['Kind'] == 7) {
                $regionsArr[$q->Fields['RegionID']] = $q->Fields['RegionID'];
            } else {
                $continent = findRegionContinentByParentsID($q->Fields['ParentsID']);
                $regionsArr[$q->Fields['RegionID']] = $continent;
            }
        }
        $q->Next();
    }
    Cache::getInstance()->set('regionContinents', $regionsArr, 3600 * 24 * 3);

    return $regionsArr;
}

function getContinentsArray()
{
    $sql = "
        SELECT RegionID,
            Name
        FROM   Region
        WHERE  Kind = " . 7 . "
        ORDER BY Name
        ";
    $q = new TQuery($sql);
    $result = [];

    while (!$q->EOF) {
        $result[$q->Fields['RegionID']] = $q->Fields['Name'];
        $q->Next();
    }

    return $result;
}

function findRegionContinentByParentsID($parentsID)
{
    $sql = "
        SELECT r.RegionID,
            r.Kind,
            GROUP_CONCAT(rc.RegionID) as ParentsID
        FROM Region r
        LEFT JOIN RegionContent rc ON rc.SubRegionID = r.RegionID AND rc.Exclude = 0
        WHERE r.RegionID IN ($parentsID)
        GROUP BY r.RegionID
        ORDER BY (
            CASE
                WHEN r.Kind = 7 THEN 0 ELSE 1
            END
        )
        ";
    $q = new TQuery($sql);

    while (!$q->EOF) {
        if ($q->Fields['Kind'] == 7) {
            return $q->Fields['RegionID'];
        } elseif (!empty($q->Fields['ParentsID'])) {
            $res = findRegionContinentByParentsID($q->Fields['ParentsID']);

            if ($res) {
                return $res;
            }
        }
        $q->Next();
    }

    return null;
}

/**
 * @param datetime $startDate
 * @param datetime $endDate
 * @return array
 */
function getChangedSubAccounts($accountIds, $subAccountIds = null, $startDate, $endDate)
{
    global $Connection;

    if (!is_array($accountIds)) {
        $accountIds = [$accountIds];
    }
    $subAccountsSql = '';

    if (isset($subAccountIds)) {
        if (!is_array($subAccountIds)) {
            $subAccountIds = [$subAccountIds];
        }
        $subAccountIds = implode(', ', array_map('intval', $subAccountIds));
        $subAccountsSql = "AND sa.SubAccountID IN ({$subAccountIds})";
    }

    $accountIds = array_map('intval', $accountIds);

    $accountIds = implode(', ', $accountIds);
    $startDate = $Connection->DateTimeToSQL($startDate);
    $endDate = $Connection->DateTimeToSQL($endDate);

    return SQLToArray("
    SELECT
        sa.AccountID,
        sa.SubAccountID,
        sa.DisplayName,
        sa.Balance,
        sa.LastBalance,
        COALESCE(sa.LastChangeDate, ab.UpdateDate) as LastChangeDate,
        sa.ChangeCount as SubAccountChangeCount
    FROM SubAccount sa
    LEFT JOIN AccountBalance ab ON ab.AccountID = sa.AccountID
    WHERE
        sa.AccountID IN ({$accountIds}) {$subAccountsSql} AND ((
            sa.LastChangeDate >= {$startDate} AND
            sa.LastChangeDate <= {$endDate}
        ) OR (
            sa.LastChangeDate IS NULL AND
            ab.UpdateDate >= {$startDate} AND
            ab.UpdateDate <= {$endDate}
        )) AND
        (sa.Kind <> 'C' OR sa.Kind = 'S' OR sa.Kind IS NULL) AND
        sa.ChangeCount > 0
    GROUP BY sa.SubAccountID
    ORDER BY sa.DisplayName",
        'SubAccountID', 'Balance', true);
}

/**
 * @param datetime $startDate
 * @param datetime $endDate
 * @return array
 */
function getChangedAccounts($accountIds, $startDate, $endDate)
{
    global $Connection;

    if (!is_array($accountIds)) {
        $accountIds = [$accountIds];
    }

    foreach ($accountIds as $key => $accountId) {
        $accountIds[$key] = intval($accountId);
    }

    $accountIds = implode(', ', $accountIds);
    $startDate = $Connection->DateTimeToSQL($startDate);
    $endDate = $Connection->DateTimeToSQL($endDate);

    return SQLToArray("
    SELECT
        ab.AccountID,
        MAX(ab.UpdateDate) as MaxUpdateDate,
        COUNT(ab.AccountID) as ChangeCount
    FROM AccountBalance ab
    WHERE
        ab.AccountID IN ({$accountIds}) AND
        ab.SubAccountID IS NULL
    GROUP BY AccountID
    HAVING
        COUNT(ab.AccountID) > 1 AND
        MAX(ab.UpdateDate) >= {$startDate} AND
        MAX(ab.UpdateDate) <= {$endDate}",
        'AccountID', 'ChangeCount', true);
}
