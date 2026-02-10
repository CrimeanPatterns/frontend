<?
$schema = "checkAccountHistory";
require "start.php";
drawHeader("Check Account History");

?>
    <form action="/manager/checkAccount.php" method="get" name="s">
        <label for="ID">AccountID:</label>
        <input type="text" name="ID" value="<?=ArrayVal($_GET, "ID")?>"/>
        <input type="hidden" name="History" value="1"/>
        <br />
        <br />
        <input type="submit" value="Check" />
    </form>
<?

?>
