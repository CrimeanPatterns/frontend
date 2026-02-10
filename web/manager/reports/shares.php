<?
$schema = "shares";
require "../start.php";
drawHeader("Shares");
$provs = array();
$total = 0;
$rule = array(
    1 => 100,
    2 => 10,
    3 => 1
);
$p = new TQuery("select ProviderID, Code, Name, Category from Provider where Kind = 1");
while (!$p->EOF){
    $prov = $p->Fields;
    $a = new TQuery("select count(*) as c from Account where ProviderID = ".$prov['ProviderID']);
    $prov['Accounts'] = $a->Fields['c'];
    $prov['Points'] = $prov['Accounts'] * $rule[$prov['Category']];
    $total += $prov['Points'];
    $provs[] = $prov;
    $p->Next();
}
echo "Only airline providers are taken into account";
echo "<table border='1'><tr><td>LP</td><td># of Accounts</td><td>Category</td><td>Points</td><td>Share</td></tr>";
foreach ($provs as $pr){
    echo "<tr>";
    echo "<td>".$pr['Name']."</td>";
    echo "<td>".$pr['Accounts']."</td>";
    echo "<td>".$pr['Category']."</td>";
    echo "<td>".$pr['Points']."</td>";
    echo "<td>".number_format(($pr['Points']/$total)*100, 2)."%</td>";
    echo "</tr>";
}
echo "</table>";
drawFooter();
?>
