<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\DeviceAction;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use AwardWallet\MainBundle\Worker\PushNotification\LogHelper;
use AwardWallet\MainBundle\Worker\PushNotification\Platform\AppleResponse;
use AwardWallet\MainBundle\Worker\PushNotification\Platform\MacHandler;
use AwardWallet\MainBundle\Worker\PushNotification\Platform\SafariWebPushMessage;

/**
 * @group push
 * @group mobile
 * @group frontend-unit
 */
class MacHandlerTest extends BasePlatformHandlerTest
{
    /**
     * @covers \AwardWallet\MainBundle\Worker\PushNotification\Platform\MacHandler::prepareMessage
     */
    public function testPrepareMessage()
    {
        $handler = new MacHandler($this->getNotificationHelperMock(), new LogHelper('test'), $this->getLoggerInterfaceMock());

        $this->assertEquals(-1, $handler->getCounter()->lastMessageId);
        $this->assertEquals(-1, $handler->getCounter()->lastErrorId);

        $notification = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_SAFARI, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['body' => ['pay' => 1, 'load' => 'load'], 'url' => '/account/list'], '', (new Options())->setDeadlineTimestamp($expiration = time() + 60 * 30));

        /** @var SafariWebPushMessage $prepared */
        $prepared = $handler->prepareMessage($notification);

        $this->assertEquals($deviceKey, $prepared->getDeviceIdentifier());
        $this->assertEquals(['aps' => [
            'alert' => [
                'title' => 'message',
                'body' => [
                    'pay' => 1,
                    'load' => 'load',
                ],
            ],
            'url-args' => ['account/list'],
        ],
        ],
            $prepared->getMessageBody()
        );

        $this->assertEqualsWithDelta($expiration, $prepared->getExpiry(), 5, '');
        $this->assertEquals(0, $handler->getCounter()->lastMessageId);
        $this->assertEquals(-1, $handler->getCounter()->lastErrorId);
    }

    /**
     * @covers \AwardWallet\MainBundle\Worker\PushNotification\Platform\MacHandler::checkResponses
     */
    public function testSuccessCheckResponses()
    {
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_SAFARI, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);

        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('info')->with(
            'safari push successfully sent',
            $this->arrayArgumentWithSubset(['_aw_push_success' => 1, '_aw_push_ios_success_seqid' => 3])
        );

        $handler = new MacHandler($this->getNotificationHelperMock(), new LogHelper('test'), $logger);
        $responses = [];

        foreach (range(1, 3) as $i) {
            $handler->prepareMessage($notificationDTO); // increase lastMessageId counter
            /**
             * emulate success (APNS should return nothing).
             *
             * @see \RMS\PushNotificationsBundle\Service\OS\AppleNotification::sendMessages
             */
            $responses[] = true;
        }

        $handler->checkResponses($notificationDTO, $responses);
    }

    public function testFailSTATUSSHUTDOWN()
    {
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_SAFARI, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);
        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('error')->with(
            $this->equalTo($text = 'Received shutdown status code from apple. Throwing exception to restart worker'),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $this->expectException(get_class(new \RuntimeException()));
        $this->expectExceptionMessage($text);

        $handler = new MacHandler($this->getNotificationHelperMock(), new LogHelper('test'), $logger);
        /** @var AppleResponse $response */
        $response = [];
        $response['status'] = MacHandler::STATUS_SHUTDOWN;
        $response['command'] = MacHandler::COMMAND;
        $response['identifier'] = 1;
        $handler->prepareMessage($notificationDTO); // increase lastMessageId counter
        $handler->prepareMessage($notificationDTO); // increase lastMessageId counter

        $handler->checkResponses($notificationDTO, [true, $response]);
        $this->assertEquals(1, $handler->getCounter()->lastMessageId);
        $this->assertEquals(1, $handler->getCounter()->lastErrorId);
    }

    public function testFailSTATUSINVALIDPAYLOADSIZE()
    {
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_SAFARI, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);
        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('error')->with(
            $text = sprintf("Invalid payload size. Message: \"%s\",\nPayload: %s", $notificationDTO->getMessage(), json_encode($notificationDTO->getPayload())),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $handler = new MacHandler($this->getNotificationHelperMock(), new LogHelper('test'), $logger);
        /** @var AppleResponse $response */
        $response = [];
        $response['status'] = MacHandler::STATUS_INVALID_PAYLOAD_SIZE;
        $response['command'] = MacHandler::COMMAND;
        $response['identifier'] = 0;
        $handler->prepareMessage($notificationDTO); // increase lastMessageId counter

        $handler->checkResponses($notificationDTO, [$response]);
        $this->assertEquals(0, $handler->getCounter()->lastMessageId);
        $this->assertEquals(0, $handler->getCounter()->lastErrorId);
    }

    public function testFailSTATUSINVALIDTOKEN()
    {
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_SAFARI, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);
        $notificationHelper = $this->getNotificationHelperMock();
        $notificationHelper->expects($this->once())->method('deviceAction')->with(
            $this->equalTo($notificationDTO),
            $this->equalTo(DeviceAction::REMOVE)
        );

        $handler = new MacHandler($notificationHelper, new LogHelper('test'), $this->getLoggerInterfaceMock());
        /** @var AppleResponse $response */
        $response = [];
        $response['status'] = MacHandler::STATUS_INVALID_TOKEN;
        $response['command'] = MacHandler::COMMAND;
        $response['identifier'] = 0;
        $handler->prepareMessage($notificationDTO); // increase lastMessageId counter

        $handler->checkResponses($notificationDTO, [$response]);
    }

    public function testFailUnknownStatus()
    {
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_SAFARI, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);
        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('error')->with(
            $text = sprintf('Unhandled ios status response: "%s"', 100),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $notificationHelper = $this->getNotificationHelperMock();
        $notificationHelper->expects($this->once())->method('retry')->with($notificationDTO);

        $handler = new MacHandler($notificationHelper, new LogHelper('test'), $logger);
        /** @var AppleResponse $response */
        $response = [];
        $response['status'] = 100;
        $response['command'] = MacHandler::COMMAND;
        $response['identifier'] = 0;
        $handler->prepareMessage($notificationDTO); // increase lastMessageId counter

        $handler->checkResponses($notificationDTO, [$response]);
    }
}
