<?php

require __DIR__ . '/../kernel/public.php';

require_once __DIR__ . '/../account/common.php';

require_once __DIR__ . '/../kernel/TAccountInfo.php';

$providerId = intval(ArrayVal($_POST, 'providerId'));
$properties = ArrayVal($_POST, 'properties');
$redirectId = intval(ArrayVal($_POST, "redirectId"));

AuthorizeUser();
checkAjaxCSRF();

// for counting redirects
$redirectQ = new TQuery("select * from Redirect where RedirectID = $redirectId");

if ($redirectQ->EOF) {
    $Interface->DiePage("URL not found");
}
$Connection->Execute(InsertSQL("RedirectHit", [
    "RedirectID" => $redirectId,
    "HitDate" => "now()",
    "UserID" => ArrayVal($_SESSION, 'UserID', 'null'),
]));
// check provider
$filter = getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($_SESSION["UserID"])->getProviderFilter();
$q = new TQuery("
    SELECT
      ProviderID, ProgramName, Code
    FROM
      Provider p
    WHERE
      ProviderID = {$providerId}
      AND $filter
");

if ($q->EOF) {
    DieTrace("No provider " . $providerId);
}

$result = [];
$partnerPlugin = file_get_contents("$sPath/engine/{$q->Fields['Code']}/extension.js");
$result['partnerAccount'] = [
    'accountId' => 0,
    'accountExist' => 0,
    'providerCode' => $q->Fields['Code'],
    'providerName' => $q->Fields['ProgramName'],
    'login' => ArrayVal($properties, 'login'),
    'login2' => ArrayVal($properties, 'login2'),
    'password' => ArrayVal($properties, 'password'),
    'step' => 'startRegistration',
    'focusTab' => false,
    'properties' => $properties,
    'plugin' => $partnerPlugin,
];

$qAcc = new TQuery("select * from Account where ProviderID = {$providerId} and UserID = {$_SESSION["UserID"]}");

if (!$qAcc->EOF) {
    $result['partnerAccount']['accountExist'] = 1;
}

header('Content-Type: application/json');
echo json_encode($result);
