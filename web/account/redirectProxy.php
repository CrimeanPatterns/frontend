<?php

use AwardWallet\MainBundle\Globals\StringUtils;

$HTTP_SESSION_START = true;

require "../kernel/public.php";

require_once "common.php";

require_once __DIR__ . '/../../bundles/AwardWallet/MainBundle/Globals/UserAgentUtils.php';

// read params
$nID = intval(ArrayVal($_GET, "ID"));
$goto = null;

if (isset($_GET['Goto']) && !empty($session->get('RedirectTo' . $nID)) && ($session->get('RedirectTo' . $nID) == $_GET['Goto'])) {
    $goto = $_GET['Goto'];
}
NoCache();
AuthorizeUser();

GetAgentFilters($_SESSION['UserID'], "All", $sUserAgentAccountFilter, $sUserAgentCouponFilter, true);

if (isset($_SESSION['AllowRedirectTo']) && ($_SESSION['AllowRedirectTo'] == $nID)) {
    $sUserAgentAccountFilter = "1 = 1";
}
$q = new TQuery("select a.*, p.RedirectByHTTPS, p.DisplayName, p.Name as ProviderName, p.Code as ProviderCode, p.ClickURL from Account a join Provider p on a.ProviderID = p.ProviderID where ( $sUserAgentAccountFilter ) and a.AccountID = $nID");

if ($q->EOF) {
    $Interface->DiePage("You are currently logged in as {$_SESSION['FirstName']} {$_SESSION['LastName']},
	however you are attempting to access someone else's account.<br><br>
	You can either <a href=\"/security/logout?BackTo=" . urlencode($_SERVER['REQUEST_URI']) . "\">log out and log in as that user</a>, or you can link your accounts using our <a href=\"/agent/connections.php?Source=A\">Connections</a> feature.");
}
$account = getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($nID);

if (!isGranted('AUTOLOGIN', $account) && !isGranted('REDIRECT', $account)) {
    throw new Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
}

if (!isGranted('CLIENT_PASSWORD_ACCESS')) {
    getSymfonyContainer()->get("logger")->warning("missing referer on password request");
}
getSymfonyContainer()->get("monolog.logger.security")->info("password access for autologin proxy, accountId: {$nID}, userId: {$_SESSION['UserID']}");

function getDefaultLink($accountID)
{
    $q = new TQuery("
			SELECT 
			    IF(
			        pc.LoginURL is null or pc.LoginURL = '',
			        p.LoginURL,
			        pc.LoginURL
                ) as `LoginURL`
			FROM   Account a
				JOIN Provider p ON 
				    a.ProviderID = p.ProviderID
				LEFT JOIN ProviderCountry pc ON 
				    p.ProviderID = pc.ProviderID AND
				    a.Login2 = pc.CountryID
			WHERE  a.AccountID = {$accountID}
			LIMIT 1
	");

    if ($q->EOF) {
        return "/";
    }

    return $q->Fields['LoginURL'];
}

function getAutologinFrame($accountID, $successUrl = null)
{
    $q = new TQuery("
			SELECT a.*                   ,
			       p.Code AS ProviderCode,
			       p.State as ProviderState,
				   p.Login2Caption,
				   p.AutoLogin,
			       a.ProviderID
			FROM   Account a
			       JOIN Provider p
			       ON     a.ProviderID = p.ProviderID
			WHERE  a.AccountID         = {$accountID}
		");

    if ($q->EOF) {
        return null;
    }

    $decryptor = getSymfonyContainer()->get(\AwardWallet\Common\PasswordCrypt\PasswordDecryptor::class);
    $q->Fields["Pass"] = $decryptor->decrypt($q->Fields["Pass"]);

    if ((function_exists('LoadCookiePassword') && !LoadCookiePassword($q->Fields, false)) || ($q->Fields["ProviderState"] < PROVIDER_ENABLED) || !in_array($q->Fields['AutoLogin'], [AUTOLOGIN_DISABLED, AUTOLOGIN_SERVER, AUTOLOGIN_MIXED, AUTOLOGIN_EXTENSION])) {
        return null;
    }

    $autologin = new AccountAutologin();
    $targetType = CommonCheckAccountFactory::detectPlatform();

    $startUrl = null;

    // refs#17733 disable clickUrl for HHonors Diamond members
    if (22 == $q->Fields['ProviderID']) {
        /** @var \AwardWallet\MainBundle\Entity\Account $accountEntity */
        $accountEntity = getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountID);
        $eliteLevelData = getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class)->getEliteLevelFieldsByValue(
            $q->Fields['ProviderID'],
            $accountEntity->getEliteLevel()
        );

        if (
            isset($eliteLevelData['Rank'])
            && \in_array($eliteLevelData['Rank'], [2, 3]) // [Gold, Diamond]
        ) {
            $startUrl =
                StringUtils::notEmptyOrNull($accountEntity->getProviderid()->getLoginurl()) ??
                $successUrl;
        }
    }

    $userData = getSymfonyContainer()->get("jms_serializer")->serialize(new AwardWallet\MainBundle\Loyalty\Resources\UserData($accountID), "json");

    return $autologin->getAutologinFrame($q->Fields['ProviderCode'], $q->Fields['Login'], $q->Fields['Login2'], $q->Fields['Login3'], $q->Fields['Pass'], $successUrl, $q->Fields['UserID'], $targetType, $startUrl, $userData);
}

$errorLink = null;

try {
    $frame = getAutologinFrame($nID, $goto);

    if (!isset($frame)) {
        $errorLink = getDefaultLink($nID);
    }
} catch (Exception $e) {
    DieTrace('Autologin error: ' . $e->getMessage(), false);
    $errorLink = getDefaultLink($nID);
}

if (isset($errorLink)) {
    ScriptRedirect($errorLink);
} else {
    $isMobile = \AwardWallet\MainBundle\Globals\UserAgentUtils::isMobileBrowser($_SERVER['HTTP_USER_AGENT']);

    if (!empty($q->Fields['ClickURL']) && strpos($frame, json_encode($q->Fields['ClickURL'])) !== false) {
        getSymfonyContainer()->get("monolog.logger.stat")->info("partner autologin", [
            'accountId' => $nID,
            'provider' => $q->Fields['ProviderCode'],
            'ua' => $_SERVER['HTTP_USER_AGENT'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'isMobile' => $isMobile,
            'clickUrl' => $q->Fields['ClickURL'],
        ]);
    }
    getSymfonyContainer()->get("monolog.logger.security")->info("autologin frame, accountId: {$nID}, userId: {$_SESSION['UserID']}");
    $frame = str_ireplace("AWREFCODE", ($_SESSION['UserFields']['RefCode'] ?? "") . ($isMobile ? '-m' : '-d'), $frame);
    echo $frame;
}
