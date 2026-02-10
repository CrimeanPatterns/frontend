<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\MainBundle\Worker\PushNotification\LogHelper;
use AwardWallet\MainBundle\Worker\PushNotification\NotificationHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class PushyHandler implements PlatformHandlerInterface
{
    public const MAX_TIME_TO_LIVE = 365 * 24 * 3600;
    /**
     * @var NotificationHelper
     */
    protected $notificationHelper;
    /**
     * @var LogHelper
     */
    protected $logHelper;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(NotificationHelper $notificationHelper, LogHelper $logHelper, LoggerInterface $logger)
    {
        $this->notificationHelper = $notificationHelper;
        $this->logHelper = $logHelper;
        $this->logger = $logger;
    }

    public function prepareMessage(Notification $notification)
    {
        $message = new PushyMessage();
        $message->setDeviceIdentifier($notification->getDeviceKey());
        $message->setMessage($notification->getMessage());

        $options = [];

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
     * @param ResponseInterface[] $responses
     */
    public function checkResponses(Notification $notificationDTO, array $responses)
    {
        if (!$responses) {
            return;
        }

        $response = current($responses);

        if (
            is_array($responseJson = @json_decode((string) $response->getBody(), true))
            && isset($responseJson['id'], $responseJson['success'])
            && $responseJson['success']
        ) {
            $this->logger->info('pushy push successfully sent', array_merge(
                [
                    "message" => $notificationDTO->getMessage(),
                    "payload" => $notificationDTO->getPayload(),
                    "pushy_message_id" => $responseJson['id'],
                ],
                $this->logHelper->getSuccessContext($notificationDTO)
            ));
        } elseif (isset($responseJson['error'])) {
            // TODO: error handling
            $this->logger->warning(
                sprintf('pushy returned error: %s', $responseJson['error']),
                $this->logHelper->getFailContext(
                    $notificationDTO,
                    ['pushy_error' => $responseJson['error']]
                )
            );
        } else {
            $this->notificationHelper->retry($notificationDTO);

            $this->logger->warning(
                sprintf('Unknown response structure'),
                $this->logHelper->getFailContext(
                    $notificationDTO,
                    [
                        'response_body' => (string) $response->getBody(),
                        'response_headers' => json_encode($response->getHeaders()),
                    ]
                )
            );
        }
    }
}
