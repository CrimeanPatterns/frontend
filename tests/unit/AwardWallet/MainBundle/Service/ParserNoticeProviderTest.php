<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Service\ParserNoticeProvider;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class ParserNoticeProviderTest extends BaseContainerTest
{
    public function testQuery()
    {
        $provs = [
            ['Code' => 'testprovider', 'ProviderID' => 123],
        ];
        $data = [
            0 => [
                'DetectionDate' => '2018-12-24T07:22:11.504Z',
                'ProviderID' => 123,
                'AccountId' => 3280555,
                'RequestId' => null,
                'ErrorMessage' => 'flight-0-1: missing airline name',
            ],
            1 => [
                'DetectionDate' => '2018-12-24T07:20:07.307Z',
                'ProviderID' => 123,
                'AccountId' => 2600130,
                'RequestId' => null,
                'ErrorMessage' => 'flight-0-0: missing airline name',
            ],
        ];

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($provs);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement);

        $curlDriver = $this->createMock(\HttpDriverInterface::class);
        $curlDriver
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturn(new \HttpDriverResponse(file_get_contents(__DIR__ . '/Fixtures/ParserNoticeProvider/pnp.json'), 200));

        $memcached = $this->createMock(\Memcached::class);
        $memcached
            ->expects(self::once())
            ->method('get')
            ->willReturn(false);

        $logger = $this->container->get(LoggerInterface::class);

        $pnp = new ParserNoticeProvider(
            $memcached,
            'nowhere:9200',
            $connection,
            $curlDriver,
            $logger
        );

        [$fromCache, $newData] = $pnp->search(strtotime('20018-12-24'));
        $this->assertFalse($fromCache);
        $this->assertEquals(2, count($newData));
        $this->assertEquals($data[0]['AccountId'], $newData[0]['AccountId']);
        $this->assertEquals($data[1]['AccountId'], $newData[1]['AccountId']);
    }
}
