<?
require "../kernel/public.php";
require_once "$sPath/schema/PasswordVault.php";
require_once "$sPath/manager/passwordVault/common.php";

$sTitle = "Share account";

require "$sPath/lib/admin/design/header.php";

$accountId = intval(ArrayVal($_GET, 'ID'));
$user = ArrayVal($_GET, 'User');
$qUser = new TQuery("select UserID, Email from Usr where Login = '".addslashes($user)."'");
if($qUser->EOF)
	die("User $user not found");
$issue = ArrayVal($_GET, 'Issue');
$q = new TQuery("select
	a.Login,
	a.UserID,
	a.AccountID,
	p.Code,
	p.DisplayName,
	u.FirstName,
	u.LastName
from
	Account a
	join Usr u on a.UserID = u.UserID
	left outer join Provider p on a.ProviderID = p.ProviderID
where
	a.AccountID = ".$accountId);
if(!$q->EOF){
	echo "User {$user} requested access to account:<br/>
	<br/>
	Provider: {$q->Fields['DisplayName']} / {$q->Fields['Code']}<br/>
	Account owner: {$q->Fields['FirstName']} {$q->Fields['LastName']} ({$q->Fields['UserID']})<br/>
	Login: {$q->Fields['Login']}<br/>
	AccountID: {$q->Fields['AccountID']}<br/>";
	if($issue != "")
		echo "Issue: http://redmine.awardwallet.com/issues/{$issue}<br/>";
	$qVault = new TQuery("select * from PasswordVault where AccountID = $accountId");
	echo "<br/>";
	if(!$qVault->EOF){
		$expirationDate = $Connection->SQLToDateTime($qVault->Fields['ExpirationDate']);
		echo "This PasswordVault record already exists. Approved: {$qVault->Fields['Approved']}, Expiration date: ".date(DATE_FORMAT, $expirationDate).".<br/>";
		if($expirationDate < time())
			echo "<b>Record expired!</b><br/>";
	}
	else
		echo "PasswordVault record not exists.<br/>";
	if($_SERVER['REQUEST_METHOD'] == 'POST' && isValidFormToken()){
		echo "<br/>";
		if(isset($_POST['Allow'])){
			echo "Request allowed.<br/>";
			if($qVault->EOF){
				$Connection->Execute("insert into PasswordVault(UserID, AccountID, CreationDate, ExpirationDate, IssueID)
				values({$qUser->Fields['UserID']}, $accountId, now(), adddate(now(), interval 1 month), '".addslashes($issue)."')");
				$qVault->Close();
				$qVault->Open();
				sharePasswordToStaff($qVault->Fields['PasswordVaultID']);
				echo "PasswordVault record created for all Staff.<br/>";
			}
			else
				$Connection->Execute("update PasswordVault set Approved = 1, ExpirationDate = adddate(now(), interval 1 month)
				where PasswordVaultID = ".$qVault->Fields['PasswordVaultID']);
			notifyApproved($qVault->Fields['PasswordVaultID']);
			echo "<a href='/lib/admin/table/edit.php?Schema=PasswordVault&ID={$qVault->Fields['PasswordVaultID']}'>Fine tune</a><br/>";
		}
		if(isset($_POST['Deny'])){
			mail($qUser->Fields['Email'].", alexi@awardwallet.com, vladimir@awardwallet.com", 'Account request #'.$accountId, "Request denied", EMAIL_HEADERS);
			echo "Request denied<br/>";
			if(!$qVault->EOF)
				$Connection->Execute("delete from PasswordVault where PasswordVaultID = ".$qVault->Fields['PasswordVaultID']);
		}
	}
	else
		echo "<br/><form method='post'>
            <input type='hidden' name='FormToken' value='" . GetFormToken() . "'>
			<input type='submit' name='Allow' value='Allow'/>
			<input type='submit' name='Deny' value='Deny'/>
		</form>";
}
else
	echo("Account $accountId not found");

require "$sPath/lib/admin/design/footer.php";
?>
