<?php

namespace AwardWallet\Tests\Unit\Listeners;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\CustomHeadersListener;
use AwardWallet\Tests\Unit\BaseTest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @group frontend-unit
 */
class CustomHeadersListenerTest extends BaseTest
{
    public function testOnKernelResponse()
    {
        $user = $this->getUser();

        $tokenStorage = $this->createMock(AwTokenStorageInterface::class);
        $tokenStorage->method('getUser')
            ->will($this->onConsecutiveCalls(false, $user));

        /** @var AwTokenStorageInterface $tokenStorage */
        $listener = new CustomHeadersListener(
            $tokenStorage,
            new AppProcessor('frontend'),
            $this->createMock(LoggerInterface::class),
            'https://some-comet-server/',
            'awardwallet.docker',
            'business.awardwallet.docker',
            'https',
            false,
            '//some.cdn.host'
        );
        $response = new Response();

        // epmty user
        $event = $this->getResponseEvent($response);
        $listener->onKernelResponse($event);
        $this->assertNull($response->headers->get('x-aw-userid'));

        // with user
        $response = new Response();
        $event = $this->getResponseEvent($response);
        $listener->onKernelResponse($event);
        $this->assertEquals('123456', $response->headers->get('x-aw-userid'));
    }

    protected function getResponseEvent(Response $response)
    {
        return new FilterResponseEvent($this->createMock(HttpKernelInterface::class), new Request(), HttpKernelInterface::MASTER_REQUEST, $response);
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
