<?
require __DIR__.'/../../web/kernel/public.php';

echo "this script will delete records from AccountShare, that were created not by Account owner (reshared)\n";
$opts = getopt("f");
if(!isset($opts['f']))
	echo "test mode, use -f to apply changes\n";

$q = new TQuery("select
	ash.AccountShareID, ash.AccountID, p.DisplayName, a.Login, ua.AgentID,
	a.UserID, u.FirstName, u.LastName,
	ua.ClientID, cu.FirstName as ClientFirstName, cu.LastName as ClientLastName
from
	AccountShare ash
	join UserAgent ua on ash.UserAgentID = ua.UserAgentID
	join Account a on ash.AccountID = a.AccountID
	join Usr u on a.UserID = u.UserID
	join Usr cu on ua.ClientID = cu.UserID
	left outer join Provider p on a.ProviderID = p.ProviderID
where
	ua.ClientID <> a.UserID");
$fixed = 0;
while(!$q->EOF){
	echo "account {$q->Fields['AccountID']} - {$q->Fields['DisplayName']} - {$q->Fields['Login']}, "
	."client: {$q->Fields['ClientID']} - {$q->Fields['ClientFirstName']} {$q->Fields['ClientLastName']}"
	.", user: {$q->Fields['UserID']} - {$q->Fields['FirstName']} {$q->Fields['LastName']}"
	.", agent: {$q->Fields['AgentID']}\n";
	if(isset($opts['f']))
		$Connection->Execute("delete from AccountShare where AccountShareID = {$q->Fields['AccountShareID']}");
	$fixed++;
	$q->Next();
}

echo "done, fixed: $fixed\n";
