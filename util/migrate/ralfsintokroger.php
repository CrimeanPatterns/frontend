#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";

echo "Migrate Ralphs Rewards into Kroger Brands\n";

$opts = getopt("hfu:");
if(isset($opts['h']))
    die("usage
	".basename(__FILE__)." [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$ralphsId = Lookup("Provider", "Code", "ProviderID", "'ralphs'", true);
$krogerId = Lookup("Provider", "Code", "ProviderID", "'kroger'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = $ralphsId";
if(isset($opts['u']))
    $sql .= " and UserID = ".intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while(!$q->EOF){
    if(processRalphs($q->Fields, $krogerId, isset($opts['f'])))
        $migrated++;
        $q->Next();
}

function processRalphs($fields, $krogerId, $force){
    global $Connection;
    echo "user {$fields['UserID']}, ralphs login ".$fields['Login'];
    $migrate = true;
    $q = new TQuery("select AccountID, Login from Account
	where ProviderID = $krogerId and Login = '".addslashes($fields['Login'])."' and UserID = {$fields['UserID']}");
    while(!$q->EOF){
        if($q->Fields['Login'] == $fields['Login']){
            echo ", found kroger with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if($migrate){
        echo ", migrating\n";
        if($force){
            $Connection->Execute("update Account set ProviderID = $krogerId, Login2 = 'ralphs.com' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else{
        echo ", skip\n";
    }
    return $migrate;
}
