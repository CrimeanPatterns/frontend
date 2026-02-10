<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use AwardWallet\Tests\Unit\BaseTest;
use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

abstract class BasePushNotificationsTest extends BaseTest
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function getLoggerInterfaceMock()
    {
        return $this->createMock(Logger::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    protected function getProducerInterfaceMock()
    {
        return $this->createMock('\\OldSound\\RabbitMqBundle\\RabbitMq\\ProducerInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    protected function getDelayedProducerMock()
    {
        return $this->getProducerInterfaceMock();
    }

    /**
     * @param int $type
     * @param string $key
     * @param string $message
     * @return Notification
     */
    protected function getSimpleIosNotificationDTO($type = MobileDevice::TYPE_ANDROID, $key = 'deviceKey1234ABC', $message = 'message', array $payload = ['pay' => 1, 'load' => 'load'], $routingKey = '', ?Options $options = null)
    {
        $device = (new MobileDevice())
            ->setDeviceType($type)
            ->setDeviceKey($key)
            ->setAppVersion('3.26.1')
            ->setUser(new Usr());

        return new Notification($message, $payload, $device, $routingKey, '', $options);
    }

    protected function getApiVersioningMock(bool $versionSupportsReturn = false): ApiVersioningService
    {
        $mock = $this->prophesize(ApiVersioningService::class);
        $mock->versionSupports(Argument::cetera())->willReturn($versionSupportsReturn);

        return $mock->reveal();
    }
}
