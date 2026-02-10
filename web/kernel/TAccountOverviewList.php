<?php
require_once __DIR__ . "/../account/common.php";

class TAccountOverviewList extends TAccountList
{
    protected $Page = "/account/overview.php";
    protected $UserAgentAccountFilter;
    protected $ProviderAccounts;
    protected $corporateGroup;

    public function setDefaultParams()
    {
        parent::setDefaultParams();
        $this->Grouped = false;
        $this->ShowNames = false;
        $this->Caption = null;
    }

    public function getProviderAccounts()
    {
        $q = new TQuery("select /* getProviderAccounts */
			/* shared */
			a.ProviderID,
			a.AccountID as ID,
			trim(trailing '.' from trim(trailing '0' from round(a.Balance, 10))) as Balance,
			trim(trailing '.' from trim(trailing '0' from round(a.LastBalance, 7))) as LastBalance,
			a.LastChangeDate
		from
			Account a
			join AccountShare ash on a.AccountID = ash.AccountID
			join UserAgent ua on ua.AgentID = {$_SESSION['UserID']} and ua.UserAgentID = ash.UserAgentID
			join Provider p on a.ProviderID = p.ProviderID
			where " . userProviderFilter() . "
		union
		select
			a.ProviderID,
			a.AccountID as ID,
			trim(trailing '.' from trim(trailing '0' from round(a.Balance, 10))) as Balance,
			trim(trailing '.' from trim(trailing '0' from round(a.LastBalance, 7))) as LastBalance,
			a.LastChangeDate
		from
			Account a
			join Provider p on a.ProviderID = p.ProviderID
			where a.UserID = {$_SESSION['UserID']} and " . userProviderFilter() . "
		");
        $result = [];

        while (!$q->EOF) {
            $result[$q->Fields['ProviderID']][] = $q->Fields;
            $q->Next();
        }

        return $result;
    }

    public function buildSQL($sFilter, $sCouponFilter)
    {
        global $PrintInFile;
        $min = 'min';
        $max = 'max';
        $sum = 'sum';

        if (!empty($PrintInFile)) {
            $min = $max = $sum = "";
        }

        if (ArrayVal($_GET, 'Coupons') == '1') {
            $sFilter .= "
			AND a.AccountID IN
			    (
			    SELECT DISTINCT(a.accountID)
			    FROM Account a, SubAccount sa
			    WHERE a.accountID = sa.accountID
			    AND sa.Kind='C'
			    )";
        }

        $fields = [
            "p.ProviderID" => "ProviderID",
            "p.Code" => "ProviderCode",
            "coalesce( p.DisplayName, a.ProgramName )" => "DisplayName",
            "coalesce( p.LoginURL, a.LoginURL )" => "LoginURL",
            "p.AutoLogin" => "AutoLogin",
            "p.CanCheck" => "CanCheck",
            "p.CanCheckBalance" => "CanCheckBalance",
            "p.Site" => "Site",
            "p.ExpirationDateNote" => "ExpirationDateNote",
            "p.BalanceFormat" => "BalanceFormat",
            "p.Kind" => "Kind",
            "p.CanCheckExpiration" => "CanCheckExpiration",
            "p.ExpirationAlwaysKnown" => "ExpirationAlwaysKnown",
            "p.ExpirationUnknownNote" => "ExpirationUnknownNote",
            "p.CustomDisplayName" => "CustomDisplayName",
            "p.Name" => "FullProviderName",
            "coalesce( p.ShortName, a.ProgramName )" => "ProviderName",
            "p.TradeMin" => "TradeMin",
            "p.Currency" => "Currency",
            "p.EliteLevelsCount" => "EliteLevelsCount",
            "p.CheckInBrowser" => "CheckInBrowser",
            "p.Corporate" => "Corporate",
        ];

        $fieldList = "
		" . implode(", ", $fields) . ",
		" . ((!empty($PrintInFile)) ? "1 as Accounts," : "count(a.AccountID) as Accounts,") . "
		a.comment,
		trim(trailing '.' from trim(trailing '0' from round($sum(IF(a.TotalBalance > 0,a.TotalBalance,0)), 10))) as Balance,
		trim(trailing '.' from trim(trailing '0' from round($sum(IF(a.TotalBalance > 0,a.TotalBalance,0)), 10))) as TotalBalance,
		$min(case when a.ExpirationDate > now() then a.ExpirationDate else null end) as ExpirationDate,
		$min(a.ExpirationDate) as MinExpirationDate,
		$min(a.Login) as Login,
		$min(a.Login3) as Login3,
		'Account' as TableName,
		$min(a.UserName) as UserName,
		$min(a.AccountID) as ID,
		$min(a.comment) as comment,
		$min(a.ErrorCode) as ErrorCode,
		$min(a.ErrorMessage) as ErrorMessage,
		$min(a.State) as State,
		$min(a.UserID) as UserID,
		$max(a.AccessLevel) as AccessLevel,
		DATE_FORMAT(DATE($min(a.UpdateDate)),'%M %e, %Y') as UpdateDate,
		$min(a.ExpirationAutoSet) as ExpirationAutoSet,
		$min(a.LastChangeDate) as LastChangeDate,
		$min(a.ChangeCount) as ChangeCount,
		trim(trailing '.' from trim(trailing '0' from round($sum(IF(a.LastBalance > 0, a.LastBalance, 0)), 7))) as LastBalance,
		$min(a.ExpirationWarning) as ExpirationWarning,
		$min(a.Balance) as RawBalance,
		$min(a.SavePassword) as SavePassword,
		$min(a.SubAccounts) as SubAccounts,
		$min(a.UserAgentID) as UserAgentID,
		$min(a.SuccessCheckDate) as SuccessCheckDate,
		$min(a.Login2) as Login2,
		$min(a.Goal) as Goal,
		$min(a.DontTrackExpiration) as DontTrackExpiration,
		" . ((!empty($PrintInFile)) ? "a.AccountID" : "group_concat(a.AccountID) as IDS");

        $this->ProviderAccounts = $this->GetProviderAccounts();

        $sharedDetailFieldList = ImplodeAssoc(" as ", ", ", $fields) . ", " . $this->detailFields(
            "concat( coalesce( ua.FirstName, u.FirstName ), ' ', coalesce( ua.LastName, u.LastName ) )",
            "ash.AccessLevel"
        );

        $myDetailFieldList = ImplodeAssoc(" as ", ", ", $fields) . ", " . $this->detailFields(
            "concat( coalesce( ua.FirstName, u.FirstName ), ' ', coalesce( ua.LastName, u.LastName ) )",
            ACCESS_WRITE
        );

        $sql = " /* TAccountOverviewList::buildSQL */
		select
			$fieldList
		from (
			select
				$sharedDetailFieldList
			from
				Account a
				left outer join Provider p on a.ProviderID = p.ProviderID
				join (
					select UserAgent.UserAgentID, AccountID, AgentID, AccessLevel
					from UserAgent, AccountShare where UserAgent.UserAgentID = AccountShare.UserAgentID
					and UserAgent.AgentID = {$_SESSION['UserID']}
				) ash on a.AccountID = ash.AccountID
				join UserAgent ua on ash.UserAgentID = ua.UserAgentID,
				Usr u
			where
				a.UserID = u.UserID
				and " . userProviderFilter() . "
				{$sFilter}
			union
			select
				$myDetailFieldList
			from
				Account a
				left outer join Provider p on a.ProviderID = p.ProviderID
				left outer join UserAgent ua on a.UserAgentID = ua.UserAgentID,
				Usr u
			where
				a.UserID = u.UserID
				and " . userProviderFilter() . "
				and a.UserID = {$_SESSION['UserID']}
				{$sFilter}
			) a" .
        ((!empty($PrintInFile)) ? "" :
        " group by
			" . implode(", ", $fields)) .
        " {$this->orderby}";

        return $sql;
    }

    public function FormatFields(&$arFields)
    {
        $arFields["Login"] = htmlspecialchars($arFields["Login"]);

        if ($arFields["Accounts"] == "1") {
            $arFields["ExpirationDate"] = $arFields["MinExpirationDate"];
        } else {
            $arFields['ListLink'] = "/account/list.php?UserAgentID=All&ProviderID={$arFields['ProviderID']}";
            $arFields["Login"] = "<a class='multipleAccounts' href='" . htmlspecialchars($arFields['ListLink']) . "'>{$arFields['Accounts']} accounts</a>";
            $arFields['comment'] = '';
        }
    }

    public function DrawPopup($arFields, $subAccountID = null)
    {
        global $Interface;
        $id = $arFields['ID'];

        if (isset($subAccountID)) {
            $id .= "sa" . $subAccountID;
        }
        ?>
		<div id="extRow<?php echo $id; ?>" tableName="<?php echo $arFields['TableName']; ?>" checkInBrowser="<?php echo $arFields['CheckInBrowser']; ?>" providerId="<?php echo $arFields['ProviderID']; ?>" providerCode="<?php echo $arFields['ProviderCode']; ?>" accounts="<?php echo $arFields['Accounts']; ?>" rowId="<?php echo $arFields['ID']; ?>" subAccountId="<?php echo $subAccountID; ?>" style="display: none;" class="rowPopup roundedBox" onmouseover="overRow('<?php echo $id; ?>')" onmouseout="outRow('<?php echo $id; ?>')">Loading..</div>
		<?php
    }

    public function PostFormatFields(&$arFields)
    {
        if ($arFields["Accounts"] > 1) {
            $this->Totals[$arFields['Kind']]["Accounts"] += $arFields["Accounts"] - 1;
        }
    }

    public function getProps($arFields)
    {
        $props = parent::getProps($arFields);

        if ($arFields["Accounts"] > 1) {
            $props->Number = null;
        }

        return $props;
    }

    // obj - reference to TAccountList, if any
    public static function getLastChange($arFields, $curBalance, $subAccountId, &$lastBalance, &$lastChangeDate, &$obj = null)
    {
        $lastBalance = null;
        $lastChangeDate = null;

        if ($arFields['CheckInBrowser'] > 0) {
            return;
        }

        if ($arFields["Accounts"] == 1) {
            $accounts = [$arFields];
        } elseif (isset($obj->ProviderAccounts[$arFields['ProviderID']])) {
            $accounts = $obj->ProviderAccounts[$arFields['ProviderID']];
        } else {
            $accounts = [];
        }

        foreach ($accounts as $fields) {
            if (!isset($subAccountId) && ($fields['LastBalance'] != '')) {
                $lastBalance = $fields['LastBalance'];
                $lastChangeDate = $fields['LastChangeDate'];
            } else {
                $qHistory = new TQuery("select trim(trailing '.' from trim(trailing '0' from round(Balance, 7))) as Balance, UpdateDate
				from AccountBalance
				where AccountID = {$fields['ID']}" . (isset($subAccountId) ? " and SubAccountID = {$subAccountId}" : "") . " and round(Balance, 2) <> " . round($fields['Balance'], 2) . "
				order by UpdateDate desc limit 1");

                if (!$qHistory->EOF) {
                    if (!isset($lastBalance)) {
                        $lastBalance = $qHistory->Fields['Balance'];
                    } else {
                        $lastBalance += $qHistory->Fields['Balance'];
                    }

                    if (!isset($lastChangeDate) || ($qHistory->Fields['UpdateDate'] < $lastChangeDate)) {
                        $lastChangeDate = $qHistory->Fields['UpdateDate'];
                    }
                }

                if (!isset($subAccountId)) {
                    TAccountList::saveChangeDate($fields['ID'], $fields['Balance'], $lastBalance, $lastChangeDate);
                }
            }
        }
    }

    public function GetProgramLink($arFields, $sRedirectURL, $bCanRedirect)
    {
        if ($arFields['Accounts'] == 1) {
            $result = parent::GetProgramLink($arFields, $sRedirectURL, $bCanRedirect);
        } else {
            $result = "<a href='{$arFields['ListLink']}'>{$arFields['DisplayName']}</a>";
        }

        return $result;
    }

    public function TuneEditLinks($arFields, &$arEditLinks)
    {
        global $Interface;

        if ($arFields['Accounts'] > 1) {
            unset($arEditLinks['edit']);
            unset($arEditLinks['delete']);
            $arEditLinks['details'] = "<a class='iconLink detailsLink' href='" . htmlspecialchars($arFields['ListLink']) . "'>" . $Interface->getTitleIconLink('details') . "</a>";

            if (isset($arEditLinks['check'])) {
                $arEditLinks['check'] = "<a data-providerKind='{$arFields['Kind']}' data-ids='{$arFields['IDS']}' class='iconLink checkLink checkAllLink' href='" . htmlspecialchars($arFields['ListLink'] . "&StartCheck=1") . "'>" . $Interface->getTitleIconLink('check all') . "</a>";
            }
        }
    }

    protected function getFirstSort()
    {
        return " a.Corporate desc, ";
    }

    protected function beginRow($arFields)
    {
        if ($arFields['Corporate'] != $this->corporateGroup) {
            $this->corporateGroup = $arFields['Corporate'];

            if ($arFields['Corporate'] == 1) {
                $this->drawGroupCaption('Corporate accounts');
            } else {
                $this->drawGroupCaption('Personal accounts');
            }
        }
    }

    private function detailFields($userName, $accessLevel)
    {
        return "a.AccountID,
		a.comment,
		a.TotalBalance,
		a.ExpirationDate,
		a.Login,
		a.Login3,
		$userName as UserName,
		a.ErrorCode,
		a.ErrorMessage,
		a.State,
		a.UserID,
		$accessLevel as AccessLevel,
		a.UpdateDate,
		a.ExpirationAutoSet,
		a.ChangeCount,
		a.LastBalance,
		a.ExpirationWarning,
		a.Balance,
		a.SavePassword,
		a.SubAccounts,
		a.UserAgentID,
		a.SuccessCheckDate,
		a.Login2,
		a.Goal,
		a.DontTrackExpiration,
		a.LastChangeDate";
    }
}
