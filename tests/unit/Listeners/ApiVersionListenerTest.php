<?php

namespace AwardWallet\Tests\Unit\Listeners;

use AwardWallet\MainBundle\Configuration\ApiVersion;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\ApiVersionListener;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\Tests\Unit\BaseTest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @group frontend-unit
 */
class ApiVersionListenerTest extends BaseTest
{
    /**
     * @dataProvider versionsProvider
     */
    public function testApiVersioning($version, $headerExists)
    {
        $request = $this->createRequest(new ApiVersion(['min' => $version]));
        $request->headers->set(MobileHeaders::MOBILE_VERSION, '3.0.10');

        $serviceMock = $this->getVersioningServiceMock();
        $serviceMock->expects($this->once())->method('setVersion')->with('3.0.10');
        $serviceMock->expects($this->once())->method('getVersion')->willReturn(\Herrera\Version\Parser::toVersion('3.0.10'));

        $listener = new ApiVersionListener($serviceMock, $this->getTranslatorMock(), $this->getLoggerMock());
        $requestEvent = new GetResponseEvent($kernelMock = $this->getKernelMock(), $request, HttpKernelInterface::MASTER_REQUEST);
        $controllerEvent = new FilterControllerEvent($kernelMock, function () {
            return new JsonResponse(['data' => 'payload']);
        }, $request, null);

        $listener->onKernelRequest($requestEvent);
        $listener->onKernelController($controllerEvent);
        $responseEvent = new FilterResponseEvent($this->getKernelMock(), $request, HttpKernelInterface::MASTER_REQUEST, call_user_func($controllerEvent->getController()));
        $response = $responseEvent->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($headerExists, $response->headers->has(MobileHeaders::MOBILE_VERSION));
        $data = @json_decode($response->getContent(), true);

        if ($headerExists) {
            $this->assertEquals(['error' => 'translated'], $data);
        } else {
            $this->assertEquals(['data' => 'payload'], $data);
        }
    }

    public function versionsProvider()
    {
        return [
            ['0.0.99', false],
            ['2.0.99', false],
            ['3.0.0', false],
            ['3.0.1', false],
            ['3.0.9', false],
            ['3.0.10', false],
            ['3.0.11', true],
            ['3.1.0', true],
            ['3.1.9', true],
            ['3.10.9', true],
            ['4.0.0', true],
        ];
    }

    public function testApiInvalidVersion()
    {
        $request = $this->createRequest(new ApiVersion(['min' => '3.0.10']));
        $request->headers->set(MobileHeaders::MOBILE_VERSION, '3.sdsdsdsds.10');

        $logger = $this->getLoggerMock();
        $logger->expects($this->once())->method('warning')->with(
            "The version string representation \"3.sdsdsdsds.10\" is invalid.",
            ['_aw_api_module' => 'vers']
        );
        $listener = new ApiVersionListener($this->getVersioningServiceMock(), $this->getTranslatorMock(), $logger);

        $requestEvent = new GetResponseEvent($kernelMock = $this->getKernelMock(), $request, HttpKernelInterface::MASTER_REQUEST);
        $controllerEvent = new FilterControllerEvent($kernelMock, function () {
            return new JsonResponse(['data' => 'payload']);
        }, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener->onKernelRequest($requestEvent);
        $listener->onKernelController($controllerEvent);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\HttpKernel\KernelInterface
     */
    protected function getKernelMock()
    {
        return $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|\Symfony\Contracts\Translation\TranslatorInterface
     */
    protected function getTranslatorMock()
    {
        $mock = $this->createMock('\Symfony\Contracts\Translation\TranslatorInterface');
        $mock->expects($this->any())->method('trans')->willReturn('translated');

        return $mock;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    protected function getLoggerMock()
    {
        return $this->createMock('\\Psr\\Log\\LoggerInterface');
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|\AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService
     */
    protected function getVersioningServiceMock()
    {
        return $this->createMock('\\AwardWallet\\MainBundle\\Globals\\ApiVersioning\\ApiVersioningService');
    }

    private function createRequest(?ApiVersion $cache = null)
    {
        return new Request(
            [], [], [
                '_api_version' => $cache,
            ]
        );
    }
}
