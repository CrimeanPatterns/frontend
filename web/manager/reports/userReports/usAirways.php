<?php

$schema = "usersReports";
require "../../start.php";
drawHeader("US Airways Users");

$q = $Connection->Execute("select count(distinct(Usr.UserID)) as `count` from Usr join Account on Usr.UserID = Account.UserID join Trip on Account.AccountID = Trip.AccountID where Trip.ProviderID = 27 and Cancelled = 0 and date_add(Trip.UpdateDate, interval 1 year) > now() and not Usr.UserID in(select Usr.UserID from Usr join Account on Account.UserID = Usr.UserID join AccountHistory on AccountHistory.AccountID = Account.AccountID where Description like 'US WORLD CARD%' OR Description like 'US MASTERCARD%' and date_add(PostingDate, interval 2 year) > now())");
$row = mysql_fetch_assoc($q);
print "US Airways users total: ".$row['count'].'<br>';
print "<br>US Airways users accounts: <br>";
$q = $Connection->Execute("
    select Usr.UserID as `userId`, Account.AccountID as `accountId`
    from Usr join Account on Usr.UserID = Account.UserID join Trip on Account.AccountID = Trip.AccountID
    where Trip.ProviderID = 27 and Cancelled = 0 and date_add(Trip.UpdateDate, interval 1 year) > now()
    and not Usr.UserID in(select Usr.UserID from Usr join Account on Account.UserID = Usr.UserID join AccountHistory on AccountHistory.AccountID = Account.AccountID where Description like 'US WORLD CARD%' OR Description like 'US MASTERCARD%' and date_add(PostingDate, interval 2 year) > now()) group by AccountID order by UserID");
$i=0;
print "<table border = 1><tr><td><b>#</b></td><td><b>UserID</b></td><td><b>AccountID</b></td></tr>";
while($row = mysql_fetch_assoc($q)){
$i++;
print "<tr><td>$i</td><td>".$row['userId']."</td><td>".$row['accountId']."</td></tr>";
}
print "</table>";

print "<br /><br />";
print "<a href = \"javascript:history.back()\">Back</a>";
drawFooter();
