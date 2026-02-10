<?
$schema = "Provider";
require "../start.php";
drawHeader("Providers with ShortName constraint violation");
echo "<strong>Providers with ShortName constraint violation</strong><br />";
$q = new TQuery("select ProviderID as pid, Name as n, ShortName as sn from Provider where length(ShortName) > 19");
if ($q->EOF)
    echo "No providers found<br />";
else{
    echo "<table border = 1><tr><td>Id</td><td>Name</td><td>ShortName</td></tr>";
    while (!$q->EOF){
        echo "<tr><td>{$q->Fields['pid']}</td><td>{$q->Fields['n']}</td><td>{$q->Fields['sn']}</td></tr>";
        $q->Next();
    }
    echo "</table><br />";
    echo '<strong>'.$q->Position.' total</strong><br /><br />';
}
drawFooter();
?>
