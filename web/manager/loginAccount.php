<?

use AwardWallet\MainBundle\Security\StringSanitizer;

$schema = "loginAccount";
require "start.php";
drawHeader("Account info", "");

global $Interface;

$sql = "
	SELECT ProviderID, DisplayName 
	FROM Provider
	ORDER BY Name";
$q = new TQuery($sql);
?>
<style>

</style>
<form action="/manager/loginAccount.php" method="get" name="s">
    <label>Provider:</label>
    <select style="width: 200px;" name="ProviderID" onchange="document.forms['s'].elements['UnusedID'].value=this.value;">
        <?
        while(!$q->EOF){
            echo "<option value='{$q->Fields['ProviderID']}' ".((isset($_GET['ProviderID']) && $q->Fields['ProviderID'] == $_GET['ProviderID'])?'selected="selected"':'')." >{$q->Fields['DisplayName']}</option>";
            $q->Next();
        }
        ?>
    </select>
    <select name="UnusedID" onchange="document.forms['s'].elements['ProviderID'].value=this.value;" disabled="disabled">
        <?
        $sql = "
            SELECT ProviderID, Code
            FROM Provider
            ORDER BY Code";
        $q = new TQuery($sql);
        while(!$q->EOF){
            echo "<option value='{$q->Fields['ProviderID']}' ".((isset($_GET['ProviderID']) && $q->Fields['ProviderID'] == $_GET['ProviderID'])?'selected="selected"':'')." >{$q->Fields['Code']}</option>";
            $q->Next();
        }
        ?>
    </select>
    <br />
    <label for="Login">Login:</label>
    <input type="text" name="Login" size="35" onchange="document.forms['s'].elements['ID'].value='';" onClick="document.forms['s'].elements['Login'].select();" style="margin-left: 17px;" value="<?=ArrayVal($_GET, "Login")?>"/>
    <div> <b> or </b> </div>
    <label for="ID">AccountID:</label>
    <input type="text" name="ID" onchange="document.forms['s'].elements['Login'].value='';" onClick="document.forms['s'].elements['ID'].select();" value="<?=ArrayVal($_GET, "ID")?>"/>
    <br />
    <br />
    <input type="submit" value="Find Info" />
</form>
<script type="text/javascript">
    document.forms['s'].elements['UnusedID'].disabled = false;
</script>
<?

/** @var \Doctrine\DBAL\Connection $connection */
$connection = getSymfonyContainer()->get('doctrine')->getConnection();

if (!empty($_GET["ProviderID"]) && !empty($_GET['Login'])) {
    $stmt = $connection->executeQuery("
        select a.UserID, a.AccountID, a.Login, p.DisplayName, p.Code, a.ProviderID
        from Account as a
        join Provider as p on p.ProviderID = a.ProviderID
        where a.ProviderID = ?
        and a.Login = ?",
        [$_GET['ProviderID'], $_GET['Login']]
    );
}
elseif (isset($_GET['ID'])) {
	$stmt = $connection->executeQuery("
        select a.UserID, a.AccountID, a.Login, p.DisplayName, p.Code, a.ProviderID
        from Account as a
        join Provider as p on p.ProviderID = a.ProviderID
        where AccountID = ?",
        [$_GET['ID']]
    );
}
	if(isset($stmt) && ($qFields = $stmt->fetchAssociative())) {
?>
<br />
Provider: <a href="/manager/list.php?Schema=Provider&ProviderID=<?=$qFields['ProviderID']?>" title="LPs" target=\"_blank\"><?=$qFields['Code']?></a> &mdash; <?=$qFields['DisplayName']?><br />
UserID: <a href="/manager/impersonate?UserID=<?=$qFields['UserID']?>" title="Impersonate" target=\"_blank\"><?=$qFields['UserID']?></a>
<?
    if (isset($qFields['AccountID']))
        echo "<br />AccountID: <a href=\"/manager/impersonate?UserID={$qFields['UserID']}&AutoSubmit&AwPlus=1\" title=\"Auto Impersonate\" target=\"_blank\">{$qFields['AccountID']}</a>";
    if (isset($qFields['Login'])) {
        echo "<br /> Login: <a href=\"/manager/passwordVault/requestPassword.php?ID={$qFields['AccountID']}\" title=\"Password request\" target=\"_blank\">". StringSanitizer::encodeHtmlEntities($qFields['Login'])."</a>";
    }

    echo "<br /><br /><a href=\"/manager/list.php?UserID={$qFields['UserID']}&s1.x=0&s1.y=0&Login=&FirstName=&LastName=&Email=&EmailVerified=&LastUserAgent=&LogonCount=&CameFrom=&DefaultBookerID=&Referer=&ProvidersCount=&PlansCount=&AccountLevel=&Schema=UserAdmin\" title=\"Open UserAdmin sсhema\" target=\"_blank\">Open User info</a>";

	} else {
        if (isset($_GET['ID']) || isset($_GET["ProviderID"], $_GET['Login']))
		    echo "<br/><span style='color:red;'>Account not found</span>";
        else
            $Interface->FooterScripts[] = "setTimeout(function(){ document.forms['s'].elements['UnusedID'].value=document.forms['s'].elements['ProviderID'].value; }, 0);";
	}
drawFooter();
?>
