<?php

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\MainBundle\Service\OneCard\OneCardMapper;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

require "../kernel/public.php";

require_once $sPath . "/kernel/TForm.php";

require_once $sPath . "/kernel/TAccountInfo.php";

require_once $sPath . "/lib/cart/public.php";

require_once $sPath . "/account/common.php";

require_once $sPath . "/lib/cart/public.php";

define('LINES_PER_CARD', 15);
global $newOrder;
AuthorizeUser();

if (isGranted("SITE_ND_SWITCH") && SITE_MODE == SITE_MODE_BUSINESS) {
    Redirect("/");
}

// begin determining menus
require $sPath . "/design/topMenu/main.php";

require $sPath . "/design/leftMenu/award.php";

NDSkin::setLayout("@AwardWalletMain/Layout/onecard.html.twig");

if (isset($_GET['cartID'])) {
    getSymfonyContainer()->get("aw.widget.onecard_menu")->setActiveItem('history');
} else {
    getSymfonyContainer()->get("aw.widget.onecard_menu")->setActiveItem('order');
}

if (isGranted("SITE_ND_SWITCH")) {
    $redirectUrl = getSymfonyContainer()->get('router')->generate('aw_one_card');
} else {
    $redirectUrl = "/onecard/";
}

if (SITE_MODE == SITE_MODE_PERSONAL) {
    $topMenu['My Balances']['selected'] = false;
}
$topMenu['OneCard']['selected'] = true;
unset($othersMenu);
$leftMenu = [
    'Place New Order' => [
        'caption' => 'Place New Order',
        'path' => '/onecard',
        'selected' => false,
    ],
    'Order History' => [
        'caption' => 'Order History',
        'path' => '/onecard/history.php',
        'selected' => false,
    ],
];
// end determining menus

$sTitle = "Awardwallet OneCard";
$bSecuredPage = false;

require "$sPath/design/header.php";

require "common.php";
?>
<link rel="stylesheet" type="text/css" href="/design/onecard.css?v=<?php echo FILE_VERSION; ?>"/>
<?php
// direct link

