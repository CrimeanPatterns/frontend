<?php

use AwardWallet\MainBundle\Parameter\UnremovableUsersParameter;
use AwardWallet\MainBundle\Security\StringSanitizer;

class TUserAdminList extends TBaseAdminUserList
{
    /**
     * @var object|\Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    private $router;
    /**
     * @var bool
     */
    private $canImpersonate;

    /**
     * @var int[]
     */
    private $unremovableUsers;

    public function TUserAdminList($sTable, $arFields, $sDefaultSort)
    {
        parent::__construct($sTable, $arFields, $sDefaultSort);
        $this->Fields['Login']['FilterField'] = 'u.Login';
        $this->router = getSymfonyContainer()->get("router");
        $this->canImpersonate = isGranted('ROLE_MANAGE_IMPERSONATE');
        $this->unremovableUsers = getSymfonyContainer()->get(UnremovableUsersParameter::class)->get();
    }

    public function ProcessAction($action, $ids)
    {
        if ($action == 'delete') {
            $ids = array_diff($ids, $this->unremovableUsers);
        }
        parent::ProcessAction($action, $ids);
    }

    public function FormatFields($output = 'html')
    {
        global $arAccountErrorCode;
        parent::FormatFields();
        $arFields = &$this->Query->Fields;

        if (!isset($arFields['ProvidersCount'])) {
            $q = new TQuery("select count(AccountID) as ProgramsCount,
			count(distinct ProviderID) as ProvidersCount
			from Account
			where UserID = {$arFields['UserID']}");
            $arFields['ProgramsCount'] = $q->Fields['ProgramsCount'];
            $arFields['ProvidersCount'] = $q->Fields['ProvidersCount'];
        }

        if (!isset($arFields['PlansCount'])) {
            $arFields['PlansCount'] = 0;

            foreach (["Trip", "Rental", "Reservation", "Direction"] as $table) {
                $q = new TQuery("select count(*) as Cnt from $table where UserID = {$arFields['UserID']}");
                $arFields['PlansCount'] += $q->Fields['Cnt'];
            }
        }

        if ($this->canImpersonate) {
            $arFields['ProvidersCount'] = "<a target=_blank title='Login as user and view programs' href='/manager/impersonate?UserID={$arFields['UserID']}&Goto=" . urlencode("/account/list.php") . "'>{$arFields['ProgramsCount']}</a>";
        } else {
            $arFields['ProvidersCount'] = $arFields['ProgramsCount'];
        }
        $this->FilterForm->Fields["ProvidersCount"]["filterWidth"] = 20;

        if (isset($this->FilterForm->Fields["ProvidersCount"]["Value"])) {
            $arFields["ProvidersCount"] = sprintf("%d", $arFields['MaxBalance']) . " points";
            $q = new TQuery("select AccountID, round(Balance, 0) as Balance, ErrorCode, SavePassword from Account where UserID = {$arFields['UserID']}
			and ProviderID = {$this->FilterForm->Fields["ProvidersCount"]["Value"]}
			order by Balance desc limit 1");

            if (!$q->EOF) {
                $arLinks = [];

                while (!$q->EOF) {
                    $note = "";

                    if ($q->Fields['ErrorCode'] != ACCOUNT_CHECKED) {
                        $note .= " <span title='" . $arAccountErrorCode[$q->Fields['ErrorCode']] . "'>x</span>";
                    }

                    if ($q->Fields['SavePassword'] != SAVE_PASSWORD_DATABASE) {
                        $note .= " <span title='Password saved locally'>*</span>";
                    }
                    $arLinks[] = "<a title='Request password' href='/manager/passwordVault/requestPassword.php?ID={$q->Fields['AccountID']}'>{$q->Fields['Balance']} points</a>{$note}";
                    $q->Next();
                }
                $arFields["ProvidersCount"] = implode("<br>", $arLinks);
            }
        }

        if ($this->OriginalFields['AccountLevel'] != ACCOUNT_LEVEL_BUSINESS && $this->canImpersonate) {
            $arFields['UserID'] = "<a title='Impersonate' href='/manager/impersonate?UserID={$arFields['UserID']}'>{$arFields['UserID']}</a>";
        }

        if ($this->OriginalFields['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS) {
            $arFields['Login'] = $arFields['Company'];
            $arFields['FirstName'] = '';
            $arFields['LastName'] = '';
            $arFields['Email'] = '';
        }

        $loginMethodsInfo = $this->getLoginMethodsInfo();

        if (count($loginMethodsInfo) > 0) {
            $arFields["Login"] .= "<br/>auth: " . implode(", ", $loginMethodsInfo);
        }

        if (!$this->canImpersonate) {
            $arFields["Email"] = "***";
        }

        if (!empty($this->OriginalFields["ValidMailboxesCount"])) {
            $arFields["ValidMailboxesCount"] = sprintf(
                '<a href="javascript:void(0);" onclick="showMailboxes(%d)" title="View mailboxes">%d</a><div id="mailbox-list-%d"></div>',
                $this->OriginalFields["UserID"],
                $this->OriginalFields["ValidMailboxesCount"],
                $this->OriginalFields["UserID"]
            );
        }

        $arFields['AccountLevel'] = "<a target='_blank' title='See user orders' href='?Schema=AdminCart&UserID={$this->OriginalFields['UserID']}'>{$arFields['AccountLevel']}</a>";

        if (!empty($this->OriginalFields['PlusExpirationDate'])) {
            $date = strtotime($this->OriginalFields['PlusExpirationDate']);

            if (date("Y-m-d", $date) >= date("Y-m-d")) {
                $arFields['AccountLevel'] .= "<div style='white-space: nowrap;' title='Plus Expiration Date'>Exp: " . date("Y-m-d", $date) . "</div>";
            }
        }

        if (!empty($this->OriginalFields['Subscription'])) {
            $subName = ArrayVal(\AwardWallet\MainBundle\Entity\Usr::SUBSCRIPTION_NAMES, $this->OriginalFields['Subscription'], $this->OriginalFields['Subscription']);

            if (!empty($this->OriginalFields['PaypalRecurringProfileID']) && $this->OriginalFields['Subscription'] == \AwardWallet\MainBundle\Entity\Usr::SUBSCRIPTION_PAYPAL) {
                $subName = '<a target=_blank href="' . getSymfonyContainer()->get("router")->generate("aw_manager_billing_agreement", ["id" => $this->OriginalFields['PaypalRecurringProfileID']]) . "\">{$subName}</a>";

                if (!empty($this->OriginalFields['NextBillingDate'])) {
                    $date = strtotime($this->OriginalFields['NextBillingDate']);

                    if (date("Y-m-d", $date) >= date("Y-m-d")) {
                        $subName .= "<br/><span style='white-space: nowrap;' title='Next Billing Date'>Next: " . date("Y-m-d", $date) . "</span>";
                    }
                }

                if (!empty($this->OriginalFields['PaypalSuspendedUntilDate'])) {
                    $date = strtotime($this->OriginalFields['PaypalSuspendedUntilDate']);

                    if (date("Y-m-d", $date) >= date("Y-m-d")) {
                        $subName .= "<br/><span style='white-space: nowrap;' title='Suspended Until'>Suspended: " . date("Y-m-d", $date) . "</span>";
                    }
                }
            }
            $arFields['AccountLevel'] .= "<div style='white-space: nowrap;' title='Subscription'>S: " . $subName . "</div>";
        }

        if (!empty($this->OriginalFields['Fraud'])) {
            $arFields['AccountLevel'] .= "<div style='white-space: nowrap; font-weight: bold; color: red;'>Fraud</div>";
        }

        $arFields['LastUserAgent'] = $this->userBrowser($arFields['LastUserAgent']) . $this->browserExtension($arFields);
        $arFields['EmailVerified'] = substr($arFields['EmailVerified'], 0, 5);

        if (isset($arFields['CreationDateTime'])) {
            $arFields['CreationDateTime'] = date('m/d/Y H:i', strtotime($arFields['CreationDateTime']));
        }
    }

    public function browserExtension($fields)
    {
        $result = "";

        if ($fields['ExtensionVersion'] != '') {
            $result .= "<br/>ext v" . $fields['ExtensionVersion'];

            if ($fields['LastUserAgent'] != $fields['ExtensionBrowser']) {
                $result . " in " . $this->userBrowser($fields['ExtensionBrowser']);
            }

            if ($fields['ExtensionLastUseDate'] != '') {
                $result .= ", last used: " . $fields['ExtensionLastUseDate'];
            }
        }

        return $result;
    }

    public function OpenQuery()
    {
        $orderBy = parent::GetOrderBy();
        $from = "Usr u";
        $fields = "u.UserID,
		u.Login,
		u.FirstName,
		u.LastName,
		u.Email,
		u.Company,
		u.LogonCount,
		u.LastScreenWidth,
		u.CameFrom,
		u.DefaultBookerID,
		u.Referer,
		u.AccountLevel,
		u.Accounts as ProgramsCount,
		u.Providers as ProvidersCount,
		u.SavePassword,
		u.LastUserAgent,
		u.ExtensionVersion,
		u.ExtensionBrowser,
		u.ExtensionLastUseDate,
		u.EmailVerified,
		u.PlusExpirationDate,
		u.NextBillingDate,
		u.PaypalSuspendedUntilDate,
		u.Subscription,
		u.PaypalRecurringProfileID,
		u.Fraud,
		u.CreationDateTime,
		case when u.Pass is not null then 1 else 0 end as PasswordSet,
		u.ValidMailboxesCount";
        $grouped = false;

        if (preg_match("/PlansCount/ims", $orderBy)) {
            $fields .= ", ( count( t.TripID )
			+ count( l.RentalID )
			+ count( r.ReservationID )
			+ count( d.DirectionID )
			+ count( s.RestaurantID ) ) as PlansCount";
            $from .= " left outer join
			Rental l on l.UserID = u.UserID
			left outer join
			Trip t on t.UserID = u.UserID
			left outer join
			Reservation r on r.UserID = u.UserID
			left outer join
			Direction d on d.UserID = u.UserID
			left outer join
			Restaurant s on s.UserID = u.UserID";
            $grouped = true;
        }

        if (ArrayVal($_GET, 'ProvidersCount') != '') {
            $fields .= ", count( a.AccountID ) as ProgramsCount,
			count( distinct a.ProviderID ) as ProvidersCount,
			max( a.Balance ) as MaxBalance";
            $from .= " left outer join Account a on u.UserID = a.UserID";
            $grouped = true;
        } else {
            $this->Fields['ProvidersCount']['Sort'] = 'ProgramsCount DESC';
        }

        $where = $this->getWhere();

        $this->SQL = "select {$fields} from {$from} where {$where} [Filters]";

        if ($grouped) {
            $this->SQL .= " group by u.UserID,
			u.Login,
			u.FirstName,
			u.LastName,
			u.Email,
			u.Company,
			u.LogonCount,
			u.LastScreenWidth,
			u.CameFrom,
			u.DefaultBookerID,
			u.Accounts,
			u.Providers,
			u.SavePassword,
			u.LastUserAgent,
			u.ExtensionVersion,
			u.ExtensionBrowser,
			u.ExtensionLastUseDate,
			u.EmailVerified,
			u.PlusExpirationDate,
			u.NextBillingDate,
			u.PaypalSuspendedUntilDate,
			u.Subscription,
			u.PayPalRecurringProfileID,
			u.Fraud,
			u.Referer,
			u.AccountLevel,
			u.CreationDateTime,
			PasswordSet,
			u.ValidMailboxesCount";
        }
        parent::OpenQuery();
    }

    public function GetFieldFilter($sField, $arField)
    {
        switch ($sField) {
            case "UserID":
                $sField = "u.UserID";

                return parent::GetFieldFilter($sField, $arField);

                break;

            case "ProvidersCount":
                if (isset($arField['Value'])) {
                    return " and a.ProviderID = {$arField['Value']}";
                } else {
                    return "";
                }

                break;

            case "Login":
                if (isset($arField['Value'])) {
                    return " and (u.Login like '%" . addslashes($arField["Value"]) . "%' or u.Company like '%" . addslashes($arField["Value"]) . "%')";
                } else {
                    return "";
                }

                break;

            case "LastUserAgent":
                if (isset($arField['Value'])) {
                    $pattern = "" . addslashes($arField["Value"]) . "(\/| )([0-9.]+)";
                    $result = " and u.LastUserAgent REGEXP('" . $pattern . "')";

                    if (strtolower($arField['Value']) == 'version') {
                        $result .= " AND u.LastUserAgent NOT REGEXP('Opera(\/| )([0-9.]+)')";
                    }

                    return $result;
                } else {
                    return "";
                }

                break;

            default:
                return parent::GetFieldFilter($sField, $arField);
        }
    }

    public function GetOrderBy()
    {
        global $Interface;
        $result = parent::GetOrderBy();

        if (stripos($result, 'order by PlansCount') !== false && $this->GetFilters() == "") {
            $Interface->DiePage("You can not sort by plans without any filters. Select some filters first");
        }

        return $result;
    }

    public function CreateFilterForm()
    {
        parent::CreateFilterForm();
        $this->FilterForm->Fields["ProvidersCount"] = [
            "Type" => "integer",
            "Options" => ["" => "not set"] + SQLToArray("select ProviderID, DisplayName from Provider order by DisplayName", "ProviderID", "DisplayName"),
            "InputAttributes" => "style='width: 50px;'",
        ];
        $this->FilterForm->CompleteFields();
    }

    public function GetEditLinks()
    {
        $links = [];
        $link = parent::GetEditLinks();

        if ($link !== '') {
            $links[] = $link;
        }

        if (isGranted('ROLE_MANAGE_EDIT_USER') || isGranted('ROLE_MANAGE_LIMITED_EDIT_USER') || isGranted('ROLE_MANAGE_USERADMIN')) {
            $links[] = "<a href='/manager/user-view-basic-info/{$this->OriginalFields['UserID']}'>View</a>";
        }

        if (isGranted('ROLE_MANAGE_EDIT_USER') || isGranted('ROLE_MANAGE_LIMITED_EDIT_USER')) {
            $links[] = "<a href='/manager/fraud/{$this->OriginalFields['UserID']}'>Fraud</a>";
        }

        if (isGranted('ROLE_MANAGE_GIVEAWPLUS')) {
            $links[] = "<a href='/manager/give-awplus?UserID={$this->OriginalFields['UserID']}'>Give AW plus</a>";
        }

        if (isGranted("ROLE_MANAGE_DELETE_USER")) {
            $links[] = "<a href='/manager/delete-user?UserID={$this->OriginalFields['UserID']}'>Delete</a>";
        }

        if (isGranted("ROLE_MANAGE_CANCEL_SUBSCRIPTION") && !empty($this->OriginalFields['Subscription'])) {
            $links[] = "<a href='/manager/billing/cancel-subscription/{$this->OriginalFields['UserID']}'>Cancel Subscription</a>";
        }

        if (isGranted('ROLE_MANAGE_LIMITED_EDIT_USER')) {
            $links[] = "<a href='/manager/email-template-test-groups/{$this->OriginalFields['UserID']}'>Test groups</a>";
        }

        $links[] = "<a href='/manager/oneTimeCodes.php?UserID={$this->OriginalFields['UserID']}' title='One time codes'>OTC</a>";

        return implode(" | ", $links);
    }

    public function DrawButtons($closeTable = true)
    {
        parent::DrawButtons(false);

        $showMailboxesRoute = $this->router->generate('aw_enhanced_action', [
            'schema' => 'UserAdmin',
            'action' => 'mailbox-list',
        ]);
        echo "
			<script type=\"text/javascript\">
				function approveToBeta(form) {
					selected = selectedCheckBoxes( form, 'sel' );
					if( selected != \"\" )
						\$(form).append('<input name=\"Selection\" value=\"'+selected+'\">');
					else {
						window.alert('No items selected');
						return false;
					}
					\$(form).attr('action', '/lib/admin/user/emailApproveToBeta.php?BackTo=" . urlencode($_SERVER['SCRIPT_NAME'] . "?" . $_SERVER['QUERY_STRING']) . "').attr('method', 'post').submit();
				}
				function disable2Factor(form, el) {
                    $(el).attr('disabled', 'disabled');
                    var selected = selectedCheckBoxes(form, 'sel');
                    $.ajax({
                        url: '/2factorauth/disableByManager',
                        type: 'post',
                        data: {
                            'selected': selected
                        },
                        success: function() {
                            location.reload();
                        }
                    })
                }  
                function showMailboxes(userId) {
                    $.ajax({
                        url: '$showMailboxesRoute',
                        type: 'get',
                        data: {
                            'userId': userId
                        },
                        success: function(data) {
                            if (!data.mailboxes || data.mailboxes.length === 0) {
                                alert('No mailboxes');
                                return;
                            }
                            
                            var list = $('<ul></ul>');
                            
                            for (var i = 0; i < data.mailboxes.length; i++) {
                                list.append($('<li></li>').text(data.mailboxes[i]));
                            }
                            
                            $('#mailbox-list-' + userId).html(list);
                        }
                    });
                }  	 
			</script>
		";
        echo "<input class='button' type=button value=\"Approve to beta\" onclick=\"approveToBeta(this.form);\"> ";

        if (isGranted("ROLE_STAFF_ASSISTANT")) {
            echo "<input class='button' type=button value=\"Disable 2Factor auth\" onclick=\"disable2Factor(this.form, this);\"> ";
        }

        if ($closeTable) {
            echo "</td></tr></table>";
        }

        if (empty($this->isAlreadyExtend)) {
            $this->isAlreadyExtend = true;
            echo $this->getPriodExtend();
        }
    }

    public function userBrowser($agent)
    {
        $pattern = "/(MSIE|Opera|Firefox|Chrome|Version|Opera Mini|Netscape|Konqueror|SeaMonkey|Camino|Minefield|Iceweasel|K-Meleon|Maxthon)(?:\/| )([0-9.]+)/";
        preg_match($pattern, $agent, $browser_info);

        if (!isset($browser_info[1]) || !isset($browser_info[2])) {
            return htmlspecialchars($agent);
        }
        [, $browser, $version] = $browser_info;

        if (preg_match("/(Opera|OPR)[\/ ]([0-9.]+)/i", $agent, $opera)) {
            return 'Opera ' . $opera[2];
        }

        if ($browser == 'MSIE') {
            preg_match("/(Maxthon|Avant Browser|MyIE2)/i", $agent, $ie);

            if ($ie) {
                return $ie[1] . ' based on IE ' . $version;
            }

            return 'IE ' . $version;
        }

        if ($browser == 'Firefox') {
            preg_match("/(Flock|Navigator|Epiphany)\/([0-9.]+)/", $agent, $ff);

            if ($ff) {
                return $ff[1] . ' ' . $ff[2];
            }
        }

        if ($browser == 'Opera' && $version == '9.80') {
            return 'Opera ' . substr($agent, -5);
        }

        if ($browser == 'Version') {
            return 'Safari ' . $version;
        }

        if (!$browser && strpos($agent, 'Gecko')) {
            return 'Browser based on Gecko';
        }

        return htmlspecialchars($browser) . ' ' . $version;
    }

    private function getLoginMethodsInfo(): array
    {
        $result = [];

        if ($this->OriginalFields["PasswordSet"] == "1") {
            $result[] = "password";
        }

        foreach (new TQuery("select Provider, Email, FirstName, LastName from UserOAuth where UserID = " . (int) $this->OriginalFields['UserID']) as $row) {
            $result[] .= "<span title='{$row['Email']} {$row['FirstName']} {$row['LastName']}'>{$row['Provider']}</span>";
        }

        return $result;
    }

    private function getWhere(): string
    {
        $conn = getSymfonyContainer()->get('database_connection');
        $where = '1';

        if (!empty($_GET['dfrom'])) {
            $where = 'CreationDateTime >= ' . $conn->quote($_GET['dfrom'] . ' 00:00:00');

            if (!empty($_GET['dto'])) {
                $where = 'CreationDateTime BETWEEN ' . $conn->quote($_GET['dfrom'] . ' 00:00') . ' AND ' . $conn->quote($_GET['dto'] . ' 23:59:59');
            }
        } elseif (!empty($_GET['dto'])) {
            $where = 'CreationDateTime <= ' . $conn->quote($_GET['dto'] . ' 23:59:59');
        }

        return $where;
    }

    private function getPriodExtend()
    {
        $where = $this->getWhere();
        $countUsersByWhere = '1' != $where
            ? getSymfonyContainer()->get('database_connection')->fetchOne('select count(*) from Usr where ' . $where)
            : null;

        $extend = '
        <div style="float:left;padding:10px 0 0 10px;min-height: 40px;">
            <form id="extForm" method="get" action="/manager/list.php" class="qs-form">
            <input type="hidden" name="Schema" value="UserAdmin">
                <div class="qs-filter-date" style="padding: 5px 0;">
                    <div style="width: 700px;">
                        Creation Date:
                            from <input type="date" name="dfrom" value="' . StringSanitizer::encodeHtmlEntities($_GET['dfrom'] ?? '') . '" title="date from">
                            to <input type="date" name="dto" value="' . StringSanitizer::encodeHtmlEntities($_GET['dto'] ?? '') . '" title="date to"> or 
                        <select>
                            <option value="">Choose Period</option>
                            ' . $this->getPeriodOptions() . '
                        </select>
                        <button type="submit" style="position: relative;margin: 0 15px;padding: 0 10px;"> Apply </button>
                        <a href="#reset-period" onclick="$(\'#extForm input[type=date]\').val(\'\')" style="float: right;margin-top:3px;">(reset period)</a>
                        ' . (null !== $countUsersByWhere ? '<br><b>Count: ' . $countUsersByWhere . '</b>' : '') . '
                    </div>
                </div>
            </form>
        </div>';
        $scripts = '
            $("#extendFixedMenu ").prepend("' . addslashes(str_replace("\n", '', $extend)) . '");
            $("select", "#extForm").change(function() {
                var dates = $(this).val().split("=");
                $(\'input[name="dfrom"]\', "#extForm").val(dates[0]);
                if (undefined !== dates[1])
                    $(\'input[name="dto"]\', "#extForm").val(dates[1]);
            });
        ';

        $style = '
        <style type="text/css">
            .qs-form label {cursor: pointer}
            #contentBody {margin-top: 80px !important;}
        </style>
        ';
        $scripts .= '
            $(document.body).append("' . addslashes(str_replace("\n", '', $style)) . '");
        ';

        return '<script>' . $scripts . '</script>';
    }

    private function getPeriodOptions()
    {
        $periods = [
            [
                'title' => 'Yesterday',
                'start' => date('Y-m-d', strtotime('yesterday')),
                'end' => date('Y-m-d', strtotime('yesterday')),
            ],
            [
                'title' => 'Current Week',
                'start' => date('Y-m-d', strtotime('monday this week')),
                'end' => date('Y-m-d', strtotime('sunday this week')),
            ],
            [
                'title' => 'Last Week',
                'start' => date('Y-m-d', strtotime('monday last week')),
                'end' => date('Y-m-d', strtotime('sunday last week')),
            ],
            [
                'title' => 'Current Month',
                'start' => date('Y-m-d', strtotime('first day of this month')),
                'end' => date('Y-m-d', strtotime('last day of this month')),
            ],
            [
                'title' => 'Last Month',
                'start' => date('Y-m-d', strtotime('first day of last month')),
                'end' => date('Y-m-d', strtotime('last day of last month')),
            ],
            [
                'title' => '3 Months Ago',
                'start' => date('Y-m-d', strtotime('first day of 3 month ago')),
                'end' => date('Y-m-d', strtotime('last day of 3 month ago')),
            ],
            [
                'title' => 'Last 6 Months',
                'start' => date('Y-m-d', strtotime('first day of 6 month ago')),
                'end' => date('Y-m-d', strtotime('last day of last month')),
            ],
            [
                'title' => 'Current Year',
                'start' => date('Y-01-01'),
                'end' => date('Y-m-d'),
            ],
        ];

        $options = '';

        foreach ($periods as $period) {
            $start = $period['start'];
            $end = $period['end'];
            $options .= '<option value="' . $start . '=' . $end . '">' . $period['title'] . '</option>';
        }

        return $options;
    }
}
