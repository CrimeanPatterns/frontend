<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Service\Account\ManualUpdateHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ManualUpdateController extends AbstractController
{
    /**
     * Getting information for the account balance entry form.
     *
     * @Route("/manual-update-form/{id}", name="aw_account_list_manual_update", requirements={"id"="\d+"}, methods={"GET"}, condition="request.isXmlHttpRequest()")
     * @IsGranted("ROLE_USER")
     * @IsGranted("CSRF")
     */
    public function getAccountInfo(
        int $id,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        ManualUpdateHelper $manualUpdateHelper
    ): JsonResponse {
        $account = $entityManager->getRepository(Account::class)->find($id);

        if ($account === null) {
            return new JsonResponse(['success' => false, 'message' => 'Account does not exist.']);
        } elseif (!$authorizationChecker->isGranted('READ_EXTPROP', $account)) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied.']);
        }

        $result = $manualUpdateHelper->getData($account);

        return new JsonResponse([
            'success' => true,
            'balance' => $account->getBalance(),
            'eliteLevel' => $result->getEliteLevel(),
            'eliteLevelOptions' => $result->getEliteLevelOptions(),
            'mailboxConnected' => $result->isMailboxConnected(),
            'notifyMe' => $result->isNotifyMe(),
        ]);
    }
}
