<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Tip;
use AwardWallet\MainBundle\Entity\UserTip;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class TipController extends AbstractController
{
    /**
     * @Route("/tip/mark/{tipId}", name="aw_tip_mark", methods={"POST"}, options={"expose"=true}, requirements={"tipId"="\d+"})
     * @Security("is_granted('ROLE_USER')")
     * @ParamConverter("tip", class="AwardWalletMainBundle:Tip", options={"id"="tipId"})
     * @return JsonResponse
     * @throws ORMException
     */
    public function markRead(
        Tip $tip,
        Request $request,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $result = ['success' => true];

        if ($authorizationChecker->isGranted('USER_IMPERSONATED')) {
            return new JsonResponse($result);
        }

        $event = $request->request->get('event');
        /** @var Usr $user */
        $user = $this->getUser();
        $sqlOnUpdate = [];

        $data = [
            'TipID' => $tip->getId(),
            'UserID' => $user->getId(),
        ];

        $userTip = $entityManager->getRepository(UserTip::class)->findOneBy([
            'tipId' => $tip,
            'userId' => $user,
        ]);

        if ('close' === $event) {
            $data['CloseDate'] = $entityManager->getConnection()->quote(date('Y-m-d H:i:s'));
            $sqlOnUpdate[] = 'CloseDate = ' . $data['CloseDate'];
        } elseif ('click' === $event) {
            $data['ClickDate'] = $entityManager->getConnection()->quote(date('Y-m-d H:i:s'));
            $sqlOnUpdate[] = 'ClickDate = ' . $data['ClickDate'];
        }

        if ('show' === $event
            || null === $userTip
            || null === $userTip->getShowDate()) {
            $data['ShowDate'] = $entityManager->getConnection()->quote(date('Y-m-d H:i:s'));
            $data['ShowCount'] = null === $userTip
                ? 1
                : $userTip->getShowCount() + 1;
            $sqlOnUpdate[] = 'ShowDate = ' . $data['ShowDate'];
        }

        $entityManager->getConnection()->executeStatement('
            INSERT IGNORE INTO UserTip (' . implode(',', array_keys($data)) . ')
            VALUES (' . implode(',', $data) . ')
            ' . (empty($sqlOnUpdate)
                ? ''
                : 'ON DUPLICATE KEY UPDATE ' . implode(',', $sqlOnUpdate)
        )
        );

        return new JsonResponse($result);
    }
}
