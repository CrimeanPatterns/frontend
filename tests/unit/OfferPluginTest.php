<?php

namespace AwardWallet\Tests\Unit;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\Persistence\ManagerRegistry;

require_once __DIR__ . '/../../web/manager/offer/OfferPlugin.php';

/**
 * @group frontend-unit
 */
class OfferPluginTest extends BaseTest
{
    public function testAddOneUser()
    {
        $offerId = 100500;
        $connection = $this->getConnectionMock();
        $stmt = $this->getMockBuilder(Statement::class)->disableOriginalConstructor()->getMock();
        $stmt->method('fetchColumn')->willReturn(100501);
        $connection->method('executeQuery')->withConsecutive(
            ["select LastUserID from Offer where OfferID = ?", [$offerId], [], null],
            ["insert into OfferUser (OfferID, UserID, CreationDate, Manual, Params) values ({$offerId}, 100, now(), 0, '') on duplicate key update CreationDate = now(), Params = VALUES(Params)", [], [], null]
        )->will($this->onConsecutiveCalls($stmt, null));

        $registry = $this->getRegistryMock();
        $registry->method('getConnection')->willReturn($connection);

        $plugin = new TestOfferPlugin($offerId, $registry);
        $ref = new \ReflectionMethod(TestOfferPlugin::class, 'addUser');
        $ref->setAccessible(true);
        $ref->invoke($plugin, 100, []);
        $ref->setAccessible(false);
    }

    public function testAddManyUsers()
    {
        $offerId = 100500;
        $connection = $this->getConnectionMock();

        $stmt = $this->getMockBuilder(Statement::class)->disableOriginalConstructor()->getMock();
        $stmt->method('fetchColumn')->willReturn(100501);

        $users = [];
        $values = [];

        for ($i = 1; $i <= 500; $i++) {
            $users[] = [$i, ['k' => 'v', 'k1' => 'v\'1']];
            $values[] = "({$offerId}, {$i}, now(), 0, 'k=v\nk1=v\'1')";
        }
        $values2 = [];

        for ($i = 501; $i <= 999; $i++) {
            $users[] = [$i, ['k' => 'v', 'k1' => 'v\'1']];
            $values2[] = "({$offerId}, {$i}, now(), 0, 'k=v\nk1=v\'1')";
        }
        $values = implode(', ', $values);
        $values2 = implode(', ', $values2);

        $connection->method('executeQuery')->withConsecutive(
            ["select LastUserID from Offer where OfferID = ?", [$offerId], [], null],
            ["insert into OfferUser (OfferID, UserID, CreationDate, Manual, Params) values {$values} on duplicate key update CreationDate = now(), Params = VALUES(Params)", [], [], null],
            ["insert into OfferUser (OfferID, UserID, CreationDate, Manual, Params) values {$values2} on duplicate key update CreationDate = now(), Params = VALUES(Params)", [], [], null]
        )->will($this->onConsecutiveCalls($stmt, null, null));

        $registry = $this->getRegistryMock();
        $registry->method('getConnection')->willReturn($connection);
        $plugin = new TestOfferPlugin($offerId, $registry);

        $ref = new \ReflectionMethod(TestOfferPlugin::class, 'addUsers');
        $ref->setAccessible(true);
        $ref->invoke($plugin, $users);
        $ref->setAccessible(false);
    }

    protected function getRegistryMock()
    {
        return $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConnectionMock()
    {
        return $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
    }
}

class TestOfferPlugin extends \OfferPlugin
{
    protected function searchUsers()
    {
    }
}
