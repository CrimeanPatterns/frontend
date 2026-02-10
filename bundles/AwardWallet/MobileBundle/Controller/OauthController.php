<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Controller\HomeController;
use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ExternalTracking\ExternalTrackingListHandler;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ExternalTracking\Superfly;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ReferalListener;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\OAuth\AppleOAuth;
use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\AppleExchangeCodeRequest;
use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\ExchangeCodeRequest;
use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\UserName;
use AwardWallet\MainBundle\Security\OAuth\Factory;
use AwardWallet\MainBundle\Security\OAuth\Mobile\MobileRedirectUrlFactory;
use AwardWallet\MainBundle\Security\OAuth\Mobile\MobileSchemaRedirectUrlFactory;
use AwardWallet\MainBundle\Security\OAuth\OAuthAction;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\CallbackData;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\OAuthCallbackHandler;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\RedirectCallbackStorage;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\AuthorizedUser;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\CallbackErrorInterface;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\ExistingUserError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\InvalidState;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\LoggedIn;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\MissingMailboxAccess;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\Registered;
use AwardWallet\MainBundle\Security\OAuth\OAuthType;
use AwardWallet\MainBundle\Security\OAuth\StateFactory;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticationRequestListener;
use AwardWallet\MainBundle\Service\User\Constants;
use AwardWallet\MobileBundle\Form\View\UrlTransformer;
use AwardWallet\Strings\Strings;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\f\call;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/oauth")
 */
