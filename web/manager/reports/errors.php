<?
$schema = "Offer";
require "../start.php";
drawHeader("Errors");
$pid = intval($_GET["ProviderID"]);
if ($pid > 0){
    $q = new TQuery("select distinct ErrorMessage from Account where ProviderID = $pid");
}
echo "<table border=1>";
while (!$q->EOF){
    echo "<tr><td>".$q->Fields["ErrorMessage"]."</td></tr>";
    $q->Next();
}
echo "</table>";
drawFooter();
?>
