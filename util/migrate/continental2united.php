#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";

echo "merging continental and united\n";

$opts = getopt("hfu:");
if(isset($opts['h']))
	die("usage
	".basename(__FILE__)." [options]
options:
	-h: help
	-f: force run, otherwise test only
	-u <user id>: this user only
");

$continentalId = Lookup("Provider", "Code", "ProviderID", "'continental'", true);
$unitedId = Lookup("Provider", "Code", "ProviderID", "'mileageplus'", true);

$sql = "select AccountID, Login, UserID, UserAgentID from Account where ProviderID = $continentalId";
if(isset($opts['u']))
	$sql .= " and UserID = ".intval($opts['u']);

echo "loading..\n";
$q = new TQuery($sql);
$migrated = 0;
while(!$q->EOF){
	if(processContinental($q->Fields, $unitedId, isset($opts['f'])))
		$migrated++;
	$q->Next();
}

echo "done, processed {$q->Position} accounts, switched to united: {$migrated}\n";

function processContinental($fields, $unitedId, $force){
	global $Connection;
	echo "user {$fields['UserID']}, continental login ".$fields['Login'];
	$migrate = true;
	$q = new TQuery("select AccountID from Account
	where ProviderID = $unitedId and Login = '".addslashes($fields['Login'])."' and UserID = {$fields['UserID']}");
	if($q->EOF){
		$continentalNumber = getAccountNumber($fields['AccountID']);
		if(isset($continentalNumber)){
			echo ", number: ".$continentalNumber;
			$sql = "select AccountID, Login from Account where ProviderID = $unitedId and UserID = ".$fields['UserID'];
			$q = new TQuery($sql);
			while(!$q->EOF){
				$unitedNumber = getAccountNumber($q->Fields['AccountID']);
				if(isset($unitedNumber)){
					echo ", united number: ".$unitedNumber;
					if($unitedNumber == $continentalNumber){
						echo ", match";
						$migrate = false;
						break;
					}
				}
				$q->Next();
			}
		}
		else{
			echo ", number unknown";
		}
	}
	else{
		echo ", found united with same login";
		$migrate = false;
	}
	if($migrate){
		echo ", migrating\n";
		if($force){
			$Connection->Execute("update Account set ProviderID = $unitedId where AccountID = {$fields['AccountID']}");
			$Connection->Execute("delete from AccountProperty where AccountID = {$fields['AccountID']}");
		}
	}
	else{
		echo ", skip\n";
	}
	return $migrate;
}

function getAccountNumber($accountId){
	$q = new TQuery("select
		ap.Val
	from
		AccountProperty ap
		join ProviderProperty pp on ap.ProviderPropertyID = pp.ProviderPropertyID
	where
		ap.AccountID = $accountId
		and pp.Kind = ".PROPERTY_KIND_NUMBER);
	if(!$q->EOF)
		return $q->Fields['Val'];
	else
		return null;
}