class OauthController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    protected const OAUTH_LIST = [
        OAuthType::APPLE,
        OAuthType::GOOGLE,
        OAuthType::MICROSOFT,
        OAuthType::YAHOO,
        OAuthType::AOL,
    ];

    protected const SCOPE_BASE = 'base';
    protected const SCOPE_ADDITIONAL = 'additional';

    protected const QUERY_REF_KEY = 'refId';
    /**
     * @var Factory
     */
    private $oauthFactory;
    /**
     * @var string
     */
    private $googleClientId;
    /**
     * @var string
     */
    private $googleIosClientId;
    /**
     * @var StateFactory
     */
    private $stateFactory;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var string
     */
    private $appleClientId;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var AppleOAuth
     */
    private $appleIosOauthProvider;
    /**
     * @var MobileRedirectUrlFactory
     */
    private $mobileRedirectUrlFactory;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var UrlTransformer
     */
    private $mobileUrlGenerator;
    /**
     * @var RedirectCallbackStorage
     */
    private $redirectCallbackStorage;
    /**
     * @var OAuthCallbackHandler
     */
    private $oauthCallbackHandler;
    /**
     * @var ExternalTrackingListHandler
     */
    private $externalTrackingListHandler;

    public function __construct(
        LocalizeService $localizeService,
        Factory $oauthFactory,
        StateFactory $stateFactory,
        ApiVersioningService $apiVersioning,
        TranslatorInterface $translator,
        AwTokenStorageInterface $tokenStorage,
        LoggerInterface $securityLogger,
        AppleOAuth $appleIosOauthProvider,
        MobileRedirectUrlFactory $mobileRedirectUrlFactory,
        RedirectCallbackStorage $redirectCallbackStorage,
        OAuthCallbackHandler $oauthCallbackHandler,
        EntityManagerInterface $entityManager,
        UrlTransformer $mobileUrlGenerator,
        ExternalTrackingListHandler $externalTrackingListHandler,
        string $googleClientId,
        string $googleIosClientId,
        string $appleClientId
    ) {
        $localizeService->setRegionalSettings();
        $this->oauthFactory = $oauthFactory;
        $this->googleClientId = $googleClientId;
        $this->googleIosClientId = $googleIosClientId;
        $this->stateFactory = $stateFactory;
        $this->apiVersioning = $apiVersioning;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->appleClientId = $appleClientId;
        $this->logger = $securityLogger;
        $this->appleIosOauthProvider = $appleIosOauthProvider;
        $this->mobileRedirectUrlFactory = $mobileRedirectUrlFactory;
        $this->entityManager = $entityManager;
        $this->mobileUrlGenerator = $mobileUrlGenerator;
        $this->redirectCallbackStorage = $redirectCallbackStorage;
        $this->oauthCallbackHandler = $oauthCallbackHandler;
        $this->externalTrackingListHandler = $externalTrackingListHandler;
    }

    /**
     * @Route("/{authProvider}",
     *     name="awm_auth_url_gen",
     *     methods={"POST"},
     *     requirements={"authProvider" = "yahoo|aol|microsoft|google|apple"}
     * )
     * @JsonDecode
     * @Security("is_granted('CSRF')")
     */
    public function urlAction(
        Request $request,
        string $authProvider,
        AuthorizationCheckerInterface $authorizationChecker
    ): Response {
        $request->query->set('mobile', true);
        $request->query->set('rememberMe', 'true');
        $request->query->set(
            self::QUERY_REF_KEY,
            ($session = $request->getSession()) ? $session->get(ReferalListener::SESSION_REF_KEY) : null
        );
        $action = $request->get('action');
        $mailboxAccess = $request->get('mailbox', true);

        if (!isset($action) && $mailboxAccess) {
            $action = OAuthAction::MAILBOX;
        }

        if (!StateFactory::isValidAction($action)) {
            throw new BadRequestHttpException("unknown action");
        }

        if ($this->isAuthorized($authorizationChecker) && $action !== OAuthAction::MAILBOX) {
            return $this->successJsonResponse([
                'authenticated' => true,
            ]);
        }

        $profileAccess = \in_array($action, [OAuthAction::REGISTER, OAuthAction::LOGIN]);

        if (OAuthType::GOOGLE === $authProvider) {
            $state = $this->stateFactory->createState(
                OAuthType::GOOGLE,
                null,
                (bool) $mailboxAccess,
                $profileAccess,
                $action,
                $request
            );

            $scopes =
                it(call(function () use ($mailboxAccess, $profileAccess) {
                    if ($mailboxAccess) {
                        yield self::SCOPE_ADDITIONAL => \Google_Service_Gmail::GMAIL_READONLY;
                    }

                    if ($profileAccess) {
                        yield self::SCOPE_BASE => \Google_Service_Oauth2::USERINFO_PROFILE;

                        yield self::SCOPE_BASE => \Google_Service_Oauth2::USERINFO_EMAIL;
                    }
                }))
                ->collapseByKey()
                ->toArrayWithKeys();

            return $this->jsonResponse([
                'scopes' =>
                    it($scopes)
                    ->flatten(1)
                    ->collect()
                    ->unique()
                    ->toArray(),
                'incrementalAuthScopes' => $scopes,
                'webClientId' => $this->googleClientId,
                'iosClientId' => $this->googleIosClientId,
                'forceCodeForRefreshToken' => true,
                'state' => $state,
                'redirectUrl' => MobileSchemaRedirectUrlFactory::make($authProvider),
            ]);
        } elseif (
            (OAuthType::APPLE === $authProvider)
            && $this->apiVersioning->supports(MobileVersions::ANDROID)
        ) {
            $oauth = $this->oauthFactory->getByType(OAuthType::APPLE);
            $state = $this->stateFactory->createState(
                OAuthType::APPLE,
                null,
                (bool) $mailboxAccess,
                $profileAccess,
                $action,
                $request
            );

            return $this->jsonResponse([
                "clientId" => $this->appleClientId,
                "responseType" => 'ALL',
                "scope" => 'ALL',
                "state" => $state,
                "nonce" => \bin2hex(\random_bytes(10)),
                "redirectUrl" => MobileSchemaRedirectUrlFactory::make($authProvider),
                "consentUrl" => $oauth->getConsentUrl(
                    $state,
                    $this->mobileRedirectUrlFactory->make($authProvider),
                    (bool) $mailboxAccess,
                    $profileAccess,
                    null
                ),
            ]);
        } else {
            $email = $request->get('email');

            if (
                ($action === OAuthAction::MAILBOX)
                && isset($email) && !\is_string($email)
            ) {
                throw $this->createNotFoundException();
            }

            $state = $this->stateFactory->createState(
                $authProvider,
                null,
                (bool) $mailboxAccess,
                $profileAccess,
                $action,
                $request
            );
            $oauth = (OAuthType::APPLE === $authProvider) ?
                $this->appleIosOauthProvider :
                $this->oauthFactory->getByType($authProvider);

            $redirectUrl = MobileSchemaRedirectUrlFactory::make($authProvider);

            if ($this->apiVersioning->supports(MobileVersions::IOS)) {
                $redirectUrl = \urlencode($redirectUrl);
            }

            return $this->jsonResponse([
                'consentUrl' => $oauth->getConsentUrl(
                    $state,
                    $this->mobileRedirectUrlFactory->make($authProvider),
                    (bool) $mailboxAccess,
                    $profileAccess,
                    $email
                ),
                'state' => $state,
                "redirectUrl" => $redirectUrl,
            ]);
        }
    }

    /**
     * @Route("/callback", name="awm_auth_callback", methods={"POST"})
     * @JsonDecode
     * @Security("is_granted('CSRF')")
     */
    public function callbackAction(Request $request, TranslatorInterface $translator): JsonResponse
    {
        $requestData = $request->request->all();
        $authProvider =
            it(self::OAUTH_LIST)
            ->find(function (string $provider) use ($requestData) { return isset($requestData[$provider]); });

        if (null === $authProvider) {
            throw new BadRequestHttpException('empty auth provider');
        }

        if (
            (OAuthType::APPLE === $authProvider)
            && $this->apiVersioning->supports(MobileVersions::ANDROID)
        ) {
            $storedData = $this->redirectCallbackStorage->load($requestData[$authProvider]['code']);

            if (!$storedData) {
                throw new BadRequestHttpException('no params loaded');
            }

            $callbackData = $this->getCallbackData($authProvider, $storedData);
        } else {
            $callbackData = $this->getCallbackData($authProvider, $requestData[$authProvider]);
        }

        $request->attributes->set(Constants::REQUEST_PLATFORM_KEY, Constants::REQUEST_PLATFORM_MOBILE);
        $result = $this->oauthCallbackHandler->handle($authProvider, $callbackData, $request);

        if ($result instanceof InvalidState) {
            throw new BadRequestHttpException('empty state');
        }

        if ($result instanceof AuthorizedUser) {
            return $this->successJsonResponse([
                'email' => $this->tokenStorage->getUser()->getEmail(),
                'authenticated' => true,
            ]);
        }

        if ($result instanceof ExistingUserError) {
            return $this->errorJsonResponse(
                $result->getTextError(),
                [
                    'requiredPassword' => true,
                    'email' => $result->getEmail(),
                ]
            );
        }

        if (
            ($result instanceof LoggedIn)
            || ($result instanceof Registered)
            || ($result instanceof MissingMailboxAccess)
        ) {
            $session = $request->getSession();

            if ($session) {
                $session->set(MobileReauthenticationRequestListener::SESSION_ENABLE_KEYCHAIN_AFTER_LOGGING_IN_KEY, true);
            }

            $response = $this->successJsonResponse(['email' => $result->getEmail()]);
            $initialRequestQuery = $callbackData->getState() ? $callbackData->getState()->getQuery() : [];
            $registered =
                ($result instanceof Registered)
                || ($result instanceof MissingMailboxAccess && $result->isRegistered())
            ;

            if (
                $registered
                && (HomeController::SUPERFLY_LEAD_ID == ($initialRequestQuery[self::QUERY_REF_KEY] ?? null))
            ) {
                $this->externalTrackingListHandler->add(new Superfly([Superfly::EVENT_COMPLETE_REGISTRATION]));
            }

            return $response;
        }

        if ($result instanceof CallbackErrorInterface) {
            return $this->errorJsonResponse($result->getTextError());
        }

        throw new \LogicException('unknown result: ' . \get_class($result));
    }

    /**
     * @Route("/native-redirect", name="awm_native_redirect", methods={"GET"})
     */
    public function nativeRedirectAction(Request $request)
    {
        $code = $request->get('code');

        if (StringUtils::isEmpty($code)) {
            throw $this->createNotFoundException();
        }

        return $this->redirect("awardwallet://oauth/microsoft?code={$code}");
    }

    /**
     * @Route("/{id}", name="awm_unlink_action", requirements={"id"="\d+"}, methods={"DELETE"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     * @ParamConverter("userOAuth", class="AwardWalletMainBundle:UserOAuth")
     */
    public function unlinkAction(UserOAuth $userOAuth)
    {
        if (!$this->isGranted('UNLINK', $userOAuth)) {
            throw $this->createNotFoundException();
        }

        $user = $userOAuth->getUser();

        if ($user->getOAuth()->count() === 1 && empty($user->getPassword())) {
            return $this->errorJsonResponse(
                $this->translator->trans('warning.before-unlink-last-oauth'),
                [
                    'setPassword' => true,
                    'formLink' => $this->mobileUrlGenerator->generate('aw_mobile_change_password'),
                ]
            );
        }

        $this->logger->info("oauth unlink", [
            'extra' => json_encode([
                'provider' => $userOAuth->getProvider(),
                'email' => $userOAuth->getEmail(),
                'fullName' => $userOAuth->getFullName(),
            ]),
        ]);
        $this->entityManager->remove($userOAuth);
        $this->entityManager->flush();

        return $this->successJsonResponse();
    }

    /**
     * @Route("/native-redirect-apple", name="awm_native_redirect_apple", methods={"POST"})
     */
    public function nativeRedirectAppleAction(Request $request)
    {
        $code = $request->request->get('code', $request->request->get('authorizationCode'));

        if (StringUtils::isEmpty($code)) {
            throw $this->createNotFoundException();
        }

        $this->redirectCallbackStorage->save($code, $request->request->all());

        return $this->redirect("awardwallet://oauth/apple?code={$code}");
    }

    protected function getCallbackData(string $type, array $data): CallbackData
    {
        $logData = $data;
        \array_walk_recursive($logData, function (&$value) {
            if (\is_string($value)) {
                $value = Strings::cutInMiddle($value, 8);
            }
        });
        $this->logger->info('oauth callback data', ['callback_data' => $logData]);

        $code = $data['serverAuthCode'] ?? $data['authorizationCode'] ?? $data['code'] ?? null;
        $callbackData = (new CallbackData())
            ->setCode($code)
            ->setSerializedState($data['state'] ?? null)
            ->setError($data['error'] ?? null)
            ->setErrorDescription($data['error_description'] ?? null)
            ->setRawCallbackData($data);

        if (OAuthType::APPLE === $type) {
            $userMeta = null;

            if (isset($data['user'])) {
                $userInfo = @\json_decode($data['user'], true);
                $userMeta = new UserName(
                    $userInfo['firstName'] ?? '',
                    $userInfo['lastName'] ?? ''
                );
            }

            if (
                isset($data['fullName']['givenName'])
                || isset($data['fullName']['familyName'])
            ) {
                $userMeta = new UserName(
                    $data['fullName']['givenName'] ?? '',
                    $data['fullName']['familyName'] ?? ''
                );
            }

            $exchangeCodeRequest = new AppleExchangeCodeRequest(
                $code ?? '',
                $this->mobileRedirectUrlFactory->make($type),
                $userMeta
            );
        } else {
            $exchangeCodeRequest = new ExchangeCodeRequest(
                $code ?? '',
                $this->mobileRedirectUrlFactory->make($type)
            );
        }

        $callbackData->setExchangeCodeRequest($exchangeCodeRequest);

        return $callbackData;
    }
}
