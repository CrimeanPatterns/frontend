<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\BetaTesterListener;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Translator;
use AwardWallet\MainBundle\Service\TransHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @group frontend-unit
 */
class TranslationDumpTest extends BaseTest
{
    protected $usr;

    public function testRoleTrigger()
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/m/api/', 'HTTP_HOST' => 'site.com']);
        $translator = $this->getTranslator([]);
        $listener = new BetaTesterListener($this->getTokenStorageMock(), $translator, $this->getLoggerInterface(), $this->getMemcachedMock(), $this->getTransHelperMock());

        $event = new GetResponseEvent($this->getHttpKernelInterface(), $request, HttpKernelInterface::MASTER_REQUEST);
        $this->assertFalse($translator->isDumpKeysEnabled());
        $listener->onKernelRequest($event);
        $this->assertTrue($translator->isDumpKeysEnabled());
    }

    public function testTransDump()
    {
        $translator = $this->getTranslator(
            [
                'messages' => [
                    'test.id1' => 'test id1',
                    'test.id2' => 'test %param1%',
                    'test.id4' => 'test id4',
                    'test.id5' => 'test %param1%',
                ],
                'domain' => [
                    'test.id3' => 'test %param1%',
                    'test.id6' => 'test %param1%',
                ],
            ]
        );

        $translator->setDumpKeysEnabled();
        $translator->trans('test.id1');
        $translator->trans('test.id2', ['%param1%' => 'value2']);
        $translator->trans('test.id3', ['%param1%' => 'value3'], 'domain');

        $translator->trans('test.id4', ['%count%' => 1]);
        $translator->trans('test.id5', ['%count%' => 2, '%param1%' => 'value5']);
        $translator->trans('test.id6', ['%count%' => 3, '%param1%' => 'value6'], 'domain');

        $this->assertEquals([
            'test id1' => [
                'key' => 'test.id1',
                'value' => 'test id1',
                'domain' => 'messages',
            ],
            'test value2' => [
                'key' => 'test.id2',
                'value' => 'test value2',
                'domain' => 'messages',
                'parameters' => ['%param1%' => 'value2'],
            ],
            'test value3' => [
                'key' => 'test.id3',
                'value' => 'test value3',
                'domain' => 'domain',
                'parameters' => ['%param1%' => 'value3'],
            ],

            'test id4' => [
                'key' => 'test.id4',
                'value' => 'test id4',
                'domain' => 'messages',
                'parameters' => ['%count%' => 1],
            ],
            'test value5' => [
                'key' => 'test.id5',
                'value' => 'test value5',
                'domain' => 'messages',
                'parameters' => ['%param1%' => 'value5', '%count%' => 2],
            ],
            'test value6' => [
                'key' => 'test.id6',
                'value' => 'test value6',
                'parameters' => ['%param1%' => 'value6', '%count%' => 3],
                'domain' => 'domain',
            ],
        ], $translator->getDumpedKeys());
    }

    public function testResponse()
    {
        $kernel = $this->getHttpKernelInterface();
        $logger = $this->getLoggerInterface();
        $translator = $this->getTranslator([
            'messages' => [
                'test.id1' => 'test id1',
            ],
        ]);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/m/api/', 'HTTP_HOST' => 'site.com']);
        $response = new Response('testcontent');
        $response->headers->set('Content-Type', 'text/plain');

        $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
        $listener = new BetaTesterListener($this->getTokenStorageMock(), $translator, $logger, $this->getMemcachedMock(), $this->getTransHelperMock());

        $listener->onKernelResponse($event);
        $this->assertEquals('testcontent', $response->getContent());

        $translator->setDumpKeysEnabled();

        $response = new JsonResponse(['data' => 'data']);
        $translator->trans('test.id1');
        $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
        $listener->onKernelResponse($event);
        $this->assertEquals(
            $expected = [
                'data' => 'data',
                'translationKeys' => [
                    'test id1' => [
                        'key' => 'test.id1',
                        'domain' => 'messages',
                        'value' => 'test id1',
                    ],
                ],
            ],
            json_decode($event->getResponse()->getContent(), true)
        );

        $response = new Response(json_encode(['data' => 'data']));
        $response->headers->set('Content-Type', 'application/json');
        $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);
        $listener->onKernelResponse($event);
        $this->assertEquals($expected, json_decode($event->getResponse()->getContent(), true));
    }

    protected function getTranslator($messages)
    {
        $container = $this->createMock('\\Symfony\\Component\\DependencyInjection\\ContainerInterface');

        if (count($messages) !== 0) {
            $catalogue = new MessageCatalogue('en');

            foreach ($messages as $domain => $keys) {
                foreach ($keys as $key => $translation) {
                    $catalogue->set($key, $translation, $domain);
                }
            }
            $loader = $this->createMock('Symfony\Component\Translation\Loader\LoaderInterface');
            $loader
                ->expects($this->once())
                ->method('load')
                ->will($this->returnValue($catalogue))
            ;

            $container->expects($this->any())
                ->method('get')
                ->will($this->returnValue($loader));
        }

        $translator = new Translator(
            $container,
            new MessageFormatter(new IdentityTranslator()),
            'en',
            ['loader' => ['loader']]
        );

        $translator->setLocale('en');
        $translator->addResource('loader', 'foo', 'en');

        return $translator;
    }

    protected function getCatalogue()
    {
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorage
     * @throws \PHPUnit_Framework_Exception
     */
    protected function getTokenStorageMock()
    {
        $tokenStorage = $this->createMock('\\Symfony\\Component\\Security\\Core\\Authentication\\Token\\Storage\\TokenStorageInterface');

        $user = $this->usr ? $this->usr : $this->usr = new Usr();

        $token = $this->createMock('\\Symfony\\Component\\Security\\Core\\Authentication\\Token\\TokenInterface');
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        return $tokenStorage;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AuthorizationChecker
     * @throws \PHPUnit_Framework_Exception
     */
    protected function getTransHelperMock()
    {
        $transHelper = $this->createMock(TransHelper::class);

        $transHelper->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
        $transHelper->expects($this->any())
            ->method('isUserTranslator')
            ->willReturn(true);

        return $transHelper;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Translator
     * @throws \PHPUnit_Framework_Exception
     */
    protected function getTranslatorMock()
    {
        $translator = $this->getMockBuilder('\\AwardWallet\\MainBundle\\FrameworkExtension\\Translator\\Translator')->disableOriginalConstructor()->getMock();
        $translator->expects($this->once())
            ->method('setDumpKeysEnabled');

        return $translator;
    }

    protected function getHttpKernelInterface()
    {
        return $this->createMock('\\Symfony\\Component\\HttpKernel\\HttpKernelInterface');
    }

    protected function getLoggerInterface()
    {
        return $this->createMock('\\Psr\\Log\\LoggerInterface');
    }

    protected function getMemcachedMock()
    {
        return $this->getMockBuilder(\Memcached::class)->disableOriginalConstructor()->getMock();
    }
}
