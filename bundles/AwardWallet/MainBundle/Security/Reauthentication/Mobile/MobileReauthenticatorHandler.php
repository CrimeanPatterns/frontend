<?php

namespace AwardWallet\MainBundle\Security\Reauthentication\Mobile;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Security\Reauthentication\AuthenticatedUser;
use AwardWallet\MainBundle\Security\Reauthentication\Environment;
use AwardWallet\MainBundle\Security\Reauthentication\Reauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthRequest;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthResponse;
use AwardWallet\MainBundle\Security\Reauthentication\ResultResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MobileReauthenticatorHandler
{
    /**
     * @var Reauthenticator
     */
    private $reauthenticator;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var MobileDeviceManager
     */
    private $mobileDeviceManager;
    private BinaryLoggerFactory $check;

    public function __construct(
        Reauthenticator $reauthenticator,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        MobileDeviceManager $mobileDeviceManager,
        LoggerInterface $logger
    ) {
        $this->reauthenticator = $reauthenticator;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->mobileDeviceManager = $mobileDeviceManager;
        $this->check =
            (new BinaryLoggerFactory(
                (new ContextAwareLoggerWrapper($logger))
                ->pushContext([Context::SERVER_MODULE_KEY => 'mobile_reauthentication_handler'])
                ->setMessagePrefix('mobile reauth handler: ')
            ))
            ->toInfo();
    }

    public function handle(string $action, Request $request, array $methods = [], bool $autoReset = true): ?Response
    {
        $checkThat = $this->check;
        $authUser = new AuthenticatedUser(
            $this->tokenStorage->getUser(),
            $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')
        );
        $environment = Environment::fromRequest($request);

        if (
            $methods
            && $checkThat('request')->hasNo('method from trigger-list')
               ->on(!it($methods)->any(fn (string $method) => $request->isMethod($method)))
        ) {
            return null;
        }

        if (
            $checkThat('request')->hasGot('context header')
                ->on($request->headers->has(MobileReauthHeaders::CONTEXT))
            && (
                $checkThat('request')->hasGot('input header')
                    ->on($request->headers->has(MobileReauthHeaders::INPUT))
                || $checkThat('request')->hasGot('intent header')
                    ->on($request->headers->has(MobileReauthHeaders::INTENT))
            )
        ) {
            return $this->handleVerify($request, $action, $authUser, $environment, $autoReset);
        }

        return $this->handleStart($authUser, $action, $environment, $request);
    }

    /**
     * @param string[] $methods
     */
    public function handleAuto(Request $request, array $methods = [], bool $autoReset = true): ?Response
    {
        return $this->handle($this->getAction($request), $request, $methods, $autoReset);
    }

    public function reset(string $action): void
    {
        $this->reauthenticator->reset($action);
    }

    public function resetAuto(Request $request): void
    {
        $this->reset($this->getAction($request));
    }

    protected function getAction(Request $request): string
    {
        $action = $request->attributes->get('_route');

        if (StringUtils::isEmpty($action)) {
            throw new AccessDeniedHttpException();
        }

        $routeParams = $request->attributes->get('_route_params');

        if (\is_array($routeParams) && $routeParams) {
            $action .=
                '_' .
                it($routeParams)
                ->mapIndexed(function (?string $value, ?string $param) { return self::paramToString($param) . '_' . self::paramToString($value); })
                ->joinToString('_and_');
        }

        return $action;
    }

    protected static function paramToString($value): string
    {
        return \is_null($value) ? 'null' : (string) $value;
    }

    protected function handleError(ResultResponse $resultResponse): Response
    {
        $response = new JsonResponse("Reauth failed", 403);
        $headers = $response->headers;

        if (KeychainReauthenticator::EMPTY_ERROR !== $resultResponse->error) {
            $headers->set(MobileReauthHeaders::ERROR, $resultResponse->error);
        }

        $headers->set(MobileReauthHeaders::RETRY, $resultResponse->canRetryOnError ? 'true' : 'false');

        return $response;
    }

    protected function handleBadRequest(): void
    {
        throw new BadRequestHttpException();
    }

    private function handleStart(AuthenticatedUser $authUser, string $action, Environment $environment, Request $request): ?JsonResponse
    {
        try {
            $startResult = $this->reauthenticator->start($authUser, $action, $environment);
        } catch (\InvalidArgumentException $e) {
            $this->handleBadRequest();
        }

        if (ReauthResponse::ACTION_AUTHORIZED === $startResult->action) {
            $request->attributes->set(MobileReauthenticationRequestListener::REQUEST_SUCCESS_INPUT_ATTRIBUTE, true);

            return null;
        }

        $response = new JsonResponse("Reauth required", 403);
        $headers = $response->headers;
        $headers->set(MobileReauthHeaders::CONTEXT, $startResult->context);
        $headers->set(MobileReauthHeaders::REQUIRED, ('code' === $startResult->context) ? \rtrim($startResult->inputTitle, ':') : 'true');

        return $response;
    }

    private function handleVerify(Request $request, string $action, AuthenticatedUser $authUser, Environment $environment, bool $autoReset): ?Response
    {
        $context = $request->headers->get(MobileReauthHeaders::CONTEXT);
        $intent = $request->headers->get(MobileReauthHeaders::INTENT);

        $reauthRequest = new ReauthRequest(
            $action,
            $context,
            $request->headers->get(MobileReauthHeaders::INPUT),
            $intent
        );

        try {
            $verifyResult = $this->reauthenticator->verify($authUser, $reauthRequest, $environment);
        } catch (\InvalidArgumentException $e) {
            $this->handleBadRequest();
        }

        if ($verifyResult->success) { // success intent or input
            if ($reauthRequest->haveIntent()) {
                return $this->handleStart($authUser, $action, $environment, $request);
            } else {
                $request->attributes->set(MobileReauthenticationRequestListener::REQUEST_SUCCESS_INPUT_ATTRIBUTE, true);

                if ($autoReset) {
                    $request->attributes->set(MobileReauthenticationRequestListener::REQUEST_ACTION_ATTRIBUTE, $action);
                    $request->attributes->set(MobileReauthenticationRequestListener::REQUEST_RESET_ATTRIBUTE, true);
                }

                if (
                    (KeychainReauthenticator::TYPE !== $context)
                    && StringUtils::isEmpty($intent)
                ) {
                    $secret = $this->mobileDeviceManager->generateKeychainForCurrentDevice();

                    if (isset($secret)) {
                        $request->attributes->set(
                            MobileReauthenticationRequestListener::REQUEST_KEYCHAIN_ATTRIBUTE,
                            $secret
                        );
                    }
                }

                return null;
            }
        }

        return $this->handleError($verifyResult);
    }
}
