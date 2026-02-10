<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Service\Lounge\CurlBrowser;
use AwardWallet\MainBundle\Service\Lounge\NoProxyException;
use AwardWallet\MainBundle\Service\Lounge\Proxy;
use AwardWallet\MainBundle\Service\Lounge\ProxyList;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Codeception\Util\Stub;

/**
 * @group frontend-unit
 */
class ProxyTest extends BaseContainerTest
{
    public function test()
    {
        $attempt = 0;
        $proxy = new Proxy(
            $this->makeEmpty(ProxyList::class, [
                'getProxyList' => [
                    '1.1.1.1',
                    '2.2.2.2',
                    '3.3.3.3',
                ],
            ])
        );
        $browser = $this->makeEmpty(CurlBrowser::class, [
            'setProxy' => Stub::exactly(3, function (string $proxy) {
                $this->assertNotNull($proxy);

                return $this->makeEmpty(CurlBrowser::class);
            }),
        ]);
        $proxy->useProxy(function () use (&$attempt) {
            if ($attempt++ < 2) {
                return Proxy::CHANGE_PROXY;
            }

            return true;
        }, $browser);
    }

    public function testNoProxyException()
    {
        $this->expectException(NoProxyException::class);
        $proxy = new Proxy(
            $this->makeEmpty(ProxyList::class, [
                'getProxyList' => [],
            ])
        );
        $proxy->useProxy(function () {
            return Proxy::CHANGE_PROXY;
        }, $this->makeEmpty(CurlBrowser::class));
    }
}
