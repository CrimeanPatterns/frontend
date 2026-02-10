#!/usr/bin/php
<?
require "../web/kernel/public.php";
require_once __DIR__."/../web/lib/3dParty/Cli/Cli.php";

global $Connection;

class deleteAccounts {
	public function getAllAccountIDs($providerID, $loginNotStartsWith, $userId){
		$len = strlen($loginNotStartsWith);
		$q = new TQuery("select AccountID
							from Account
							where ProviderID = {$providerID}
							and SUBSTR(Login, 0, {$len}) != '{$loginNotStartsWith}'
							".($userId != ""?" and UserID = $userId":""));
		if (!$q->EOF){
			return $q;
		}
		else{
			return null;
		}
	}
	
	public function getAccountsCount($providerID, $loginNotStartsWith, $userId){
		$len = strlen($loginNotStartsWith);
		$q = new TQuery("select count(*) as Cnt
							from Account
							where ProviderID = {$providerID}
							and SUBSTR(Login, 0, {$len}) != '{$loginNotStartsWith}'
							".($userId != ""?" and UserID = $userId":""));
		return $q->Fields['Cnt'];
	}
}

$cliHelp = new CliHelp();
$cliHelp->setUsageScript('deleteAccounts.php')
		 ->setCLIParams(
		 	array(
				'providerID:' => array(
			 		'short' 	=> 'p:',
			 		'desc'		=> 'ProviderID',
			 		'regExp'	=> "/^(\d+)$/ims",
			 		'error'		=> "ProviderID must be numeric",
			 		'default'	=> '1', 		// American Airlines
			 		'callback'	=> function ($v) use ($Connection) {
			 			if (is_int($v) && $v <= 0)
			 				return 'providerID must be greater than zero';
			 				
			 			return true;
			 		},
			 	),
				 'userID:' => array(
				 		'short' 	=> 'u:',
				 		'desc'		=> 'UserID',
				 		'regExp'	=> "/^(\d+)$/ims",
				 		'error'		=> "UserID must be numeric",
				 		'default'	=> '',
				 		'callback'	=> function ($v) use ($Connection) {
				 			if (is_int($v) && $v <= 0)
				 				return 'providerID must be greater than zero';

				 			return true;
				 		},
				 	),
				'login:' => array(
			 		'short' 	=> 'l:',
			 		'desc'		=> 'Login not starts with',
			 		'default'	=> 'fake.', 	// for American Airlines Accounts
			 	),
		 	)
		 )
		->setExample('php deleteAccounts.php -p 1 -t 60');

$cli = new Cli($cliHelp, false);
$result = $cli->validate();
$input = $cli->getInput();

if ($input['help'] || is_array($result)) {
	echo $cliHelp;
	if (is_array($result)) {
		$cli->addError($result);
	}
	exit();
}

$deleteAccounts = new deleteAccounts();
$allCount = $deleteAccounts->getAccountsCount($input['providerID'], $input['login'], $input['userID']);
$cli->Log("{$allCount} accounts found\n");
$q = $deleteAccounts->getAllAccountIDs($input['providerID'], $input['login'], $input['userID']);

if ($allCount > 0){
	$deleteCount = 0;
	$cli->createProgressBar($allCount);
	while (!$q->EOF){
		$cli->updateProgressBar($q->Position);
		BrowserMigration::clearAccount($q->Fields['AccountID'], false);
		$q->Next();
		$deleteCount++;
		set_time_limit(60);
	}

	$accLeft = $allCount - $deleteCount;
	if ($q->EOF){
		$cli->addGoodEvent("Success! {$deleteCount} accounts was deleted, {$accLeft} left");
	}
	else{
		$cli->Log("WARNING: {$deleteCount} accounts was deleted, {$accLeft} left.\n");
	}
}
else{
	$cli->Log("Cannot find any accounts\n");
}
?>
