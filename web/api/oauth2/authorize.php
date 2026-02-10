<?php
require __DIR__ . "/../../kernel/public.php";

/**
 * authorize endpoint.
 */

require_once __DIR__ . "/AWOAuth2.php";

AuthorizeUser();

$sTitle = "Authorization";
$bSecuredPage = true;

$scopeNames  = [
    'accounts' => 'read your account information',
    'debugProxy' => 'access debug proxy'
];

$oauth = new AWOAuth2();
if (isset($_POST['action'])) {
	$oauth->finishClientAuthorization($_POST["action"] == "yes", $_POST);
}
$auth_params = $oauth->getAuthorizeParams();

if (!isset($scopeNames[$auth_params['scope']])) {
    $Interface->DiePage("Invalid scope");
}

require __DIR__ . "/../../design/header.php";

$Interface->DrawBeginBox("style='margin-left: auto; margin-right: auto; width: 660px;'", "Authorization", false, "");

?>
<div style="padding: 20px;">
	<form method="post" name="oa2">
		<input type="hidden" name="action"/>
		<?php foreach ($auth_params as $k => $v) { ?>
		<input type="hidden" name="<?php echo $k ?>" value="<?php echo $v ?>"/>
		<?php } ?>
		Do you authorize <strong><?=$auth_params['client_id']?></strong> to <?=$scopeNames[$auth_params['scope']]?> ?
		<div style="padding-top: 20px;">
			<?=$Interface->DrawButton("Yes, Authorize", "onclick=\"var form = document.forms['oa2']; form.action.value = 'yes'; form.submit(); return false;\"")?>
			<?=$Interface->DrawButton("No", "onclick=\"var form = document.forms['oa2']; form.action.value = 'no'; form.submit(); return false;\"")?>
		</div>
	</form>
</div>
<?

$Interface->DrawEndBox();

require __DIR__ . "/../../design/footer.php";

