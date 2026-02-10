<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\DeviceAction;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use AwardWallet\MainBundle\Worker\PushNotification\LogHelper;
use AwardWallet\MainBundle\Worker\PushNotification\Platform\AndroidHandler;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use RMS\PushNotificationsBundle\Message\AndroidMessage;

/**
 * @group push
 * @group mobile
 * @group frontend-unit
 */
class AndroidHandlerTest extends BasePlatformHandlerTest
{
    public const GCM_SERVER_ERROR_RESPONSE = <<<GCM
<!DOCTYPE html>
<html lang=en>
  <meta charset=utf-8>
  <meta name=viewport content="initial-scale=1, minimum-scale=1, width=device-width">
  <title>Error 502 (Server Error)!!1</title>
  <style>
    *{margin:0;padding:0}html,code{font:15px/22px arial,sans-serif}html{background:#fff;color:#222;padding:15px}body{margin:7% auto 0;max-width:390px;min-height:180px;padding:30px 0 15px}* > body{background:url(//www.google.com/images/errors/robot.png) 100% 5px no-repeat;padding-right:205px}p{margin:11px 0 22px;overflow:hidden}ins{color:#777;text-decoration:none}a img{border:0}@media screen and (max-width:772px){body{background:none;margin-top:0;max-width:none;padding-right:0}}#logo{background:url(//www.google.com/images/errors/logo_sm_2.png) no-repeat}@media only screen and (min-resolution:192dpi){#logo{background:url(//www.google.com/images/errors/logo_sm_2_hr.png) no-repeat 0% 0%/100% 100%;-moz-border-image:url(//www.google.com/images/errors/logo_sm_2_hr.png) 0}}@media only screen and (-webkit-min-device-pixel-ratio:2){#logo{background:url(//www.google.com/images/errors/logo_sm_2_hr.png) no-repeat;-webkit-background-size:100% 100%}}#logo{display:inline-block;height:55px;width:150px}
  </style>
  <a href=//www.google.com/><span id=logo aria-label=Google></span></a>
  <p><b>502.</b> <ins>That’s an error.</ins>
  <p>The server encountered a temporary error and could not complete your request.<p>Please try again in 30 seconds.  <ins>That’s all we know.</ins>
GCM;

    public function testPrepareMessage()
    {
        $handler = new AndroidHandler($this->getNotificationHelperMock(), new LogHelper('test'), $this->getLoggerInterfaceMock());

        $notification = $this->getSimpleIosNotificationDTO(
            MobileDevice::TYPE_ANDROID,
            $deviceKey = 'deviceKey1234ABC',
            $message = 'message',
            $payload = [
                'pay' => 1,
                'load' => 'load',
                'channel' => 'some',
            ],
            '',
            (new Options())
                ->setDeadlineTimestamp($expiration = time() + 60 * 30)
        );

        /** @var AndroidMessage $prepared */
        $prepared = $handler->prepareMessage($notification);

        $this->assertEquals($deviceKey, $prepared->getDeviceIdentifier());
        $this->assertEquals('AwardWallet', $prepared->getCollapseKey());
        $this->assertTrue($prepared->isGCM());
        $this->assertEquals($message, $prepared->getMessage());
        $this->assertEquals([
            'message' => $message,
            'title' => 'AwardWallet',
            'load' => 'load',
            'pay' => 1,
            'channel' => 'some',
        ],
            $prepared->getData()
        );
        $this->assertEqualsWithDelta(60 * 30, $prepared->getGCMOptions()['ttl'], 10, 'invalid expiration time');
    }

    public function testSuccessResponse()
    {
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_ANDROID, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);

        $notificationHelper = $this->getNotificationHelperMock();

        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('info')->with(
            $this->equalTo('google push successfully sent'),
            $this->arrayArgumentWithSubset([
                '_aw_push_success' => 1,
                '_aw_push_device_key' => $notificationDTO->getDeviceKey(),
                'payload' => $notificationDTO->getPayload(),
                'message' => $notificationDTO->getMessage(),
            ])
        );

        $handler = new AndroidHandler($notificationHelper, new LogHelper('test'), $logger);
        $handler->checkResponses($notificationDTO, [['name' => 'some_message_id']]);
    }

    /**
     * @dataProvider unregisteredDeviceProvider
     */
    public function testUnregisteredDevice($error)
    {
        $response = new NotFound();
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_ANDROID, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);

        $notificationHelper = $this->getNotificationHelperMock();
        $notificationHelper->expects($this->once())->method('deviceAction')->with(
            $notificationDTO,
            DeviceAction::REMOVE
        );

        $handler = new AndroidHandler($notificationHelper, new LogHelper('test'), $this->getLoggerInterfaceMock());
        $handler->checkResponses($notificationDTO, [$response]);
    }

    public function testInactiveDeviceFor270DaysDevice()
    {
        $response = new ServerUnavailable("Client error: `POST https://fcm.googleapis.com/fcm/send/dfsf9s8fs98fh9sd8fhs9d8fh` resulted in a `404 Not Found` response:\nA valid push subscription endpoint should be specified in the URL as such: https://fcm.googleapis.com/wp/d90jsd09js90:APA (truncated...)\n");
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_ANDROID, $deviceKey = 'deviceKey1234ABC', 'message', ['pay' => 1, 'load' => 'load']);

        $notificationHelper = $this->getNotificationHelperMock();
        $notificationHelper->expects($this->once())->method('deviceAction')->with(
            $notificationDTO,
            DeviceAction::REMOVE
        );

        $handler = new AndroidHandler($notificationHelper, new LogHelper('test'), $this->getLoggerInterfaceMock());
        $handler->checkResponses($notificationDTO, [$response]);
    }

    public function unregisteredDeviceProvider()
    {
        return [
            ['InvalidRegistration'],
            ['NotRegistered'],
            ['MismatchSenderId'],
        ];
    }

    public function testErrorUnavailable()
    {
        $response = new ServerUnavailable();
        $response = $response->withRetryAfter(new \DateTimeImmutable("+1 hour"));
        $notificationDTO = $this->getSimpleIosNotificationDTO(MobileDevice::TYPE_ANDROID, $deviceKey = 'deviceKey1234ABC', $message = 'message', $payload = ['pay' => 1, 'load' => 'load']);

        $notificationHelper = $this->getNotificationHelperMock();
        $notificationHelper->expects($this->once())->method('retryAfter')->with(
            3600,
            $notificationDTO
        );

        $handler = new AndroidHandler($notificationHelper, new LogHelper('test'), $this->getLoggerInterfaceMock());
        $handler->checkResponses($notificationDTO, [$response]);
    }
}
