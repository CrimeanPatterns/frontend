#!/usr/bin/php
<?
require __DIR__ . "/../../web/kernel/public.php";

echo "merging hotelclub and hotels\n";

$opts = getopt("hfu:");
if (isset($opts['h']))
    die("usage
	" . basename(__FILE__) . " [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$hotelclubId = Lookup("Provider", "Code", "ProviderID", "'hotelclub'", true);
$hotelsId = Lookup("Provider", "Code", "ProviderID", "'hotels'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = {$hotelclubId}";
if (isset($opts['u']))
    $sql .= " and UserID = " . intval($opts['u']);

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processHotelclub($q->Fields, $hotelsId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to hotels: {$migrated}\n";

function processHotelclub($fields, $hotelsId, $force) {
    global $Connection;
    echo "user {$fields['UserID']}, hotelclub login " . $fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $hotelsId and Login = '" . addslashes($fields['Login']) . "' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found hotels with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $hotelsId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else
        echo ", skip\n";

    return $migrate;
}
