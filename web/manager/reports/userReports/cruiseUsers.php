<?php

$schema = "usersReports";
require "../../start.php";
drawHeader("Cruise Users");

print "<h2>Cruise Users</h2><br>";

$cruiseCategory = TRIP_CATEGORY_CRUISE;

/* $parsed = QueryTopDef("
	SELECT COUNT(*) as `Count` FROM (SELECT DISTINCT u.UserID
	FROM Trip t
		JOIN Account a ON t.AccountID = a.AccountID
		JOIN Usr u ON a.UserID = u.UserID
	WHERE t.Category = {$cruiseCategory} AND t.Parsed = 1) i
", 'Count', 0);

$custom = QueryTopDef("
	SELECT COUNT(*) as `Count` FROM (SELECT DISTINCT u.UserID
	FROM Trip t
		JOIN Account a ON t.AccountID = a.AccountID
		JOIN Usr u ON a.UserID = u.UserID
	WHERE t.Category = {$cruiseCategory} AND t.Parsed <> 1 AND u.UserID not in (
		SELECT DISTINCT u.UserID
		FROM Trip t
			JOIN Account a ON t.AccountID = a.AccountID
			JOIN Usr u ON a.UserID = u.UserID
		WHERE t.Category = {$cruiseCategory} AND t.Parsed = 1
	)) i
", 'Count', 0);

$total = $parsed + $custom; 

print "<p>Users with cruise reservations:</p><table>
<tr><td>Parsed:</td><td>$parsed</td></tr>
<tr><td>User created only:</td><td>$custom</td></tr>
<tr><td align=right>Total:</td><td><b>$total</b></td></tr>
</table>"; */

$total = QueryTopDef("
	SELECT COUNT(*) as `Count` FROM 
	(SELECT DISTINCT(UserID)
	FROM Account JOIN Provider ON Account.ProviderID = Provider.ProviderID WHERE Provider.Kind = 10
	UNION SELECT DISTINCT (Usr.UserID)
	FROM Trip JOIN Account ON Trip.AccountID = Account.AccountID JOIN Usr ON Usr.UserID = Account.UserID
	WHERE Trip.Category = {$cruiseCategory}) as U
", 'Count', 0);

print "$total users have accounts for cruise providers or have cruise reservations";
print "<br><br>";
print "<a href = \"javascript:history.back()\">Back</a>";
drawFooter();
?>
