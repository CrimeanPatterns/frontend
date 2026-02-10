<?php

namespace AwardWallet\MainBundle\Event\PushNotification;

use AwardWallet\Common\DateTimeUtils;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Event\OneTimeCodeEvent;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\InterruptionLevel;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;

class ProfileListener
{
    /**
     * @var Sender
     */
    private $sender;

    public function __construct(Sender $sender)
    {
        $this->sender = $sender;
    }

    public function onOneTimeCode(OneTimeCodeEvent $event)
    {
        $user = $event->getUser();
        $code = $event->getOneTimeCode();
        $devices = $this->sender->loadDevices([$user], MobileDevice::TYPES_ALL, Content::TYPE_ONE_TIME_CODE);

        if (!$devices) {
            return;
        }

        $message = new Trans(
            /** @Desc("Your AwardWallet One Time Access Code is: %one-time-code%") */
            'push-notifications.security.one-time-code.message',
            ['%one-time-code%' => $code]
        );

        $title = new Trans(
            /** @Desc("AwardWallet One Time Access Code") */
            'push-notifications.security.one-time-code.title'
        );

        $isSent = $this->sender->send(
            new Content(
                $title,
                $message,
                Content::TYPE_ONE_TIME_CODE,
                null,
                (new Options())
                    ->setDeadlineTimestamp(time() + DateTimeUtils::SECONDS_PER_DAY)
                    ->setPriority(8)
                    ->setInterruptionLevel(InterruptionLevel::TIME_SENSITIVE)
            ),
            $devices
        );

        if ($isSent) {
            $event->setNotified(true);
        }
    }
}
