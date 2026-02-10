<?
$schema = "resetAccountHistory";
require "start.php";
drawHeader("Reset account history");

if (isset($_POST['ID'])) {
    $accountID = intval($_POST['ID']);
    $Connection->Execute("update Account set HistoryState = null, HistoryVersion = null where AccountID = '{$accountID}'");
    print "Account history has been reset for <b>AccountID: {$accountID}</b><br /><br />";
}
?>

<form action="/manager/resetAccountHistory.php" method="post" name="s">
    <label for="ID">AccountID:</label>
    <input type="text" name="ID" value="<?=ArrayVal($_GET, "ID")?>"/>
    <br />
    <br />
    <input type="submit" value="Reset" />
</form>
<?

?>
