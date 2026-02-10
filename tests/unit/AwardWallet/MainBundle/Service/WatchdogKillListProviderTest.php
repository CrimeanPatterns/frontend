<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Service\WatchdogKillListProvider;
use AwardWallet\Tests\Unit\BaseTest;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;

/**
 * @group frontend-unit
 */
class WatchdogKillListProviderTest extends BaseTest
{
    public function testQuery()
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                ['Code' => 'testprovider', 'ProviderID' => 123, 'UpdateDate' => '2012-01-01', 'AccountID' => 1416242],
                ['Code' => 'testprovider', 'ProviderID' => 123, 'UpdateDate' => '2012-01-02', 'AccountID' => 1416243],
            ]);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement);

        $curlDriver = $this->createMock(\HttpDriverInterface::class);
        $curlDriver
            ->expects($this->once())
            ->method('request')
            ->willReturn(new \HttpDriverResponse(file_get_contents(__DIR__ . '/Fixtures/WatchdogKillListProvider/killed.json'), 200));

        $memcached = $this->createMock(\Memcached::class);
        $memcached
            ->expects(self::once())
            ->method('get')
            ->willReturn(false);

        $klp = new WatchdogKillListProvider(
            $memcached,
            'nowhere:9200',
            $connection,
            $curlDriver
        );

        $accounts = $klp->search();

        $this->assertEquals(2, count($accounts[123]));
        $this->assertEquals("received timeout, will not save: User queue timeout", $accounts[123][1]["DebugInfo"]);
    }
}
