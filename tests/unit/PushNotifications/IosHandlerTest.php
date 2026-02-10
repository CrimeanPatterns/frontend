<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use Apns\Exception\ApnsException;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\DeviceAction;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use AwardWallet\MainBundle\Worker\PushNotification\LogHelper;
use AwardWallet\MainBundle\Worker\PushNotification\NotificationHelper;
use AwardWallet\MainBundle\Worker\PushNotification\Platform\AppleResponse;
use AwardWallet\MainBundle\Worker\PushNotification\Platform\IosHandler;
use AwardWallet\MainBundle\Worker\PushNotification\Platform\iOSMessage;
use AwardWallet\MainBundle\Worker\PushNotification\Platform\IosSender;
use Prophecy\Argument;

/**
 * @group push
 * @group mobile
 * @group frontend-unit
 */
class IosHandlerTest extends BasePlatformHandlerTest
{
    /**
     * @covers \AwardWallet\MainBundle\Worker\PushNotification\Platform\IosHandler::prepareMessage
     */
    public function testPrepareMessage()
    {
        $handler = new IosHandler($this->getNotificationHelperMock(), new LogHelper('test'), $this->getLoggerInterfaceMock(), $this->getAPNSClientMock());
        $notification = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_IOS, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load'], '', (new Options())->setDeadlineTimestamp($expiration = time() + 60 * 30));

        /** @var iOSMessage $prepared */
        $prepared = $handler->prepareMessage($notification);

        $apnsMessage = $prepared->getInnerMessage();
        $this->assertEquals($deviceKey, $apnsMessage->getDeviceIdentifier());
        $this->assertEquals([
            'aps' => [
                'alert' => $message,
                'sound' => 'default',
            ],
            'load' => 'load',
            'pay' => 1,
        ],
            $prepared->getInnerMessage()->jsonSerialize()
        );
        $this->assertEqualsWithDelta($expiration, $apnsMessage->getExpiry(), 5, '');
    }

    /**
     * @covers \AwardWallet\MainBundle\Worker\PushNotification\Platform\IosHandler::checkResponses
     */
    public function testSuccessCheckResponses()
    {
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_IOS, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);

        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('info')->with(
            'ios push successfully sent',
            $this->arrayArgumentWithSubset(['_aw_push_success' => 1])
        );

        $handler = new IosHandler($this->getNotificationHelperMock(), new LogHelper('test'), $logger, $this->getAPNSClientMock());
        $responses = [true];
        $handler->checkResponses($notificationDTO, $responses);
    }

    public function testFailSTATUSSHUTDOWN()
    {
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_IOS, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);
        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('error')->with(
            $this->equalTo($text = 'Received shutdown response from apple.'),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );
        $apnsClient = $this->prophesize(IosSender::class)
            ->reconnect()->shouldBeCalledTimes(1)
            ->getObjectProphecy()->reveal();

        $notificationHelper = $this->prophesize(NotificationHelper::class)
            ->retry(Argument::type(Notification::class))->shouldBeCalledTimes(1)
            ->getObjectProphecy()->reveal();

        $handler = new IosHandler($notificationHelper, new LogHelper('test'), $logger, $apnsClient);
        $response = new ApnsException(IosHandler::REASON_SHUTDOWN, IosHandler::CODE_SHUTDOWN);
        $handler->prepareMessage($notificationDTO); // increase lastMessageId counter
        $handler->prepareMessage($notificationDTO); // increase lastMessageId counter

        $handler->checkResponses($notificationDTO, [$response]);
    }

    public function testFailSTATUSINVALIDPAYLOADSIZE()
    {
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_IOS, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);
        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('error')->with(
            $text = 'Invalid payload.',
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $handler = new IosHandler($this->getNotificationHelperMock(), new LogHelper('test'), $logger, $this->getAPNSClientMock());
        /** @var AppleResponse $response */
        $response = new ApnsException(IosHandler::REASON_PAYLOAD_TOO_LARGE, IosHandler::CODE_PAYLOAD_TOO_LARGE);
        $handler->prepareMessage($notificationDTO); // increase lastMessageId counter

        $handler->checkResponses($notificationDTO, [$response]);
    }

    public function testFailSTATUSINVALIDTOKEN()
    {
        $this->markTestSkipped("Dry run removal");
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_IOS, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);
        $notificationHelper = $this->getNotificationHelperMock();
        $notificationHelper->expects($this->once())->method('deviceAction')->with(
            $this->equalTo($notificationDTO),
            $this->equalTo(DeviceAction::REMOVE)
        );

        $handler = new IosHandler($notificationHelper, new LogHelper('test'), $this->getLoggerInterfaceMock(), $this->getAPNSClientMock());
        /** @var AppleResponse $response */
        $response = new ApnsException(IosHandler::REASON_UNREGISTERED, IosHandler::CODE_UNREGISTERED);
        $handler->prepareMessage($notificationDTO); // increase lastMessageId counter

        $handler->checkResponses($notificationDTO, [$response]);
    }

    public function testFailUnknownStatus()
    {
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_IOS, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);
        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('error')->with(
            $text = sprintf('Unhandled ios error, code: %s, reason: %s', 100500, "someReason"),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $notificationHelper = $this->getNotificationHelperMock();
        $notificationHelper->expects($this->once())->method('retry')->with($notificationDTO);

        $handler = new IosHandler($notificationHelper, new LogHelper('test'), $logger, $this->getAPNSClientMock());
        /** @var AppleResponse $response */
        $response = new ApnsException('someReason', 100500);
        $handler->prepareMessage($notificationDTO); // increase lastMessageId counter
        $handler->checkResponses($notificationDTO, [$response]);
    }

    protected function getAPNSClientMock(): IosSender
    {
        return $this->prophesize(IosSender::class)->reveal();
    }
}
