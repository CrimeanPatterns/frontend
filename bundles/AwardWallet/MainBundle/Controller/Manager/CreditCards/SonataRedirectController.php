<?php

namespace AwardWallet\MainBundle\Controller\Manager\CreditCards;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class SonataRedirectController extends AbstractController
{
    /**
     * @Route("/manager/sonata/credit-card/{cardId}/edit")
     */
    public function sonataCreditCardEditRedirect($cardId): RedirectResponse
    {
        return new RedirectResponse('/manager/edit.php?Schema=CreditCard&ID=' . $cardId);
    }
}
