<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use Buzz\Message\Response;

class ChromeHandler extends AndroidHandler
{
    public function prepareMessage(Notification $notification)
    {
        $message = new ChromeMessage();
        $message->setDeviceIdentifier($notification->getDeviceKey());
        $message->setMessage($notification->getMessage());

        $options = [];

        if (null !== ($autoClose = $notification->getOptions()->isAutoClose())) {
            $options['requireInteraction'] = !$autoClose;
        }

        if (null !== ($deadlineTimestamp = $notification->getOptions()->getDeadlineTimestamp())) {
            $options['ttl'] = min(
                $deadlineTimestamp - time(),
                self::MAX_TIME_TO_LIVE - 1
            );
        }

        $message->setOptions($options);

        $message->setData($notification->getPayload());

        return $message;
    }

    /**
     * @param Response[] $responses
     */
    public function checkResponses(Notification $notificationDTO, array $responses)
    {
        $errors = [];

        foreach ($responses as $response) {
            if ($response === true) {
                $this->logger->info(
                    'chrome push successfully sent',
                    $this->logHelper->getSuccessContext(
                        $notificationDTO,
                        [
                            "message" => $notificationDTO->getMessage(),
                            "payload" => $notificationDTO->getPayload(),
                            "responses_count" => \count($responses),
                        ]
                    )
                );

                continue;
            }

            $errors[] = $response;
        }

        parent::checkResponses($notificationDTO, $errors);
    }
}
