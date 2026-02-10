<?php

namespace AwardWallet\MainBundle\Controller\Business;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends AbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/account/select-user", name="aw_business_select_user")
     */
    public function selectUserAction()
    {
        return $this->render('@AwardWalletMain/Business/Account/selectUser.html.twig');
    }
}
