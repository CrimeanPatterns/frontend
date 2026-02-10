#!/usr/bin/php
<?
require __DIR__ . "/../web/kernel/public.php";

$q = new TQuery("
    SELECT
    u.UserID, p.Code, ap.Val
FROM
    Account a
    JOIN AccountProperty ap ON ap.AccountID = a.AccountID
    JOIN ProviderProperty pp ON ap.ProviderPropertyID = pp.ProviderPropertyID
    JOIN Provider p ON p.ProviderID = a.ProviderID
    JOIN Usr u ON u.UserID = a.UserID
    LEFT JOIN Country co ON co.CountryID = u.CountryID

WHERE
    u.AccountLevel <> 3 AND
    p.ProviderID IN (84, 75, 123, 104, 87, 364, 103) AND
    (co.Code = 'US' OR (u.Region IS NOT NULL AND IF(LENGTH(u.Region) > 2, SUBSTR(u.Region, -2, 2), u.Region) = 'US')) AND
    pp.Code = 'DetectedCards'
");

$result = [];
foreach($q as $row) {
    $userId = $row['UserID'];
    $code = $row['Code'];
    $row = @unserialize($row['Val']);
    if (is_array($row)) {
        foreach($row as $card) {
            if (!empty($card['DisplayName'])) {
                $card['DisplayName'] = preg_replace("/\(?[\.\s-x]+\d{4}\)?/ims", "", $card['DisplayName']);
                $card['DisplayName'] = trim($card['DisplayName']);
                $result[$card['DisplayName']] = sprintf("%s (%d, %s)", $card['DisplayName'], $userId, $code);
            }
        }
    }
}

$i = 1;
foreach($result as $row) {
    echo "$i. {$row}\n";
    $i++;
}
