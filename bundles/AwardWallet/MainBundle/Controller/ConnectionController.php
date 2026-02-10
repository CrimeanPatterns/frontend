<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Invitecode;
use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery\UserConnectionsQuery;
use AwardWallet\MainBundle\Entity\Repositories\InvitesRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Handler\Subscriber\AddAgentGeneric;
use AwardWallet\MainBundle\Form\Handler\Subscriber\Subscriber;
use AwardWallet\MainBundle\Form\Type;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\DoNotSendException;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\MailerExceptionInterface;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\NonDeliveryException;
use AwardWallet\MainBundle\Manager\ConnectionManager;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use AwardWallet\MainBundle\Service\AccessGrantedHelper;
use AwardWallet\MainBundle\Service\AccountCounter\Counter;
use AwardWallet\MainBundle\Service\ConnectionManager\AnonymousInviteAcceptor;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\WidgetBundle\Widget\ConnectionsPersonsWidget;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConnectionController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Route("/user/connections", name="aw_user_connections", options={"expose"=true})
     * @Template("@AwardWalletMain/Connection/manageConnections.html.twig")
     */
    public function manageConnectionsAction(
        AwTokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        UserConnectionsQuery $userConnectionsQuery,
        PageVisitLogger $pageVisitLogger
    ): array {
        global $arAgentAccessLevelsAll;

        $connectionsData = $userConnectionsQuery->run($tokenStorage->getBusinessUser());
        $connectionType = [
            /** @Desc("Can share only <span class='bold'>Trips</span>") */
            'T' => $translator->trans('connections.type.trips'),
            /** @Desc("Can share only <span class='bold'>Accounts</span>") */
            'A' => $translator->trans('connections.type.accounts'),
            /** @Desc("Can share <span class='bold'>Accounts</span> and <span class='bold'>Trips</span>") */
            '*' => $translator->trans('connections.type.all'),
        ];
        $pageVisitLogger->log(PageVisitLogger::PAGE_CONNECTED_MEMBERS);

        return [
            'connections' => $connectionsData['connections'],
            'emailInvites' => $connectionsData['emailInvites'],
            'connectionType' => $connectionType,
            'pendingConnections' => $connectionsData['pendingConnections'],
            'accessLevels' => $arAgentAccessLevelsAll,
        ];
    }

    /**
     * @Route("/user/family/{userAgentId}", name="aw_user_family_edit", options={"expose"=true}, requirements={"userAgentId" = "\d+"})
     * @Security("is_granted('ROLE_USER')")
     * @ParamConverter("userAgent", class="AwardWalletMainBundle:Useragent", options={"id"="userAgentId"})
     * @Template("@AwardWalletMain/Connection/editFamily.html.twig")
     * @return array|Response
     */
    public function editFamilyAction(
        Request $request,
        Useragent $userAgent,
        AwTokenStorageInterface $tokenStorage,
        Handler $formFamilyMemberHandlerDesktop,
        AddAgentGeneric $addAgentGeneric
    ) {
        if (!$this->isGranted('EDIT', $userAgent)) {
            throw $this->createAccessDeniedException();
        }

        $isBusiness = $this->isGranted('SITE_BUSINESS_AREA');
        $user = $tokenStorage->getBusinessUser();
        $form = $this->createForm(Type\FamilyMemberType::class, $userAgent);
        $formResponseGenerator = function () use ($form, $user, $userAgent) {
            return [
                'form' => $form->createView(),
                'user' => $user,
                'userAgent' => $userAgent,
            ];
        };
        $formFamilyMemberHandlerDesktop->addHandlerSubscriber(
            (new Subscriber())
                ->setOnException(
                    $addAgentGeneric->createExceptionHandler($formResponseGenerator)
                )
                ->setOnCommit(function (HandlerEvent $event) use ($isBusiness) {
                    $event->setResponse(
                        $this->redirectToRoute(
                            $isBusiness ? 'aw_business_members' : 'aw_user_connections'
                        )
                    );
                })
        );

        if ($response = $formFamilyMemberHandlerDesktop->handleRequestTransactionally($form, $request)) {
            return $response;
        }

        return $formResponseGenerator();
    }

    /**
     * @Route(
     *     "/user/family/{userAgentId}/{code}/unsubscribe",
     *     name="aw_user_family_unsubscribe",
     *     options={"expose"=true},
     *     requirements={"userAgentId" = "\d+", "code" = "\w+"}
     * )
     * @Route("/agent/editFamilyMember.php", name="aw_user_family_unsubscribe_old")
     * @Template("@AwardWalletMain/Connection/unsubscribeFamily.html.twig")
     */
    public function unsubscribeFamilyAction(
        Request $request,
        UseragentRepository $useragentRepository,
        EntityManagerInterface $em,
        ?int $userAgentId = null,
        ?string $code = null
    ): array {
        if (
            is_null($userAgentId)
            && is_null($code)
            && $request->query->has('ID')
            && is_numeric($oldId = $request->query->get('ID'))
            && $request->query->has('Code')
            && is_string($oldCode = $request->query->get('Code'))
            && !empty($oldCode)
        ) {
            $userAgentId = $oldId;
            $code = $oldCode;
        }

        if (empty($userAgentId) || empty($code)) {
            throw $this->createNotFoundException();
        }

        $ua = $useragentRepository->findOneBy([
            'useragentid' => $userAgentId,
            'sharecode' => $code,
        ]);

        if (!$ua) {
            throw $this->createNotFoundException();
        }

        $ua->setSendemails(false);
        $em->flush();

        return [];
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Route("/user/connections/{userAgentId}", name="aw_user_connection_edit", options={"expose"=true}, requirements={"userAgentId" = "\d+"})
     * @ParamConverter("userAgent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     * @Template("@AwardWalletMain/Connection/editConnection.html.twig")
     * @return array|Response
     */
    public function editConnectionAction(
        Request $request,
        Useragent $userAgent,
        ConnectionsPersonsWidget $connectionsPersonsWidget,
        Handler $formEditConnectionHandlerDesktop,
        AccessGrantedHelper $accessGrantHelper,
        AwTokenStorageInterface $tokenStorage,
        Counter $counter
    ) {
        if (!$this->isGranted('EDIT', $userAgent)) {
            throw $this->createAccessDeniedException();
        }

        $connectionsPersonsWidget->setActiveItem($userAgent->getId());
        $form = $this->createForm(Type\ConnectionEditType::class, $userAgent);

        if ($formEditConnectionHandlerDesktop->handleRequest($form, $request)) {
            return $this->redirectToRoute('aw_user_connections');
        }

        $user = $tokenStorage->getBusinessUser();
        $accessGranted = $accessGrantHelper->calculateAccess($user, $userAgent->getAgentid());
        $allTimelinesShared = $accessGranted['sharedTimelinesCount'] === $accessGranted['familyMembersCount'];
        $accessGranted['grantedFull'] = $allTimelinesShared && $accessGranted['tripDefaults'] && $accessGranted['tripLevel']
            && $accessGranted['accountDefaults'] && $accessGranted['accountFull'] && $accessGranted['accessLevelFull'];
        $accessGranted['grantedRead'] = $allTimelinesShared && $accessGranted['tripDefaults'] && $accessGranted['accountDefaults']
            && $accessGranted['accountFull'] && ($accessGranted['accessLevel'] >= 0);
        $accessGranted['isBusinessAgent'] = $userAgent->getAgentid()->isBusiness();
        $accessGiven = $accessGrantHelper->calculateAccess($userAgent->getAgentid(), $user);

        $accountSummary = $counter->calculate($userAgent->getAgentid()->getId());
        $accessGiven['accountShared'] = $accountSummary->getCount($userAgent->getId());

        return [
            'form' => $form->createView(),
            'userAgent' => $userAgent,
            'accessGranted' => $accessGranted,
            'accessGiven' => $accessGiven,
        ];
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/members/request/{type}/{userAgentId}", name="aw_member_control_request", methods={"POST"}, options={"expose"=true})
     * @ParamConverter("userAgent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     */
    public function requestControlAction(
        Request $request,
        Useragent $userAgent,
        $type,
        UseragentRepository $userAgentRepository,
        AccessGrantedHelper $accessGrantHelper,
        TranslatorInterface $translator
    ) {
        if (!$this->isGranted('EDIT', $userAgent)) {
            throw $this->createAccessDeniedException();
        }

        $backLink = $userAgentRepository->findOneBy(['agentid' => $userAgent->getClientid(), 'clientid' => $userAgent->getAgentid()]);

        if ($accessGrantHelper->sendMail($backLink, $type == 'full')) {
            $request->getSession()->getFlashBag()->add(
                'notice',
                $translator->trans(
                    /** @Desc("You have successfully requested control.") */
                    'notice.control-success-requested'
                )
            );
        }

        return $this->redirectToRoute(
            $this->isGranted('SITE_BUSINESS_AREA') ? 'aw_business_member_edit' : 'aw_user_connection_edit',
            ['userAgentId' => $userAgent->getId()]
        );
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/members/grant-access/{userAgentId}/never-show", name="aw_member_never_show_request_action", methods={"POST"}, options={"expose"=true})
     * @ParamConverter("userAgent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     */
    public function neverShowPopupAction(
        Useragent $userAgent,
        ConnectionManager $connectionManager,
        AwTokenStorageInterface $tokenStorage
    ): JsonResponse {
        if (!$this->isGranted('EDIT', $userAgent)) {
            $result = [
                'success' => false,
            ];
        } else {
            $connectionManager->grantAccess($userAgent, 'never-show', $tokenStorage->getBusinessUser());
            $result = [
                'success' => true,
            ];
        }

        return $this->json($result);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/members/grant-access/{userAgentId}/{accessType}",
     *     name="aw_member_grant_access_action",
     *     methods={"POST"},
     *     requirements={"accessType" = "readonly|full"},
     *     options={"expose"=true}
     * )
     * @ParamConverter("userAgent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     */
    public function grantAccessAction(
        Useragent $userAgent,
        $accessType,
        ConnectionManager $connectionManager,
        AwTokenStorageInterface $tokenStorage,
        ProgramShareManager $programShareManager
    ) {
        if (!$this->isGranted('EDIT', $userAgent)) {
            $result = [
                'success' => false,
            ];
        } else {
            $connectionManager->grantAccess($userAgent, $accessType, $tokenStorage->getBusinessUser());
            $code = $programShareManager->getShareAllCode($userAgent, $accessType);
            $redirect = $this->generateUrl('aw_share_all', ['code' => $code]);

            $result = [
                'success' => true,
                'redirect' => $redirect,
            ];
        }

        return $this->json($result);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/members/invite/{userAgentId}", name="aw_invite_family", methods={"POST"}, options={"expose"=true})
     * @ParamConverter("userAgent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     */
    public function inviteFamilyMemberAction(
        Useragent $userAgent,
        Request $request,
        ConnectionManager $connectionManager,
        AwTokenStorageInterface $tokenStorage,
        TranslatorInterface $translator
    ) {
        if (!$userAgent->isFamilyMember() && !$this->isGranted('EDIT', $userAgent)) {
            throw $this->createAccessDeniedException();
        }

        $email = $request->get('Email');

        try {
            [$success, $failReason] = $connectionManager->inviteFamilyMemberBruteforceSafe(
                $userAgent,
                $email,
                $tokenStorage->getBusinessUser()
            );
        } catch (NonDeliveryException $e) {
            return $this->json([
                'error' => $translator->trans('email_failed.ndr', ["%email%" => $email]),
            ]);
        } catch (DoNotSendException $e) {
            return $this->json([
                'error' => $translator->trans('email_failed.donotsend', ["%email%" => $email]),
            ]);
        } catch (MailerExceptionInterface $_) {
            return $this->json(['success' => true]);
        }

        if ($success) {
            return $this->json(['success' => true]);
        } else {
            return $this->json(['error' => $failReason]);
        }
    }

    /**
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/invite/{shareCode}", name="aw_invite_confirm", options={"expose"=true}, requirements={"shareCode": "\w{20}"})
     * @Template("@AwardWalletMain/Connection/invite.html.twig")
     */
    public function inviteAction(
        Request $request,
        $shareCode,
        AnonymousInviteAcceptor $anonymousInviteAcceptor,
        InvitesRepository $invitesRepository,
        AwTokenStorageInterface $tokenStorage,
        ConnectionManager $connectionManager
    ) {
        $invites = $invitesRepository->findBy(['code' => $shareCode, 'approved' => false]);

        if (!count($invites)) {
            return $this->redirectToRoute('aw_home');
        }

        /** @var Invites $invite */
        $invite = $invites[0];
        $user = $tokenStorage->getBusinessUser();

        if ($user && $invite->getInviterid()->getId() === $user->getId()) {
            return [
                'inviteYourSelf' => true,
                'invite' => $invite,
                'user' => $user,
            ];
        }

        if ($request->isMethod('POST')) {
            if ($user) {
                $connectionManager->acceptInviteByRegisteredUser($user, $invite);

                return $this->redirectToRoute('aw_user_connections');
            } else {
                $anonymousInviteAcceptor->acceptInviteByAnonymousUser($invite);

                return $this->redirectToRoute('aw_register');
            }
        }

        return [
            'invite' => $invite,
            'user' => $user,
        ];
    }

    /**
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA') and is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/invite/reject/{shareCode}", name="aw_invite_reject", methods={"POST"}, options={"expose"=true}, requirements={"shareCode": "\w{20}"})
     */
    public function rejectInviteAction(
        InvitesRepository $invitesRepository,
        EntityManagerInterface $em,
        $shareCode
    ) {
        $invites = $invitesRepository->findBy(['code' => $shareCode, 'approved' => false]);

        if (!count($invites)) {
            return $this->json(['error' => 'Invite expired or not found']);
        }

        /** @var Invites $invite */
        $invite = $invites[0];
        $em->remove($invite);
        $em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/user/cancel/{useragentid}", name="aw_invite_cancel", methods={"POST"}, requirements={"useragentid": "\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED') and is_granted('EDIT', useragent)")
     * @ParamConverter("useragent", class="AwardWalletMainBundle:Useragent")
     */
    public function uninviteAction(Useragent $useragent, UseragentRepository $useragentRepository): JsonResponse
    {
        $useragentRepository->cancelInvite($useragent, $useragent->getEmail());

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/user/cancel-email-invite/{invitecodeid}", name="aw_cancel_email_invite", methods={"POST"}, requirements={"invitecodeid": "\d+"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED') and is_granted('EDIT', invitecode)")
     * @ParamConverter("invitecode", class="AwardWalletMainBundle:Invitecode")
     */
    public function uninvitecodeAction(Invitecode $invitecode, ConnectionManager $connectionManager): JsonResponse
    {
        $connectionManager->cancelInvite($invitecode);

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/members/deny/{userAgentId}", name="aw_members_deny", methods={"POST"}, options={"expose"=true})
     * @ParamConverter("useragent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     * @Security("is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED') and is_granted('EDIT', useragent)")
     */
    public function denyAction(
        Useragent $useragent,
        AwTokenStorageInterface $tokenStorage,
        ConnectionManager $connectionManager
    ) {
        if ($this->isGranted('SITE_BUSINESS_AREA') && !$this->isGranted('USER_BUSINESS_ADMIN')) {
            return $this->redirect('/agent/members.php');
        }

        if ($connectionManager->denyConnection($useragent, $tokenStorage->getBusinessUser())) {
            return $this->json(['success' => true]);
        }

        return $this->json(['error' => 'Error denying connection']);
    }
}
