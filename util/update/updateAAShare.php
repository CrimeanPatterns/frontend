<?
require __DIR__ . "/../../web/kernel/public.php";
echo "=== Updating AAShare Table ===\n";
$today = date('Y-m-d', time());
$monthBeginning = date('Y-m').'-01';
$monthEnding = date('Y-m-d', strtotime("+1 month", strtotime($monthBeginning)));
echo "Today is $today\n";
echo "Month begins from $monthBeginning\n";
$Connection->Execute("delete from AAShare where `CountDate` >= '$monthBeginning' and `CountDate` <= '$monthEnding'");
$q = new TQuery("
    select count(*) as aac
    from Account a, Provider p
    where a.ProviderID = p.ProviderID and p.Code = 'aa'");
$aac = $q->Fields['aac'];
$q = new TQuery("
    select (((select count(*)
    from Account a, Provider p
    where a.ProviderID = p.ProviderID and p.Category = 1 and p.Kind = 1) * 100)
    +
    ((select count(*)
    from Account a, Provider p
    where a.ProviderID = p.ProviderID and p.Category = 2 and p.Kind = 1) * 10)
    +
    ((select count(*)
    from Account a, Provider p
    where a.ProviderID = p.ProviderID and p.Category = 3 and p.Kind = 1) * 1))
    as Total");
$Total = $q->Fields['Total'];
$aaas = round(($aac * 100) / $Total, 2);
echo "$aaas\n";
$Connection->Execute("insert into AAShare
    (`CountDate`, `Share`, `AAAccounts`, `USAccounts`, `TotalWeight`)
    values ('$today', '$aaas', '$aac', '0', '$Total')");
echo "=== Finished Updating AAShare Table ===\n";
?>
