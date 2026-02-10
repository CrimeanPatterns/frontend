<?php

namespace AwardWallet\Tests\Unit\MainBundle\Globals;

use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ApiVersionListener;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\LocaleListener;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TranslatorHijacker;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\SymfonyEnvironmentExecutor\SymfonyContext;
use AwardWallet\MainBundle\Globals\SymfonyEnvironmentExecutor\SymfonyEnvironmentExecutor;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Globals\SymfonyEnvironmentExecutor\SymfonyEnvironmentExecutor
 */
class SymfonyEnvironmentExecutorTest extends BaseTest
{
    public function _before()
    {
        parent::_before();

        if (isset($_SESSION['UserID'])) {
            unset($_SESSION['UserID']);
        }
    }

    /**
     * @covers ::load
     */
    public function testLoadShouldThrowWhenRequestExistsInStack(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Request stack contains request(s): updater_route');
        $requestStackMock = $this->prophesize(RequestStack::class);
        $requestStackMock
            ->getCurrentRequest()
            ->shouldBeCalledOnce()
            ->willReturn($this->makeRequest('updater_route'));

        $loader = new SymfonyEnvironmentExecutor(
            $requestStackMock->reveal(),
            $this->prophesize(AwTokenStorageInterface::class)->reveal(),
            $this->prophesize(ApiVersionListener::class)->reveal(),
            $this->prophesize(LocaleListener::class)->reveal(),
            $this->prophesize(LocalizeService::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(LocalPasswordsManager::class)->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $this->prophesize(TranslatorHijacker::class)->reveal(),
            $this->prophesize(UsrRepository::class)->reveal()
        );

        $loader->process(new SymfonyContext(new Usr(), new Request()), fn () => true);
    }

    /**
     * @covers ::load
     */
    public function testLoadShouldThrowWhenUserIDInGlobalSessionArray(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('UserID found in $_SESSION');

        if (!isset($_SESSION)) {
            $_SESSION = [];
        }

        $_SESSION['UserID'] = 100500;

        $loader = new SymfonyEnvironmentExecutor(
            $this->prophesize(RequestStack::class)->reveal(),
            $this->prophesize(AwTokenStorageInterface::class)->reveal(),
            $this->prophesize(ApiVersionListener::class)->reveal(),
            $this->prophesize(LocaleListener::class)->reveal(),
            $this->prophesize(LocalizeService::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(LocalPasswordsManager::class)->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $this->prophesize(TranslatorHijacker::class)->reveal(),
            $this->prophesize(UsrRepository::class)->reveal()
        );

        $loader->process(new SymfonyContext(new Usr(), new Request()), fn () => true);
    }

    /**
     * @covers ::load
     */
    public function testLoadShouldThrowWhenTokenInTokenStorage(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('/^Token storage contains token:/');
        $tokenStorageMock = $this->prophesize(AwTokenStorageInterface::class);
        $tokenStorageMock
            ->getToken()
            ->shouldBeCalledOnce()
            ->willReturn(
                $this->prophesize(TokenInterface::class)->reveal()
            );

        $loader = new SymfonyEnvironmentExecutor(
            $this->prophesize(RequestStack::class)->reveal(),
            $tokenStorageMock->reveal(),
            $this->prophesize(ApiVersionListener::class)->reveal(),
            $this->prophesize(LocaleListener::class)->reveal(),
            $this->prophesize(LocalizeService::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(LocalPasswordsManager::class)->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $this->prophesize(TranslatorHijacker::class)->reveal(),
            $this->prophesize(UsrRepository::class)->reveal()
        );

        $loader->process(new SymfonyContext(new Usr(), new Request()), fn () => true);
    }

    /**
     * @covers ::load
     */
    public function testSuccessLoad(): void
    {
        $userMock = $this->prophesize(Usr::class);
        $userMock
            ->getRoles()
            ->shouldBeCalledOnce()
            ->willReturn(
                [
                    'ROLE1',
                    'ROLE2',
                ]
            );
        $locale = 'fr_FR';
        $userMock
            ->getLocale()
            ->willReturn($locale);
        $userMock = $userMock->reveal();
        $request = new Request();

        $tokenStorageMock = $this->prophesize(AwTokenStorageInterface::class);
        $tokenStorageMock
            ->getToken()
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $tokenStorageMock
            ->setToken(Argument::that(function ($token) use ($userMock) {
                return
                    ($token instanceof TokenInterface)
                    && ($user = $token->getUser())
                    && ($user === $userMock)
                    && $token->getRoleNames() === ['ROLE1', 'ROLE2'];
            }))
            ->will(function () use ($tokenStorageMock) {
                $tokenStorageMock->setToken()->shouldBeCalledOnce();
                $tokenStorageMock->clearBusinessUser();
            })
            ->shouldBeCalledOnce();

        $requestStackMock = $this->prophesize(RequestStack::class);
        $requestStackMock
            ->getCurrentRequest()
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $requestStackMock
            ->push(Argument::exact($request))
            ->will(function () use ($requestStackMock) {
                $requestStackMock->pop()->shouldBeCalledOnce();
            })
            ->shouldBeCalledOnce();

        $eventAssert = fn ($event) =>
            ($event instanceof GetResponseEvent)
            && ($event->getRequest() === $request)
            && ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST);

        $localeListener = $this->prophesize(LocaleListener::class);
        $localeListener
            ->getLocale(Argument::that($eventAssert))
            ->shouldBeCalledOnce();

        $apiVersionListener = $this->prophesize(ApiVersionListener::class);
        $apiVersionListener
            ->onKernelRequest(Argument::that($eventAssert))
            ->will(fn () =>
                $apiVersionListener
                    ->onKernelRequest(Argument::that(fn ($event) =>
                        ($event instanceof GetResponseEvent)
                        && ($event->getRequest() !== $request)
                        && ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST)
                    ))
                    ->shouldBeCalledOnce()
            )
            ->shouldBeCalledOnce();

        $localizer = $this->prophesize(LocalizeService::class);
        $localizer
            ->getLocale()
            ->will(function () use ($localizer) {
                $localizer
                    ->setUser(null)
                    ->shouldBeCalled();
                $localizer
                    ->setLocale(null)
                    ->shouldBeCalled();
            })
            ->shouldBeCalled();

        $localPasswordsManager = $this->prophesize(LocalPasswordsManager::class);
        $localPasswordsManager
            ->clear()
            ->shouldBeCalled();

        $translator = $this->prophesize(Translator::class);
        $translator
            ->setLocale($locale)
            ->will(function () use ($translator) {
                $translator
                    ->setLocale('en_US')
                    ->shouldBeCalled();
            })
            ->shouldBeCalled();

        $translatorHijacker = $this->prophesize(TranslatorHijacker::class);
        $translatorHijacker
            ->clearContext()
            ->shouldBeCalled();

        $loader = new SymfonyEnvironmentExecutor(
            $requestStackMock->reveal(),
            $tokenStorageMock->reveal(),
            $apiVersionListener->reveal(),
            $localeListener->reveal(),
            $localizer->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $localPasswordsManager->reveal(),
            $translator->reveal(),
            $translatorHijacker->reveal(),
            $this->prophesize(UsrRepository::class)->reveal()
        );

        $loader->process(new SymfonyContext($userMock, $request), fn () => true);
    }

    /**
     * @covers ::clear
     */
    public function testClearShouldFailWhenMoreThanOneRequestInRequestStack(): void
    {
        $requestStackMock = $this->prophesize(RequestStack::class);
        $stack = [
            $this->makeRequest('route1'),
            $this->makeRequest('route2'),
        ];
        $requestStackMock
            ->getCurrentRequest()
            ->will(function () use ($requestStackMock, &$stack) {
                $requestStackMock
                    ->push(Argument::cetera())
                    ->shouldBeCalledOnce();
                $requestStackMock
                    ->pop()
                    ->will(function () use (&$stack): ?Request {
                        return \array_shift($stack);
                    })
                    ->shouldBeCalledTimes(3);

                return null;
            })
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->log(
                LogLevel::ERROR,
                Argument::containingString('symfony environment loader: Request stack must contains only one or zero request, 2 found, routes: ["route1","route2"]'),
                Argument::cetera()
            )
            ->shouldBeCalled();

        $loader = new SymfonyEnvironmentExecutor(
            $requestStackMock->reveal(),
            $this->prophesize(AwTokenStorageInterface::class)->reveal(),
            $this->prophesize(ApiVersionListener::class)->reveal(),
            $this->prophesize(LocaleListener::class)->reveal(),
            $this->prophesize(LocalizeService::class)->reveal(),
            $logger->reveal(),
            $this->prophesize(LocalPasswordsManager::class)->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $this->prophesize(TranslatorHijacker::class)->reveal(),
            $this->prophesize(UsrRepository::class)->reveal()
        );
        $loader->process(new SymfonyContext(new Usr(), new Request()), fn () => true);
    }

    /**
     * @covers ::clear
     */
    public function testSuccessClear(): void
    {
        $requestStackMock = $this->prophesize(RequestStack::class);
        $stack = [
            $this->makeRequest('route1'),
        ];
        $requestStackMock
            ->getCurrentRequest()
            ->will(function () use ($requestStackMock, &$stack) {
                $requestStackMock
                    ->push(Argument::cetera())
                    ->shouldBeCalledOnce();
                $requestStackMock
                    ->pop()
                    ->will(function () use (&$stack): ?Request {
                        return \array_shift($stack);
                    })
                    ->shouldBeCalledTimes(2);

                return null;
            })
            ->shouldBeCalledOnce();

        $tokenStorageMock = $this->prophesize(AwTokenStorageInterface::class);
        $tokenStorageMock->setToken();

        $loader = new SymfonyEnvironmentExecutor(
            $requestStackMock->reveal(),
            $tokenStorageMock->reveal(),
            $this->prophesize(ApiVersionListener::class)->reveal(),
            $this->prophesize(LocaleListener::class)->reveal(),
            $this->prophesize(LocalizeService::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(LocalPasswordsManager::class)->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $this->prophesize(TranslatorHijacker::class)->reveal(),
            $this->prophesize(UsrRepository::class)->reveal()
        );
        $loader->process(new SymfonyContext(new Usr(), new Request()), fn () => true);
    }

    /**
     * @covers ::clear
     */
    public function testImpersonateRole(): void
    {
        $isImpersonated = true;
        $userMock = $this->prophesize(Usr::class);
        $userMock
            ->getRoles()
            ->shouldBeCalledOnce()
            ->willReturn(['ROLE1']);
        $userMock = $userMock->reveal();

        $impersonatorMock = $this->prophesize(Usr::class);
        $impersonatorMock
            ->getRoles()
            ->shouldBeCalledOnce()
            ->willReturn(['ROLE2']);
        $impersonatorMock = $impersonatorMock->reveal();
        $usrRepositoryMock = $this->prophesize(UsrRepository::class);
        $usrRepositoryMock
            ->find(100500)
            ->shouldBeCalledOnce()
            ->willReturn($impersonatorMock);
        $tokenStorageMock = $this->prophesize(AwTokenStorageInterface::class);
        $tokenStorageMock
            ->getToken()
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $tokenStorageMock
            ->setToken(Argument::that(function ($token) use ($userMock, $impersonatorMock) {
                return
                    ($token instanceof SwitchUserToken)
                    && ($user = $token->getUser())
                    && ($user === $userMock)
                    && $token->getRoleNames() === ['ROLE1', 'ROLE_IMPERSONATED']

                    && ($impersonatorToken = $token->getOriginalToken())
                    && ($impersonatorUser = $impersonatorToken->getUser())
                    && ($impersonatorUser === $impersonatorMock)
                    && ($impersonatorToken->getRoleNames() === ['ROLE2'])
                ;
            }))
            ->will(function () use ($tokenStorageMock) {
                $tokenStorageMock->setToken()->shouldBeCalledOnce();
                $tokenStorageMock->clearBusinessUser();
            })
            ->shouldBeCalledOnce();

        $loader = new SymfonyEnvironmentExecutor(
            $this->prophesize(RequestStack::class)->reveal(),
            $tokenStorageMock->reveal(),
            $this->prophesize(ApiVersionListener::class)->reveal(),
            $this->prophesize(LocaleListener::class)->reveal(),
            $this->prophesize(LocalizeService::class)->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(LocalPasswordsManager::class)->reveal(),
            $this->prophesize(TranslatorInterface::class)->reveal(),
            $this->prophesize(TranslatorHijacker::class)->reveal(),
            $usrRepositoryMock->reveal()
        );

        $loader->process(new SymfonyContext($userMock, new Request(), 100500), fn () => true);
    }

    protected function _after()
    {
        parent::_after();

        if (isset($_SESSION['UserID'])) {
            unset($_SESSION['UserID']);
        }
    }

    protected function makeRequest(string $route): Request
    {
        $request = new Request();
        $request->attributes->set('_route', $route);

        return $request;
    }
}
