<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\OutdatedClientException;
use AwardWallet\MainBundle\Worker\PushNotification\NotificationWorker;
use AwardWallet\MainBundle\Worker\PushNotification\Platform\PlatformHandlerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Prophecy\Argument;
use RMS\PushNotificationsBundle\Message\MessageInterface;
use RMS\PushNotificationsBundle\Service\Notifications;

/**
 * @group mobile
 * @group push
 * @group frontend-unit
 */
class NotificationWorkerTest extends BaseWorkerTest
{
    private const TEST_MOBILE_DEVICE_ID = 1234567890;

    public function testOutdatedClient()
    {
        $notification = $this->getNotificationWithVersion($newVersion = Notification::VERSION + 1);
        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('error')->with(
            $this->equalTo($errorMessage = sprintf('version mismatch: class "%s" :: "%s" != "%s"', 'AwardWallet\\MainBundle\\Worker\\PushNotification\\DTO\\Notification', Notification::VERSION, $newVersion)),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $deviceManager = $this->prophesize(MobileDeviceManager::class);
        $deviceManager->deviceExists(Argument::cetera())->shouldNotBeCalled()->willReturn(true);
        $worker = new NotificationWorker($this->getNotificationsMock(), $this->getProducerInterfaceMock(), $this->getDelayedProducerMock(), $logger, $deviceManager->reveal());
        $this->expectException(get_class(new OutdatedClientException()));
        $this->expectExceptionMessage($errorMessage);
        $worker->execute(new AMQPMessage(serialize($notification)));
    }

    public function testOutdatedMessage()
    {
        $notification = $this->getNotificationWithVersion($newVersion = Notification::VERSION - 1);
        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('error')->with(
            $this->equalTo(sprintf('version mismatch: class "%s" :: "%s" != "%s"', 'AwardWallet\\MainBundle\\Worker\\PushNotification\\DTO\\Notification', Notification::VERSION, $newVersion)),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $deviceManager = $this->prophesize(MobileDeviceManager::class);
        $deviceManager->deviceExists(self::TEST_MOBILE_DEVICE_ID)->shouldNotBeCalled()->willReturn(true);
        $worker = new NotificationWorker($this->getNotificationsMock(), $this->getProducerInterfaceMock(), $this->getDelayedProducerMock(), $logger, $deviceManager->reveal());
        $this->assertTrue($worker->execute(new AMQPMessage(serialize($notification))));
    }

    public function testGarbageMessage()
    {
        $logger = $this->getLoggerInterfaceMock();
        $serialized = serialize(new \stdClass());
        $logger->expects($this->once())->method('error')->with(
            $this->equalTo(sprintf('Unserialization failed, data: "%s"', $serialized)),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $deviceManager = $this->prophesize(MobileDeviceManager::class);
        $deviceManager->deviceExists(Argument::cetera())->shouldNotBeCalled()->willReturn(true);
        $worker = new NotificationWorker($this->getNotificationsMock(), $this->getProducerInterfaceMock(), $this->getDelayedProducerMock(), $logger, $deviceManager->reveal());
        $this->assertTrue($worker->execute(new AMQPMessage($serialized)));
    }

    public function testUndefinedPlatform()
    {
        $logger = $this->getLoggerInterfaceMock();
        $notification = new Notification('message', [], $this->getMobileDevice()->setUser(new Usr())->setDeviceType(99));
        $logger->expects($this->once())->method('error')->with(
            $this->equalTo($text = sprintf('undefined platformId: %d', 99)),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $this->expectException(get_class(new \RuntimeException()));
        $this->expectExceptionMessage($text);
        $deviceManager = $this->prophesize(MobileDeviceManager::class);
        $deviceManager->deviceExists(Argument::cetera())->shouldBeCalled()->willReturn(true);
        $worker = new NotificationWorker($this->getNotificationsMock(), $this->getProducerInterfaceMock(), $this->getDelayedProducerMock(), $logger, $deviceManager->reveal());
        $this->assertFalse($worker->execute(new AMQPMessage(serialize($notification))));
    }

    public function testDeviceDoesNotExists()
    {
        $logger = $this->getLoggerInterfaceMock();
        $notification = new Notification('message', [], $this->getMobileDevice()->setUser(new Usr())->setDeviceType(99));
        $logger->expects($this->once())->method('info')->with(
            $this->equalTo("device not found"),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $deviceManager = $this->prophesize(MobileDeviceManager::class);
        $deviceManager->deviceExists(self::TEST_MOBILE_DEVICE_ID)->shouldBeCalled()->willReturn(false);
        $sender = $this->prophesize(Notifications::class);
        $sender->send(Argument::cetera())->shouldNotBeCalled();
        $handler = $this->prophesize(PlatformHandlerInterface::class);
        $handler
            ->prepareMessage($notification)
            ->shouldNotBeCalled()
            ->willReturn($this->prophesize(MessageInterface::class)->reveal());
        $handler
            ->checkResponses($notification, Argument::cetera())
            ->shouldNotBeCalled();
        $worker = new NotificationWorker($sender->reveal(), $this->getProducerInterfaceMock(), $this->getDelayedProducerMock(), $logger, $deviceManager->reveal());
        $worker->addHandler(99, $handler->reveal());
        $this->assertTrue($worker->execute(new AMQPMessage(serialize($notification))));
    }

    public function testValidSend()
    {
        $logger = $this->getLoggerInterfaceMock();
        $notification = new Notification('message', [], $this->getMobileDevice()->setUser(new Usr())->setDeviceType(99));
        $deviceManager = $this->prophesize(MobileDeviceManager::class);
        $deviceManager->deviceExists(self::TEST_MOBILE_DEVICE_ID)->shouldBeCalled()->willReturn(true);
        $message = $this->prophesize(MessageInterface::class);
        $message->getTargetOS()->willReturn('ios')->shouldBeCalled();
        $handler = $this->prophesize(PlatformHandlerInterface::class);
        $handler
            ->prepareMessage($notification)
            ->shouldBeCalled()
            ->willReturn($message->reveal());
        $handler
            ->checkResponses($notification, [true])
            ->shouldBeCalled();
        $sender = $this->prophesize(Notifications::class);
        $sender->getResponses('ios')->willReturn([true])->shouldBeCalled();
        $sender->send($message)->willReturn(true)->shouldBeCalled();
        $worker = new NotificationWorker($sender->reveal(), $this->getProducerInterfaceMock(), $this->getDelayedProducerMock(), $logger, $deviceManager->reveal());
        $worker->addHandler(99, $handler->reveal());
        $this->assertTrue($worker->execute(new AMQPMessage(serialize($notification))));
    }

    protected function getMobileDevice(): MobileDevice
    {
        $device = new MobileDevice();
        $idReflectionProperty = new \ReflectionProperty(MobileDevice::class, 'mobileDeviceId');
        $idReflectionProperty->setAccessible(true);
        $idReflectionProperty->setValue($device, self::TEST_MOBILE_DEVICE_ID);
        $idReflectionProperty->setAccessible(false);

        return $device;
    }

    protected function getNotificationWithVersion($version): Notification
    {
        $device = $this->getMobileDevice();
        $notification = new Notification('message', [], $device->setUser(new Usr()));
        $versionReflectionProperty = new \ReflectionProperty(Notification::class, 'version');
        $versionReflectionProperty->setAccessible(true);
        $versionReflectionProperty->setValue($notification, $version);
        $versionReflectionProperty->setAccessible(false);

        return $notification;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Notifications
     */
    protected function getNotificationsMock()
    {
        return $this->createMock(Notifications::class);
    }
}
