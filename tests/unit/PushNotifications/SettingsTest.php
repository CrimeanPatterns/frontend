<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class SettingsTest extends BaseUserTest
{
    /**
     * @dataProvider personalNotificationsDataProvider
     */
    public function testPeronalNotification($deviceType, $fields, $contentType, $expectingPush)
    {
        $appVersion = $deviceType == MobileDevice::TYPE_CHROME ? 'web' : '2.11.23';
        $this->db->haveInDatabase('MobileDevice', ['DeviceKey' => 'test' . $this->user->getUserid(), 'DeviceType' => $deviceType, 'Lang' => 'en', 'UserID' => $this->user->getUserid(), 'AppVersion' => $appVersion]);
        $this->db->executeQuery("update Usr set {$this->setFields($fields)} where UserID = " . $this->user->getUserid());

        $producer = $this->mockServiceWithBuilder('old_sound_rabbit_mq.push_notification_producer');
        $sender = $this->container->get(Sender::class);

        if ($expectingPush) {
            $producer->expects($this->once())->method('publish');
        } else {
            $producer->expects($this->never())->method('publish');
        }
        $sender->send(new Content('Some Title', 'Some message', $contentType), $sender->loadDevices([$this->user], MobileDevice::TYPES_ALL, $contentType));
    }

    public function personalNotificationsDataProvider()
    {
        return array_merge(
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpExpire', 'WpDisableAll', Content::TYPE_ACCOUNT_EXPIRATION),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpRewardsActivity', 'WpDisableAll', Content::TYPE_REWARDS_ACTIVITY),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpNewPlans', 'WpDisableAll', Content::TYPE_NEW_ITINERARY),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpPlanChanges', 'WpDisableAll', Content::TYPE_CHANGED_ITINERARY),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpCheckins', 'WpDisableAll', Content::TYPE_CHECKIN_REMINDER),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpBookingMessages', 'WpDisableAll', Content::TYPE_BOOKING, true, true),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpProductUpdates', 'WpDisableAll', Content::TYPE_PRODUCT_UPDATES),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpOffers', 'WpDisableAll', Content::TYPE_OFFER),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpNewBlogPosts', 'WpDisableAll', Content::TYPE_BLOG_POST),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpInviteeReg', 'WpDisableAll', Content::TYPE_INVITEE_REG),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpPlanChanges', 'WpDisableAll', Content::TYPE_FLIGHT_DELAY),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpPlanChanges', 'WpDisableAll', Content::TYPE_FLIGHT_TIME_CHANGED),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpPlanChanges', 'WpDisableAll', Content::TYPE_FLIGHT_BAGGAGE_CHANGE),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpCheckins', 'WpDisableAll', Content::TYPE_LEG_ARRIVED),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpCheckins', 'WpDisableAll', Content::TYPE_HOTEL_PHONE),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpPlanChanges', 'WpDisableAll', Content::TYPE_FLIGHT_CANCELLATION),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpPlanChanges', 'WpDisableAll', Content::TYPE_FLIGHT_REINSTATED),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpCheckins', 'WpDisableAll', Content::TYPE_CONNECTION_INFO),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpPlanChanges', 'WpDisableAll', Content::TYPE_CONNECTION_INFO_GATE_CHANGE),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpCheckins', 'WpDisableAll', Content::TYPE_FLIGHT_DEPARTURE),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpCheckins', 'WpDisableAll', Content::TYPE_FLIGHT_BOARDING),
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpCheckins', 'WpDisableAll', Content::TYPE_PRECHECKIN_REMINDER),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpExpire', 'MpDisableAll', Content::TYPE_ACCOUNT_EXPIRATION),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpRewardsActivity', 'MpDisableAll', Content::TYPE_REWARDS_ACTIVITY),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpNewPlans', 'MpDisableAll', Content::TYPE_NEW_ITINERARY),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpPlanChanges', 'MpDisableAll', Content::TYPE_CHANGED_ITINERARY),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpCheckins', 'MpDisableAll', Content::TYPE_CHECKIN_REMINDER),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpBookingMessages', 'MpDisableAll', Content::TYPE_BOOKING, true, true),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpProductUpdates', 'MpDisableAll', Content::TYPE_PRODUCT_UPDATES),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpOffers', 'MpDisableAll', Content::TYPE_OFFER),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpNewBlogPosts', 'MpDisableAll', Content::TYPE_BLOG_POST),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpInviteeReg', 'MpDisableAll', Content::TYPE_INVITEE_REG),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpPlanChanges', 'MpDisableAll', Content::TYPE_FLIGHT_DELAY),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpPlanChanges', 'MpDisableAll', Content::TYPE_FLIGHT_TIME_CHANGED),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpPlanChanges', 'MpDisableAll', Content::TYPE_FLIGHT_BAGGAGE_CHANGE),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpCheckins', 'MpDisableAll', Content::TYPE_LEG_ARRIVED),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpCheckins', 'MpDisableAll', Content::TYPE_HOTEL_PHONE),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpPlanChanges', 'MpDisableAll', Content::TYPE_FLIGHT_CANCELLATION),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpPlanChanges', 'MpDisableAll', Content::TYPE_FLIGHT_REINSTATED),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpCheckins', 'MpDisableAll', Content::TYPE_CONNECTION_INFO),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpPlanChanges', 'MpDisableAll', Content::TYPE_CONNECTION_INFO_GATE_CHANGE),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpCheckins', 'MpDisableAll', Content::TYPE_FLIGHT_DEPARTURE),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpCheckins', 'MpDisableAll', Content::TYPE_FLIGHT_BOARDING),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpCheckins', 'MpDisableAll', Content::TYPE_PRECHECKIN_REMINDER)
        );
    }

    /**
     * @dataProvider businessNotificationsDataProvider
     */
    public function testBusinessNotification($deviceType, $fields, $contentType, $expectingPush)
    {
        $businessUserId = $this->aw->createAwUser(null, null, ['AccountLevel' => ACCOUNT_LEVEL_BUSINESS]);
        $this->aw->createConnection($businessUserId, $this->user->getUserid(), true, null, ['AccessLevel' => ACCESS_ADMIN]);
        $this->aw->createConnection($this->user->getUserid(), $businessUserId, true);

        $appVersion = $deviceType == MobileDevice::TYPE_CHROME ? 'web' : '2.11.23';
        $this->db->haveInDatabase('MobileDevice', ['DeviceKey' => 'test' . $this->user->getUserid(), 'DeviceType' => $deviceType, 'Lang' => 'en', 'UserID' => $this->user->getUserid(), 'AppVersion' => $appVersion]);
        $this->db->executeQuery("update Usr set {$this->setFields($fields)} where UserID = " . $this->user->getUserid());

        $producer = $this->mockServiceWithBuilder('old_sound_rabbit_mq.push_notification_producer');
        $sender = $this->container->get(Sender::class);

        if ($expectingPush) {
            $producer->expects($this->once())->method('publish');
        } else {
            $producer->expects($this->never())->method('publish');
        }
        $sender->send(new Content('Some Title', 'Some message', $contentType), $sender->loadDevices([$this->user], MobileDevice::TYPES_ALL, $contentType));
    }

    public function businessNotificationsDataProvider()
    {
        return array_merge(
            $this->dataSet(MobileDevice::TYPE_CHROME, 'wpBookingMessages', 'WpDisableAll', Content::TYPE_BOOKING, false, true),
            $this->dataSet(MobileDevice::TYPE_ANDROID, 'mpBookingMessages', 'MpDisableAll', Content::TYPE_BOOKING, false, true)
        );
    }

    private function setFields($fields)
    {
        return implode(', ', array_map(function ($fieldName, $fieldValue) {
            return sprintf('%s = %s', $fieldName, $fieldValue);
        }, array_keys($fields), $fields));
    }

    private function dataSet($device, $setting, $disableAllSetting, $contentType, $settingOffExpected = false, $disableAllExpected = false)
    {
        return [
            [$device, [$setting => 1], $contentType, true],
            [$device, [$setting => 0], $contentType, $settingOffExpected],
            [$device, [$setting => 1, $disableAllSetting => 1], $contentType, $disableAllExpected],
        ];
    }
}
