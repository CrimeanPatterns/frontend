<?php
// disable session to prevent interference with user
require "../kernel/public.php";
require_once("commonFuncForList.php");
require_once "$sPath/kernel/TAccountOverviewList.php";

if (SITE_MODE != SITE_MODE_BUSINESS)
	die("this script only for business version");

// authorize user
AuthorizeUser();
if (isBusinessMismanagement()) {
	echo "Access denied\r\n";/*checked*/
	exit();
}

// load data
$nUserAgentID = "All";
CheckUserAgentID($nUserAgentID);
$_SESSION['UserAgentID'] = $nUserAgentID;
$PrintInFile = true;

$agentIdFilter = '';
if (!empty($_GET['agentId'])) {
    $agentId = (int) $_GET['agentId'];
    $agent = getUserAgent($agentId);

    if (!empty($agent) && (int) $_SESSION['UserID'] === (int) $agent['AgentID']) {
        $agentIdFilter = ' AND (ua.UserAgentID = ' . $agentId . ')';
    }
}

$objList = new TAccountOverviewList();
$objList->Caption = "Corporate accounts";
$objList->ListCategory("and (p.Corporate = 1)" . $agentIdFilter, "", null, 0, null);
$objList->Caption = "Personal accounts";
$objList->ListCategory("and (p.Corporate = 0)" . $agentIdFilter, "", null, 0, null);
$objList->DrawTotals();

ob_clean();

header('Content-Type: application/octet-stream');
header('content-Disposition: attachment;filename="Accounts for '.$_SESSION['UserFields']['Company'].'.csv"');
header('Cache-Control: max-age=0');

$f = fopen('php://output', 'w');
$titles = [
    "Account ID",
	"Type",
	"Account Owner",
	"User ID",
	"Award Program",
	"Account Number",
	"Login Name",
	"Balance",
	"Last Change",
	"Expiration",
	"Comments"
];
$kindTitles = getExportKindTitles();
$titles = array_merge($titles, $kindTitles);

fputcsv($f, $titles);

foreach($objList->Rows as $row){
	if(!isset($row['FormattedBalance']))
		$row['FormattedBalance'] = $row['Balance'];

	if(is_null($row['Balance']) || is_null($row['LastBalance']))
		$lastChange = '';
	else
		$lastChange = $row['Balance'] - $row['LastBalance'];

	$props = getPropsByKind(ArrayVal($row, 'Properties', []));

    $expirationDate = ArrayVal($row, 'ExpirationDate');
    if (!empty($expirationDate)) {
        $expirationDate = strtotime($expirationDate);
        if (false !== $expirationDate) {
            $expirationDate = date('m/d/Y', $expirationDate) . ' ';
        } else {
            $expirationDate = '';
        }
    }
	$values = [
        ArrayVal($row, 'ID'),
		ArrayVal($arProviderKind, $row['Kind'], "Custom"),
		htmlspecialchars_decode($row['Kind'] == 'SubAccount' ? "" : $row['UserName']),
		ArrayVal($row, 'UserID'),
		htmlspecialchars_decode($row['DisplayName']),
		$row['Kind'] == 'SubAccount' ? "" : '="' . ArrayVal($row, 'Number') . '"',
		$row['Kind'] == 'SubAccount' ? "" : '="' . htmlspecialchars_decode($row['Login']) . '"',
		round(ArrayVal($row, 'TotalBalance', 0), 2),
		$lastChange,
        $expirationDate,
		ArrayVal($row, 'comment')
	];

	foreach($kindTitles as $kind => $caption)
		$values[] = ArrayVal($props, $kind);

	fputcsv($f, $values);
}

fclose($f);

