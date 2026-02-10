#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";

echo "merging parknflycorp and parknfly\n";

$opts = getopt("hfu:");
if(isset($opts['h']))
    die("usage
	".basename(__FILE__)." [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$parknflycorpId = Lookup("Provider", "Code", "ProviderID", "'parknflycorp'", true);
$parknflyId = Lookup("Provider", "Code", "ProviderID", "'parknfly'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = $parknflycorpId";
if(isset($opts['u']))
    $sql .= " and UserID = ".intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while(!$q->EOF){
    if(processParknflycorp($q->Fields, $parknflyId, isset($opts['f'])))
        $migrated++;
    $q->Next();
}

echo "done, processed {$q->Position} accounts, switched to parknfly: {$migrated}\n";

function processParknflycorp($fields, $parknflyId, $force){
    global $Connection;
    echo "user {$fields['UserID']}, parknflycorp login ".$fields['Login'];
    $migrate = true;
    $q = new TQuery("select AccountID, Login from Account
	where ProviderID = $parknflyId and Login = '".addslashes($fields['Login'])."' and UserID = {$fields['UserID']}");
    while(!$q->EOF){
        if($q->Fields['Login'] == $fields['Login']){
            echo ", found parknfly with same login";
            $migrate = false;
            break;
        }
        $q->Next();
    }
    if($migrate){
        echo ", migrating\n";
        if($force){
            $Connection->Execute("update Account set ProviderID = $parknflyId, Login2 = 'USA' where AccountID = {$fields['AccountID']}");
            $Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
        }
    }
    else{
        echo ", skip\n";
    }
    return $migrate;
}
