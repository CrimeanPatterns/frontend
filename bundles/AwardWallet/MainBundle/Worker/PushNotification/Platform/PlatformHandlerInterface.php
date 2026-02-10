<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use RMS\PushNotificationsBundle\Message\MessageInterface;

interface PlatformHandlerInterface
{
    /**
     * @return MessageInterface
     */
    public function prepareMessage(Notification $notificationDTO);

    /**
     * @param mixed[] $responses
     */
    public function checkResponses(Notification $notificationDTO, array $responses);
}
