<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TestController extends AbstractController
{
    /**
     * @Template("@AwardWalletMain/Manager/Test/test.html.twig")
     * @Route("/manager/test", name="aw_manager_test")
     */
    public function testAction(AuthorizationCheckerInterface $authorizationChecker)
    {
        return ['ccAccess' => var_export($authorizationChecker->isGranted('ROLE_MANAGE_CREDITCARDS'), true)];
    }
}
