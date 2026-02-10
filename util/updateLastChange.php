#!/usr/bin/php
<?
require "../web/kernel/public.php";
require_once "$sPath/kernel/TAccountList.php";

echo "updating last change date and balance\n";

$q = new TQuery("select AccountID as ID,
	trim(trailing '.' from trim(trailing '0' from round(Balance, 10))) as Balance,
	trim(trailing '.' from trim(trailing '0' from round(LastBalance, 7))) as LastBalance,
	LastChangeDate
from
	Account");
while(!$q->EOF){
	if(($q->Position % 1000) == 0)
		echo "{$q->Position} rows processed\n";
	TAccountList::getLastChange($q->Fields, $q->Fields['Balance'], null, $lastBalance, $lastChangeDate);
	$q->Next();
}
echo "done\n";
