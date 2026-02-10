<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Worker\PushNotification\NotificationHelper;

abstract class BasePlatformHandlerTest extends BasePushNotificationsTest
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|NotificationHelper
     */
    protected function getNotificationHelperMock()
    {
        return $this->getMockBuilder('\\AwardWallet\\MainBundle\\Worker\\PushNotification\\NotificationHelper')->disableOriginalConstructor()->getMock();
    }
}
