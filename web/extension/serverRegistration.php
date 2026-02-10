<?php
require __DIR__ . '/../kernel/public.php';
require_once __DIR__ . '/../account/common.php';
require_once __DIR__ . '/../kernel/TAccountInfo.php';

$properties = ArrayVal($_POST, 'properties');
$providerCode = preg_replace('#[^\w]+#ims', '', ArrayVal($_POST, "providerCode"));

AuthorizeUser();
checkAjaxCSRF();

require_once(__DIR__."/../engine/".strtolower($providerCode)."/autoRegistration.php");

$result = call_user_func("autoRegistration".ucfirst($providerCode), $properties);

header('Content-Type: application/json');
echo json_encode($result);