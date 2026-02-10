<?

require( "../../kernel/public.php" );
require_once( "../../account/common.php" );

$nID = intval( $QS["ID"] );
$q = new TQuery("select a.*, p.ProviderGroup
	from Account a
	join Provider p on a.ProviderID = p.ProviderID
	where a.AccountID = $nID");
if ( $q->EOF ){
	die("Account not found");
}
try {
	$options = CommonCheckAccountFactory::getDefaultOptions();
	$options->checkIts = true;
	CommonCheckAccountFactory::checkAndSave($nID, $options);
} catch (Exception $e) {
	if (!processCheckException($e))
		DieTrace($e->getMessage(), false);
	else
		die($e->getMessage());
}
Redirect(urlPathAndQuery($_SERVER['HTTP_REFERER']));

?>