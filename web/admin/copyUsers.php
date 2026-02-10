<?
require( "../kernel/public.php" );
require_once( "$sPath/kernel/TForm.php" );
require_once( "$sPath/kernel/TSchemaManager.php" );

$schemaManager = new TSchemaManager();

require( "$sPath/lib/admin/design/header.php" );

echo "<h1>Copy users with all dependencies</h1>";

$form = new TBaseForm(array(
	"User" => array(
		"Type" => "string",
		"Required" => true,
		"Value" => "",
		"Caption" => "From User",
		"Note" => "ID, Login, or email"
	),
	"Login" => array(
		"Type" => "string",
		"Required" => true,
		"Note" => "New user login",
		"Caption" => "New login",
	),
	"Email" => array(
		"Type" => "string",
		"Required" => true,
		"Note" => "New user email",
		"Caption" => "New email",
	),
	"Pass" => array(
		"Type" => "string",
		"Required" => true,
		"Caption" => "New password",
		"Note" => "New user password",
	),
	"Preview" => array(
		"Type" => "boolean",
		"Required" => true,
		"Caption" => "Preview only",
	),
));
$form->SubmitButtonCaption = "Copy table row";
$form->OnCheck = "checkForm";

if($form->IsPost && $form->Check()){
	ob_end_flush();
	$userId = $form->Fields['User']['Row']['UserID'];
	echo "<h2>preparing data for user $userId</h2>";
	$excludeRows = array();
	$qAgents = new TQuery("select ua.UserAgentID, au.UserAgentID as BackID from UserAgent ua
	left outer join UserAgent au on ua.AgentID = au.ClientID and ua.ClientID = au.AgentID
	where ua.AgentID = {$userId} and ua.ClientID is not null");
	while(!$qAgents->EOF){
		$excludeRows[] = "UserAgent_".$qAgents->Fields['UserAgentID'];
		$excludeRows[] = "UserAgent_".$qAgents->Fields['BackID'];
		$qAgents->Next();
	}
	echo "<h2>searching</h2>";
	$rows = $schemaManager->ChildRows("Usr", $form->Fields['User']['Row'], $excludeRows, function(string $table, $id) {
	    return !in_array($table, ['Session', 'QsTransaction', 'OA2Token', 'OA2Code', 'PasswordVaultUser', 'PasswordVault', 'PasswordVaultLog', 'Coupon', 'MobileDevice']);
    });
	$rows[] = array(
		"Table" => 'Usr',
		"ID" => $userId,
		"Files" => $schemaManager->RowFiles("Usr", $form->Fields['User']['Row']),
	);
	$rows = array_reverse($rows);
	echo "<h2>loading</h2>";
	$schemaManager->loadRows($rows);
	$rows[0]['Values']['Login'] = $form->Fields['Login']['Value'];
	$rows[0]['Values']['Email'] = $form->Fields['Email']['Value'];
	$rows[0]['Values']['Pass'] = getSymfonyPasswordEncoder()->encodePassword($form->Fields['Pass']['Value'], null);
	$rows[0]['Values']['RefCode'] = RandomStr(ord('a'), ord('z'), 10);
	if($form->Fields['Preview']['Value'] == '1'){
		echo "<h2>Preview</h2>";
		echo "<pre>".print_r($rows, true)."</pre>";
		echo "<h2>done</h2>";
	}
	else{
		echo "<h2>copying</h2>";
		$schemaManager->CopyRows($rows);
		echo "<h2>done, copied user {$userId}, new login: {$form->Fields['Login']['Value']}, pass: {$form->Fields['Pass']['Value']}</h2>";
	}
}

echo $form->HTML();

require( "$sPath/lib/admin/design/footer.php" );

function checkForm(){
	global $form;
	$form->CalcSQLValues();
	$user = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\EntitySerializer::class)->entityToArray(getSymfonyContainer()->get("aw.manager.user_manager")->findUser($form->Fields['User']['Value'], false));
	if(!isset($user))
		return "User not found";
	$form->Fields['User']['Row'] = $user;
	foreach(array("Login", "Email") as $field){
		$q = new TQuery("select * from Usr where {$field} = {$form->Fields[$field]['SQLValue']}");
		if(!$q->EOF)
			return "User with this {$field} already exists";
	}
	return null;
}

