<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use AwardWallet\MainBundle\Worker\PushNotification\DTO\DeviceAction;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\MainBundle\Worker\PushNotification\LogHelper;
use AwardWallet\MainBundle\Worker\PushNotification\NotificationHelper;
use Psr\Log\LoggerInterface;
use RMS\PushNotificationsBundle\Message\MessageInterface;

class MacHandler implements PlatformHandlerInterface
{
    /**
     * @see https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/CommunicatingWIthAPS.html#//apple_ref/doc/uid/TP40008194-CH101-SW1
     */
    public const STATUS_NO_ERRORS = 0;
    public const STATUS_PROCESSING_ERROR = 1;
    public const STATUS_MISSING_DEVICE_TOKEN = 2;
    public const STATUS_MISSING_TOPIC = 3;
    public const STATUS_PAYLOAD = 4;
    public const STATUS_INVALID_TOKEN_SIZE = 5;
    public const STATUS_INVALID_TOPIC_SIZE = 6;
    public const STATUS_INVALID_PAYLOAD_SIZE = 7;
    public const STATUS_INVALID_TOKEN = 8;
    public const STATUS_SHUTDOWN = 10;
    public const STATUS_NONE = 255;

    public const COMMAND = 8;
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
    /**
     * @var Counter
     */
    protected $counter;

    public function __construct(
        NotificationHelper $notificationHelper,
        LogHelper $logHelper,
        LoggerInterface $logger
    ) {
        $this->counter = new Counter();
        $this->notificationHelper = $notificationHelper;
        $this->logHelper = $logHelper;
        $this->logger = $logger;
    }

    public function getCounter(): Counter
    {
        return $this->counter;
    }

    /**
     * @return MessageInterface
     */
    public function prepareMessage(Notification $data)
    {
        $message = new SafariWebPushMessage();
        $message->setMessage($data->getMessage());
        $message->setDeviceIdentifier($data->getDeviceKey());
        $message->setData($data->getPayload());

        if (null !== ($deadlineTimestamp = $data->getOptions()->getDeadlineTimestamp())) {
            $message->setExpiry($deadlineTimestamp);
        }

        $this->counter->lastMessageId++;

        return $message;
    }

    /**
     * @param array[int|AppleResponse] $responses
     */
    public function checkResponses(Notification $notificationDTO, array $responses)
    {
        if (!$responses) {
            return;
        }

        $counter = $this->counter;

        if (isset($responses[$counter->lastMessageId]) && true === $responses[$counter->lastMessageId]) {
            $this->logger->info('safari push successfully sent', array_merge(
                [
                    "message" => $notificationDTO->getMessage(),
                    "payload" => $notificationDTO->getPayload(),
                    "seqId" => $counter->lastMessageId - $counter->lastErrorId,
                    "seqIdType" => 'web',
                ],
                $this->logHelper->getSuccessContext($notificationDTO, ['_aw_push_ios_success_seqid' => $counter->lastMessageId - $counter->lastErrorId])
            ));

            return;
        }

        /** @var AppleResponse $response */
        foreach (array_filter($responses, 'is_array') as $response) {
            // skip previous errors
            if (!isset($response['identifier']) || ($counter->lastMessageId != $response['identifier'])) {
                continue;
            }

            if (!(isset($response['command']) && self::COMMAND == $response['command'])) {
                continue;
            }

            if (!isset($response['status'])) {
                continue;
            }

            $counter->lastErrorId = $counter->lastMessageId;

            switch ($response['status']) {
                case self::STATUS_SHUTDOWN:
                    $this->logger->error($text = 'Received shutdown status code from apple. Throwing exception to restart worker', $this->logHelper->getFailContext($notificationDTO));

                    /**
                     * Force worker restart to open new APNS connection.
                     *
                     * @see https://github.com/richsage/RMSPushNotificationsBundle/issues/91
                     */
                    throw new \RuntimeException($text);

                case self::STATUS_INVALID_PAYLOAD_SIZE:
                    $this->logger->error(sprintf("Invalid payload size. Message: \"%s\",\nPayload: %s", $notificationDTO->getMessage(), json_encode($notificationDTO->getPayload())), $this->logHelper->getFailContext($notificationDTO));

                    break;

                case self::STATUS_INVALID_TOKEN:
                    $this->notificationHelper->deviceAction($notificationDTO, DeviceAction::REMOVE);

                    break;

                default:
                    $this->logger->error(sprintf('Unhandled ios status response: "%s"', $response['status']), $this->logHelper->getFailContext($notificationDTO));
                    $this->notificationHelper->retry($notificationDTO);

                    break;
            }
        }
    }
}

/**
 * Class AppleResponse.
 *
 * @property int $identifier
 * @property int $command
 * @property int $status
 */
class AppleResponse extends \ArrayObject
{
}

class Counter
{
    /**
     * @var int
     */
    public $lastMessageId = -1;
    /**
     * @var int
     */
    public $lastErrorId = -1;
}
