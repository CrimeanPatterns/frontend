<?php

namespace AwardWallet\Tests\Unit\MainBundle\Controller;

use AwardWallet\MainBundle\Controller\SocksController;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\SocksMessaging\AccessCheckHandler;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @group frontend-unit
 */
class SocksControllerTest extends BaseTest
{
    public function testInvalidInput(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid auth request');
        $controller = new SocksController();
        $controller->authAction(
            $this->prepareRequest('{{'),
            $this->prophesize(AccessCheckHandler::class)
                ->checkAuth(Argument::cetera())
                ->shouldNotBeCalled()
                ->getObjectProphecy()
                ->reveal(),
            $this->prophesize(AntiBruteforceLockerService::class)->reveal()
        );
    }

    public function testValidInput()
    {
        $controller = new SocksController();
        $controller->setContainer(
            $this->prophesize(ContainerInterface::class)
                ->get('kernel')
                ->willReturn(
                    $this->prophesize(KernelInterface::class)->reveal()
                )
                ->getObjectProphecy()

                ->get(LocalizeService::class)
                ->willReturn(
                    $this->prophesize(LocalizeService::class)->reveal()
                )
                ->getObjectProphecy()

                ->reveal()
        );
        $response = $controller->authAction(
            $this->prepareRequest('{"channels": ["channel1", "channel2"],"client": "some_client"}'),
            $this->prophesize(AccessCheckHandler::class)
                ->checkAuth(Argument::that(function (object $input) {
                    return
                        ($input->channels == ['channel1', 'channel2'])
                        && ($input->client == 'some_client');
                }))
                ->willReturn($checkResult = ['channel1' => true, 'channel2' => false])
                ->shouldBeCalledOnce()
                ->getObjectProphecy()
                ->reveal(),
            $this->prophesize(AntiBruteforceLockerService::class)->reveal()
        );

        $this->assertTrue($response instanceof JsonResponse);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($checkResult, \json_decode($response->getContent(), true));
    }

    protected function prepareRequest(string $content): Request
    {
        $request = new Request([], [], [], [], [], [], $content);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }
}
