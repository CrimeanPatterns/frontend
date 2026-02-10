<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Service\BalanceWatch\Constants;
use AwardWallet\MainBundle\Service\BalanceWatch\Stopper;
use AwardWallet\MainBundle\Service\BalanceWatch\Timeout;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/manager/balance-watch")
 */
class BalanceWatchController extends AbstractController
{
    /**
     * @Route("/stop/{accountId}", name="aw_manager_balancewatch_stop", methods={"GET", "POST"}, requirements={"accountId"="\d+"})
     * @Security("is_granted('ROLE_MANAGE_BALANCEWATCH')")
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id"="accountId"})
     * @return RedirectResponse
     * @throws
     */
    public function stopAction(Request $request, Account $account, Timeout $bwTimeout, Stopper $bwStopper)
    {
        $redirect = $request->headers->get('referer') ?? $request->get('backTo');

        if (empty($redirect)) {
            throw new AccessDeniedException();
        }

        if ($bwTimeout->getTimeoutSeconds($account)) {
            $bwStopper->stopBalanceWatch($account, Constants::EVENT_FORCED_STOP);
        }

        return new RedirectResponse($redirect);
    }
}
