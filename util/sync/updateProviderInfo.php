#!/usr/bin/php
<?
require __DIR__."/../../web/kernel/public.php";
require_once __DIR__."/../../web/lib/3dParty/Cli/Cli.php";

$um = getSymfonyContainer()->get("aw.manager.user_manager");

$cliHelp = new CliHelp();
$cliHelp->setUsageScript(basename(__FILE__))
		 ->setCLIParams(
		 	array(
		 		'server:' => array(
			 		'short' 	=> 's:',
			 		'desc'		=> 'awardwllet server, to work with',
			 		'default'	=> 'awardwallet.com',
			 	),
		 	)
		 )
		->setExample(basename(__FILE__));
$cli = new Cli($cliHelp, false);
$result = $cli->validate();
$input = $cli->getInput();

$cli->Log("updating provider options\n");

/** @var \AwardWallet\MainBundle\Entity\Usr $user */
$user = getSymfonyContainer()->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find(7);
$um->loadToken($user, false, \AwardWallet\MainBundle\Manager\UserManager::LOGIN_TYPE_ADMINISTRATIVE);

$q = new TQuery("select * from Provider where State >= ".PROVIDER_ENABLED);
$login2Options = array();
$login3Options = array();
$authorizationChecker = getSymfonyContainer()->get("security.authorization_checker");
while(!$q->EOF){
	if(($q->Position % 50) == 0)
		$cli->Log("processed {$q->Position} records\n");
	$fields = array('UserAgentID' => TAccountForm::GetUserAgentIDDef(0));
	$fields += TAccountForm::getLoginFieldsDefs($q->Fields, false, 0, false, true, "");
	if(ProviderAPIVersion($q->Fields['Code']) == 3){
		$checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getAccountChecker($q->Fields['Code']);
        $checker->authorizationChecker = $authorizationChecker;

        // for logging, sometime checkers write logs in TuneFormFields
		$checker->AccountFields['Partner'] = 'awardwallet';
		$checker->globalLogger = getSymfonyContainer()->get("logger");
		$checker->UseCurlBrowser();

		$checker->TuneFormFields($fields, array());
	}
	# Login2
	if(isset($fields["Login2"]["Options"]))
		$login2Options[$q->Fields['ProviderID']] = $fields["Login2"]["Options"];
	else
		$login2Options[$q->Fields['ProviderID']] = array();
	$update = ["Login2Required" => !empty($fields['Login2']['Required']) ? 1 : 0];
	# Login3
	if(isset($fields["Login3"]["Options"]))
		$login3Options[$q->Fields['ProviderID']] = $fields["Login3"]["Options"];
	else
		$login3Options[$q->Fields['ProviderID']] = array();
	$update["Login3Required"] = !empty($fields['Login3']['Required']) ? 1 : 0;

	$Connection->Execute(UpdateSQL("Provider", ["ProviderID" => $q->Fields['ProviderID']], $update));

	$q->Next();
}

if(count($login2Options) == 0 && count($login3Options) == 0)
	DieTrace("No login2/login3 options detected. Something wrong.");

# Save
$ThrowErrorExceptions = true;
$Connection->Execute("start transaction");
try {
	$Connection->Execute("delete from ProviderInputOption");
	$count = 0;
	$index = 0;

	if (count($login2Options) > 0)
		$count += saveOptions($login2Options, 'Login2', 'ProviderInputOption', 'Code', 'Name', true, $index);

	if (count($login3Options) > 0)
		$count += saveOptions($login3Options, 'Login3', 'ProviderInputOption', 'Code', 'Name', true, $index);

	$Connection->Execute("commit");
	echo "saved {$count} rows to ProviderInputOption table\n";
} catch(Exception $e){
	$cli->addError("error, rolling back", true);
	$Connection->Execute("rollback");
	DieTrace($e->getMessage());
}

function saveOptions($options, $fieldName, $table, $codeField, $nameField, $withSortIndex, & $startIndex = 0){
	global $Connection;
	$count = 0;

	foreach($options as $providerId => $providerOptions){
		$codes = array();
		foreach($providerOptions as $code => $name){
			if (in_array(mb_convert_case($code, MB_CASE_LOWER), $codes))
				continue;
			$codes[] = mb_convert_case($code, MB_CASE_LOWER);
			$values = array(
				$codeField => mysql_quote($code),
				$nameField => mysql_quote($name),
				"FieldName"	 => mysql_quote($fieldName),
				"ProviderID" => $providerId,
			);
			if($withSortIndex){
				$values['SortIndex'] = $startIndex;
				$startIndex++;
			}
			$Connection->Execute(InsertSQL($table, $values));
			$count++;
		}
	}

	return $count;
}
