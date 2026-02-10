#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";

echo "merging palm837 and landrys\n";

$opts = getopt("hfu:");
if (isset($opts['h'])) {
    die("usage
	".basename(__FILE__)." [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");
}

$palm837Id = Lookup("Provider", "Code", "ProviderID", "'palm837'", true);
$landrysId = Lookup("Provider", "Code", "ProviderID", "'landrys'", true);

$sql = "select AccountID, Login, Login2, UserID, UserAgentID from Account where ProviderID = {$palm837Id}";
if (isset($opts['u'])) {
    $sql .= " and UserID = ".intval($opts['u']);
}

echo "loading...\n";
$q = new TQuery($sql);
$migrated = 0;
while (!$q->EOF) {
    if (processPalm837($q->Fields, $landrysId, isset($opts['f']))) {
        $migrated++;
    }
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to landrys: {$migrated}\n";

function processPalm837($fields, $landrysId, $force)
{
    global $Connection;
    echo "user {$fields['UserID']}, palm837 login ".$fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account where ProviderID = $landrysId and Login = '".addslashes($fields['Login'])."' and UserID = {$fields['UserID']}");
    while (!$q->EOF) {
        if ($q->Fields['Login'] == $fields['Login']) {
            echo ", found landrys account with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if ($migrate) {
        echo ", migrating\n";
        if ($force) {
            $Connection->Execute("update Account set ProviderID = $landrysId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    } else {
        echo ", skip\n";
    }

    return $migrate;
}
