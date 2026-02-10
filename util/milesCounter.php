<?
require "../web/kernel/public.php";

$miles = 1000000;
$sql = "
SELECT 
	SUM(IF(AllBalance >= $miles,1,0)) AllCount,
	SUM(IF(MilesBalance >= $miles,1,0)) MilesCount
FROM (
	SELECT 
		SUM(IF(a.Balance > 0,IF(sa.Balance > 0,sa.Balance+a.Balance,a.Balance),IF(sa.Balance > 0,sa.Balance,0))) AllBalance,
		SUM(
			CASE WHEN 
				p.Kind = 1 OR p.Kind = 2 
			THEN 
				IF(a.Balance > 0,IF(sa.Balance > 0,sa.Balance+a.Balance,a.Balance),IF(sa.Balance > 0,sa.Balance,0)) 
			END 
		) MilesBalance
	FROM 
		Usr u 
		JOIN Account a ON a.UserID = u.UserID AND a.UserAgentID IS NULL
		LEFT JOIN SubAccount sa ON a.AccountID = sa.AccountID
		LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
	GROUP BY u.UserID
) Totals
";

echo "Start Query! \n\n";
$Totals = new TQuery($sql);

if(!$Totals->EOF){
    echo "# of users with over 1M miles across all provider types: {$Totals->Fields['AllCount']} \n";
    echo "# of users with over 1M miles across hotel and air providers only: {$Totals->Fields['MilesCount']} \n";
} else   
    echo "Nothing Found";
?>
