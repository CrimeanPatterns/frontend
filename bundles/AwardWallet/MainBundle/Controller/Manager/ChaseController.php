<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ChaseController extends AbstractController
{
    /**
     * @Route("/manager/chase", name="aw_manager_chase")
     * @Security("is_granted('ROLE_MANAGE_CHASE')")
     * @Template("@AwardWalletMain/Manager/Chase/index.html.twig")
     */
    public function indexAction(
        Request $request,
        EntityManagerInterface $entityManager,
        \Memcached $memcached,
        RouterInterface $router
    ) {
        $messages = [];

        if ($request->isMethod('POST')) {
            $accountId = $request->request->get('accountId');

            if (empty($accountId)) {
                $memcached->delete("chase_code_bait");
            } else {
                $valid = true;

                if (is_numeric($accountId)) {
                    $acc = $entityManager->getRepository(Account::class)->find($accountId);

                    if (empty($acc)) {
                        $messages[] = "Account $accountId not found";
                        $valid = false;
                    }
                } elseif ($accountId != 'all') {
                    $valid = false;
                    $messages[] = "Enter account id or 'all'";
                }

                if ($valid) {
                    $memcached->set("chase_code_bait", ['state' => 'waiting', 'accountId' => $accountId, 'user' => $this->getUser()->getLogin()], SECONDS_PER_DAY * 10);
                }
            }
        }

        $bait = $memcached->get("chase_code_bait");

        if (empty($bait)) {
            $messages[] = "Fishing off";
        } else {
            $messages[] = "Fishing on: <pre>" . json_encode($bait, JSON_PRETTY_PRINT) . "</pre>";
        }

        if (!empty($bait['passwordVaultId'])) {
            $messages[] = "<a href='" . $router->generate('aw_manager_pv_share', ['ID' => $bait['passwordVaultId']], UrlGeneratorInterface::ABSOLUTE_URL) . "'>Show password</a>";
        }

        return ['bait' => $bait, 'messages' => $messages];
    }
}
