#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";

echo "merging windham and triprewards\n";

$opts = getopt("hfu:");
if(isset($opts['h']))
    die("usage
	".basename(__FILE__)." [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$windhamId = Lookup("Provider", "Code", "ProviderID", "'windham'", true);
$triprewardsId = Lookup("Provider", "Code", "ProviderID", "'triprewards'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = $windhamId";
if(isset($opts['u']))
    $sql .= " and UserID = ".intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while(!$q->EOF){
    if(processWindham($q->Fields, $triprewardsId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to triprewards: {$migrated}\n";

function processWindham($fields, $triprewardsId, $force){
    global $Connection;
    echo "user {$fields['UserID']}, windham login ".$fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $triprewardsId and Login = '".addslashes($fields['Login'])."' and UserID = {$fields['UserID']}");
    while(!$q->EOF){
        if($q->Fields['Login'] == $fields['Login']){
            echo ", found triprewards with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if($migrate){
        echo ", migrating\n";
        if($force){
            $Connection->Execute("update Account set ProviderID = $triprewardsId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else{
        echo ", skip\n";
    }
    return $migrate;
}
