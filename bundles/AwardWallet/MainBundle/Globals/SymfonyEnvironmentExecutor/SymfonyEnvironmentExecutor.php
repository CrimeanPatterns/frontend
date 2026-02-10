<?php

namespace AwardWallet\MainBundle\Globals\SymfonyEnvironmentExecutor;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ApiVersionListener;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\LocaleListener;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TranslatorHijacker;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SymfonyEnvironmentExecutor
{
    private RequestStack $requestStack;
    private AwTokenStorageInterface $tokenStorage;
    private ApiVersionListener $apiVersionListener;
    private LocaleListener $localeListener;
    private LocalizeService $localizer;
    private LoggerInterface $logger;
    private LocalPasswordsManager $localPasswordsManager;
    private TranslatorInterface $translator;
    private TranslatorHijacker $translatorHijacker;
    private UsrRepository $usrRepository;

    public function __construct(
        RequestStack $requestStack,
        AwTokenStorageInterface $tokenStorage,
        ApiVersionListener $apiVersionListener,
        LocaleListener $localeListener,
        LocalizeService $localizer,
        LoggerInterface $logger,
        LocalPasswordsManager $localPasswordsManager,
        TranslatorInterface $translator,
        TranslatorHijacker $translatorHijacker,
        UsrRepository $usrRepository
    ) {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->apiVersionListener = $apiVersionListener;
        $this->localeListener = $localeListener;
        $this->localizer = $localizer;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('symfony environment loader: ')
            ->pushContext([Context::SERVER_MODULE_KEY => 'symfony_environment_loader']);
        $this->localPasswordsManager = $localPasswordsManager;
        $this->translator = $translator;
        $this->translatorHijacker = $translatorHijacker;
        $this->usrRepository = $usrRepository;
    }

    public function process(SymfonyContext $symfonyContext, callable $callable)
    {
        $this->load($symfonyContext);

        try {
            return $callable();
        } finally {
            $this->clear();
        }
    }

    protected function load(SymfonyContext $symfonyContext): void
    {
        if (isset($_SESSION['UserID'])) {
            throw new \RuntimeException(\sprintf('UserID found in $_SESSION'));
        }

        if ($requestInStack = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException(\sprintf('Request stack contains request(s): %s', $requestInStack->attributes->get('_route')));
        }

        $user = $symfonyContext->getUser();
        $request = $symfonyContext->getRequest();

        $this->requestStack->push($request);

        if ($token = $this->tokenStorage->getToken()) {
            throw new \RuntimeException(\sprintf('Token storage contains token: %s', \get_class($token)));
        }

        $impersonatorUserId = $symfonyContext->getImpersonatorUserId();

        if (null !== $impersonatorUserId) {
            $impersonatorUser = $this->usrRepository->find($impersonatorUserId);
            $token = new SwitchUserToken(
                $user,
                'SYNTHETIC_CREDENTIALS',
                'secured_area',
                \array_merge($user->getRoles(), ['ROLE_IMPERSONATED']),
                new PostAuthenticationGuardToken(
                    $impersonatorUser,
                    'secured_area',
                    $impersonatorUser->getRoles()
                ),
            );
        } else {
            $token = new PostAuthenticationGuardToken($user, 'secured_area', $user->getRoles());
        }

        $this->tokenStorage->setToken($token);

        $getResponseEvent = new RequestEvent(
            $this->makeStubKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $this->localeListener->getLocale($getResponseEvent);
        $this->apiVersionListener->onKernelRequest($getResponseEvent);
        $this->localizer->getLocale();

        if ($this->translator instanceof Translator) {
            $this->translator->setLocale($user->getLocale());
        }

        if ((new RequestMatcher('^/m/api/'))->matches($request)) {
            $this->translatorHijacker->setContext('mobile');
        }

        LocalizeService::defineDateTimeFormat($this->localizer->getLocale());
    }

    protected function clear(): void
    {
        if ($this->translator instanceof Translator) {
            $this->translator->setLocale(\Locale::getDefault());
        }

        $this->translatorHijacker->clearContext();
        $this->localizer->setUser(null);
        $this->localizer->setLocale(null);
        $this->apiVersionListener->onKernelRequest(new RequestEvent(
            $this->makeStubKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        ));
        $this->localPasswordsManager->clear();
        $this->tokenStorage->setToken();
        $this->tokenStorage->clearBusinessUser();

        if (isset($_SESSION['UserID'])) {
            unset($_SESSION['UserID']);
        }

        // clear in loop
        $requests = [];

        while ($request = $this->requestStack->pop()) {
            $requests[] = $request;
        }

        if (\count($requests) > 1) {
            $this->logger->error(\sprintf(
                'Request stack must contains only one or zero request, %d found, routes: %s',
                \count($requests),
                it($requests)
                ->map(fn (Request $request) => $request->attributes->get('_route'))
                ->filterNotNull()
                ->toJSON()
            ));
        }
    }

    protected function makeStubKernel(): HttpKernelInterface
    {
        return new class() implements HttpKernelInterface {
            public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
            {
            }
        };
    }
}
