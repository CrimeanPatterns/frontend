#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";

echo "merging amazonaff and amazongift\n";

$opts = getopt("hfu:");
if(isset($opts['h']))
    die("usage
	".basename(__FILE__)." [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$amazonaffId = Lookup("Provider", "Code", "ProviderID", "'amazonaff'", true);
$amazongiftId = Lookup("Provider", "Code", "ProviderID", "'amazongift'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = $amazonaffId";
if(isset($opts['u']))
    $sql .= " and UserID = ".intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while(!$q->EOF){
    if(processAmazon($q->Fields, $amazongiftId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to amazongift: {$migrated}\n";

function processAmazon($fields, $amazongiftId, $force){
    global $Connection;
    echo "user {$fields['UserID']}, amazonaff login ".$fields['Login'];
    $migrate = true;
    $q = new TQuery("select * from Account
	where ProviderID = $amazongiftId and Login = '".addslashes($fields['Login'])."' and UserID = {$fields['UserID']}");
    while(!$q->EOF){
        if($q->Fields['Login'] == $fields['Login']){
            echo ", found amazongift with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if($migrate){
        echo ", migrating\n";
        if($force){
            $Connection->Execute("update Account set ProviderID = $amazongiftId where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else{
        echo ", skip\n";
    }
    return $migrate;
}
