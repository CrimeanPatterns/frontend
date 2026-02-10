<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Worker\PushNotification\DTO\DeviceAction;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\MainBundle\Worker\PushNotification\LogHelper;
use AwardWallet\MainBundle\Worker\PushNotification\NotificationHelper;
use PHPUnit\Framework\Constraint\Callback;

/**
 * Class NotificationHelperTest.
 *
 * @group mobile
 * @group push
 * @group frontend-unit
 */
class NotificationHelperTest extends BasePushNotificationsTest
{
    public function testRetryOneTime()
    {
        $notification = $this->getSimpleIosNotificationDTO(1, '1', '1', [], $routingKey = 'key');
        $notification->getOptions()->setPriority(11);

        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('warning')->with(
            'Retrying',
            $this->arrayArgumentWithSubset(['_aw_push_retries_exp' => 1])
        );

        $delayedProducer = $this->getDelayedProducerMock();
        $delayedProducer->expects($this->once())->method('publish')->with(
            $this->anything(),
            $this->equalTo($routingKey),
            $this->logicalAnd(
                $this->validateDelay(2000),
                $this->callback(fn (array $options) => 11 === ($options['priority'] ?? null))
            )
        );

        $helper = new NotificationHelper($this->getProducerInterfaceMock(), $delayedProducer, $logger, new LogHelper('test'));

        $this->assertEquals(0, $notification->getRetries());
        $helper->retry($notification);
        $this->assertEquals(1, $notification->getRetries());
    }

    public function testRetryExceeded()
    {
        $notification = $this->getSimpleIosNotificationDTO(1, '1', '1', [], $routingKey = 'key');
        $notification->addRetries(17);

        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('critical')->with(
            sprintf('Retry limit exceeded, message: "%s"', $notification->getMessage()),
            $this->arrayArgumentWithSubset(['_aw_push_fail' => 1])
        );

        $delayedProducer = $this->getDelayedProducerMock();
        $delayedProducer->expects($this->never())->method('publish');

        $helper = new NotificationHelper($this->getProducerInterfaceMock(), $delayedProducer, $logger, new LogHelper('test'));
        $helper->retry($notification);
    }

    /**
     * @dataProvider retryAfterProvider
     */
    public function testRetryAfter($after, $retries, $delayedTiming, ?Notification $notification = null)
    {
        if (null === $notification) {
            $notification = $this->getSimpleIosNotificationDTO(1, '1', '1', [], 'key');
        }

        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('warning')->with(
            'Retrying',
            $this->arrayArgumentWithSubset(['_aw_push_retries_exp' => $retries])
        );

        $delayedProducer = $this->getDelayedProducerMock();
        $delayedProducer->expects($this->once())->method('publish')->with(
            $this->anything(),
            $this->equalTo('key'),
            $this->validateDelay($delayedTiming)
        );

        $helper = new NotificationHelper($this->getProducerInterfaceMock(), $delayedProducer, $logger, new LogHelper('test'));
        $helper->retryAfter($after, $notification);
        $this->assertEquals($retries, $notification->getRetries());
    }

    public function retryAfterProvider()
    {
        return [
            [10, 4, 16000],
            [5, 3, 8000],
            [4, 2, 4000],
            [1, 1, 2000],
            [30, 5, 32000],
        ];
    }

    /**
     * @dataProvider retryAfterWithExpBackoffDataProvider
     */
    public function testRetryAfterWithExpBackoff($after, $retries, $delayedTiming)
    {
        $notification = $this->getSimpleIosNotificationDTO(1, '1', '1', [], 'key');
        $notification->addRetries(4);
        $this->testRetryAfter($after, $retries, $delayedTiming, $notification);
    }

    public function retryAfterWithExpBackoffDataProvider()
    {
        return [
            [15, 5, 32000],
            [16, 5, 32000],
            [17, 6, 64000],
            [48, 6, 64000],
            [49, 7, 128000],
        ];
    }

    /**
     * @dataProvider retryAfterMalformedDataProvider
     */
    public function testRetryAfterMalformed($after)
    {
        $notification = $this->getSimpleIosNotificationDTO(1, '1', '1', [], 'key');
        $notification->addRetries(4);

        $logger = $this->getLoggerInterfaceMock();

        $logger->expects($this->once())->method('error')->with(
            sprintf('Malformed delay value: %d, reset to 2', $after),
            $this->anything()
        );
        $logger->expects($this->once())->method('warning')->with(
            'Retrying',
            $this->arrayArgumentWithSubset(['_aw_push_retries_exp' => 6])
        );

        $delayedProducer = $this->getDelayedProducerMock();
        $delayedProducer->expects($this->once())->method('publish')->with(
            $this->anything(),
            $this->equalTo('key'),
            $this->validateDelay(64000)
        );

        $helper = new NotificationHelper($this->getProducerInterfaceMock(), $delayedProducer, $logger, new LogHelper('test'));
        $helper->retryAfter($after, $notification);
    }

    public function retryAfterMalformedDataProvider()
    {
        return [
            [100500],
            [-100500],
            [NAN],
            [INF],
            [-INF],
        ];
    }

    public function testDeviceAction()
    {
        $notification = $this->getSimpleIosNotificationDTO(1, '1', '1', [], $routingKey = 'key');

        $logger = $this->getLoggerInterfaceMock();
        $logger->expects($this->once())->method('info')->with(
            sprintf('Send device action "%s", deviceKey: %s', 'renew', $notification->getDeviceKey()),
            $this->arrayArgumentWithSubset(['_aw_push_device_action' => 'renew'])
        );

        $producer = $this->getProducerInterfaceMock();
        $producer->expects($this->once())->method('publish')->with(
            @serialize(new DeviceAction($notification, 1, null))
        );

        $helper = new NotificationHelper($producer, $this->getDelayedProducerMock(), $logger, new LogHelper('test'));
        $helper->deviceAction($notification, 1);
    }

    protected function validateDelay(int $delay): Callback
    {
        return $this->callback(function ($options) use ($delay) {
            $delayData = $options['application_headers']['x-delay'] ?? [];

            return
                (($delayData[0] ?? null) === 'I')
                && \is_int($delayData[1] ?? null)
                && (($delayData[1] - $delay) >= 0)
                && ($delayData[1] - $delay) <= ($delay / 8);
        });
    }
}
