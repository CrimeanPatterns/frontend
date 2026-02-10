<?php
require "../kernel/public.php";
ob_end_flush();
echo "fixing AccountShare..<br>";

$q = new TQuery("select ua.AgentID, ua.ClientID, ash.*
from
AccountShare ash
join Account a on ash.AccountID = a.AccountID
join UserAgent ua on ua.UserAgentID = ash.UserAgentID
join Provider p on a.ProviderID = p.ProviderID
where ua.AgentID = a.UserID
order by ua.ClientID, ua.AgentID");
while(!$q->EOF){
	echo implode(", ", $q->Fields)."<br>";
	$Connection->Execute("delete from AccountShare where AccountShareID = {$q->Fields['AccountShareID']}");
	$q->Next();
}
echo "done<br>";

?>