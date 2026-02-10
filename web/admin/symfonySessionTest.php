<?php

require __DIR__ . "/../kernel/public.php";

// test for app/liteSymfony/app.php

echo "session: " . $_SESSION['Login'] . "<br/>";

$container = getSymfonyContainer();
$session = $container->get('session');
$twig = $container->get('twig');
$user = $container->get('security.token_storage')->getToken()->getUser();

echo "user: " . $user->getLogin() . "<br/>";

$request = $container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find(1715);
$invoice = $request->getLastInvoice();
$manager = $container->get('aw.manager.abrequest_manager');
$manager->markAsPaid($invoice, 333.0, \AwardWallet\MainBundle\Entity\AbInvoice::PAYMENTTYPE_CREDITCARD);

echo 'OK';
