<?php

namespace AwardWallet\Tests\Unit\Checker;

use AwardWallet\Engine\Settings;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class ProxyTest extends BaseContainerTest
{
    /**
     * @dataProvider proxyProvider
     */
    public function testRegion($memcachedData, $regions, $expectedResponse)
    {
        $cache = new \Memcached();
        $cache->addServer(MEMCACHED_HOST, 11211);
        $cache->set("awardwallet_proxy_list_2", $memcachedData, 60);
        $this->assertEquals($memcachedData, $cache->get("awardwallet_proxy_list_2"));

        $providerCode = 'TestProxy' . StringHandler::getRandomCode(7);
        $this->aw->createAwProvider(
            $providerCode,
            $providerCode,
            [],
            [
                'testDOP' => function ($regions = []) {
                    return $this->proxyDOP($regions);
                },
            ],
            [],
            ["\\AwardWallet\\Engine\\ProxyList"]
        );
        $checker = $this->container->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getAccountChecker($providerCode);
        $checker->InitBrowser();
        global $Config;
        $Config[CONFIG_TRAVEL_PLANS] = false;
        $result = $checker->testDOP($regions);

        if (!empty($expectedResponse)) {
            $this->assertContains($result, $expectedResponse);
        } else {
            $this->assertNotEmpty($result);
        }
    }

    public function proxyProvider()
    {
        $data = '[
            {"ip": "104.236.137.179", "port":3128, "datacenter": "sfo1", "creationDate": "2016-12-12 13:30:17"},
            {"ip": "128.199.249.248", "port":3128, "datacenter": "sgp1", "creationDate": "2016-12-12 12:54:15"},
            {"ip": "178.62.62.217", "port":3128, "datacenter": "lon1", "creationDate": "2016-12-12 12:53:33"},
            {"ip": "138.197.139.110", "port":3128, "datacenter": "tor1", "creationDate": "2016-12-12 13:29:32"},
            {"ip": "139.59.141.137", "port":3128, "datacenter": "fra1", "creationDate": "2016-12-12 12:53:22"},
            {"ip": "162.243.96.165", "port":3128, "datacenter": "nyc2", "creationDate": "2016-12-12 12:54:24"},
            {"ip": "45.55.183.43", "port":3128, "datacenter": "nyc3", "creationDate": "2016-12-12 12:55:42"},
            {"ip": "198.199.74.130", "port":3128, "datacenter": "nyc1", "creationDate": "2016-12-12 12:53:41"},
            {"ip": "188.166.77.74", "port":3128, "datacenter": "ams3", "creationDate": "2016-12-12 12:55:15"},
            {"ip": "95.85.43.200", "port":3128, "datacenter": "ams2", "creationDate": "2016-12-12 12:55:20"}]';

        return [
            // memcached data,      region,                     expected response
            [$data,                 ["bad9"],                   []],
            [$data,                 Settings::DATACENTERS_EU,   ["178.62.62.217:3128", "139.59.141.137:3128", "188.166.77.74:3128", "95.85.43.200:3128"]],
            [$data,                 ["lon1"],                   ["178.62.62.217:3128"]],
            [null,                  [],                         []],
            [null,                  Settings::DATACENTERS_EU,   []],
        ];
    }
}
