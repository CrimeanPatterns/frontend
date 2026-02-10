<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_INDEX')")
     * @Route("/manager/")
     */
    public function indexAction()
    {
        return $this->render("@AwardWalletMain/Manager/index.html.twig");
    }
}
