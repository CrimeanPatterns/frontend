<?
require "../web/kernel/public.php";

$sql = "
SELECT 
	u.UserID,
	COUNT(a.AccountID) AccountsNum
FROM
	Usr u	
	LEFT JOIN Account a ON u.UserID = a.UserID	
	JOIN 
	(
	    SELECT
		    u1.UserID UID1, 
		    u2.UserID UID2, 
		    u3.UserID UID3	
	    FROM 
		    Usr u1, 
		    Usr u2, 
		    Usr u3
	    WHERE 
		    u1.CameFrom = 4
		    AND u2.CameFrom = 4
		    AND u3.CameFrom = 4
		    AND u1.UserID != u2.UserID
		    AND u1.UserID != u3.UserID
		    AND u2.UserID != u3.UserID
		    AND u1.RegistrationIP IS NOT NULL
		    AND u2.RegistrationIP IS NOT NULL
		    AND u3.RegistrationIP IS NOT NULL
		    AND u1.RegistrationIP = u2.RegistrationIP
		    AND u2.RegistrationIP = u3.RegistrationIP 
		    AND (UNIX_TIMESTAMP(u1.CreationDateTime) - UNIX_TIMESTAMP(u2.CreationDateTime)) < 3600*24 AND (UNIX_TIMESTAMP(u1.CreationDateTime) - UNIX_TIMESTAMP(u2.CreationDateTime)) > 0
		    AND (UNIX_TIMESTAMP(u1.CreationDateTime) - UNIX_TIMESTAMP(u3.CreationDateTime)) < 3600*24 AND (UNIX_TIMESTAMP(u1.CreationDateTime) - UNIX_TIMESTAMP(u3.CreationDateTime)) > 0
		    AND (UNIX_TIMESTAMP(u2.CreationDateTime) - UNIX_TIMESTAMP(u3.CreationDateTime)) < 3600*24 AND (UNIX_TIMESTAMP(u2.CreationDateTime) - UNIX_TIMESTAMP(u3.CreationDateTime)) > 0
	)
	fu ON (
	    u.UserID = fu.UID1
	    OR u.UserID = fu.UID2
	    OR u.UserID = fu.UID3
	)

GROUP BY u.UserID
ORDER BY AccountsNum
";

$fakeUsers = new TQuery($sql);
while (!$fakeUsers->EOF && $fakeUsers->Fields['AccountsNum'] == 0){
    echo $fakeUsers->Fields['UserID']."\n"; 
    $fakeUsers->Next();
}

echo "Total: ".$fakeUsers->Position."\n";
echo "END\n";


?>
