<?php

$schema = "usersReports";
require "../../start.php";
drawHeader("Delta & Starwood Users");

print "<h2>Delta & Starwood Users</h2><br>";

$q = $Connection->Execute("
 select Account.UserID as u, AccountID, ProviderID
 from Account
 group by Account.UserID having
 (select count(distinct(AccountID)) from Account where UserID = u and (ProviderID = 7 or ProviderID = 145)) > 0
 and (select count(distinct(AccountID)) from Account where UserID = u and (ProviderID = 25 or ProviderID = 575)) > 0
 ");
$i=0;
print "<table border = 1><tr><td><b>#</b></td><td><b>UserID</b></td></tr>";
while($row = mysql_fetch_assoc($q)){
    $i++;
    print "<tr><td>$i</td><td>".$row['u']."</td></tr>";
}
print "</table>";

print "<br /><br />";
print "<a href = \"javascript:history.back()\">Back</a>";
drawFooter();
