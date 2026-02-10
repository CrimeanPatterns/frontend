<?php

namespace AwardWallet\Tests\Unit\Listeners;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\MobileDeviceListener;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Service\ThemeResolver;
use AwardWallet\Tests\Unit\BaseTest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @group frontend-unit
 */
class MobileDeviceListenerTest extends BaseTest
{
    public const DEVICE_ID_HEADER = MobileHeaders::MOBILE_DEVICE_ID;
    public const DEVICE_APP_VERSION = MobileHeaders::MOBILE_VERSION;

    public const MOBILE_SERVER = ['REQUEST_URI' => '/m/api/', 'HTTP_HOST' => 'site.com'];

    public function testRequestEmptyUser()
    {
        $tokenStorage = $this->createMock(AwTokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getUser')
            ->willReturn(false);

        /** @var AwTokenStorageInterface $tokenStorage */
        $listener = new MobileDeviceListener(
            $tokenStorage,
            $this->neverUsed($this->getMobileDeviceManager()),
            $this->createMock(LoggerInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(ThemeResolver::class),
        );

        $listener->onKernelRequest($this->getRequestEvent(new Request()));
    }

    /**
     * @dataProvider requestEmptyLocaleDataProvider
     */
    public function testRequestEmptyLocale(Request $request)
    {
        $tokenStorage = $this->createMock(AwTokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getUser')
            ->willReturn($this->getUser());

        /** @var AwTokenStorageInterface $tokenStorage */
        $listener = new MobileDeviceListener(
            $tokenStorage,
            $this->neverUsed($this->getMobileDeviceManager()),
            $this->createMock(LoggerInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(ThemeResolver::class),
        );

        $listener->onKernelRequest($this->getRequestEvent($request));
    }

    public function requestEmptyLocaleDataProvider()
    {
        $result = [new Request()];

        $result[] = ($request = new Request());
        $request->setLocale('');

        return array_map(function ($el) { return [$el]; }, $result);
    }

    /**
     * @dataProvider requestValidLocaleDataProvider
     */
    public function testRequestValidLocale(Request $request, $deviceId, $locale, $appVersion)
    {
        $tokenStorage = $this->createMock(AwTokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getUser')
            ->willReturn($user = $this->getUser());

        $deviceManager = $this->getMobileDeviceManager();
        $deviceManager->expects($this->once())
            ->method('updateDeviceInfo')
            ->with($user->getUserid(), (string) $deviceId, $locale, $appVersion);

        /** @var AwTokenStorageInterface $tokenStorage */
        $listener = new MobileDeviceListener(
            $tokenStorage,
            $deviceManager,
            $this->createMock(LoggerInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(ThemeResolver::class),
        );

        $listener->onKernelRequest($this->getRequestEvent($request));
    }

    public function testClientInfoLog()
    {
        $request = new Request();
        $request->headers->set(MobileHeaders::MOBILE_PLATFORM, 'some_platform');
        $request->headers->set(MobileHeaders::MOBILE_VERSION, '1.2.13+abcefd1234');
        $request->setLocale('ru');
        $request->attributes->set('_route', 'some_route');

        $logger = $this->prophesize(LoggerInterface::class)
            ->info('client_info',
                [
                    'version' => '1.2.13_abcefd1234',
                    'platform' => 'some_platform',
                    'route' => 'some_route',
                    'locale' => 'ru',
                ]
            )
            ->willReturn()
            ->getObjectProphecy()
            ->reveal();

        $listener = new MobileDeviceListener(
            $this->prophesize(AwTokenStorageInterface::class)->reveal(),
            $this->prophesize(MobileDeviceManager::class)->reveal(),
            $logger,
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(ThemeResolver::class),
        );
        $listener->onKernelRequest(new GetResponseEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, HttpKernelInterface::MASTER_REQUEST));
    }

    public function requestValidLocaleDataProvider()
    {
        $result = [];

        $appVersion = '1.1.1';

        $request = new Request();
        $request->headers->set(self::DEVICE_ID_HEADER, $id = '100500');
        $request->headers->set(self::DEVICE_APP_VERSION, $appVersion);
        $result[] = [$request, $id, 'en', $appVersion];

        $request = new Request();
        $request->headers->set(self::DEVICE_ID_HEADER, $id = '100500');
        $request->headers->set(self::DEVICE_APP_VERSION, $appVersion);
        $request->setLocale('fr');
        $result[] = [$request, $id, 'fr', $appVersion];

        return $result;
    }

    protected function getRequestEvent(Request $request)
    {
        return new GetResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MASTER_REQUEST);
    }

    protected function getResponseEvent(Response $response, ?Request $request = null)
    {
        return new FilterResponseEvent($this->createMock(HttpKernelInterface::class), $request ?: new Request(), HttpKernelInterface::MASTER_REQUEST, $response);
    }

    protected function getMobileDeviceManager()
    {
        return $this->getMockBuilder(MobileDeviceManager::class)->disableOriginalConstructor()->getMock();
    }

    protected function getUser()
    {
        $user = new Usr();
        $property = new \ReflectionProperty(Usr::class, 'userid');
        $property->setAccessible(true);
        $property->setValue($user, 123456);
        $property->setAccessible(false);

        return $user;
    }
}
