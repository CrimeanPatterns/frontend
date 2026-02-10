<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use AwardWallet\WidgetBundle\Widget\ConnectionsPersonsWidget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ShareController extends AbstractController
{
    private ProgramShareManager $programShareManager;

    public function __construct(ProgramShareManager $programShareManager)
    {
        $this->programShareManager = $programShareManager;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/share/{code}", name="aw_share_all", options={"expose"=true})
     * @Template("@AwardWalletMain/Share/shareAll.html.twig")
     * @return array
     */
    public function shareAllAction(
        $code,
        AuthorizationCheckerInterface $authorizationChecker,
        ConnectionsPersonsWidget $connectionsPersonsWidget
    ) {
        $user = $this->getUser();
        $decoded = $this->programShareManager->decodeShareAllCode($code);

        if (empty($decoded)) {
            throw $this->createAccessDeniedException();
        }
        /** @var Useragent $userAgent */
        [$userAgent, $type] = $decoded;

        if (!$authorizationChecker->isGranted('CONNECTION_APPROVED', $userAgent)) {
            throw new AccessDeniedHttpException();
        }

        /** @var Useragent $connection */
        [$agent, $connection, $usrAccounts] = $this->programShareManager->apiSharingShareAll($user, $userAgent, $type);

        $savePasswords = [];

        if ($type == 'full') {
            foreach ($usrAccounts as $id => $usrAccount) {
                if ($usrAccount['TableName'] == 'Account') {
                    if ($usrAccount['SavePassword'] === SAVE_PASSWORD_LOCALLY) {
                        $savePasswords[$id] = true;
                    }
                }
            }
        }

        $connectionsPersonsWidget->setActiveItem($connection->getUseragentid());

        return [
            'agent' => $agent,
            'userAgent' => $userAgent,
            'accounts' => $usrAccounts,
            'savePasswords' => $savePasswords,
            'fullAccess' => $type == 'full',
        ];
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/deny-all/{userAgentId}", name="aw_share_deny_all", requirements={"id" = "\d+"}, options={"expose"=true})
     * @ParamConverter("userAgent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function denyAllAction(
        Useragent $userAgent,
        AwTokenStorageInterface $tokenStorage,
        UseragentRepository $useragentRepository,
        RouterInterface $router
    ) {
        $user = $tokenStorage->getBusinessUser();
        $userId = $user->getUserid();

        if ($userId != $userAgent->getAgentid()->getUserid() && $userId != $userAgent->getClientid()->getUserid()) {
            throw new NotFoundHttpException();
        }

        if ($useragentRepository->findBy([
            'clientid' => [
                $userAgent->getAgentid()->getUserid(),
                $userAgent->getClientid()->getUserid(),
            ],
            'agentid' => $tokenStorage->getBusinessUser()->getUserid(),
            'isapproved' => true,
            'accesslevel' => [
                UseragentRepository::ACCESS_ADMIN,
                UseragentRepository::ACCESS_BOOKING_MANAGER,
            ],
        ])) {
            throw new NotFoundHttpException();
        }

        $this->programShareManager->apiSharingDenyAll($user, $userAgent);

        return $this->redirect($router->generate('aw_user_connections'));
    }
}
