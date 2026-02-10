<?php

namespace AwardWallet\Tests\Unit\Listeners;

use AwardWallet\MainBundle\Configuration\AwCache;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\AwHttpCacheListener;
use AwardWallet\Tests\Unit\BaseTest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * @group frontend-unit
 */
class AwHttpCacheListenerTest extends BaseTest
{
    public const RESPONSE_DATA = 'data';
    public const SERVER_URI = '/some/route';

    public const SERVER_CONFIG = ['REQUEST_URI' => self::SERVER_URI, 'HTTP_HOST' => 'site.com'];

    public function testResponseWithNoAnnotation()
    {
        $this->getDefaultListener()
            ->onKernelResponse($this->createEventMock(
                new Request(),
                $response = new Response()
            ));

        $this->never();

        $this->assertEquals('Mon, 26 Jul 1997 05:00:00 GMT', $response->headers->get('expires'));
        $this->assertEquals('no-cache', $response->headers->get('pragma'));
        $this->assertEquals('max-age=0, must-revalidate, no-cache, no-store, post-check=0, pre-check=0, private', $response->headers->get('cache-control'));
    }

    /**
     * @dataProvider cacheControlModificationsDataProvider
     */
    public function testCacheControlModifications(AwCache $cache, $cacheControlExpected)
    {
        $this->getDefaultListener()
            ->onKernelResponse($this->createEventMock(
                $this->getParametrizedRequest($cache),
                $response = new Response()
            ));

        $this->assertEquals($cacheControlExpected, $response->headers->get('cache-control'));
    }

    public function cacheControlModificationsDataProvider()
    {
        $data = [[new AwCache([]), 'no-cache, private']];

        $data[] = [$cache = new AwCache([]), 'no-store, private'];
        $cache->setNoStore(true);

        $data[] = [$cache = new AwCache([]), 'no-cache, private'];
        $cache->setNoCache(true);

        $data[] = [$cache = new AwCache([]), 'no-cache, no-store, private'];
        $cache->setNoCache(true);
        $cache->setNoStore(true);

        return $data;
    }

    /**
     * @dataProvider etagContentHashDataProvider
     * @param string $if_none_match_Expected
     */
    public function testEtagContentHash(Request $request, $if_none_match_Expected, Response $responseExpected, callable $loggerProvider)
    {
        $this->getListener(call_user_func($loggerProvider))
            ->onKernelResponse($this->createEventMock(
                $request,
                $response = new Response(self::RESPONSE_DATA)
            ));

        $this->assertEquals($if_none_match_Expected, $request->headers->get('if-none-match'));

        $this->assertEquals($responseExpected->getStatusCode(), $response->getStatusCode());
        $this->assertEquals($responseExpected->getContent(), $response->getContent());
    }

    public function etagContentHashDataProvider()
    {
        $data = [];

        $cache = new AwCache([]);
        $cache->setEtagContentHash('sha256');

        $hash = hash('sha256', self::RESPONSE_DATA);
        $hashModified = hash('sha256', self::RESPONSE_DATA . 'modified');

        // simple
        $response = new Response(self::RESPONSE_DATA, 200);
        $request = $this->getParametrizedRequest($cache);
        $request->headers->set('if-none-match', $hash);
        $data[] = [$request, $hash, $response, $this->getNeverCalledLoggerProvider()];

        // gzipped
        $response = new Response(self::RESPONSE_DATA);
        $request = $this->getParametrizedRequest($cache);
        $request->headers->set('if-none-match', $hashModified . '-gzip');
        $data[] = [$request, $hashModified, $response, $this->getNeverCalledLoggerProvider()];

        // gzipped single qouted
        $response = new Response(self::RESPONSE_DATA);
        $request = $this->getParametrizedRequest($cache);
        $request->headers->set('if-none-match', '\'' . $hashModified . '-gzip\'');
        $data[] = [$request, '\'' . $hashModified . '\'', $response, $this->getNeverCalledLoggerProvider()];

        // gzipped dobule qouted
        $response = new Response(self::RESPONSE_DATA);
        $request = $this->getParametrizedRequest($cache);
        $request->headers->set('if-none-match', '"' . $hashModified . '-gzip"');
        $data[] = [$request, '"' . $hashModified . '"', $response, $this->getNeverCalledLoggerProvider()];

        // hit
        $response = new Response('', 304);
        $request = $this->getParametrizedRequest($cache);
        $request->headers->set('if-none-match', "\"{$hash}-gzip\"");
        $data[] = [$request, "\"$hash\"", $response, $this->getOnceCalledLoggerProvider()];

        // qouted
        $response = new Response('', 304);
        $request = $this->getParametrizedRequest($cache);
        $request->headers->set('if-none-match', "\"$hash\"");
        $data[] = [$request, "\"$hash\"", $response, $this->getOnceCalledLoggerProvider()];

        return $data;
    }

    protected function getNeverCalledLoggerProvider()
    {
        return function () {
            return $this->neverUsed($this->createMock(LoggerInterface::class));
        };
    }

    protected function getOnceCalledLoggerProvider()
    {
        return function () {
            $logger = $this->createMock(LoggerInterface::class);
            $logger->expects($this->once())
                ->method('warning')
                ->with('Etag cache hit', ['_aw_server_module' => 'awcache', '_aw_server_uri' => self::SERVER_URI]);

            return $logger;
        };
    }

    protected function getListener(LoggerInterface $logger)
    {
        return new AwHttpCacheListener($logger);
    }

    /**
     * @return AwHttpCacheListener|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDefaultListener()
    {
        return $this->getListener($this->createMock(LoggerInterface::class));
    }

    /**
     * @see \Sensio\Bundle\FrameworkExtraBundle\Tests\EventListener\HttpCacheListenerTest::createEventMock
     * @return \PHPUnit_Framework_MockObject_MockObject|FilterResponseEvent
     */
    protected function createEventMock(Request $request, Response $response)
    {
        $event = $this->getMockBuilder(FilterResponseEvent::class)->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $event
            ->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($response));

        return $event;
    }

    protected function getParametrizedRequest(AwCache $cache, $serverConfig = self::SERVER_CONFIG)
    {
        return new Request([], [], ['_awcache' => $cache], [], [], $serverConfig);
    }
}
