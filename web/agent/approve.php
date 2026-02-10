<?php

use AwardWallet\MainBundle\Manager\ConnectionManager;

require "../kernel/public.php";

require_once "../schema/User.php";

AuthorizeUser();
checkAjaxCSRF();

// User Limit in personal interface
if (SITE_MODE == SITE_MODE_PERSONAL
    && MyConnectionsCount() >= PERSONAL_INTERFACE_MAX_USERS
    && isset($_SESSION['UserID'])
    && !in_array($_SESSION['UserID'], $eliteUsers)) {
    $outer = TUserSchema::getTextErrorAboutLimitUsers(false, TUserSchema::isAdminBusinessAccount($_SESSION['UserID']));
    echo $outer;
//	$Interface->DiePage($outer);
} else {
    $nID = intval(ArrayVal($QS, 'AgentID'));
    $em = getSymfonyContainer()->get("doctrine.orm.default_entity_manager");
    $uaRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
    $ua1 = $uaRep->findOneBy([
        "agentid" => $nID,
        "clientid" => $_SESSION['UserID'],
        "isapproved" => 0,
    ]);

    getSymfonyContainer()->get(ConnectionManager::class)->approveConnection(
        $ua1,
        $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($_SESSION['UserID'])
    );

    $_SESSION['NewPartner'] = true;
    $script = (SITE_MODE == SITE_MODE_PERSONAL) ? 'editConnection.php' : 'editBusinessConnection.php';

    echo "OK";
}
