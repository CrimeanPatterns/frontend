<?
require "../kernel/public.php";
if(!isset($_SESSION['UserID']))
	die("Unauthorized");

requirePasswordAccess();
checkAjaxCSRF();

$email = $_POST['email'];
if(filter_var($email, FILTER_VALIDATE_EMAIL)){
	$email = addslashes($email);
	
	$uID = Lookup('Usr', 'Email', 'UserID', "'{$email}'");
	if(isset($uID) && $uID != $_SESSION['UserID']){
		echo "User with this email already exists. Please choose another email";
		exit;
	}
	
	$Connection->Execute("update Usr set EmailVerified = ".EMAIL_UNVERIFIED.", Email = '{$email}' where UserID = {$_SESSION['UserID']}");
//	$Connection->Execute("update EmailNDR set Cnt = 0 where Address = '{$email}'");
    $container = getSymfonyContainer();
    $tokenProvider = $container->get(\AwardWallet\MainBundle\Security\RememberMe\RememberMeTokenProvider::class);
    $tokenProvider->deleteTokenByUserId($_SESSION['UserID']);
    $userManager = $container->get("aw.manager.user_manager");
    $userManager->refreshToken();
	$_SESSION['EmailVerified'] = EMAIL_UNVERIFIED;
	$_SESSION['UserFields']['EmailVerified'] = EMAIL_UNVERIFIED;	
	echo "OK";	
} else {
	echo "Email - Invalid Value.";
}
?>