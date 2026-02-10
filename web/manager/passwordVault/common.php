<?

require_once __DIR__."/../../schema/PasswordVault.php";

function notifyApproved($passwordVaultId){
	$q = new TQuery("select
		pv.*,
		p.DisplayName,
		p.Code,
		u.Email,
		concat(u.FirstName, ' ', u.LastName) as UserName
	from
		PasswordVault pv
		join Usr u on pv.UserID = u.UserID
		join Account a on pv.AccountID = a.AccountID
		join Provider p on a.ProviderID = p.ProviderID
	where
		pv.PasswordVaultID = $passwordVaultId");
	mail(
		$q->Fields['Email'].", alexi@awardwallet.com, vladimir@awardwallet.com", 'Account request #'.$q->Fields["AccountID"],
		"Work can begin.

code: {$q->Fields['Code']}".($q->Fields["IssueID"] != ""?"
issue: https://redmine.awardwallet.com/issues/{$q->Fields["IssueID"]}":"")."
account: https://awardwallet.com/manager/passwordVault/get.php?ID={$q->Fields['PasswordVaultID']}
account owner: {$q->Fields['UserID']}
", EMAIL_HEADERS);
}

function approveAndExtend($passwordVaultId){
	global $Connection;
	$q = new TQuery("select
		p.Kind,
		pv.UserID
	from
		PasswordVault pv
		left outer join Account a on pv.AccountID = a.AccountID
		left outer join Provider p on a.ProviderID = p.ProviderID
	where
		pv.PasswordVaultID = ".$passwordVaultId);
	$cc = ($q->Fields['Kind'] == PROVIDER_KIND_CREDITCARD);
	$ccOperator = TPasswordVaultSchema::canRequestCC($q->Fields['UserID']);
	if($cc && $ccOperator)
		$period = 1;
	else
		$period = 30;
	$Connection->Execute("update PasswordVault
	set Approved = 1, ExpirationDate = case when ExpirationDate > adddate(now(), $period) then ExpirationDate else adddate(now(), $period) end
	where PasswordVaultID = $passwordVaultId");
}

function sharePasswordToStaff($passwordVaultId, $groupName = null){
	global $Connection;
	$q = new TQuery("select
		p.Kind
	from
		PasswordVault pv
		join Account a on pv.AccountID = a.AccountID
		join Provider p on p.ProviderID = a.ProviderID
	where
		pv.PasswordVaultID = $passwordVaultId");
	if(!isset($groupName))
		if(!$q->EOF && $q->Fields['Kind'] == PROVIDER_KIND_CREDITCARD)
			$groupName = 'Admins,Credit card passwords';
		else
			$groupName = 'Staff';
	foreach(explode(',', $groupName) as $group)
		$Connection->Execute("insert into PasswordVaultUser(
			PasswordVaultID,
			UserID
		)
		select
			{$passwordVaultId},
			u.UserID
		from
			Usr u
			join GroupUserLink gl on u.UserID = gl.UserID
			join SiteGroup g on gl.SiteGroupID = g.SiteGroupID
			left outer join PasswordVaultUser pvu on pvu.PasswordVaultID = {$passwordVaultId} and pvu.UserID = u.UserID
		where
			g.GroupName = '".addslashes($group)."'
			and pvu.UserID is null");
}

/**
 * @param $accountId
 * @param $login
 * @param $case
 * @param null $passwordVaultId
 * @return int passwordVaultId when request auto approved, or null
 */
function requestPassword($accountId, $login, $case, $passwordVaultId = null){
	global $Connection;
	$result = null;
	$qUser = new TQuery("select
		u.UserID,
		concat(u.FirstName, ' ', u.LastName) as UserName
	from Usr u
		join GroupUserLink gl on u.UserID = gl.UserID
		join SiteGroup g on gl.SiteGroupID = g.SiteGroupID
	where
		g.GroupName = 'Staff' and u.Login = '".addslashes($login)."'");
	if($qUser->EOF)
		DieTrace("User $login not found, or not in staff");
	$cc = false;
	if($accountId > 0){
		$q = new TQuery("select
			a.Pass,
			a.Login,
			a.UserID,
			a.AccountID,
			ua.ClientID,
			p.Code,
			p.DisplayName,
			u.FirstName,
			u.LastName,
			p.Kind
		from
			Account a
			join Usr u on a.UserID = u.UserID
			left outer join Provider p on a.ProviderID = p.ProviderID
			left outer join UserAgent ua on a.UserAgentID = ua.UserAgentID
		where
			a.AccountID = ".$accountId);
		$cc = $q->Fields['Kind'] == PROVIDER_KIND_CREDITCARD;
		$body = "{$qUser->Fields['UserName']} requested to work on:

Provider: {$q->Fields['DisplayName']} / {$q->Fields['Code']}
User ID: {$q->Fields['UserID']}
AccountID: {$q->Fields['AccountID']}
";
	}
	else{
		$body = "{$qUser->Fields['UserName']} requested to work on:

Provider: custom account
Login: {$login}
PasswordValueID: {$passwordVaultId}
";
	}
	if(!isset($passwordVaultId)){
		if($cc && TPasswordVaultSchema::canRequestCC($qUser->Fields['UserID']))
			$period = 3;
		else
			$period = 30;
		$Connection->Execute("insert into PasswordVault(UserID, AccountID, CreationDate, ExpirationDate, IssueID, Approved)
		values({$qUser->Fields['UserID']}, $accountId, now(), adddate(now(), interval $period day), " . ((int)$case) . ", 0)");
		$passwordVaultId = $Connection->InsertID();
	}
	else
		$Connection->Execute("update PasswordVault set UserID = {$qUser->Fields['UserID']}, Approved = 0
		where PasswordVaultID = {$passwordVaultId}");

	$autoApproved = TPasswordVaultSchema::GetUsers('Auto approve password', "u.UserID");
	$autoApproveCc = TPasswordVaultSchema::GetUsers('Auto approve CC', "u.UserID");
	if((!$cc || in_array($qUser->Fields['UserID'], $autoApproveCc)) && in_array($qUser->Fields['UserID'], $autoApproved)){
		sharePasswordToStaff($passwordVaultId, 'Auto approve password');
		approveAndExtend($passwordVaultId);
		$result = $passwordVaultId;
	}
	else{
		sharePasswordToStaff($passwordVaultId);
		$qPending = new TQuery("select count(*) as Cnt from PasswordVault where Approved = 0");
		$pending = $qPending->Fields['Cnt'];
		if($case != "")
			$body .= "Issue: https://redmine.awardwallet.com/issues/{$case}\n";
		$body .= "\nGrant or deny:
https://{$_SERVER['HTTP_HOST']}/admin/shareAccount.php?ID=$accountId&Issue=".urlencode($case)."&User=".urlencode($login)."\n

All pending requests ($pending):
https://{$_SERVER['HTTP_HOST']}/lib/admin/table/list.php?Schema=PasswordVault&Approved=0\n
";
		mailTo(
			'alexi@awardwallet.com, vladimir@awardwallet.com',
			'Account request #'.$accountId,
			$body,
			EMAIL_HEADERS
		);
	}
	return $result;
}

/**
 * @param $providerId
 * @param $login
 * @param $login2
 * @param $password
 * @return pv ID if record was added otherwise false
 */
function addToPasswordVault($providerId, $login, $login2, $login3, $password, $userId = null, $partner = null, $answers = []){
	global $Connection;
	$result = false;
	$partner = addslashes($partner);
	$login = addslashes($login);
	$login2 = addslashes($login2);
	$login3 = addslashes($login3);
	$password = addslashes($password);
	$providerId = intval($providerId);
	$sql = "SELECT  * FROM PasswordVault
	WHERE Partner = '{$partner}' AND Login = '{$login}' AND Login2 = '{$login2}' AND Login3 = '{$login3}' AND Pass = '{$password}' AND ProviderID"
	.(empty($providerId)?" is null":" = $providerId");
	$userId = intval($userId);
	if(empty($userId) && isset($_SESSION['UserID']))
		$userId = $_SESSION['UserID'];
	$staff = TPasswordVaultSchema::GetUsers();
	if(empty($userId) || !isset($staff[$userId]))
		$userId = getBotUserId();
	$q = new TQuery($sql);
	if(!$q->EOF)
		if(!empty($q->Fields['Answers']))
			$answers = array_merge(json_decode($q->Fields['Answers'], true), $answers);
	if(empty($answers))
		$answers = '';
	else
		$answers = addslashes(json_encode($answers));
	if(!$q->EOF){
		$Connection->Execute("UPDATE PasswordVault SET ExpirationDate = adddate(now(), interval 1 month), Answers = '$answers'
		WHERE PasswordVaultID = {$q->Fields['PasswordVaultID']}");
		sharePasswordToStaff($q->Fields['PasswordVaultID']);
        $result = $q->Fields['PasswordVaultID'];
	} else {
		if(empty($providerId))
			$providerId = "null";
		$sql = "
		INSERT INTO PasswordVault (Partner, Login, Login2, Login3, Pass, IssueID, CreationDate, ExpirationDate, Approved, UserID, ProviderID, Answers)
		VALUES('$partner', '$login', '$login2', '$login3', '$password', '0', now(), adddate(now(), interval 1 month), 1, $userId, $providerId, '$answers')
		";
		$Connection->Execute($sql);
		$ID = $Connection->InsertID();
		sharePasswordToStaff($ID);
		$result = $ID;
	}
	$Connection->Execute("delete from PasswordVault where ExpirationDate < adddate(now(), -365)");
	return $result;
}

function getBotUserId(){
	return Lookup("Usr", "Login", "UserID", "'AwardWalletBot'", true);
}

function searchPasswordVault($accountId, $login){
	$sql = "select pv.PasswordVaultID, pv.Approved
	from
		PasswordVault pv
		join PasswordVaultUser pvu on pv.PasswordVaultID = pvu.PasswordVaultID
	where
		pv.ExpirationDate > now()
		and pvu.UserID = {$_SESSION['UserID']}";
	if(isset($accountId))
		$sql .= " and pv.AccountID = $accountId";
	else
		$sql .= " and pv.Login = '".addslashes($login)."' and pv.AccountID is null";
	$q = new TQuery($sql);
	if($q->EOF)
		return null;
	else
		return $q->Fields;
}