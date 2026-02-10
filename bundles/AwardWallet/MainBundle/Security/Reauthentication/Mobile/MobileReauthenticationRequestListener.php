<?php

namespace AwardWallet\MainBundle\Security\Reauthentication\Mobile;

use AwardWallet\MainBundle\Configuration\Reauthentication;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class MobileReauthenticationRequestListener
{
    public const REQUEST_SUCCESS_INPUT_ATTRIBUTE = '_reauthentication_mobile_success_input';
    public const REQUEST_RESET_ATTRIBUTE = '_reauthentication_mobile_reset';
    public const REQUEST_ACTION_ATTRIBUTE = '_reauthentication_mobile_action';
    public const REQUEST_KEYCHAIN_ATTRIBUTE = '_reauthentication_keychain';

    public const SESSION_ENABLE_KEYCHAIN_AFTER_LOGGING_IN_KEY = 'session_enable_keychain_after_logging_in';

    private MobileReauthenticatorHandler $mobileReauthenticatorHandler;
    private ApiVersioningService $apiVersioning;
    private LoggerInterface $logger;
    private BinaryLoggerFactory $check;

    public function __construct(
        MobileReauthenticatorHandler $mobileReauthenticatorHandler,
        ApiVersioningService $apiVersioning,
        LoggerInterface $logger
    ) {
        $this->mobileReauthenticatorHandler = $mobileReauthenticatorHandler;
        $this->apiVersioning = $apiVersioning;
        $this->check =
            (new BinaryLoggerFactory(
                (new ContextAwareLoggerWrapper($logger))
                ->pushContext([Context::SERVER_MODULE_KEY => 'mobile_reauthentication_listener'])
                ->setMessagePrefix('mobile reauth listener: ')
            ))
            ->toInfo();

        $this->logger = $logger;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();

        if (
            !$request->attributes->has('_reauthentication')
            || !($request->attributes->get('_reauthentication') instanceof Reauthentication)
        ) {
            return;
        }

        /** @var Reauthentication $reauthAnnotation */
        $reauthAnnotation = $request->attributes->get('_reauthentication');

        if (
            $this->check->that('device support check')->is('enabled')
            ->on($reauthAnnotation->getCheckDeviceSupport())
            && $this->check->that('device')->doesNot('support LOGIN_AUTH feature')
            ->on($this->apiVersioning->notSupports(MobileVersions::LOGIN_OAUTH))
        ) {
            return;
        }

        $response = $this->mobileReauthenticatorHandler->handleAuto($request, $reauthAnnotation->getMethods(), $reauthAnnotation->isAutoReset());

        if ($response) {
            $event->setController(function () use ($response) {
                return $response;
            });
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->attributes->has(self::REQUEST_SUCCESS_INPUT_ATTRIBUTE)) {
            $response = $event->getResponse();
            $response->headers->set(MobileReauthHeaders::SUCCESS, 'true');
        }

        if ($request->attributes->has(self::REQUEST_RESET_ATTRIBUTE)) {
            if ($request->attributes->has(self::REQUEST_ACTION_ATTRIBUTE)) {
                $this->mobileReauthenticatorHandler->reset($request->attributes->get(self::REQUEST_ACTION_ATTRIBUTE));
            } else {
                $this->mobileReauthenticatorHandler->resetAuto($request);
            }
        }

        if ($request->attributes->has(self::REQUEST_KEYCHAIN_ATTRIBUTE)) {
            $response = $event->getResponse();
            $response->headers->set(MobileReauthHeaders::CONTEXT, KeychainReauthenticator::TYPE);
            $response->headers->set(MobileReauthHeaders::INPUT, $request->attributes->get(self::REQUEST_KEYCHAIN_ATTRIBUTE));
            $event->setResponse($response);
        }
    }
}
