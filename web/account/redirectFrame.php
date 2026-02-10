<?php

$HTTP_SESSION_START = true;

require "../kernel/public.php";

require_once "common.php";
NoCache();
AuthorizeUser();

$engine = getSymfonyContainer()->get(\AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface::class);

if (get_class($engine) !== \AwardWallet\MainBundle\Globals\Updater\Engine\Local::class) {
    exit("Bad request");
}

$nID = (int) ArrayVal($QS, "ID");
$account = getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($nID);

if (!isGranted('AUTOLOGIN', $account) && !isGranted('REDIRECT', $account)) {
    throw new Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
}

GetAgentFilters($_SESSION['UserID'], "All", $sUserAgentAccountFilter, $sUserAgentCouponFilter, true);

if (isset($_SESSION['AllowRedirectTo']) && ($_SESSION['AllowRedirectTo'] == $nID)) {
    $sUserAgentAccountFilter = "1 = 1";
}
$q = new TQuery("select a.*, p.RedirectByHTTPS, p.ImageURL, p.ClickURL, p.Code as ProviderCode
from Account a join Provider p on a.ProviderID = p.ProviderID
where ( $sUserAgentAccountFilter ) and a.AccountID = $nID");

if ($q->EOF) {
    exit("Access to this account is denied");
}

if (isset($_SESSION['RedirectArg' . $nID])) {
    $arg = $_SESSION['RedirectArg' . $nID];
    unset($_SESSION['RedirectArg' . $nID]);
} else {
    $targetURL = null;
    $session = getSymfonyContainer()->get("session");

    if (isset($_GET['Goto']) && !empty($session->get('RedirectTo' . $nID)) && ($session->get('RedirectTo' . $nID) == $_GET['Goto'])) {
        $targetURL = $_GET['Goto'];
    }
    $arg = RedirectAccount($nID, $targetURL, CommonCheckAccountFactory::detectPlatform());
}

if ($q->Fields['ClickURL'] != '') {
    $arg['ClickURL'] = $q->Fields['ClickURL'];
}

if ($q->Fields['ImageURL'] != '') {
    $arg['ImageURL'] = $q->Fields['ImageURL'];
}

$manager = new AutologinManager($nID, $arg);

if (isset($arg['TargetURL'])) {
    $manager->successPages[] = $arg['TargetURL'];
}
$manager->drawPage();
