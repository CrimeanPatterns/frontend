<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Scanner\AnalyticsLogger;
use AwardWallet\MainBundle\Scanner\MailboxFinder;
use AwardWallet\MainBundle\Scanner\MailboxManager;
use AwardWallet\MainBundle\Scanner\Mobile\ListItemFormatter;
use AwardWallet\MainBundle\Scanner\UserAgentValidator;
use AwardWallet\MainBundle\Scanner\WarningGenerator;
use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\ExchangeCodeRequest;
use AwardWallet\MainBundle\Security\OAuth\Factory;
use AwardWallet\MainBundle\Security\OAuth\OAuthAction;
use AwardWallet\MainBundle\Security\OAuth\StateFactory;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\ImapMailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\Mailbox;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Model\OAuthMailbox;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/mailbox")
 */
class MailboxController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    protected const OAUTH_LIST = [
        Mailbox::TYPE_GOOGLE,
        Mailbox::TYPE_MICROSOFT,
        Mailbox::TYPE_YAHOO,
        Mailbox::TYPE_AOL,
    ];

    private const SESSION_STATE_KEY = 'mobile_oauth_csrf';
    private LoggerInterface $logger;
    private ApiVersioningService $apiVersioning;
    private AwTokenStorageInterface $awTokenStorage;
    private RouterInterface $router;
    private PageVisitLogger $pageVisitLogger;

    public function __construct(
        LoggerInterface $logger,
        ApiVersioningService $apiVersioning,
        AwTokenStorageInterface $awTokenStorage,
        LocalizeService $localizeService,
        RouterInterface $router,
        PageVisitLogger $pageVisitLogger
    ) {
        $localizeService->setRegionalSettings();
        $this->logger = $logger;
        $this->apiVersioning = $apiVersioning;
        $this->awTokenStorage = $awTokenStorage;
        $this->router = $router;
        $this->pageVisitLogger = $pageVisitLogger;
    }

    /**
     * @Route("/add", name="awm_usermailbox_add", methods={"POST"})
     * @Security("is_granted('CSRF')")
     * @JsonDecode()
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function addAction(Request $request, EmailScannerApi $emailScannerApi, TranslatorInterface $translator, AnalyticsLogger $analyticsLogger, MailboxFinder $mailboxFinder, MailboxManager $mailboxManager, Factory $oauthFactory, UserAgentValidator $userAgentValidator)
    {
        $requestData = $request->request->all();
        $this->logger->debug("user request metadata: " . \json_encode(self::getRequestMetadata($requestData)));

        $agentId = $userAgentValidator->checkAgentId($requestData['agentId'] ?? null, NotFoundHttpException::class);
        $this->logger->debug('detected ' . ($agentId) ? " no agentId" : "agentId: $agentId");

        if (StringUtils::isNotEmpty($requestData['email'] ?? '')) {
            $this->logger->debug('detected IMAP');

            if (!\filter_var($requestData['email'], FILTER_VALIDATE_EMAIL)) {
                $this->logger->error('invalid email');

                return $this->jsonResponse(['error' => $translator->trans("user.email.invalid", [], 'validators')]);
            }

            if (StringUtils::isNotEmpty($requestData['password'] ?? '')) {
                /** @var ImapMailbox $existingMailbox */
                if ($existingMailbox = $mailboxFinder->findFirstByEmailAndType($this->awTokenStorage->getBusinessUser(), $requestData['email'], Mailbox::TYPE_IMAP)) {
                    [$success, $failReason] = $mailboxManager->updateImap(
                        $this->awTokenStorage->getBusinessUser(),
                        $existingMailbox,
                        $requestData['email'],
                        $requestData['password']
                    );
                } else {
                    [$success, $failReason] = $mailboxManager->addImap(
                        $this->awTokenStorage->getBusinessUser(),
                        $requestData['email'],
                        $requestData['password'],
                        $agentId
                    );
                }

                if ($success) {
                    $analyticsLogger->logMailboxAdded(
                        Mailbox::TYPE_IMAP,
                        $this->awTokenStorage->getBusinessUser()->getId(),
                        'mobile'
                    );

                    return $this->jsonResponse(['status' => 'added']);
                } else {
                    return $this->jsonResponse(['error' => $failReason], Response::HTTP_TOO_MANY_REQUESTS);
                }
            } else {
                $type = $emailScannerApi->detectType($requestData['email'])->getType();

                if ($type === Mailbox::TYPE_IMAP) {
                    return $this->jsonResponse(['status' => 'ask_password']);
                } else {
                    return $this->jsonResponse(['type' => $type]);
                }
            }
        } elseif (
            null !== ($provider =
                it(self::OAUTH_LIST)
                ->find(function (string $provider) use ($requestData) { return isset($requestData[$provider]); })
            )
        ) {
            $this->logger->debug("detected OAuth provider: $provider");
            $oauth = $oauthFactory->getByType($provider);
            $code =
                $requestData[$provider]['serverAuthCode'] ??
                $requestData[$provider]['code'] ??
                $requestData[$provider][$provider]['serverAuthCode'] ?? // buggy client workaround
                $requestData[$provider][$provider]['code'] ?? // buggy client workaround
                '';
            $jsonProbe = @\json_decode($code, true);

            $this->logger->debug(
                "OAuth code length: " . \strlen($code) . ', ' .
                    (\json_last_error() === \JSON_ERROR_NONE ?
                        'valid json: ' . (\is_array($jsonProbe) ? \json_encode(self::getRequestMetadata($jsonProbe)) : \gettype($jsonProbe)) :
                        'invalid json'
                    )
            );

            if (!\is_null($jsonProbe)) {
                throw new \Exception("OAuth code is not a plain-string but json");
            }

            $exchangeResult = $oauth->exchangeCode(new ExchangeCodeRequest($code, $this->getRedirectUri($provider, false)));

            if ($exchangeResult->getError() !== null) {
                return $this->jsonResponse(['error' => $exchangeResult->getError()], Response::HTTP_TOO_MANY_REQUESTS);
            }

            [$success, $failReason] = $mailboxManager->linkMailbox($this->awTokenStorage->getBusinessUser(), $agentId, $provider, $exchangeResult->getUserInfo()->getEmail(), $exchangeResult->getTokens());

            if ($success) {
                // WARNING: may be actually linked mailbox, not added
                $analyticsLogger->logMailboxAdded(
                    $provider,
                    $this->awTokenStorage->getBusinessUser()->getId(),
                    'mobile'
                );
                $this->pageVisitLogger->log(PageVisitLogger::PAGE_ADD_CHANGE_CONNECTED_MAILBOXES, true);

                return $this->jsonResponse(['added' => true]);
            } else {
                return $this->jsonResponse(['error' => $failReason], Response::HTTP_TOO_MANY_REQUESTS);
            }
        } else {
            $this->logger->error('no provider detected');

            return $this->jsonResponse(['error' => 'Missing request data']);
        }
    }

    /**
     * @Route("/update/{id}", name="awm_usermailbox_update", requirements={"id"="\d+"}, methods={"POST"})
     * @JsonDecode()
     * @Security("is_granted('CSRF')")
     */
    public function updateAction(
        Request $request,
        string $id,
        EmailScannerApi $emailScannerApi,
        Factory $oauthFactory,
        SessionInterface $session,
        StateFactory $stateFactory,
        AwTokenStorageInterface $awTokenStorage,
        $googleClientId,
        $googleIosClientId
    ): Response {
        $mailbox =
            it($emailScannerApi->listMailboxes(['user_' . $awTokenStorage->getBusinessUser()->getId()]))
            ->find(function (Mailbox $mailbox) use ($id) { return $mailbox->getId() === $id; });

        if (!$mailbox) {
            return $this->jsonResponse([]);
        }
        $this->pageVisitLogger->log(PageVisitLogger::PAGE_ADD_CHANGE_CONNECTED_MAILBOXES, true);

        if ($mailbox instanceof OAuthMailbox) {
            return $this->jsonResponse(
                it(call(function () use ($mailbox, $oauthFactory, $stateFactory, $request, $googleClientId, $googleIosClientId) {
                    yield from [
                        'type' => $mailbox->getType(),
                        'email' => $mailbox->getEmail(),
                    ];

                    if ($mailbox->getType() === Mailbox::TYPE_GOOGLE) {
                        yield from [
                            'scopes' => [\Google_Service_Gmail::GMAIL_READONLY],
                            'webClientId' => $googleClientId,
                            'iosClientId' => $googleIosClientId,
                        ];
                    } else {
                        $oauth = $oauthFactory->getByType($mailbox->getType());
                        $state = $stateFactory->createState(
                            $mailbox->getType(),
                            $userData['userAgent'] ?? null,
                            true,
                            false,
                            OAuthAction::MAILBOX,
                            $request
                        );
                        $oauth->login_hint = $mailbox->getEmail();

                        yield from [
                            'consentUrl' => $oauth->getConsentUrl(
                                $state,
                                $this->getRedirectUri(
                                    $mailbox->getType(),
                                    false
                                ),
                                true,
                                false,
                                $mailbox->getEmail()
                            ),
                            'redirectUrl' => $this->getRedirectUri($mailbox->getType(), true),
                        ];
                    }
                }))
                ->toArrayWithKeys()
            );
        } elseif ($mailbox instanceof ImapMailbox) {
            return $this->jsonResponse([
                'type' => $mailbox->getType(),
                'email' => $mailbox->getLogin(),
            ]);
        } else {
            return $this->jsonResponse([]);
        }
    }

    /**
     * @Route("/check-status", name="awm_usermailbox_check_status")
     */
    public function checkStatusAction(WarningGenerator $warningGenerator, ListItemFormatter $listItemFormatter)
    {
        $allowedTypes = \array_merge(
            [Mailbox::TYPE_IMAP],
            WarningGenerator::DEFAULT_ALLOWED_TYPES
        );

        return $this->jsonResponse(
            it(($mailbox = $warningGenerator->getUnauthorizedMailbox($allowedTypes)) ? [$mailbox] : [])
            ->map(function (Mailbox $mailbox) use ($listItemFormatter) {
                return $listItemFormatter->format($mailbox);
            })
            ->toArray()
        );
    }

    /**
     * @Route("/check-status/1", name="awm_usermailbox_check_status_debug")
     */
    public function checkStatusDebugAction(MailboxFinder $mailboxFinder, ListItemFormatter $listItemFormatter, AwTokenStorageInterface $awTokenStorage)
    {
        return $this->jsonResponse(
            it($mailboxFinder->findAllByUser($awTokenStorage->getBusinessUser()))
            ->filter(function (Mailbox $mailbox) { return $mailbox->getErrorCode() === Mailbox::ERROR_CODE_AUTHENTICATION; })
            ->take(1)
            ->map(function (Mailbox $mailbox) use ($listItemFormatter) {
                return $listItemFormatter->format($mailbox);
            })
            ->toArray()
        );
    }

    /**
     * @Route("/list", name="awm_usermailbox_list")
     * @throws \AwardWallet\MainBundle\Service\EmailParsing\Client\ApiException
     */
    public function listAction(EmailScannerApi $emailScannerApi, ListItemFormatter $listItemFormatter, AwTokenStorageInterface $awTokenStorage)
    {
        $user = $awTokenStorage->getBusinessUser();
        $mailboxes = $emailScannerApi->listMailboxes(["user_" . $user->getUserid()]);
        $formattedMailboxes =
            it($mailboxes)
            ->mapIndexed(function (Mailbox $mailbox) use ($listItemFormatter) {
                return $listItemFormatter->format($mailbox);
            })
            ->toArray();
        $this->pageVisitLogger->log(PageVisitLogger::PAGE_ADD_CHANGE_CONNECTED_MAILBOXES, true);

        if ($this->apiVersioning->supports(MobileVersions::MAILBOX_OWNER)) {
            return $this->jsonResponse([
                'mailboxes' => $formattedMailboxes,
                'owners' => it([[
                    'value' => '',
                    'name' => $user->getFullName(),
                ]])
                    ->chain((function () use ($user) {
                        /** @var Useragent $familyMember */
                        foreach ($user->getFamilyMembers() as $familyMember) {
                            yield [
                                'value' => $familyMember->getUseragentid(),
                                'name' => $familyMember->getFullName(),
                            ];
                        }
                    })())
                    ->toArray(),
            ]);
        } else {
            return $this->jsonResponse($formattedMailboxes);
        }
    }

    /**
     * @Route("/{id}", name="awm_usermailbox_delete", requirements={"id"="\d+"}, methods={"DELETE"})
     * @Security("is_granted('CSRF')")
     */
    public function deleteAction(string $id, MailboxManager $mailboxManager, AwTokenStorageInterface $awTokenStorage)
    {
        $user = $awTokenStorage->getBusinessUser();
        $mailbox = $mailboxManager->delete($user, (int) $id);

        if ($mailbox) {
            return $this->jsonResponse(['success' => true]);
        } else {
            throw $this->createNotFoundException();
        }
    }

    protected static function getRequestMetadata(array $request)
    {
        \array_walk_recursive($request, function (&$value, $key) {
            switch (true) {
                case \is_float($value):
                case \is_int($value):
                case \is_bool($value):
                case \is_null($value):
                case \is_array($value):
                    break;

                case \is_string($value):
                    $value = (StringUtils::isEmpty($value) ? 'empty' : 'non-empty') . " string";

                    break;

                default:
                    throw new \LogicException('Undefined!');
            }
        });

        return $request;
    }

    protected function getRedirectUri(string $type, bool $encodeSchemaUrl): string
    {
        switch ($type) {
            case Mailbox::TYPE_GOOGLE:
                return $this->router->generate("aw_usermailbox_oauthcallback", ["type" => $type], UrlGeneratorInterface::ABSOLUTE_URL);

            case Mailbox::TYPE_MICROSOFT:
                $url = $this->router->generate('awm_native_redirect', [], UrlGeneratorInterface::ABSOLUTE_URL);

                if ($encodeSchemaUrl && $this->apiVersioning->supports(MobileVersions::IOS)) {
                    $url = \urlencode($url);
                }

                return $url;

            default:
                $url = 'awardwallet://oauth/' . $type;

                if ($encodeSchemaUrl && $this->apiVersioning->supports(MobileVersions::IOS)) {
                    $url = \urlencode($url);
                }

                return $url;
        }
    }
}
