<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Invitecode;
use AwardWallet\MainBundle\Entity\Invites;
use AwardWallet\MainBundle\Entity\Query\UserConnectionsQuery\Connection;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Handler\Subscriber\AddAgentGeneric;
use AwardWallet\MainBundle\Form\Handler\Subscriber\Subscriber;
use AwardWallet\MainBundle\Form\Type\Mobile\ConnectionEditType;
use AwardWallet\MainBundle\Form\Type\Mobile\FamilyMemberType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\DoNotSendException;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\MailerExceptionInterface;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\NonDeliveryException;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler as Str;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\ConnectionManager;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use AwardWallet\MainBundle\Service\AccessGrantedHelper;
use AwardWallet\MainBundle\Service\AccountAccessApi\AuthStateManager;
use AwardWallet\MainBundle\Service\AccountAccessApi\BusinessFinder;
use AwardWallet\MainBundle\Service\ConnectionManager\AnonymousInviteAcceptor;
use AwardWallet\MainBundle\Service\LegacyUrlGenerator;
use AwardWallet\MainBundle\Service\UserConnectionsListFormatterMobile;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iter\fromCallable;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/connections")
 */
class ConnectionController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    private AwTokenStorageInterface $awTokenStorage;
    private UserConnectionsListFormatterMobile $userConnectionsListFormatterMobile;
    private Handler $awFormFamilyMemberHandlerMobile;
    private AddAgentGeneric $addAgentGeneric;
    private Handler $awFormEditConnectionHandleMobile;
    private EntityManagerInterface $entityManager;
    private AccessGrantedHelper $accessGrantedHelper;
    private TranslatorInterface $translator;
    private FormDehydrator $formDehydrator;

    public function __construct(
        LocalizeService $localizeService,
        AwTokenStorageInterface $awTokenStorage,
        UserConnectionsListFormatterMobile $userConnectionsListFormatterMobile,
        Handler $awFormFamilyMemberHandlerMobile,
        AddAgentGeneric $addAgentGeneric,
        Handler $awFormEditConnectionHandleMobile,
        EntityManagerInterface $entityManager,
        AccessGrantedHelper $accessGrantedHelper,
        TranslatorInterface $translator,
        FormDehydrator $formDehydrator
    ) {
        $localizeService->setRegionalSettings();
        $this->awTokenStorage = $awTokenStorage;
        $this->userConnectionsListFormatterMobile = $userConnectionsListFormatterMobile;
        $this->awFormFamilyMemberHandlerMobile = $awFormFamilyMemberHandlerMobile;
        $this->addAgentGeneric = $addAgentGeneric;
        $this->awFormEditConnectionHandleMobile = $awFormEditConnectionHandleMobile;
        $this->entityManager = $entityManager;
        $this->accessGrantedHelper = $accessGrantedHelper;
        $this->translator = $translator;
        $this->formDehydrator = $formDehydrator;
    }

    /**
     * @Route("/", methods={"GET"})
     */
    public function listAction(UserConnectionsListFormatterMobile $userConnectionsListFormatterMobile): Response
    {
        return new JsonResponse($userConnectionsListFormatterMobile->loadFormattedData($this->awTokenStorage->getBusinessUser()));
    }

    /**
     * @Route("/invite/reminder/{inviteCodeId}",
     *     name="awm_connection_invite_send_reminder",
     *     methods={"POST"},
     *     requirements = {
     *         "inviteCodeId": "\d+"
     *     }
     * )
     * @ParamConverter("invitecode", class="AwardWalletMainBundle:Invitecode", options = {"id" = "inviteCodeId"})
     * @Security("is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     */
    public function sendReminderToInviteAction(
        Request $request,
        Invitecode $invitecode,
        ConnectionManager $connectionManager
    ): JsonResponse {
        $user = $this->awTokenStorage->getBusinessUser();
        $userIp = $request->getClientIp();

        try {
            [$success, $failReason] = $connectionManager->sendReminderBruteforceSafe(
                $invitecode,
                $user,
                $userIp,
                false
            );
        } catch (NonDeliveryException $e) {
            return $this->mailErrorResponseFormatter('ndr', $e->getTarget());
        } catch (DoNotSendException $e) {
            return $this->mailErrorResponseFormatter('donotsend', $e->getTarget());
        } catch (MailerExceptionInterface $_) {
            return $this->successJsonResponse();
        }

        if ($success) {
            return $this->successJsonResponse();
        } else {
            return $this->errorJsonResponse($failReason);
        }
    }

    /**
     * @Route("/reminder/{userAgentId}",
     *     name="awm_family_member_send_reminder",
     *     methods={"POST"},
     *     requirements = {
     *         "userAgentId" = "\d{1,30}"
     *     }
     * )
     * @ParamConverter("useragent", class="AwardWalletMainBundle:Useragent", options = {"id" = "userAgentId"})
     * @Security("is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     */
    public function sendReminderToUserAgentAction(
        Request $request,
        Useragent $useragent,
        ConnectionManager $connectionManager
    ) {
        try {
            [$success, $failReason] = $connectionManager->sendReminderBruteforceSafe(
                $useragent,
                $this->awTokenStorage->getBusinessUser(),
                $request->getClientIp(),
                false,
            );
        } catch (NonDeliveryException $e) {
            return $this->mailErrorResponseFormatter('ndr', $e->getTarget());
        } catch (DoNotSendException $e) {
            return $this->mailErrorResponseFormatter('donotsend', $e->getTarget());
        } catch (MailerExceptionInterface $_) {
            return $this->successJsonResponse();
        }

        if ($success) {
            return $this->successJsonResponse();
        } elseif (\is_string($failReason)) {
            return $this->errorJsonResponse($failReason);
        } else {
            return $this->jsonResponse(['success' => false]);
        }
    }

    /**
     * @Route("/family-member/invite/{userAgentId}",
     *     name="awm_invite_family_member",
     *     methods={"POST"},
     *     requirements = {
     *         "userAgentId" = "\d{1,30}"
     *     }
     * )
     * @ParamConverter("useragent", class="AwardWalletMainBundle:Useragent", options = {"id" = "userAgentId"})
     * @JsonDecode
     * @Security("is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     */
    public function inviteFamilyMemberAction(
        Useragent $useragent,
        Request $request,
        ConnectionManager $connectionManager
    ) {
        if (
            (!$useragent->isFamilyMember() && !$this->isGranted('EDIT', $useragent))
            || StringUtils::isEmpty($email = $request->get('email'))
        ) {
            throw $this->createNotFoundException();
        }

        try {
            [$success, $failReason] = $connectionManager->inviteFamilyMemberBruteforceSafe(
                $useragent,
                $email,
                $this->awTokenStorage->getBusinessUser(),
                false
            );
        } catch (NonDeliveryException $e) {
            return $this->mailErrorResponseFormatter('ndr', $e->getTarget());
        } catch (DoNotSendException $e) {
            return $this->mailErrorResponseFormatter('donotsend', $e->getTarget());
        } catch (MailerExceptionInterface $_) {
            return $this->successJsonResponse();
        }

        if ($success) {
            return $this->successJsonResponse();
        } else {
            return $this->errorJsonResponse($failReason);
        }
    }

    /**
     * @Route("/{userAgentId}",
     *     name="awm_connection_delete",
     *     methods={"DELETE"},
     *     requirements = {
     *         "userAgentId" = "\d{1,30}"
     *     }
     * )
     * @ParamConverter("useragent", class="AwardWalletMainBundle:Useragent", options = {"id" = "userAgentId"})
     * @Security("is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     */
    public function deleteAction(
        Useragent $useragent,
        EntityManagerInterface $entityManager,
        ConnectionManager $connectionManager
    ): JsonResponse {
        if (!$this->isGranted('EDIT', $useragent) || $this->isGranted('SITE_BUSINESS_AREA')) {
            throw $this->createNotFoundException();
        }

        $userAgentRepository = $entityManager->getRepository(Useragent::class);

        if ($useragent->getClientid()) {
            $connectionManager->denyConnection($useragent, $this->awTokenStorage->getBusinessUser());
        } elseif (
            ($shareDate = $useragent->getSharedate())
            && (time() - $shareDate->getTimestamp() <= UserConnectionsListFormatterMobile::WAITING_PERIOD_SEC)
        ) {
            $userAgentRepository->cancelInvite($useragent, $useragent->getEmail());
        } else {
            $connectionManager->denyConnection($useragent, $this->awTokenStorage->getBusinessUser());
        }

        return $this->successJsonResponse();
    }

    /**
     * @Route("/approve/{userAgentId}",
     *     name="awm_connection_approve",
     *     methods={"POST"},
     *     requirements = {
     *         "userAgentId" = "\d{1,30}"
     *     }
     * )
     * @ParamConverter("useragent", class="AwardWalletMainBundle:Useragent", options = {"id" = "userAgentId"})
     * @Security("is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     */
    public function approveConnectionAction(Useragent $useragent, ConnectionManager $connectionManager): JsonResponse
    {
        if (!$this->isGranted('EDIT', $useragent)) {
            throw $this->createNotFoundException();
        }

        $connectionManager->approveConnection($useragent, $this->awTokenStorage->getBusinessUser());

        return $this->successJsonResponse();
    }

    /**
     * @Route("/{userAgentId}",
     *     name="awm_connection_edit",
     *     methods={"GET", "PUT"},
     *     requirements = {
     *         "userAgentId" = "\d{1,30}"
     *     }
     * )
     * @ParamConverter("useragent", class="AwardWalletMainBundle:Useragent", options = {"id" = "userAgentId"})
     * @JsonDecode
     */
    public function editAction(Request $request, Useragent $useragent): JsonResponse
    {
        if (!$this->isGranted('EDIT', $useragent)) {
            throw $this->createNotFoundException();
        }

        if (!$useragent->getClientid()) {
            return $this->editFamilyMember($request, $useragent);
        } elseif ($useragent->isApproved()) {
            return $this->editConnection($request, $useragent);
        } else {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @Route("/invite/{inviteCodeId}",
     *     name="awm_connection_invite_delete",
     *     methods={"DELETE"},
     *     requirements = {
     *         "inviteCodeId": "\d+"
     *     }
     * )
     * @ParamConverter("invitecode", class="AwardWalletMainBundle:Invitecode", options = {"id" = "inviteCodeId"})
     * @Security("is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     */
    public function cancelInvite(Invitecode $invitecode, ConnectionManager $connectionManager): Response
    {
        if (!$this->isGranted('EDIT', $invitecode)) {
            throw $this->createNotFoundException();
        }

        $connectionManager->cancelInvite($invitecode);

        return $this->successJsonResponse();
    }

    /**
     * @Route("/members/grant-access/{userAgentId}/{accessType}",
     *     name="awm_member_grant_access_action",
     *     methods={"POST"},
     *     requirements = {"accessType" = "readonly|full|never-show"}
     * )
     * @ParamConverter("useragent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     * @Security("is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     */
    public function grantAccessAction(
        Request $request,
        Useragent $useragent,
        string $accessType,
        ConnectionManager $connectionManager,
        ProgramShareManager $programShareManager
    ): Response {
        if (!$this->isGranted('EDIT', $useragent)) {
            throw $this->createNotFoundException();
        }

        $connectionManager->grantAccess($useragent, $accessType, $this->awTokenStorage->getBusinessUser());

        if (\in_array($accessType, ['readonly', 'full'], true)) {
            return $this->forward(
                'AwardWallet\MobileBundle\Controller\ShareController::shareAllAction',
                ['code' => $programShareManager->getShareAllCode($useragent, $accessType)]
            );
        } else {
            return $this->successJsonResponse();
        }
    }

    /**
     * @Route("/invite/confirm/{shareCode}", name="awm_invite_confirm", methods={"GET", "POST"})
     * @Security("is_granted('CSRF')")
     */
    public function confirmInviteAction(
        Request $request,
        string $shareCode,
        AnonymousInviteAcceptor $anonymousInviteAcceptor,
        EntityManagerInterface $entityManager,
        LegacyUrlGenerator $legacyUrlGenerator,
        ConnectionManager $connectionManager
    ): JsonResponse {
        /** @var Invites[] $invites */
        $invites = $entityManager->getRepository(Invites::class)->findBy(['code' => $shareCode, 'approved' => false]);

        if (!$invites) {
            throw $this->createNotFoundException();
        }

        $invite = $invites[0];

        $formatInviterData = function () use ($invite, $legacyUrlGenerator): array {
            $inviter = $invite->getInviterid();

            $result = [
                'inviterName' => $inviter->isBusiness() ?
                    $inviter->getCompany() :
                    $inviter->getFullName(),
                'avatar' => StringUtils::isNotEmpty($avatarSrc = $inviter->getAvatarLink('small')) ?
                    $legacyUrlGenerator->generateAbsoluteUrl($avatarSrc) :
                    null,
            ];

            if (
                ($user = $this->awTokenStorage->getBusinessUser())
                && StringUtils::isNotEmpty($userName = $user->getFirstname())
            ) {
                $result['name'] = $userName;
            } elseif (
                ($familyMember = $invite->getFamilyMember())
                && StringUtils::isNotEmpty($familyMemberName = $familyMember->getFullName())
            ) {
                $result['name'] = $familyMemberName;
            }

            return $result;
        };

        if (
            ($shareDate = $invite->getInvitedate())
            && ($shareDate < new \DateTime('-3 days'))
        ) {
            return $this->jsonResponse(
                \array_merge(
                    $formatInviterData(),
                    ['expired' => true]
                )
            );
        }

        if ($request->isMethod('POST')) {
            if ($user = $this->getCurrentUser()) {
                $connectionManager->acceptInviteByRegisteredUser($this->getCurrentUser(), $invite);
            } else {
                $anonymousInviteAcceptor->acceptInviteByAnonymousUser($invite);
            }

            return $this->successJsonResponse();
        } else {
            return $this->jsonResponse($formatInviterData());
        }
    }

    /**
     * @Route("/invite/reject/{shareCode}", name="awm_invite_reject", methods={"POST"})
     * @Security("is_granted('CSRF')")
     */
    public function rejectInvite(string $shareCode, EntityManagerInterface $entityManager)
    {
        /** @var Invites[] $invites */
        $invites = $entityManager->getRepository(Invites::class)->findBy(['code' => $shareCode, 'approved' => false]);

        if (!$invites) {
            throw $this->createNotFoundException();
        }

        $entityManager->remove($invites[0]);
        $entityManager->flush();

        return $this->successJsonResponse();
    }

    /**
     * @Route("/approve/{code}/{accessLevel}/{authKey}",
     *     name="awm_connections_approve",
     *     methods={"GET", "POST"},
     *     requirements = {
     *         "code"   = "\w{1,40}",
     *         "accessLevel" = "\d{1}"
     *     },
     *     defaults={"authKey" = ""},
     *     host="%host%"
     * )
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     * @return JsonResponse
     */
    public function approveAction(
        Request $request,
        $code,
        $accessLevel,
        $authKey,
        $rootDir,
        BusinessFinder $businessFinder,
        TranslatorInterface $translator,
        RouterInterface $router,
        AuthStateManager $authStateManager,
        ProgramShareManager $programShareManager
    ) {
        $userAgentRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

        $business = $businessFinder->findByCode($code);

        if ($business === null) {
            return $this->createNotFoundResponse($translator->trans(
                /** @Desc("The Callback URL has not yet been configured. Please have the business administrator <a href='%profile_link%' target='_blank'>set it up here</a>.") */
                'api.not-configured',
                ['%profile_link%' => $router->generate('aw_profile_business_api_callback_settings', [], UrlGeneratorInterface::ABSOLUTE_URL)]
            ));
        }

        if ($userAgentRep->findBy([
            'clientid' => $business->getUserid(),
            'agentid' => $this->awTokenStorage->getBusinessUser()->getId(),
            'isapproved' => true,
            'accesslevel' => [
                UseragentRepository::ACCESS_ADMIN,
                UseragentRepository::ACCESS_BOOKING_MANAGER,
            ],
        ])) {
            return $this->createNotFoundResponse($translator->trans(
                /** @Desc("You are already an administrator of this business account, you cannot invite yourself again to join this business account.") */
                'api.no-self-invite'
            ));
        }

        $authState = $authStateManager->loadAuthState($business, $authKey ?? '', $accessLevel);

        if ($authState === null) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('GET')) {
            return new JsonResponse([
                'code' => $code,
                'accessLevel' => $accessLevel,
                'toLogo' => $business
                && $business->getPicturever()
                && !Str::isEmpty($avatar = $business->getAvatarLink('small'))
                && !str::isEmpty($avatar = @file_get_contents($rootDir . '/../web' . $avatar))
                && false !== $avatar ?
                    'data:image/gif;charset=utf-8;base64,' . base64_encode($avatar) :
                    null,
                'toName' => $business->getCompany(),
                'accessLevelDescription' => $translator->trans(/** @Ignore */ $userAgentRep->getAgentAccessLevelsAll()[$accessLevel]),
                'denyUrl' => $authStateManager->getDenyUrl($business, $authState),
            ]);
        } else {
            $shareAccounts = true;

            $accessLevel = intval($accessLevel);

            if (!in_array($accessLevel, [ACCESS_READ_NUMBER, ACCESS_READ_BALANCE_AND_STATUS, ACCESS_READ_ALL, ACCESS_WRITE])) {
                $accessLevel = ACCESS_READ_ALL;
            }

            $programShareManager->apiSharingConfirm(
                $business,
                $shareAccounts,
                $accessLevel
            );

            return new JsonResponse([
                'success' => true,
                'callbackUrl' => $authStateManager->getSuccessUrl($business, $this->getUser(), $authState),
            ]);
        }
    }

    protected function mailErrorResponseFormatter(string $messageKey, \Swift_Message $target): JsonResponse
    {
        return $this->errorJsonResponse(
            $this->translator->trans('email_failed.' . $messageKey, ['%email%' => \key($target->getTo())])
        );
    }

    protected function editFamilyMember(
        Request $request,
        Useragent $useragent
    ): JsonResponse {
        $form = $this->createForm(FamilyMemberType::class, $useragent, ['method' => "PUT"]);
        $formResponseGenerator = function () use ($form, $useragent) {
            return $this->jsonResponse(\array_merge(
                ['form' => $this->formDehydrator->dehydrateForm($form)],
                $this->userConnectionsListFormatterMobile
                    ->formatConnection(new Connection($useragent))
            ));
        };

        $this->awFormFamilyMemberHandlerMobile->addHandlerSubscriber(
            (new Subscriber())
                ->setOnCommit(function (HandlerEvent $event) {
                    $event->setResponse($this->successJsonResponse());
                })
                ->setOnException(
                    $this->addAgentGeneric->createExceptionHandler($formResponseGenerator)
                )
        );

        if ($response = $this->awFormFamilyMemberHandlerMobile->handleRequestTransactionally($form, $request)) {
            return $response;
        }

        return $formResponseGenerator();
    }

    protected function editConnection(
        Request $request,
        Useragent $useragent
    ): JsonResponse {
        $form = $this->createForm(ConnectionEditType::class, $useragent, ['method' => "PUT"]);

        if ($this->awFormEditConnectionHandleMobile->handleRequest($form, $request)) {
            return $this->successJsonResponse();
        } else {
            return $this->jsonResponse(\array_merge(
                [
                    'form' => $this->formDehydrator->dehydrateForm($form, false),
                    'askAccess' => !$useragent->isPopupShown(),
                ],
                $this->userConnectionsListFormatterMobile
                    ->formatConnection($connection = new Connection(
                        $useragent,
                        $this->entityManager->getRepository(Useragent::class)->findOneBy([
                            'clientid' => $useragent->getAgentid(),
                            'agentid' => $useragent->getClientid(),
                        ])
                    )),
                [
                    'actions' => it(fromCallable(function () use ($connection) {
                        if ($connection->isApproved()) {
                            $accessGivenStats = $this->accessGrantedHelper->calculateAccess(
                                $connection->getUseragent()->getAgentid(),
                                $this->awTokenStorage->getBusinessUser()
                            );

                            if ($accessGivenStats['accountTotal']) {
                                yield UserConnectionsListFormatterMobile::ACTION_READ_ONLY;

                                yield UserConnectionsListFormatterMobile::ACTION_FULL_ACCESS;
                            }
                        } else {
                            yield UserConnectionsListFormatterMobile::ACTION_RESEND;
                        }

                        yield UserConnectionsListFormatterMobile::ACTION_DELETE;
                    }))
                        ->toArray(),
                ]
            ));
        }
    }

    protected function createNotFoundResponse(string $message): Response
    {
        return new JsonResponse(
            ['error' => $message],
            404
        );
    }
}
