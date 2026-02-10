<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\Tests\Unit\BaseUserTest;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Prophecy\Argument;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertMatchesRegularExpression;

class BaseNotificationListenerTest extends BaseUserTest
{
    protected function createAccountWithSegments(array $tripSegments)
    {
        $firstTime = true;

        return $this->createAndCheckAccountWithItineraries(
            [[
                "RecordLocator" => "ABCDEF",
                "TripSegments" => $tripSegments,
            ]],
            [
                'ShortName' => 'TestItineraryAirline',
                'IATACode' => 'TI',
            ],
            function (array $itineraries) use (&$firstTime) {
                foreach ($itineraries[0]['TripSegments'] as &$tripSegment) {
                    foreach ($tripSegment as $property => $value) {
                        if (is_array($value)) {
                            $tripSegment[$property] = $firstTime ? $value[0] : $value[1];
                        }
                    }
                }

                $firstTime = false;

                return $itineraries;
            }
        );
    }

    protected function createAccountWithItineraries(
        array $itineraries,
        array $providerData = [
            'ShortName' => 'American Airlines',
            'IATACode' => 'AA',
        ],
        ?\Closure $parseItinerariesTransformer = null
    ) {
        $providerId = $this->aw->createAwProvider(
            $code = 'tnl' . StringHandler::getRandomCode(15),
            $code,
            $providerData,
            [
                'ParseItineraries' => function () use ($itineraries, $parseItinerariesTransformer) {
                    array_walk_recursive($itineraries, function (&$value, $key) {
                        if (is_object($value)) {
                            if ($value instanceof \Closure) {
                                $value = $value();
                            }

                            if ($value instanceof \DateTimeInterface) {
                                $value = $value->getTimestamp();
                            }
                        }
                    });

                    if ($parseItinerariesTransformer) {
                        $itineraries = $parseItinerariesTransformer($itineraries);
                    }

                    return $itineraries;
                },
            ]
        );

        return $this->aw->createAwAccount($this->user->getUserid(), $providerId, "login");
    }

    protected function createAndCheckAccountWithItineraries(
        array $itineraries,
        array $providerData = [
            'ShortName' => 'TestItineraryAirline',
            'IATACode' => 'TI',
        ],
        ?\Closure $parseItinerariesTransformer = null
    ) {
        $accountId = $this->createAccountWithItineraries($itineraries, $providerData, $parseItinerariesTransformer);
        $notifications = [];
        $this->captureNotificationsOnAccountCheck($accountId, $notifications);

        return [$accountId, &$notifications];
    }

    protected function assertTripNotificationOnAction($regexp, $parseItineraries, $action = 'add')
    {
        $this->getDevice('4.0.0');
        $accountData = $this->createAccountWithSegments([
            array_merge(
                [
                    "Status" => "Confirmed",
                    "FlightNumber" => "10175",
                    "DepName" => "JFK",
                    "DepCode" => "JFK",
                    "ArrName" => "LAX",
                    "ArrCode" => "LAX",
                ],
                $parseItineraries
            ),
        ]);
        $accountId = $accountData[0];
        $notifications = &$accountData[1];

        if ('update' === $action) {
            $notifications = [];
            $this->aw->checkAccount($accountId, true);
        }

        $this->assertOneNotificationWithText($notifications, $regexp);
    }

    protected function getDevice($version = '1.1.1')
    {
        $manager = $this->container->get('aw.manager.mobile_device_manager');

        return $manager->addDevice($this->user->getUserid(), 'android', 'keykey', 'en', $version, '127.0.0.1');
    }

    protected function assertTripNotificationOnAdd($regexp, $parseItineraries)
    {
        $this->assertTripNotificationOnAction($regexp, $parseItineraries);
    }

    protected function assertTripNotificationOnUpdate($regexp, $parseItineraries)
    {
        $this->assertTripNotificationOnAction($regexp, $parseItineraries, 'update');
    }

    protected function assertOneNotificationWithText($notifications, ?string $messageRegexp = null, ?string $titleRegexp = null)
    {
        assertCount(1, $notifications, 'one notification expected');
        /** @var Notification $notification */
        $notification = @unserialize($notifications[0]);
        assertInstanceOf(Notification::class, $notification, 'invalid notification');

        if ($messageRegexp) {
            assertMatchesRegularExpression($messageRegexp, $notification->getMessage(), 'invalid notification message');
        }

        if ($titleRegexp) {
            assertMatchesRegularExpression($titleRegexp, $notification->getPayload()['title'] ?? '', 'invalid notification title');
        }
    }

    protected function captureNotificationsOnAccountCheck($accountId, array &$notifications = [])
    {
        return $this->captureNotificationsOn(function () use ($accountId) {
            $this->aw->checkAccount($accountId, true);
        }, $notifications);
    }

    protected function captureNotificationsOn(\Closure $callback, array &$notifications = [])
    {
        $producer = $this->prophesize(ProducerInterface::class);
        $producer->publish(
            Argument::that(function ($arg) use (&$notifications) {
                $notifications[] = $arg;

                return true;
            }),
            Argument::cetera()
        )->willReturn(null);

        $this->container->set('old_sound_rabbit_mq.push_notification_producer', $producer->reveal());
        $callback();

        return $notifications;
    }
}
