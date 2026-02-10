<?
$schema = "passwords";
require("../start.php");
require_once "$sPath/kernel/TForm.php";
require_once "$sPath/manager/passwordVault/common.php";

drawHeader("Request password");

global $Interface;

$form = new TForm(array(
	"AccountID" => array(
		"Type" => "integer",
		"Caption" => "Account ID",
		"Required" => true,
		"Value" => ArrayVal($_GET, 'ID'),
	),
	"Issue" => array(
		"Type" => "string",
		"Caption" => "Issue #",
		"Required" => false,
	),
));
$form->SubmitButtonCaption = "Request password";
$form->OnCheck = "checkForm";

if($form->IsPost && $form->Check()){
	$pvId = requestPassword($form->Fields['AccountID']['Value'], $_SESSION['Login'], (int)$form->Fields['Issue']['Value']);
	if(!empty($pvId))
		echo "<div class='successFrm'>Request auto approved, <a href='get.php?ID={$pvId}'>view it</a> </div>";
	else
		echo "<div class='successFrm'>Request has been sent</div>";
}

if (isset($_GET['AutoSubmit']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $Interface->FooterScripts[] = "setTimeout(function(){ document.forms['editor_form'].submit(); }, 0);";
}

echo $form->HTML();

echo "<script type='text/javascript'>
$(function(){
	$('#fldAccountID').click(function () {
        $(this).select();
    });
    $('#fldIssue').click(function () {
        $(this).select();
    });
});
</script>";

drawFooter();

function checkForm(){
	global $form;
	$accountId = intval($form->Fields['AccountID']['Value']);
	$q = new TQuery("select
		a.AccountID, a.SavePassword, p.Kind
	from
		Account a
		join Provider p on a.ProviderID = p.ProviderID
	where
		a.AccountID = $accountId");
	if($q->EOF)
		return "Account {$form->Fields['AccountID']['Value']} not found";
	if($q->Fields['SavePassword'] == SAVE_PASSWORD_LOCALLY)
		return "This account has locally saved password. You can't request it";
	if($q->Fields['Kind'] == PROVIDER_KIND_CREDITCARD && !TPasswordVaultSchema::canRequestCC($_SESSION['UserID']))
		return "This is credit card provider. You can't request password for it";
	return null;
}

?>
