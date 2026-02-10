<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Worker\PushNotification\DeviceWorker;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\DeviceAction;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use Doctrine\DBAL\Connection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @group push
 * @group mobile
 * @group frontend-unit
 */
class DeviceWorkerTest extends BaseWorkerTest
{
    public function testOutdatedClient()
    {
        $deviceAction = $this->getDeviceActionWithVersion($newVersion = DeviceAction::VERSION + 1);
        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('error')->with(
            $this->equalTo($errorMessage = sprintf('version mismatch: class "%s" :: "%s" != "%s"', 'AwardWallet\\MainBundle\\Worker\\PushNotification\\DTO\\DeviceAction', DeviceAction::VERSION, $newVersion)),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $worker = new DeviceWorker($this->getConnectionMock(), $this->getMobileDeviceManagerMock(), $logger);

        $this->expectException('\\AwardWallet\\MainBundle\\Worker\\PushNotification\\DTO\\OutdatedClientException');
        $this->expectExceptionMessage($errorMessage);
        $worker->execute(new AMQPMessage(serialize($deviceAction)));
    }

    public function testOutdatedMessage()
    {
        $deviceAction = $this->getDeviceActionWithVersion($newVersion = DeviceAction::VERSION - 1);
        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('error')->with(
            $this->equalTo($errorMessage = sprintf('version mismatch: class "%s" :: "%s" != "%s"', 'AwardWallet\\MainBundle\\Worker\\PushNotification\\DTO\\DeviceAction', DeviceAction::VERSION, $newVersion)),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $worker = new DeviceWorker($this->getConnectionMock(), $this->getMobileDeviceManagerMock(), $logger);
        $this->assertTrue($worker->execute(new AMQPMessage(serialize($deviceAction))));
    }

    public function testGarbageMessage()
    {
        $logger = $this->getLoggerInterfaceMock();
        $serialized = serialize(new \stdClass());
        $logger->expects($this->once())->method('error')->with(
            $this->equalTo(sprintf('device worker: invalid message, body "%s"', $serialized)),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $worker = new DeviceWorker($this->getConnectionMock(), $this->getMobileDeviceManagerMock(), $logger);
        $this->assertTrue($worker->execute(new AMQPMessage($serialized)));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    public function getConnectionMock()
    {
        return $this->getMockBuilder('\\Doctrine\\DBAL\\Connection')->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MobileDeviceManager
     */
    public function getMobileDeviceManagerMock()
    {
        return $this->getMockBuilder('\\AwardWallet\\MainBundle\\Manager\\MobileDeviceManager')->disableOriginalConstructor()->getMock();
    }

    /**
     * @return DeviceAction
     */
    protected function getDeviceActionWithVersion($version)
    {
        $notification = new Notification('message', [], (new MobileDevice())->setUser(new Usr()));
        $deviceAction = new DeviceAction($notification, DeviceAction::REMOVE);
        $reflectionProperty = new \ReflectionProperty('\\AwardWallet\\MainBundle\\Worker\\PushNotification\\DTO\\DeviceAction', 'version');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($deviceAction, $version);
        $reflectionProperty->setAccessible(false);

        return $deviceAction;
    }
}