// adapter for $_GET request
if ((isset($_GET['cartID'])) && (isset($_GET['userAgentID']))) {
    $cartID = intval($_GET['cartID']);
    $q = new TQuery("SELECT UserID
                     FROM OneCard
                     WHERE CartID = {$cartID}");

    if ($q->EOF || ($q->Fields['UserID'] != $_SESSION['UserID'])) {
        Redirect($redirectUrl);
    } else {
        $_POST['cartID'] = $cartID;
        $_POST['readOnly'] = 1;
        $_POST['userAgentID'] = intval($_GET['userAgentID']);
    }
}

// read data
if (isset($_POST['otherUsers']) && is_array($_POST['otherUsers'])) {
    $otherUsers = $_POST['otherUsers'];
} else {
    $otherUsers = [];
}

if (isset($_POST['readOnly']) && ($_POST['readOnly'] == 1)) {
    $readOnly = true;
} else {
    $readOnly = false;
}

if (isset($_POST['cartID']) && isset($_POST['userAgentID'])) {
    $oneCardCnt = getOneCardsCount($_SESSION['UserID']);

    if (($oneCardCnt['Left'] < 1) && (!$readOnly)) {
        Redirect($redirectUrl);
    }
    $newOrder = false;

    $cartID = intval($_POST['cartID']);
    $userAgentID = intval($_POST['userAgentID']);
    $q = new TQuery("SELECT *
						FROM OneCard
						WHERE CartID = {$cartID}
						AND UserID = {$_SESSION['UserID']}
						AND UserAgentID = {$userAgentID}");

    $otherUsers[] = $q->Fields['UserAgentID'];
    $fullName = $q->Fields['FullName'];
} else {
    $newOrder = true;
}

if (isset($_POST['browserData'])) {
    $browserData = json_decode($_POST['browserData'], true);
} else {
    $browserData = [];
}

$users = [];

if (SITE_MODE == SITE_MODE_PERSONAL) {
    if (in_array("0_0", $otherUsers) || in_array("0", $otherUsers)) {
        if ($newOrder) {
            $users["0"] = ucwords("{$_SESSION['FirstName']} {$_SESSION['LastName']}");
        } else {
            $users["0"] = $fullName;
        }
    }
}

$q = new TQuery(OtherUsersSQL());
$otherUserCount = 0;

while (!$q->EOF) {
    $otherUserCount++;

    if (in_array($q->Fields['UserAgentID'], $otherUsers) || in_array($q->Fields['UserAgentID'] . "_0", $otherUsers)) {
        if ($newOrder) {
            $users[$q->Fields['UserAgentID']] = ucwords($q->Fields['UserName']);
        } else {
            $users[$q->Fields['UserAgentID']] = $fullName;
        }
    }
    $q->Next();
}

// save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['Step']) && isValidFormToken()) {
    requirePasswordAccess();
    $rows = [];
    $modifications = "";
    $usersSaved = [];

    foreach ($users as $userAgentId => $name) {
        if (!in_array($userAgentId, $usersSaved)) {
            $usersSaved[] = $userAgentId;
        }

        if (count($usersSaved) > $oneCardCnt['Left']) {
            Redirect($redirectUrl);
        }
        $accounts = getAgentAccounts($userAgentId, $total, true);
        $totalCards = ceil(count($accounts) / (LINES_PER_CARD * 2));
        $userRows = [];

        for ($index = 0; $index < $totalCards; $index++) {
            $prefix = $userAgentId . "_" . $index;
            $row = [];
            $data = [];

            if (trim(ArrayVal($_POST, "PFront_" . $prefix)) == '') {
                continue;
            }

            foreach (['AccFront', 'PFront', 'AFront', 'SFront', 'PhFront', 'AccBack', 'PBack', 'ABack', 'SBack', 'PhBack'] as $field) {
                $postField = $field . "_" . $prefix;

                if (!isset($_POST[$postField])) {
                    DieTrace("Invalid request, missing field $postField");
                }
                $postValue = strip_tags($_POST[$postField]);

                if (strlen($postValue) > (1000 * LINES_PER_CARD)) {
                    DieTrace("Field $postField, is too long, size " . strlen($_POST[$field]));
                }
                $values = explode("\n", $postValue);

                if (count($values) > LINES_PER_CARD) {
                    DieTrace("Invalid values count in $postField, expected " . LINES_PER_CARD . ", got " . count($values));
                }

                while (count($values) < LINES_PER_CARD) {
                    $values[] = "";
                }
                $data[$field] = $values;
                $row[$field] = "'" . addslashes(htmlspecialchars_decode(implode("\n", $values))) . "'";
            }
            $modifications .= checkModifications($data);
            $fullName = ArrayVal($_POST, 'FullName_' . $prefix);

            if ($fullName == '') {
                DieTrace("Invalid request, missing FullName");
            }
            $total = ArrayVal($_POST, 'Total_' . $prefix);

            if ($total == '') {
                DieTrace("Invalid request, missing Total");
            }
            $total = round($total);
            $row['UserID'] = $_SESSION['UserID'];
            $row['UserAgentID'] = $userAgentId;
            $row['State'] = ONECARD_STATE_NEW;
            $row['OrderDate'] = 'now()';
            $row['FullName'] = "'" . addslashes($fullName) . "'";
            $row['TxtDate'] = "'" . addslashes(date(DATE_SHORT_FORMAT)) . "'";
            $row['TotalMiles'] = "'" . addslashes(number_format_localized($total, 0)) . "'";
            $row['TotalMilesNum'] = $total;
            $row['Track1'] = "'B4111111111111111^" . addslashes($fullName) . "^131210100000000000000000000000000000000'";
            $userRows[] = $row;
        }

        if (count($userRows) > 1) {
            foreach ($userRows as $index => $row) {
                $userRows[$index]['CardIndex'] = "'(" . ($index + 1) . " of " . count($userRows) . ")'";
            }
        }
        $rows = array_merge($rows, $userRows);
    }

    if (count($rows) == 0) {
        Redirect($redirectUrl);
    }
    $_SESSION['OrderedOneCards'] = $rows;
    $objCart->Clear();
    $itemTitle = "AwardWallet OneCard Credit" . s(count($usersSaved));

    if (count($rows) != count($usersSaved)) {
        $itemTitle .= "<br/>(total cards ordered: " . count($rows) . ")";
    }
    $objCart->Add(CART_ITEM_ONE_CARD_SHIPPING, 0, $_SESSION['UserID'], $itemTitle, count($usersSaved), 0, null, count($rows), "");
    Redirect("/lib/cart/selectShipping.php");
}

/**
 * check that user modified some data, mail changes to us.
 *
 * @param $data array
 * @return text of changes, or empty string
 */
function checkModifications($data)
{
    $changes = "";

    foreach (['Front', 'Back'] as $side) {
        foreach ($data['Acc' . $side] as $row => $accountId) {
            if ($accountId > 0) {
                $accountId = intval($accountId);
                $providerId = Lookup("Account", "AccountID", "ProviderID", $accountId, true);

                if (!empty($providerId)) {
                    $provider = $data['P' . $side][$row];
                    $level = showAccountLevel($accountId);
                    $phone = trim($data['Ph' . $side][$row]);
                    $phones = [];

                    if ($level != "") {
                        $phones = getLevelPhones($level, $providerId);
                    }
                    $phones += getProviderPhones($providerId);
                    $tmp = [];

                    foreach ($phones as $phoneRow) {
                        $tmp[] = $phoneRow['Phone'];
                    }
                    $phones = $tmp;
                    $values = [
                        'Account number' => [
                            'old' => showAccountNumber($accountId),
                            'new' => $data['A' . $side][$row],
                        ],
                        'Level' => [
                            'old' => $level,
                            'new' => $data['S' . $side][$row],
                        ],
                    ];

                    foreach ($values as $caption => $value) {
                        if (trim($value['old']) != trim($value['new'])) {
                            $changes .= $provider . " " . $caption . ": " . $value['old'] . " -> " . $value['new'] . "\n";
                        }
                    }

                    if (($phone != '') && !in_array($phone, $phones)) {
                        $changes .= $provider . " Phone" . ($level != "" ? " (status: $level)" : "(no status)") . ": " . implode(", ", $phones) . " -> " . $phone . "\n";
                    }
                }
            }
        }
    }

    return $changes;
}

function showStartTable($side, $userAgentID)
{
    echo '<div class = "contentTable"><table rel="' . $userAgentID . '" class = "accountsTable ' . $side . 'Side" style="background: none !important;">
		<colgroup>
			<col class = "selectAccount">
			<col class = "numberAccount">
			<col class = "statusAccount">
			<col class = "phoneAccount">
		</colgroup>
		<tbody>';
}

function showEndTable()
{
    echo '</tbody>
	</table></div>';
}

function showRow($providerName, $accountId, $number, $level, $phone, $priority, $userAgentId, $readOnly)
{
    if (isset($readOnly) && ($readOnly == true)) {
        $readOnly = "readonly";
    } else {
        $readOnly = "";
    }
    echo "<tr class='accountRow' data-priority='{$priority}' data-userAgentId='{$userAgentId}'>";

    if (!$readOnly) {
        echo "<td class = \"selectField\">
			<p class = \"title\">{$providerName}</p>
			<p class = \"accountSelected\">
				<input class='account' type='hidden' value='$accountId'/>
			</p>
			<input class='accountText' {$readOnly} type='text' value='' style='display: none;'/>
			<a class='do-empty-row' href='#'>&times;</a>
		</td>";
    } else {
        echo "<td class = \"selectField\"><input class='accountText' type='text' readonly value='$providerName'/></td>";
    }
    echo "<td><input class='number' {$readOnly} value='$number' maxlength='19'/></td>";
    echo "<td><input class='level' {$readOnly} value='$level' maxlength='16'/></td>";
    echo "<td class='phoneCell'><table border='0' cellspacing='0' cellpadding='0' style='background: none !important;'><tr><td><input class='phone' {$readOnly} value='$phone' maxlength='15'/></td><td>" . ($readOnly == "" ? "<div class='dropButton'></div>" : "") . "</td></tr></table></td>";
    echo "</tr>";
}

function showAccountRow($fields, $accounts, $readOnly)
{
    showRow(
        $fields['ProviderName'],
        $fields['ID'],
        $fields['Number'],
        $fields['Level'],
        $fields['Phone'],
        $fields['Priority'],
        $fields['UserAgentID'],
        $readOnly
    );
}

function showEmptyRow($accounts, $readOnly)
{
    showRow(
        "",
        "",
        "",
        "",
        "",
        999999,
        0,
        $readOnly
    );
}

function showAccountSelect($accounts, $selectedID)
{
    $select = "<select id='accountSelect' style='display: none;background: none !important;'>";

    foreach ($accounts as $fields) {
        $level = showAccountLevel($fields['ID']);
        $select .= "<option value = \"{$fields['ID']}\"
		data-userAgentId=\"{$fields['UserAgentID']}\"
		data-userName=\"{$fields['UserName']}\"
		data-accountId=\"{$fields['ID']}\"
		data-priority=\"{$fields['Priority']}\"
		data-level='" . addslashes($level) . "'
		data-number='" . addslashes(showAccountNumber($fields['ID'])) . "'
		data-phone='" . (intval($fields['ID']) > 0 ? addslashes(TAccountInfo::getSupportPhone($fields, $level, [])) : '') . "'
		data-provider='" . addslashes($fields['ProviderName']) . "'";

        if ($fields['ID'] == $selectedID) {
            $select .= " selected = \"selected\"";
        }
        $select .= ">{$fields['AccountName']}</option>";
    }
    $select .= "</select>";

    return $select;
}

function showTotal($userAgentId, $name, $total, $index, $totalCards)
{
    echo "<div align=\"center\" class = \"totalDiv\"><span id='fullName_{$userAgentId}'>" . $name . ",</span> <span class='formattedTotal'>" . addslashes(number_format_localized($total, 0)) . "</span> miles, " . date(DATE_SHORT_FORMAT);

    if ($totalCards > 1) {
        echo " (" . ($index + 1) . " of $totalCards)";
    }
    echo "<input type=\"hidden\" name=\"Total_{$userAgentId}\" value=\"{$total}\"/>";
    echo "</div>";
}

function showFrontSide($userAgentId, $userName, $accounts, $total, $sortedAccounts, $index, $totalCards, $readOnly)
{
    global $newOrder;
    showStartTable('front', $userAgentId);
    $rows = array_slice($accounts, 0, LINES_PER_CARD, true);
    $rowsShown = count($rows);

    if ((count($rows) < LINES_PER_CARD) && ($totalCards == 1) && $newOrder) {
        showRow(
            'Loyalty Program',
            '-1',
            'Account #',
            'Status',
            'Phone',
            '-1',
            '-1',
            $readOnly
        );
        $rowsShown++;
    }

    foreach ($rows as $fields) {
        showAccountRow($fields, $sortedAccounts, $readOnly);
    }

    for ($n = $rowsShown; $n < LINES_PER_CARD; $n++) {
        showEmptyRow($sortedAccounts, $readOnly);
    }
    showEndTable();
    showTotal($userAgentId, $userName, $total, $index, $totalCards);
}

function showBackSide($userAgentId, $accounts, $sortedAccounts, $readOnly)
{
    echo "<div class = \"cardOffset\"></div>";
    showStartTable('back', $userAgentId);
    $rows = array_slice($accounts, LINES_PER_CARD, LINES_PER_CARD, true);

    foreach ($rows as $fields) {
        showAccountRow($fields, $sortedAccounts, $readOnly);
    }

    for ($n = count($rows); $n < LINES_PER_CARD; $n++) {
        showEmptyRow($sortedAccounts, $readOnly);
    }
    showEndTable();
}

function showHeaderCaption($userName, $icons = null)
{
    echo "<tr class = \"header\">
			<td class = \"segmentHeader\">
				<div class=\"caption captionPad\">
				$icons
				<a class=\"arrow\"></a>

				<div class=\"name\">$userName</div>
				<div class=\"clear\"></div>
			</div>
			</td>
		</tr>";
}

function showFullNameInput($userAgentId, $name, $readOnly)
{
    if (isset($readOnly) && ($readOnly == true)) {
        $readOnly = "readonly";
    } else {
        $readonly = "";
    }
    echo "	</div>
			<div class= \"fullName\">
				<label>Full Name: *</label>
				<table cellspacing='0' cellpadding='0' class='inputFrame'>
					<tbody>
						<tr>
							<td class='ifLeft'></td>
							<td class='ifCenter'><input name='FullName_{$userAgentId}' {$readOnly} type='text' style='width: 300px;' class='fullName' value='" . htmlspecialchars($name) . "' onchange='ChangeFullName(this.value, \"{$userAgentId}\")'></td>
							<td class='ifRight'></td>
						</tr>
					</tbody>
				</table>
				<a href=\"#\" class = \"cardWarning\"><div class = \"iNotify\"></div></a>
				<div class='clear'></div>
			</div>";
}

function showHiddens($userAgentId)
{
    echo '<input type="hidden" name="otherUsers[]" value="' . $userAgentId . '">
	<input type="hidden" name="AccFront_' . $userAgentId . '">
	<input type="hidden" name="PFront_' . $userAgentId . '">
	<input type="hidden" name="AFront_' . $userAgentId . '">
	<input type="hidden" name="SFront_' . $userAgentId . '">
	<input type="hidden" name="PhFront_' . $userAgentId . '">
	<input type="hidden" name="AccBack_' . $userAgentId . '">
	<input type="hidden" name="PBack_' . $userAgentId . '">
	<input type="hidden" name="ABack_' . $userAgentId . '">
	<input type="hidden" name="SBack_' . $userAgentId . '">
	<input type="hidden" name="PhBack_' . $userAgentId . '">';
}

function showCard($userAgentId, $index, $totalCards, $accounts, $name, $allAccounts, $total, $readOnly)
{
    $s = $name;

    if ($totalCards > 1) {
        $s .= " <span class='cardIndex' data-user='{$userAgentId}'>(" . ($index + 1) . " of {$totalCards})</span>";
    }
    showHeaderCaption($s, '<a class="deleteLink" href="#" data-user="' . $userAgentId . '" style="display: none;"></a>');
    $prefix = $userAgentId . '_' . $index;
    echo "<tr>
			<td>
				<div class='card_{$prefix} card' data-user='{$userAgentId}'>
					<div class = \"sideCaption\">Front Side:<br/>
					" . (!$readOnly ? "<b>(click each field in the card to modify the text, double-click on the name of the program to change it)</b>" : "") . "</div>
					<div class=\"FronSideDiv" . (($total >= 1000000) ? "Gold" : "Silver") . "\">
						<div align=\"center\" class = \"logoContainer\"><div class=\"AwCardLogo\"  ></div></div>";
    showFrontSide($prefix, $name, $accounts, $total, $allAccounts, $index, $totalCards, $readOnly);
    echo "</div>
					<div class = \"sideCaption\">Back Side:</div>
					<div class=\"BackSideDiv" . (($total >= 1000000) ? "Gold" : "Silver") . "\">";
    showBackSide($prefix, $accounts, $allAccounts, $readOnly);
    echo "</div>
				</div>";
    showFullNameInput($prefix, $name, $readOnly);
    showHiddens($prefix);
    echo "</td>
	</tr>";
}

function showUserCards($userAgentId, $name, $total, $accounts, $allAccounts, $readOnly)
{
    global $newOrder;
    $totalCards = ceil(count($accounts) / (LINES_PER_CARD * 2));

    for ($n = 0; $n < $totalCards; $n++) {
        $slice = array_slice($accounts, $n * LINES_PER_CARD * 2, LINES_PER_CARD * 2, true);

        if ($newOrder) {
            uasort($slice, function ($a, $b) {
                return strcasecmp($a['AccountName'], $b['AccountName']);
            });
        }
        showCard($userAgentId, $n, $totalCards, $slice, $name, $allAccounts, $total, $readOnly);
    }
}

function drawPopupMessage()
{
    global $Interface;
    $Interface->DrawBeginBox('id="funcPopup"', null, true, 'popupWindow'); ?>
		<div style="padding: 20px;">
			The name that you specify will be written onto the magnetic strip of the card. This way you will be able to use your card at airport kiosks to check in for your flights. Please make sure your name is spelled correctly. <!-- /*checked*/"-->
		</div>
	<?php
    $Interface->DrawEndBox();
}

function getAgentAccounts($userAgentId, &$totalBalance, $showNames, $allowEmpty = true)
{
    global $newOrder;

    $em = getSymfonyContainer()->get('doctrine.orm.default_entity_manager');
    $user = $em->getRepository(Usr::class)->find($_SESSION['UserID']);
    $rows = [];

    if ($newOrder || ($userAgentId === 'All')) {
        /** @var AccountListManager $listManager */
        $listManager = getSymfonyContainer()->get(AccountListManager::class);
        /** @var OneCardMapper $mapper */
        $mapper = getSymfonyContainer()->get(OneCardMapper::class);
        /** @var OptionsFactory $optionsFactory */
        $optionsFactory = getSymfonyContainer()->get(OptionsFactory::class);
        $options = $optionsFactory
            ->createDefaultOptions()
            ->set(Options::OPTION_USER, $user)
            ->set(Options::OPTION_COUPON_FILTER, ' AND 0 = 1')
            ->set(Options::OPTION_FORMATTER, $mapper);

        if ($userAgentId !== 'All') {
            $options->set(Options::OPTION_USERAGENT, (int) $userAgentId);
        }

        $accountList = $listManager->getAccountList($options);
        $rows = it($accountList->getAccounts())
            ->mapIndexed(function (array $fields) {
                $login = $fields['LoginFieldLast'] ?? $fields['LoginFieldFirst'];

                if (strpos($login, '@') === false) {
                    $fields['SortEmail'] = 1;
                } else {
                    $fields['SortEmail'] = 2;
                }

                if (in_array(intval($fields['Kind']), [PROVIDER_KIND_AIRLINE, PROVIDER_KIND_HOTEL, PROVIDER_KIND_CAR_RENTAL, PROVIDER_KIND_TRAIN])) {
                    $fields['SortKind'] = 1;
                } else {
                    $fields['SortKind'] = 2;
                }

                if ($fields['Balance'] > 0) {
                    $fields['SortBalance'] = 1;
                } else {
                    $fields['SortBalance'] = 2;
                }

                return $fields;
            })
            ->usort(function (array $a, array $b) {
                foreach ([
                    'SortEmail' => true,
                    'SortKind' => true,
                    'ChangeCount' => false,
                    'SortBalance' => true,
                    'ProviderName' => true,
                ] as $field => $asc) {
                    if ($a[$field] == $b[$field]) {
                        continue;
                    }

                    if ($a[$field] < $b[$field]) {
                        $result = -1;
                    } else {
                        $result = 1;
                    }

                    if (!$asc) {
                        $result = $result * -1;
                    }

                    return $result;
                }

                return 0;
            })->toArrayWithKeys();
        $totalBalance = floatval(0);
    } else {
        GetAgentFilters($_SESSION['UserID'], "All", $accountFilter, $couponFilter);
        getAccountsFromOrder($accountFilterFromOrder, $providerNamesFromOrder, $accountNumbersFromOrder, $statusesFromOrder, $phonesFromOrder, $totalBalance);
        $accountsIds = [];
        $providersIds = [];

        foreach ($accountFilterFromOrder as $accountID) {
            if (!empty($accountID)) {
                $accountsIds[] = $accountID;
            }
        }

        if (sizeof($accountsIds)) {
            $q = new TQuery("SELECT AccountID, ProviderID FROM Account WHERE AccountID IN (" . implode(", ", $accountsIds) . ")");

            foreach ($q as $accRow) {
                $providersIds[$accRow['AccountID']] = $accRow['ProviderID'];
            }
        }

        foreach ($accountFilterFromOrder as $key => $accountID) {
            if (($accountID != "") || ($accountNumbersFromOrder[$key] != "") || ($statusesFromOrder[$key] != "") || ($phonesFromOrder[$key] != "")) {
                /* if ($accountID != ""){
                     $accountFilter = "a.AccountID = {$accountID}";
                     $sql = AccountsSQL($_SESSION['UserID'], $accountFilter, "0 = 1", "", "", "All");
                     $rows = array_merge($rows, loadAccountQuery($sql));
                 */
                $rows[] = ['ID' => ($accountID != '' ? $accountID : "-2"), 'UserAgentID' => '0', 	'UserName' => '',
                    'AccountName' => "", "Priority" => "", "ProviderName" => "$providerNamesFromOrder[$key]", "ProviderCode" => "", "ProviderID" => ($providersIds[$accountID] ?? ""), ];
            }
        }
        $totalBalance = floatval($totalBalance);
    }
    $accounts = [];
    $duplicates = [];
    $conn = getSymfonyContainer()->get('doctrine')->getConnection();

    foreach ($rows as $fields) {
        if (isset($fields['SubAccounts']) && (int) $fields['SubAccounts'] > 0) {
            // exclude BalanceInTotalSum
            $subAccounts = $conn->fetchAll('
	            select sa.Balance
	            from SubAccount sa
                join AccountProperty ap on (sa.SubAccountID = ap.SubAccountID)
                where
                        sa.AccountID = ' . ((int) $fields['ID']) . '
                    and ap.ProviderPropertyID = 5174
	        ');

            foreach ($subAccounts as $subAccount) {
                $subAccount['Balance'] = (float) $subAccount['Balance'];

                if (!empty($subAccount['Balance'])) {
                    $fields['TotalBalance'] -= $subAccount['Balance'];
                }
            }
        }

        if ((!$allowEmpty) && $newOrder) {
            $level = showAccountLevel($fields['ID']);
            $number = showAccountNumber($fields['ID']);
            $phone = TAccountInfo::getSupportPhone($fields, $level, []);

            if (empty($level) && empty($number) && empty($phone)) {
                continue;
            }

            if (preg_match(EMAIL_REGEXP, $number)) {
                continue;
            }
        }
        $fields['AccountName'] = $fields['ProviderName'];

        if (($fields['ProviderID'] == '') && (mb_strlen($fields['ProviderName']) > 21)) {
            $fields['ProviderName'] = mb_substr($fields['ProviderName'], 0, 21) . ".";
        }
        $accounts[] = $fields;

        if ($newOrder || ($userAgentId === "All")) {
            $totalBalance += round($fields['TotalBalance']);
        }

        if (!isset($duplicates[$fields['ProviderName']])) {
            $duplicates[$fields['ProviderName']] = 1;
        } else {
            $duplicates[$fields['ProviderName']]++;
        }
    }
    // correct duplicates with account numbers
    $duplicates = array_keys(array_filter($duplicates, function ($count) {
        return $count > 1;
    }));
    $pos = 0;

    foreach ($accounts as &$fields) {
        $fields['Priority'] = $pos;

        if (in_array($fields['ProviderName'], $duplicates)) {
            $number = showAccountNumber($fields['ID']);
            $fields['AccountName'] = $fields['ProviderName'] . ' (' . $number . ($showNames ? ', ' . $fields['UserName'] : '') . ')';
            $fields['Duplicate'] = true;
        }

        if ($newOrder || ($userAgentId === "All")) {
            $fields['Level'] = showAccountLevel($fields['ID']);
            $fields['Number'] = showAccountNumber($fields['ID']);
            $fields['Phone'] = TAccountInfo::getSupportPhone($fields, $fields['Level'], []);
        } else {
            $fields['Level'] = $statusesFromOrder[$pos];
            $fields['Number'] = $accountNumbersFromOrder[$pos];
            $fields['Phone'] = $phonesFromOrder[$pos];
        }
        $pos++;
    }

    return $accounts;
}

function getAccountsFromOrder(&$accountFilterFromOrder, &$providerNamesFromOrder, &$accountNumbersFromOrder, &$statusesFromOrder, &$phonesFromOrder, &$totalBalance)
{
    $accountFilterFromOrder = [];
    $providerNamesFromOrder = [];
    $accountNumbersFromOrder = [];
    $statusesFromOrder = [];
    $phonesFromOrder = [];

    $cartID = intval($_POST['cartID']);
    $userAgentID = intval($_POST['userAgentID']);
    $q = new TQuery("SELECT *
						FROM OneCard
						WHERE CartID = " . addslashes($cartID) . "
						AND UserID = " . addslashes($_SESSION['UserID']) . "
						AND UserAgentID = '" . addslashes($userAgentID) . "'");

    while (!$q->EOF) {
        $accountFilterFromOrder = array_merge($accountFilterFromOrder, explode("\r\n", $q->Fields['AccFront']));
        $accountFilterFromOrder = array_merge($accountFilterFromOrder, explode("\r\n", $q->Fields['AccBack']));

        $providerNamesFromOrder = array_merge($providerNamesFromOrder, explode("\r\n", $q->Fields['PFront']));
        $providerNamesFromOrder = array_merge($providerNamesFromOrder, explode("\r\n", $q->Fields['PBack']));

        $accountNumbersFromOrder = array_merge($accountNumbersFromOrder, explode("\r\n", $q->Fields['AFront']));
        $accountNumbersFromOrder = array_merge($accountNumbersFromOrder, explode("\r\n", $q->Fields['ABack']));

        $statusesFromOrder = array_merge($statusesFromOrder, explode("\r\n", $q->Fields['SFront']));
        $statusesFromOrder = array_merge($statusesFromOrder, explode("\r\n", $q->Fields['SBack']));

        $phonesFromOrder = array_merge($phonesFromOrder, explode("\r\n", $q->Fields['PhFront']));
        $phonesFromOrder = array_merge($phonesFromOrder, explode("\r\n", $q->Fields['PhBack']));

        $totalBalance = $q->Fields["TotalMilesNum"];

        $q->Next();
    }
}

function showOptions($multipleCards, $users, $accounts, $sort, $country)
{
    showHeaderCaption("Options");
    echo "
	<tr>
		<td>";

    if ($multipleCards) {
        $options = [
            'alphabet' => 'Sort accounts alphabetically across all cards',
            'priority' => 'Group the most used accounts on the same card',
        ];
        echo "
				<div class='sideCaption'>";

        foreach ($options as $key => $value) {
            echo "<input type='radio' name='Sort' value='{$key}' id='rb_{$key}'";

            if ($key == $sort) {
                echo " checked";
            }
            echo " onclick='sortAccounts()'> <label for='rb_{$key}'>{$value}</label><br/>";
        }
        echo "
				</div>";
    }
    $countries = [
        "" => "AwardWallet's best guess",
    ] + GetCountryOptions($nUSA, $nCanada);
    echo "
			<div class='sideCaption'>
				If available use phone numbers from:
				<select name='Country' onchange='countryChanged()'>";
    DrawArrayOptions($countries, $country);
    echo "
				</select>
			</div>";
    $keys = array_keys($users);

    if (!$multipleCards && (count($users) == 1) && (array_pop($keys) == 0)
            && (count($accounts[0]) < (LINES_PER_CARD * 2 - 1))) {
        showFillupOptions($users);
    }
    echo "
		</td>
	</tr>";
}

function showFillupOptions()
{
    $otherUsers = [];
    $user = getSymfonyContainer()->get('doctrine')
        ->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($_SESSION['UserID']);
    $agentList = getSymfonyContainer()->get(Counter::class)->getDetailsCountAccountsByUser($user);
    $balancesByUserAgent = [];

    foreach ($agentList as $agent) {
        if ($agent['UserName'] == 'All') {
            continue;
        }
        $ua = (!isset($agent['UserAgentID'])) ? 0 : $agent['UserAgentID'];
        $balancesByUserAgent[$ua] = $agent;
    }
    $q = new TQuery(OtherUsersSQL());

    while (!$q->EOF) {
        $count = $balancesByUserAgent[$q->Fields['UserAgentID']]['Accounts'];

        if ($count > 0) {
            $otherUsers[$q->Fields['UserAgentID']] = $q->Fields['UserName'] . " ($count)";
        }
        $q->Next();
    }

    if (count($otherUsers) > 0) {
        echo "
		<div class='sideCaption' id='fillupOptions'>
			Fill up the empty space on my OneCard with accounts from:<br/><br/>";

        foreach ($otherUsers as $userAgentId => $name) {
            echo "<input type='checkbox' id='ua_{$userAgentId}' value='{$userAgentId}' onclick='fillup()'/> {$name}<br/>";
        }
        echo "
		</div>";
    }
}

function accountAlphabetSort($a, $b)
{
    return strcasecmp($a['AccountName'], $b['AccountName']);
}

$Interface->DrawBeginBox("style='margin-left: auto; margin-right: auto; width: 756px;'", "AwardWallet OneCard Order", false, "");
echo "<br/>";
$Interface->DrawMessage("<div style='font-size: 16px'>AwardWallet OneCard is a credit card sized plastic card with magnetic strip. The card will have your personal account numbers and program phone numbers printed on the front and back sides. The card can contain up to 30 different loyalty program accounts listed. We have two types of cards: gold and silver. Those AwardWallet members who have over 1 million miles and points will get a gold card and those who have fewer than 1 million miles and points will get a silver card. The cards will be shipped internationally as well as within United States via regular USPS service.</div>", "info");

?>
<form name='cardDesigner' method='post' id='cardDesigner'>
    <input type='hidden' name='FormToken' value='<?php echo GetFormToken(); ?>'>
	<input type='hidden' name='Step' value='designer'/>
	<table id = "cardDesign">
		<tbody>
		<?php
        $allAccounts = getAgentAccounts("All", $total, $otherUserCount > 0);

// add user names, if there are duplicate provider names
if ($otherUserCount > 0) {
    foreach ($allAccounts as &$fields) {
        if (!isset($fields['Duplicate'])) {
            $fields['AccountName'] .= ' (' . $fields['UserName'] . ')';
        }
    }
}

// sort
$sort = ArrayVal($_POST, 'Sort', 'priority');
uasort($allAccounts, "accountAlphabetSort");
array_unshift($allAccounts, '');
unset($allAccounts[0]);
$allAccounts = [
    "0" => ['ID' => '0', 'UserAgentID' => '0', 'UserName' => '',
        'AccountName' => "Empty", "Priority" => "999999", "ProviderName" => "", "ProviderCode" => "", ],
    "-2" => ['ID' => '-2', 'UserAgentID' => '0', 'UserName' => '',
        'AccountName' => "Custom", "Priority" => "999998", "ProviderName" => "", "ProviderCode" => "", ],
]
    + $allAccounts;

// load user data, determine multiple cards
$accounts = [];
$total = [];
$multipleCards = false;

foreach ($users as $userAgentId => $name) {
    $accounts[$userAgentId] = getAgentAccounts($userAgentId, $userTotal, $otherUserCount > 0, true);

    if (($sort == 'alphabet') && $newOrder) {
        uasort($accounts[$userAgentId], "accountAlphabetSort");
    }
    $total[$userAgentId] = $userTotal;

    if (count($accounts[$userAgentId]) > (LINES_PER_CARD * 2)) {
        $multipleCards = true;
    }
}

$country = ArrayVal($_POST, 'Country', "");

if (!$readOnly) {
    showOptions($multipleCards, $users, $accounts, $sort, $country);
}

// show cards
foreach ($users as $userAgentId => $name) {
    showUserCards(
        $userAgentId,
        $name,
        $total[$userAgentId],
        $accounts[$userAgentId],
        $allAccounts,
        $readOnly
    );
}
?>
		</tbody>
	</table>
	<?php echo showAccountSelect($allAccounts, ""); ?>
</form>
<?php
if (!$readOnly) {
    echo $Interface->DrawButton("Continue", "style='margin-left: 10px; margin-bottom: 10px;'", 0, "onclick='saveOneCardEditor(this, false); return false;'");
}

$Interface->DrawEndBox();

$Interface->DrawBeginBox('id="waitPopup" style="position: absolute; z-index: 50; width: 300px; display: none;"', null, false);
?>
	<div style='padding-top: 10px; text-align: center;' id="waitPopupBody">
		Loading, please wait..
	</div>
	<?php
$Interface->DrawEndBox();

drawPopupMessage();

$Interface->ScriptFiles[] = '/lib/3dParty/jquery/plugins/jquery.sortElements.js';
$Interface->ScriptFiles[] = '/lib/3dParty/jquery/plugins/jquery.scrollintoview.min.js';
$Interface->FooterScripts[] = 'bindSelectChange(); showDeleteLinks();';
$Interface->FooterScripts[] = 'browserExt.pushOnReady(fillDataFromBrowser);';

?>
<div id='phoneList' accountId='none' style="display: none;"></div>
<script type="text/javascript" src="scripts.js?v=<?php echo FILE_VERSION; ?>"></script>
<script>
function ChangeFullName(newName, userAgentID){
	var name = document.getElementById('fullName_'+userAgentID);
	if (newName != ""){
		newName += ",";
	}
	name.innerText = newName;
}
</script>
<style type="text/css">
    p.title {
        border: solid 1px transparent;
        padding: 0 2px 2px !important;
    }
    .selectField {
        position: relative;
    }
    .do-empty-row {
        position: absolute;
        display: none;
        opacity: 0;
        left: -14px;
        top: 2px;
    }
    .do-empty-row:hover {
        color: darkred !important;
        text-decoration: none !important;
    }
    p.title:not(:empty) ~ .do-empty-row{
        display: inline-block;
    }
    .accountRow:hover .do-empty-row {
        opacity: 1;
    }
    #accountSelect option[value="0"] {
        display: none;
    }

</style>
<script>
    require(['jquery-boot'], function ($) {
        $(document).on('click', '.do-empty-row', function(e){
            e.preventDefault();
            const $row = $(this).closest('tr');
            $('p.title', $row).trigger('click');
            $("#accountSelect").val(0).trigger('change');
            return false;
        });
    });
</script>
<?php
require "$sPath/design/footer.php";
?>
