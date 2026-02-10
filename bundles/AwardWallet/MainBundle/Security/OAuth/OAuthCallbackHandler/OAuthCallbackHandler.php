<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Manager\Exception\NotBusinessAdministratorException;
use AwardWallet\MainBundle\Scanner\AnalyticsLogger;
use AwardWallet\MainBundle\Scanner\MailboxManager;
use AwardWallet\MainBundle\Scanner\UserAgentValidator;
use AwardWallet\MainBundle\Security\OAuth\Authenticator;
use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\AppleExchangeCodeRequest;
use AwardWallet\MainBundle\Security\OAuth\ExpiredStateException;
use AwardWallet\MainBundle\Security\OAuth\InvalidStateException;
use AwardWallet\MainBundle\Security\OAuth\OAuthAction;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\AuthorizedUser;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\CallbackResultInterface;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\CallbackTextError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\CodeExchangeTextError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\ExistingUserError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\InvalidCsrfTextError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\InvalidHost;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\InvalidState;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\LoggedIn;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\MailboxAdded;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\MissingMailboxAccess;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\NotBusinessAdministratorError;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\Registered;
use AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler\Result\UnauthorizedUser;
use AwardWallet\MainBundle\Security\OAuth\OAuthFactoryInterface;
use AwardWallet\MainBundle\Security\OAuth\Registrator;
use AwardWallet\MainBundle\Security\OAuth\State;
use AwardWallet\MainBundle\Security\OAuth\StateFactory;
use AwardWallet\Strings\Strings;
use JMS\TranslationBundle\Annotation\Desc;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OAuthCallbackHandler
{
    /**
     * @var StateFactory
     */
    private $oauthStateFactory;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var Authenticator
     */
    private $oauthAuthenticator;
    /**
     * @var Registrator
     */
    private $oauthRegistrator;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var OAuthFactoryInterface
     */
    private $oauthFactory;
    /**
     * @var UserAgentValidator
     */
    private $userAgentValidator;
    /**
     * @var MailboxManager
     */
    private $mailboxManager;
    /**
     * @var SafeExecutorFactory
     */
    private $safeExecutorFactory;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var AnalyticsLogger
     */
    private $analyticsLogger;
    private BinaryLoggerFactory $check;
    private \Memcached $memcached;

    public function __construct(
        LoggerInterface $securityLogger,
        StateFactory $oauthStateFactory,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        Authenticator $oauthAuthenticator,
        Registrator $oauthRegistrator,
        OAuthFactoryInterface $oauthFactory,
        UserAgentValidator $userAgentValidator,
        MailboxManager $mailboxManager,
        SafeExecutorFactory $safeExecutorFactory,
        TranslatorInterface $translator,
        AnalyticsLogger $analyticsLogger,
        \Memcached $memcached
    ) {
        $this->oauthStateFactory = $oauthStateFactory;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->oauthAuthenticator = $oauthAuthenticator;
        $this->oauthRegistrator = $oauthRegistrator;
        $this->logger = $securityLogger;
        $this->oauthFactory = $oauthFactory;
        $this->userAgentValidator = $userAgentValidator;
        $this->mailboxManager = $mailboxManager;
        $this->safeExecutorFactory = $safeExecutorFactory;
        $this->translator = $translator;
        $this->analyticsLogger = $analyticsLogger;
        $this->logger = (new ContextAwareLoggerWrapper($securityLogger))
            ->setMessagePrefix('oauth_callback_handler: ')
            ->pushContext([Context::SERVER_MODULE_KEY => 'oauth_callback_handler']);
        $this->check = (new BinaryLoggerFactory($securityLogger))->toInfo();
        $this->memcached = $memcached;
    }

    public function handle(string $type, CallbackData $callbackData, Request $request): CallbackResultInterface
    {
        $this->logStart($type, $callbackData);

        $state = null;
        $stateExpired = false;

        if ($callbackData->getSerializedState() !== null) {
            try {
                $state = $this->oauthStateFactory->decodeState($callbackData->getSerializedState());
            } catch (InvalidStateException $exception) {
                $this->logger->warning($exception->getMessage());
            } catch (ExpiredStateException $exception) {
                $this->logger->warning($exception->getMessage());
                $stateExpired = true;
            }
        }

        if (StringUtils::isNotEmpty($callbackData->getError())) {
            if ($state) {
                $callbackData->setState($state);
            }

            $this->logger->warning("oauth error: " . $callbackData->getError() . ', ' . $callbackData->getErrorDescription());

            return new CallbackTextError($callbackData->getError());
        }

        if (StringUtils::isEmpty($callbackData->getSerializedState())) {
            return InvalidState::getInstance();
        }

        if (!$state && $stateExpired) {
            // I wasn't able to find more suitable exception, wo let it will be InvalidCsrfTextError
            // it means the same - session has expired, try again
            return new InvalidCsrfTextError($this->translator->trans('error.auth.two-factor.password.expired'));
        }

        if (!$state) {
            return InvalidState::getInstance();
        }

        $this->logState($state);
        $callbackData->setState($state);

        if ($state->getHost() !== $request->getHost()) {
            $this->logger->info("redirecting oauth callback from {$request->getHost()} to {$state->getHost()}");

            return InvalidHost::getInstance();
        }

        $action = $state->getAction();

        if (
            $this->authorizationChecker->isGranted('ROLE_USER')
            && (OAuthAction::MAILBOX !== $action)
        ) {
            $this->logger->info("Authorized user detected for non-mailbox actions");

            return AuthorizedUser::getInstance();
        }

        if (!$this->oauthStateFactory->isValidCsrf($state)) {
            $this->logger->info("Invalid state-CSRF detected");

            return new InvalidCsrfTextError($this->translator->trans('error.auth.two-factor.password.expired'));
        }

        if (
            ($this->tokenStorage->getBusinessUser() === null)
            && ($action === OAuthAction::MAILBOX)
        ) {
            $this->logger->info("unauthorized oauth callback for mailbox action");

            return UnauthorizedUser::getInstance();
        }

        if (
            !$this->memcached->add("oauth_code_" . $callbackData->getExchangeCodeRequest()->getCode(), time(), 3600)
            && $this->memcached->getResultCode() === \Memcached::RES_NOTSTORED
        ) {
            $this->logger->warning("oauth code already used");

            return new CodeExchangeTextError($this->translator->trans('error.auth.two-factor.password.expired'));
        }

        $oauth = $this->oauthFactory->getByType($type);
        $exchangeResult = $oauth->exchangeCode(
            $callbackData->getExchangeCodeRequest(),
            $callbackData->getRawCallbackData()
        );

        if ($exchangeResult->getError() !== null) {
            $this->logger->warning("OAuth exchange error: " . $exchangeResult->getError());

            return new CodeExchangeTextError($exchangeResult->getError());
        }

        $regResult = null;
        $loggedIn = false;
        $userInfo = $exchangeResult->getUserInfo();
        $tokens = $exchangeResult->getTokens();

        if (
            (OAuthAction::REGISTER === $action)
            || (OAuthAction::LOGIN === $action)
        ) {
            try {
                $loggedIn = $this->oauthAuthenticator->authenticate($type, $userInfo, $state->isRememberMe());

                if (!$loggedIn) {
                    $regResult = $this->oauthRegistrator->register($type, $userInfo, $state->isMailboxAccess() ? $tokens : null, $request, $state->getQuery());
                }
            } catch (NotBusinessAdministratorException $e) {
                $this->logger->info("Current user is not a business administrator");

                return new NotBusinessAdministratorError($e->getMessage());
            }
        }

        $this->logger->info("oauth complete", ["action" => $action, "mailboxAccess" => $state->isMailboxAccess(), "rememberMe" => $state->isRememberMe(), "profileAccess" => $state->isProfileAccess()]);

        // user has not checked "Gmail" checkbox on granular authorization dialog
        // redirect him to google again, this time google will display "Access mailbox Yes/No?" dialog
        // because now it is single mssing scope, gmail
        $mailboxAccessDoubleAskedKey = "mada_" . $userInfo->getEmail();

        if (
            $exchangeResult->getMailboxAccess() === false
            && $state->isMailboxAccess()
            && in_array($action, [OAuthAction::REGISTER, OAuthAction::LOGIN])
            && $this->memcached->get($mailboxAccessDoubleAskedKey) === false
        ) {
            $this->memcached->set($mailboxAccessDoubleAskedKey, true, 300);
            $this->logger->info("no mailbox access, will retry authorization once");
            $user = $this->tokenStorage->getUser();

            return new MissingMailboxAccess($userInfo->getId(), $user ? $user->getEmail() : '', $regResult && $regResult->isSuccess());
        }

        $agentId = $this->userAgentValidator->checkAgentId($state->getAgentId());

        if (
            (OAuthAction::MAILBOX === $action)
            || (
                $state->isMailboxAccess()
                && (($regResult !== null && $regResult->isSuccess()) || $loggedIn)
                && $this->check->that('mailbox access')->wasNot('declined by user')
                    ->on(!$this->tokenStorage->getBusinessUser()->isDeclinedMailboxAccess($type, $userInfo->getId()))
            )
        ) {
            $this->safeExecutorFactory
                ->make(function () use ($agentId, $type, $userInfo, $tokens, $state) {
                    $this->mailboxManager->linkMailbox($this->tokenStorage->getBusinessUser(), $agentId, $type, $userInfo->getEmail(), $tokens);
                    $this->analyticsLogger->logMailboxAdded(
                        $type,
                        $this->tokenStorage->getBusinessUser()->getId(),
                        $state->getPlatform()
                    );
                })
                ->run();
        }

        if ($loggedIn) {
            $user = $this->tokenStorage->getUser();
            $this->logger->info("User was logged in!", ['UserID' => $user->getId()]);

            return new LoggedIn($user->getEmail());
        }

        if ($regResult !== null) {
            if ($regResult->isSuccess()) {
                $user = $this->tokenStorage->getUser();
                $this->logger->info("User was registered!", ['UserID' => $user->getId()]);

                return new Registered($user->getEmail(), $regResult->getTargetUrl());
            }

            $this->logger->info("Existing user was detected", ['email' => $userInfo->getEmail()]);

            return new ExistingUserError(
                $userInfo->getEmail(),
                $this->translator->trans(/** @Desc("A user with this email address (%email%) is already registered; please enter your password to log in.") */ 'email-already-registered-enter-password', ['%email%' => $userInfo->getEmail()])
            );
        }

        if (OAuthAction::MAILBOX === $action) {
            $this->logger->info("Mailbox was added");

            return MailboxAdded::getInstance();
        }

        throw new \LogicException('Unknown state');
    }

    private function logStart(string $type, CallbackData $callbackData): void
    {
        $exchangeCodeRequest = $callbackData->getExchangeCodeRequest();

        $this->logger->info(
            'oauth callback handling start',
            \array_merge(
                [
                    'type' => $type,
                    'code' => Strings::cutInMiddle($callbackData->getCode() ?? '', 4),
                    'state' => Strings::cutInMiddle($callbackData->getSerializedState() ?? '', 4),
                    'error' => $callbackData->getError(),
                    'errorDesc' => $callbackData->getErrorDescription(),
                ],
                (
                    $exchangeCodeRequest instanceof AppleExchangeCodeRequest
                    && ($userName = $exchangeCodeRequest->getUserName())
                ) ?
                    [
                        'firstName' => $userName->getFirstName(),
                        'lastName' => $userName->getLastName(),
                    ] :
                    []
            ));
    }

    private function logState(State $state): void
    {
        $this->logger->info('decoded state', [
            'userid' => $state->getUserId(),
            'agentid' => $state->getAgentId(),
            'action' => $state->getAction(),
            'host' => $state->getHost(),
            'isMailboxAccess' => $state->isMailboxAccess(),
            'isProfileAccess' => $state->isProfileAccess(),
            'isRememberMe' => $state->isRememberMe(),
            'query' => $state->getQuery(),
            'type' => $state->getType(),
            'csrf' => $state->getCsrf(),
        ]);
    }
}
