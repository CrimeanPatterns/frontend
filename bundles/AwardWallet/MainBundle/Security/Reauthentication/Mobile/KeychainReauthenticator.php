<?php

namespace AwardWallet\MainBundle\Security\Reauthentication\Mobile;

use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Security\Reauthentication\AuthenticatedUser;
use AwardWallet\MainBundle\Security\Reauthentication\Environment;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorInterface;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthRequest;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthResponse;
use AwardWallet\MainBundle\Security\Reauthentication\ResultResponse;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class KeychainReauthenticator implements ReauthenticatorInterface
{
    public const TYPE = 'keychain';
    public const EMPTY_ERROR = '_';

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationCheckoer;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var MobileDeviceManager
     */
    private $mobileDeviceManager;
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ApiVersioningService
     */
    private $apiVersioning;
    private LoggerInterface $logger;
    private BinaryLoggerFactory $check;

    public function __construct(
        AuthorizationCheckerInterface $authorizationCheckoer,
        RequestStack $requestStack,
        MobileDeviceManager $mobileDeviceManager,
        EncoderFactoryInterface $encoderFactory,
        EntityManagerInterface $entityManager,
        ApiVersioningService $apiVersioning,
        LoggerInterface $logger
    ) {
        $this->authorizationCheckoer = $authorizationCheckoer;
        $this->requestStack = $requestStack;
        $this->mobileDeviceManager = $mobileDeviceManager;
        $this->encoderFactory = $encoderFactory;
        $this->entityManager = $entityManager;
        $this->apiVersioning = $apiVersioning;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->withClass(self::class)
            ->withTypedContext();
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo();
    }

    public function start(AuthenticatedUser $authUser, string $action, Environment $environment): ReauthResponse
    {
        return ReauthResponse::ask(
            'Keychain',
            'Keychain',
            self::TYPE,
            self::TYPE
        );
    }

    public function verify(AuthenticatedUser $authUser, ReauthRequest $request, Environment $environment): ResultResponse
    {
        if ($request->getContext() !== self::TYPE) {
            throw new \InvalidArgumentException(sprintf('Wrong context "%s"', $request->getContext()));
        }

        if ($request->haveIntent()) {
            throw new \InvalidArgumentException(sprintf('Unsupported intent "%s"', $request->getIntent()));
        }

        $device = $this->mobileDeviceManager->getCurrentDevice();
        $encoder = $this->encoderFactory->getEncoder($device->getUser());

        if (
            $this->check->that('secret')->is('valid')->negativeToWarning()
            ->on($encoder->isPasswordValid($device->getSecret(), $request->getInput(), ''))
        ) {
            return ResultResponse::create(true);
        }

        $this->logger->info('clearing secret');
        $device->setSecret(null);
        $this->entityManager->flush($device);

        return ResultResponse::create(
            false,
            $this->apiVersioning->supports(MobileVersions::KEYCHAIN_REAUTHENTICATION_EMPTY_ERROR) ?
                self::EMPTY_ERROR :
                'Invalid value'
        );
    }

    public function support(AuthenticatedUser $authUser): bool
    {
        $checkThat = $this->check;

        if (
            $checkThat('site')->isNot('mobile area')
            ->on(!$this->authorizationCheckoer->isGranted('SITE_MOBILE_AREA'))
        ) {
            return false;
        }

        $request = $this->requestStack->getMasterRequest();

        if (
            $checkThat('request')->doesNot('exist')
            ->on(!$request)
        ) {
            return false;
        }

        return
            $checkThat('client')->does('have flag in headers')
                ->on(self::TYPE === $request->headers->get(MobileReauthHeaders::CONTEXT))
            && $checkThat('session')->does('have linked device')
                ->on(
                    $currentDevice = $this->mobileDeviceManager->getCurrentDevice(),
                    $currentDevice ? ['mobileDeviceID' => $currentDevice->getMobileDeviceId()] : []
                )
            && $checkThat('linked device')->does('have secret')
                ->on($currentDevice->hasSecret());
    }

    public function reset(string $action)
    {
    }
}
