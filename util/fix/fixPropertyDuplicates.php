<?
require __DIR__ . '/../../web/kernel/public.php';

echo "searching for duplicates..\n";

$q = new TQuery("select a.AccountID from Account a join Provider p on a.ProviderID = p.ProviderID where p.Code in ('mileageplus', 'rapidrewards', 'delta')");

echo "got query\n";

$duplicates = 0;
while(!$q->EOF){
	if(($q->Position % 1000) == 0)
		echo "processed {$q->Position} accounts..\n";
	$duplicates += fixDuplicates($q->Fields['AccountID']);
	$q->Next();
}

echo "done, processed {$q->Position} rows, fixed {$duplicates} duplicates\n";

function fixDuplicates($accountId){
	global $Connection;
	$duplicates = 0;
	$q = new TQuery("select AccountPropertyID, ProviderPropertyID, Val from AccountProperty where AccountID = $accountId and SubAccountID is null order by ProviderPropertyID, AccountPropertyID");
	while(!$q->EOF){
		if(!empty($last) && $last['ProviderPropertyID'] == $q->Fields['ProviderPropertyID']){
			echo "duplicate, account: $accountId, id: {$last['AccountPropertyID']}, val: {$last['Val']}\n";
			$Connection->Execute("delete from AccountProperty where AccountPropertyID = {$last['AccountPropertyID']}");
			$duplicates++;
		}
		$last = $q->Fields;
		$q->Next();
	}
	return $duplicates;
}
