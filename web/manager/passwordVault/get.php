<?php
$schema = "passwords";
require "../start.php";
require_once "$sPath/account/common.php";
require_once "$sPath/schema/PasswordVault.php";
require_once "common.php";

$passwordVaultId = intval(ArrayVal($_GET, "ID"));

$admins = TPasswordVaultSchema::GetUsers('Admins', 'u.UserID');
if(in_array($_SESSION['UserID'], $admins) || TPasswordVaultSchema::canRequestCC($_SESSION['UserID']))
	$ccFilter = "";
else
	$ccFilter = "and (p.Kind is null or p.Kind <> ".PROVIDER_KIND_CREDITCARD.")";

$q = new TQuery("select
	coalesce(a.Login, pv.Login) as Login,
	coalesce(a.Login2, pv.Login2) as Login2,
	coalesce(a.Login3, pv.Login3) as Login3,
	pv.AccountID,
	coalesce(a.Pass, pv.Pass) as Pass,
	pv.PasswordVaultID,
	p.Code,
	p.DisplayName,
	p.ProviderID,
	p.LoginURL,
	p.State as ProviderState,
	p.Questions,
	a.UserID,
	u.FirstName,
	u.LastName,
	u.Email,
	pv.ExpirationDate,
	pv.Approved,
	pv.Partner,
	pv.Answers
from
	PasswordVault pv
	left outer join Account a on pv.AccountID = a.AccountID
	left outer join Provider p on a.ProviderID = p.ProviderID or pv.ProviderID = p.ProviderID
	left outer join Usr u on a.UserID = u.UserID
where
	pv.PasswordVaultID = $passwordVaultId
	{$ccFilter}");

if($q->EOF)
	die("Account not found");

if($q->Fields["Approved"] != '1')
	drawRequestForm("Request does not approved yet. You can repeat the request.", $q->Fields['AccountID'], $q->Fields['PasswordVaultID']);

$qShare = new TQuery("select * from PasswordVaultUser where PasswordVaultID = $passwordVaultId and UserID = {$_SESSION['UserID']}");
if($qShare->EOF)
	drawRequestForm("You do not have access to this account", $q->Fields['AccountID'], $q->Fields['PasswordVaultID']);

if(($q->Fields['ExpirationDate'] != '') && ($Connection->SQLToDateTime($q->Fields['ExpirationDate']) < time()))
	drawRequestForm("This account is expired", $q->Fields['AccountID'], $q->Fields['PasswordVaultID']);

if($q->Fields['ProviderID'] != ""){
	$qAcc = new TQuery("select AccountID from Account
	where ProviderID = {$q->Fields['ProviderID']} and Login = '".addslashes($q->Fields['Login'])."' and UserID = {$_SESSION['UserID']}");
}

$Connection->Execute("insert into PasswordVaultLog(PasswordVaultID, UserID, Event, EventDate)
value({$q->Fields['PasswordVaultID']}, {$_SESSION['UserID']}, 1, now())");

getSymfonyContainer()->get("monolog.logger.security")->warning("password vault access, pv id: {$q->Fields['PasswordVaultID']}, AccountID: {$q->Fields['AccountID']}");

drawHeader("Account password", "");

if(($q->Fields['ProviderID'] != '') && isset($_GET['AddToProfile']) && ($q->Fields['ProviderID'] != "") && $qAcc->EOF){
	$Connection->Execute(InsertSQL("Account", array(
		"UserID" => $_SESSION['UserID'],
		"ProviderID" => $q->Fields['ProviderID'],
		"Login" => "'".addslashes($q->Fields["Login"])."'",
		"Login2" => "'".addslashes($q->Fields["Login2"])."'",
		"Login3" => "'".addslashes($q->Fields["Login3"])."'",
		"Pass" => "'".addslashes($q->Fields["Pass"])."'",
		"UpdateDate" => "now()",
		"CreationDate" => "now()",
		"PassChangeDate" => "now()",
		"NotRelated" => "1",
		"State" => ACCOUNT_ENABLED
	)));
	Redirect(getSymfonyContainer()->get("router")->generate("aw_account_list") . "#/?update=" . $Connection->InsertID());
}

$q->Fields['Pass'] = getSymfonyContainer()->get(\AwardWallet\Common\PasswordCrypt\PasswordDecryptor::class)->decrypt($q->Fields['Pass']);

echo "<h4>Now you, " . htmlspecialchars($_SESSION['FirstName']) . " " . htmlspecialchars($_SESSION['LastName']) . ", have password to account:</h4>";

if($q->Fields['Partner'] != ''){
	echo "Partner: {$q->Fields['Partner']}<br/>";
}
if($q->Fields['Code'] != ''){
	echo "Provider: <span id='providerCode'>{$q->Fields['Code']}</span> <button style='font-size: 110%;padding:0;background:none;border:none;color:gray;' onclick='copyText(\"#providerCode\")'>&nbsp;❒ Copy</button> / <span id='providerDisplayName'>{$q->Fields['DisplayName']}</span> <button style='font-size: 110%;padding:0;background:none;border:none;color:gray;' onclick='copyText(\"#providerDisplayName\")'>&nbsp;❒ Copy</button><br/><br/>";
}
echo "Login: <span id='login'>".htmlspecialchars($q->Fields['Login'])."</span> <button style='font-size: 110%;padding:0;background:none;border:none;color:gray;' onclick='copyText(\"#login\")'>&nbsp;❒ Copy</button><br/>";
echo "Login2: <span id='login2'>".drawTextAsLink($q->Fields['Login2'])."</span> <button style='font-size: 110%;padding:0;background:none;border:none;color:gray;' onclick='copyText(\"#login2\")'>&nbsp;❒ Copy</button><br/>";
echo "Login3: <span id='login3'>".drawTextAsLink($q->Fields['Login3'])."</span> <button style='font-size: 110%;padding:0;background: none;border:none;color:gray;' onclick='copyText(\"#login3\")'>&nbsp;❒ Copy</button><br/>";
echo "Password: <span id='password' style='font-weight: bold; background-color: yellow; font-size: 110%; padding: 2px;'>".htmlspecialchars($q->Fields['Pass'])."</span> <button style='font-size: 110%;padding:0;background:none;border:none;color:gray;' onclick='copyText(\"#password\")'>&nbsp;❒ Copy</button><br/><br/>";
if(!empty($q->Fields['Answers']))
	echo "Received answers: <pre>".htmlspecialchars(json_encode(json_decode($q->Fields['Answers'], true), JSON_PRETTY_PRINT))."</pre>";

if (!empty($q->Fields['AccountID'])) {
	$qAns = new TQuery("select `Question`, `Answer`, `CreateDate`, `Valid` from `Answer` where `AccountID` = " . $q->Fields['AccountID']);
	if (!$qAns->EOF) {
		echo "Security Questions:";
	echo <<<TABLE
<br/>
<table cellpadding='5' cellspacing='0' class="detailsTable">
	<tr>
		<td style="font-weight: bold;">Question</td>
		<td style="font-weight: bold;">Answer</td>
		<td style="font-weight: bold;">Create Date</td>
	</tr>\n
TABLE;
        while (!$qAns->EOF) {
            $answers[] = array(
                'Question'   => htmlspecialchars($qAns->Fields['Question']),
                'Answer'     => htmlspecialchars($qAns->Fields['Answer']),
                'Valid'      => $qAns->Fields['Valid'],
                'CreateDate' => $qAns->Fields['CreateDate']
            );

			$qAns->Next();
		}
        // Sort by Valid
        usort($answers, function ($a, $b) {
            return $b['Valid'] - $a['Valid'];
        });
        foreach ($answers as $key => $answer) {
            $style = ($answer['Valid']) ? $style = '' : 'style="color:#9C9C9C"';

            echo <<<ROW
	<tr>
		<td {$style}><span id='question{$key}'>{$answer['Question']}</span>  <button style='font-size: 110%;padding:0;background:none;border:none;color:gray;' onclick='copyText("#question{$key}")'>&nbsp;❒ Copy</button></td>
		<td {$style}><span id='answer{$key}'>{$answer['Answer']}</span>  <button style='font-size: 110%;padding:0;background:none;border:none;color:gray;' onclick='copyText("#answer{$key}")'>&nbsp;❒ Copy</button></td>
		<td {$style}>{$answer['CreateDate']}</td>
	</tr>\n
ROW;
        }

		echo "</table><br/>\n";
	}
	else echo " don't collected yet.<br/><br/>\n";
}

if(!empty($q->Fields['AccountID']))
	echo "AccountID: <span id='accountID'>".$q->Fields['AccountID']."</span> <button style='font-size: 110%;padding:0;background:none;border:none;color:gray;' onclick='copyText(\"#accountID\")'>&nbsp;❒ Copy</button> (<a href=\"/manager/loyalty/logs?AccountID=".$q->Fields['AccountID']."\" title=\"Logs of account ".$q->Fields['AccountID']."\" target=\"_blank\">Logs</a>)<br/>";
echo "Provider: ".$q->Fields['DisplayName']."<b> (ID: <a href=\"/manager/list.php?Schema=Provider&ProviderID=".$q->Fields['ProviderID']."\" title=\"LPs\" target=\"_blank\" style=\"font-weight: bold;\">".$q->Fields['ProviderID']."</a>, <a href=\"/manager/list.php?ProviderPropertyID=&ProviderID=".$q->Fields['ProviderID']."&s1_x=7&s1_y=6&Name=&Code=&Required=&SortIndex=&Kind=&Visible=&Schema=ProviderProperty&FormToken=&\" title=\"Provider Properties\" target=\"_blank\" >Properties</a>)</b><br/>";
echo "Login URL: <a href='{$q->Fields['LoginURL']}' target=\"_blank\" title=\"Login URL\">".$q->Fields['LoginURL']."</a><br/>";
echo "State: ".ArrayVal($arProviderState, $q->Fields['ProviderState'])."<br/>";

if(!empty($q->Fields["UserID"]))
	echo "UserID: <a title=\"Impersonate\" href=\"/manager/impersonate?UserID={$q->Fields['UserID']}\" target=\"_blank\">{$q->Fields['UserID']}</a>, <a href=\"/manager/list.php?UserID={$q->Fields['UserID']}&s1.x=0&s1.y=0&Login=&FirstName=&LastName=&Email=&EmailVerified=&LastUserAgent=&LogonCount=&CameFrom=&DefaultBookerID=&Referer=&ProvidersCount=&PlansCount=&AccountLevel=&Schema=UserAdmin\" title=\"Open UserAdmin sсhema\" target=\"_blank\">" . htmlspecialchars($q->Fields['FirstName']) . "  " . htmlspecialchars($q->Fields['LastName']) . "</a> <a href=\"/manager/impersonate?UserID={$q->Fields['UserID']}&AutoSubmit&AwPlus=1\" target=\"_blank\" title=\"Auto impersonate\" style ='color:lightgray'> >> </a><br/>";
echo "<br/>";
if(!empty($q->Fields["Email"]))
    echo "Email parsing queue: <a href='/manager/email/parser/list/all?sort=&direction=&preview=false&region=us-east-1&id=&date=&subject=&from=&to=" . htmlspecialchars($q->Fields['Email']) . "&partner=&providerId=&userData=&show%5B%5D=all&subjectAdv=&fromAdv=&toAdv=&partnerAdv=&providerAdv=&userDataAdv=' target=\"_blank\" title=\"Open parsing queue for primary email\">".htmlspecialchars($q->Fields['Email'])."</a><br/><br/>";

echo '<div style="padding-bottom: 15px;">';
echo "<a href=\"/manager/impersonate?UserID={$q->Fields['UserID']}&Goto=" . urlencode(getSymfonyContainer()->get("router")->generate("aw_account_redirect", ["ID" => $q->Fields['AccountID']])) . "&AutoSubmit&AwPlus=1\" target=\"_blank\" title=\"Auto Login to the site\">Auto Login to the site</a> <a href=\"".getSymfonyContainer()->get("router")->generate("aw_account_redirect", ["ID" => $q->Fields['AccountID']])."\" target=\"_blank\" title=\"Click here if you have already impersonated\" style ='color:lightgray'>direct link</a><br/>";
echo '</div>';

if ($q->Fields['ProviderID'] != '') {
    if (!$qAcc->EOF)
		echo "This account <a target='_blank' href='/account/list/#/?account={$qAcc->Fields['AccountID']}'>exists in your profile</a> (AccountID: <a href=\"".getSymfonyContainer()->get("router")->generate("aw_account_redirect", ["ID" => $qAcc->Fields['AccountID']])."\" target=\"_blank\" title=\"Auto Login to the site from your account\">{$qAcc->Fields['AccountID']}</a>, <a href=\"/manager/loyalty/logs?AccountID=".$qAcc->Fields['AccountID']."\" title=\"Logs of account ".$qAcc->Fields['AccountID']."\" target=\"_blank\">logs</a>)<br/>";
	else
		echo "<a target='_blank' href='?ID={$passwordVaultId}&AddToProfile=1' onclick='setTimeout(function(){document.location.reload();},3000)'>Add this account to your profile</a><br/>";
}

echo "<h4>Recent access log</h4>\n";
$qLog = new TQuery("select
	concat(u.FirstName, ' ', u.LastName) as UserLogin,
	l.EventDate
from
	PasswordVaultLog l
	join Usr u on l.UserID = u.UserID
	left outer join PasswordVault pv on pv.PasswordVaultID = l.PasswordVaultID
	left outer join Account a on pv.AccountID = a.AccountID
	left outer join Provider p on a.ProviderID = p.ProviderID
where
	pv.PasswordVaultID = {$passwordVaultId}
order by
	l.EventDate desc
limit 10");

?>
<table cellpadding='5' cellspacing='0' class="detailsTable">
	<tr>
		<td style="font-weight: bold;">Date</td>
		<td style="font-weight: bold;">Login</td>
	</tr>
<?
	while(!$qLog->EOF){
?>
	<tr>
		<td><?=date(DATE_TIME_FORMAT, $Connection->SQLToDateTime($qLog->Fields['EventDate']))?></td>
		<td><?=$qLog->Fields['UserLogin']?></td>
	</tr>
<?
		$qLog->Next();
	}
?>
</table>
<div style="padding-top: 5px;">
	<a href="/manager/passwordVault/log.php?PasswordVaultID=<?=$passwordVaultId?>">Full log</a>
</div>
<script>
    function copyText(id) {
        try {
            var aux = document.createElement("input");
            var html = document.querySelector(id).innerHTML;
            html = htmlspecialchars_decode(html);
            aux.setAttribute("value", html);
            document.body.appendChild(aux);
            aux.select();
            var successful = document.execCommand('copy');
            var msg = successful ? 'successful' : 'unsuccessful';
            console.log('Copy command was ' + msg);
            // Remove it from the body
            document.body.removeChild(aux);
        } catch (err) {
            console.log('Oops, unable to copy');
        }
    }
    function htmlspecialchars_decode(html) {
        html = html.replace(/\&amp;/g, "&");
        html = html.replace(/\&lt;/g, "<");
        html = html.replace(/\&gt;/g, ">");
        html = html.replace(/\&quot;/g, "\"");
        if (html.indexOf('target="_blank" title="Login URL">') !== -1) {
            html = html.replace(/(<([^>]*)>)/ig, "");
        }
        return html;
    }
    function stripTags(string) {
        var decoded_string = $("<div/>").html(string).text();
        return $("<div/>").html(decoded_string).text();

    }
</script>
<?

drawFooter();

function drawRequestForm($title, $accountId, $pvId){
	global $Connection;
	drawHeader("Account password");
	echo "<h4>{$title}</h4>";
	if(isset($_POST['Extension'])){
		requestPassword($accountId, $_SESSION['Login'], 'extension', $pvId);
		echo "Extension requested. You will be notified by email";
	}
	else{
		echo "<form method='post'><input type='submit' name='Extension' value='Request access to this entry'/></form>";
	}
	drawFooter();
	exit();
}

function drawTextAsLink ($text) {
    if (filter_var($text, FILTER_VALIDATE_URL))
        return "<a href='".htmlspecialchars($text)."' target=\"_blank\" title=\"Login URL\">".htmlspecialchars($text)."</a>";
    else
        return htmlspecialchars($text);
}
