#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";

echo "migrating tomthumb from kroger to safeway\n";

$opts = getopt("hfu:");
if(isset($opts['h']))
    die("usage
	".basename(__FILE__)." [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$krogerId = Lookup("Provider", "Code", "ProviderID", "'kroger'", true);
$safewayId = Lookup("Provider", "Code", "ProviderID", "'safeway'", true);

$sql = "select AccountID, Login, login2, UserID, UserAgentID from Account where ProviderID = $krogerId and Login2 = 'tomthumb.com'";
if(isset($opts['u']))
    $sql .= " and UserID = ".intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while(!$q->EOF){
    if(processTomthumb($q->Fields, $safewayId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to safeway (tomthumb.com): {$migrated}\n";

function processTomthumb($fields, $safewayId, $force){
    global $Connection;
    echo "user {$fields['UserID']}, kroger (tomthumb.com) login ".$fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $safewayId and Login = '".addslashes($fields['Login'])."' and Login2 = 'tomthumb.com' and UserID = {$fields['UserID']}");
    while(!$q->EOF){
        if($q->Fields['Login'] == $fields['Login']){
            echo ", found safeway with same login and Login2";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if($migrate){
        echo ", migrating\n";
        if($force){
            $Connection->Execute("update Account set ProviderID = $safewayId, Login2 = 'tomthumb' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else{
        echo ", skip\n";
    }
    return $migrate;
}
