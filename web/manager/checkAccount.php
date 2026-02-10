<?php

$schema = "providerStatus";

require "start.php";

require_once "$sPath/account/common.php";

$accountIds = array_unique(array_map("intval", explode(',', ArrayVal($_GET, 'ID'))));
$history = intval(ArrayVal($_GET, 'History'));

$accounts = [];
$q = new TQuery("select u.*, p.*, p.Kind as ProviderKind, a.* from Account a, Usr u, Provider p where a.AccountID IN (" . implode(',', $accountIds) . ") and a.ProviderID = p.ProviderID and a.UserID = u.UserID");

do {
    if ($q->EOF) {
        exit("Account(s) " . implode(',', $accountIds) . " not found");
    }

    if ($q->Fields['SavePassword'] != SAVE_PASSWORD_DATABASE && $q->Fields['PasswordRequired'] === 1) {
        exit("Password not saved in database");
    }
    $q->Fields['ParseHistory'] = $history == '1';
    //    $q->Fields['AutoGatherPlans'] = true;
    $accounts[] = $q->Fields;
} while (!$q->EOF && $q->Next());

$engine = getSymfonyContainer()->get(\AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface::class);
$engine->sendAccounts($accounts, 0, intval(ArrayVal($_GET, 'Source', \AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface::SOURCE_OPERATIONS)));
$timeout = 60;

header('X-Accel-Buffering: no');
session_write_close();
$bNoSession = true;

while (ob_get_level()) {
    ob_end_flush();
}

echo "<html><head><title>Checking account(s)</title></head><body>";
$referer = isset($_SERVER['HTTP_REFERER']);
echo "Checking " . count($accounts) . " account(s):<br/>";

foreach ($accounts as $account) {
    echo "[" . date("Y-m-d H:i:s") . "]: checking account {$account["AccountID"]}:";

    // restrictions for credit cards update
    if ($account['ProviderKind'] == PROVIDER_KIND_CREDITCARD) {
        $storage = getSymfonyContainer()->get('aw.security.token_storage');
        $login = $storage->getUser()->getLogin();
        $allowedLogins = [
            'aanikin',
            'vladimir1',
            'siteadmin',
            'miordan',
        ];

        if (!in_array(strtolower($login), $allowedLogins)) {
            $Interface->DiePage("Access denied to account update");
        }
    }// if ($account['ProviderKind'] == PROVIDER_KIND_CREDITCARD)

    echo "<div id='progress{$account["AccountID"]}'>0</div>\n";
    echo "<div style='display: none;'>" . str_repeat('some spacer ', 1000) . "</div>\n\n\n";
    $startTime = time();
    $sleep = 0;

    do {
        $checkProgress = getSymfonyContainer()->get(\AwardWallet\MainBundle\Updater\AccountProgress::class)->getAccountInfo($account["AccountID"]);
        $complete = $checkProgress !== null;

        if (!$complete) {
            sleep(1);
            $sleep++;
            echo "<script>document.getElementById('progress{$account["AccountID"]}').innerText = '$sleep';</script>";
        }
    } while (!$complete && (time() - $startTime) < $timeout);

    if (!$referer) {
        echo "Account {$account["AccountID"]} checked, no referer, press back, balance was: " . $account['Balance'] . ", seconds: " . (time() - $startTime) . "<br/><br/>";
    }
}
// session_start();

if ($referer && strpos($_SERVER['HTTP_REFERER'], 'manager/itineraryCheckError/details') === false) {
    echo "<script>document.location.href = " . json_encode(urlPathAndQuery($_SERVER['HTTP_REFERER'])) . ";</script>";
} else {
    $q->Open();
}

echo "</body></html>";
