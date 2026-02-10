<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Controller\LoyaltyCallbackController;
use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group frontend-unit
 */
class LoyaltyConfirmationCallbackTest extends BaseContainerTest
{
    public const USERNAME = 'awardwallet';
    private $password;

    public function _before()
    {
        parent::_before();
        $this->password = $this->container->getParameter('loyalty.callback_password');
    }

    public function _after()
    {
        $this->password = null;
        parent::_after();
    }

    public function testAccessDenied()
    {
        $request = $this->getRequestMock();
        $request->expects($this->once())->method('getPassword')->willReturn('ErrorPass!');

        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $producer = $this->getMockBuilder(Producer::class)->disableOriginalConstructor()->getMock();
        $ed = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $serializer = $this->container->get("jms_serializer");

        $controller = new LoyaltyCallbackController($producer, $logger, $ed, $em, $serializer, $this->password);
        /** @var Response $response */
        $response = $controller->callbackAction($request, LoyaltyCallbackController::CONFIRMATION_METHOD);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('access denied', $response->getContent());
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testProcessSuccess()
    {
        $content = 'Some content for callback';
        $type = LoyaltyCallbackController::CONFIRMATION_METHOD;
        $priority = 7;

        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();

        $producer = $this->getMockBuilder(Producer::class)->disableOriginalConstructor()->getMock();
        $producer->expects($this->once())->method('publish')->with($content, '', ['priority' => $priority]);

        $request = $this->getRequestMock();
        $request->expects($this->once())->method('getPassword')->willReturn($this->password);
        $request->expects($this->once())->method('getContent')->willReturn($content);

        $ed = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $serializer = $this->container->get("jms_serializer");

        $controller = new LoyaltyCallbackController($producer, $logger, $ed, $em, $serializer, $this->password);
        /** @var Response $response */
        $response = $controller->callbackAction($request, $type, $priority);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    private function getRequestMock()
    {
        $mock = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $mock->headers = new HeaderBag();
        $mock->expects($this->once())->method('getUser')->willReturn(self::USERNAME);

        return $mock;
    }
}
