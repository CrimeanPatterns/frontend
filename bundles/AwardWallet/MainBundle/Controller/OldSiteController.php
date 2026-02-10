<?php

namespace AwardWallet\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OldSiteController extends AbstractController
{
    /**
     * @Route("/bootFirewall", name="aw_oldsite_bootfirewall", requirements={"offerUserId" = "\d+"})
     * @return Response
     */
    public function bootFirewallAction()
    {
        return $this->render('@AwardWalletMain/Layout/oldsite.html.twig');
    }
}
