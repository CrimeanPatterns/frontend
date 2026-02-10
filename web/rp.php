<?php

// -----------------------------------------------------------------------
// reset password
//		reset forgotten password to user
//		URL parameters:
//			id: UserID
//			c: parameter in URL, containig password hash
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

require( "kernel/public.php" );
require_once( "$sPath/kernel/TForm.php" );
require_once( "$sPath/kernel/ComplexPasswordForm.php" );

if(NDInterface::enabled())
	Redirect(getSymfonyContainer()->get("router")->generate("aw_profile_change_password_feedback", ["id" => ArrayVal($_GET, 'id'), "code" => ArrayVal($_GET, 'c')]));

if(!empty($_GET['id']) || !empty($_GET['c'])) {
	$_SESSION['RpCode'] = ArrayVal($_GET, 'c');
	$_SESSION['RpID'] = ArrayVal($_GET, 'id');
	session_regenerate_id();
	Redirect("/rp.php");
}

$sTitle = "Reset password";
$bSecuredPage = False;

$nID = intval(ArrayVal($_SESSION, 'RpID'));
$sCode = ArrayVal($_SESSION, 'RpCode');
$QS['ID'] = $nID;
$_GET['ID'] = $nID;

require( "$sPath/design/header.php" );
echo getSymfonyContainer()->get("twig")->render("@AwardWalletMain/Profile/PersonalInfo/_passwordPolicyPopup.html.twig");
?>
<div align="center">
<?
$q = new TQuery( "select UserID, Login, Email, Pass from Usr where UserID = $nID and ResetPasswordCode = '" . addslashes( $sCode ) . "' and ResetPasswordDate > DATE_SUB(NOW(), INTERVAL 3 DAY)" );
if( $q->EOF )
	$Interface->DrawMessage( "User with this code not found or this password reset link has already expired. Try to <a href=https://awardwallet.com/?forgotPassword=1>re-send password reset request</a>", "error" );
else
{
	$formClass = "TForm";
	$initScript = "passwordComplexity.init($('#fldPass'), function(){ return '{$q->Fields['Login']}'; }, function(){ return '{$q->Fields['Email']}'; } );";
	if(getSymfonyContainer()->get("security.authorization_checker")->isGranted("SITE_ND_SWITCH"))
		$Interface->FooterScripts[] = "require(['lib/passwordComplexity'], function(passwordComplexity){ $initScript });";
	else {
		$Interface->ScriptFiles[] = "/assets/awardwalletnewdesign/js/lib/passwordComplexity.js";
		$Interface->FooterScripts[] = $initScript;
	}
	$formClass = "ComplexPasswordForm";

	$objForm = new $formClass(array(
		"Pass" => array(
			"Caption" => "New Password",
			"Note" => "8-32 characters",
			"Type" => "string",
			"InputType" => "password",
			"Size" => 32,
			"MinSize" => 8,
			"Encoding" => "symfonyPasswordEncoding",
			"Cols" => 20,
			"HTML" => true,
			"Required" => True),
		"PassConfirm" => array(
			"Caption" => "Confirm Password",
			"Type" => "string",
			"InputType" => "password",
			"Size" => 32,
			"MinSize" => 8,
			"Cols" => 20,
			"HTML" => true,
			"Required" => True,
			"Database" => False)
	));
	$objForm->Connection = $Connection;
	$objForm->SubmitButtonCaption = "Set new password";
	$objForm->TableName = "Usr";
	$objForm->KeyField = "UserID";
	$objForm->titleTxt = "Reset password";
	$objForm->SuccessURL = NULL;

	$objForm->Edit( False );

	if( $objForm->IsPost )
	{
		if( !isset( $objForm->Error ) )
		{
			$sNewPassword = $objForm->Fields["Pass"]["Value"];
			$Connection->execute("update Usr set ResetPasswordCode = null where UserID = $nID");
            // reset remember-me tokens
            getSymfonyContainer()->get(\AwardWallet\MainBundle\Security\RememberMe\RememberMeTokenProvider::class)->deleteTokenByUserId($nID);
			$q = new TQuery( "select Login from Usr where UserID = $nID" );
			$Interface->DrawMessage( "Your password has been reset. Now you can <a href='/?Login=1'>login to site</a> using login \"{$q->Fields["Login"]}\" and your new password", "info" );
		}
		else
			echo $objForm->HTML();
	}
	else
		echo $objForm->HTML();
}

?>
</div>
<?
require( "$sPath/design/footer.php" );
