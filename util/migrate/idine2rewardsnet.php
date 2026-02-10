#!/usr/bin/php
<?

require __DIR__ . "/../../web/kernel/public.php";

echo "merging rewards and rewardsnet\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$rewardsId = Lookup("Provider", "Code", "ProviderID", "'rewards'", true);
$rewardsnetId = Lookup("Provider", "Code", "ProviderID", "'rewardsnet'", true);

// update marriott accounts
processRewardsnet($rewardsnetId, isset($opts['f']));

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$rewardsId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "\nloading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processIdine($q->Fields, $rewardsnetId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to rewardsnet: {$migrated}\n";

function processRewardsnet($rewardsnetId, $force) {
    global $Connection;
    echo "\nFixed Login2 for rewardsnet accounts";
    $q = new TQuery("select * from Account where ProviderID = {$rewardsnetId}");
    while (!$q->EOF) {
        if (!empty($q->Fields['Login2'])) {
            $correct = false;
            $login2 = '';
            switch ($q->Fields['Login2']) {
                case 'https://priorityclub.rewardsnetwork.com/':
                case 'http://priorityclub.rewardsnetwork.com/':
                    $login2 = 'https://ihgrewardsclubdining.rewardsnetwork.com/';
                    break;
                case 'http://skymiles.rewardsnetwork.com/':
                    $login2 = 'https://skymiles.rewardsnetwork.com/';
                    break;
                case 'http://www.rapidrewardsdining.com/':
                    $login2 = 'https://www.rapidrewardsdining.com/';
                    break;
                case 'http://mpdining.rewardsnetwork.com/':
                    $login2 = 'https://mpdining.rewardsnetwork.com/';
                    break;
                case 'http://usairways.rewardsnetwork.com/':
                    $login2 = 'https://usairways.rewardsnetwork.com/';
                    break;
                case 'http://www.hhonorsdining.com/':
                    $login2 = 'https://www.hhonorsdining.com/';
                    break;
                case 'http://www.rewardzonedining.com/':
                    $login2 = 'https://www.rewardzonedining.com/';
                    break;
                default:
                    $correct = true;
                    break;
            }// switch ($q->Fields['Login2'])
            echo "\nAccountID: {$q->Fields['AccountID']} -> ";
            if ($force) {
                if (!$correct && !empty($login2)) {
                    echo "set Login2 as '{$login2}' (old Login2: {$q->Fields['Login2']})";
                    $Connection->Execute("update Account set Login2 = '{$login2}' where ProviderID = {$rewardsnetId} and AccountID = {$q->Fields['AccountID']}");
                }// if ($isCorrect)
                else
                    echo "Login2 is correct (Login2: {$q->Fields['Login2']})";
            }// if ($force)
            else
                echo "just test (Login2: from {$q->Fields['Login2']} to {$login2})";
        }// if (!empty($q->Fields['Login2']))
        $q->Next();
    }
}

function processIdine($fields, $rewardsnetId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, iDine login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $rewardsnetId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login'] && $q->Fields['Login2'] == 'https://www.idine.com/') {
            echo ", found rewardsnet with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $rewardsnetId, Login2 = 'https://www.idine.com/' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else {
        echo ", skip\n";
    }

    return $migrate;
}
