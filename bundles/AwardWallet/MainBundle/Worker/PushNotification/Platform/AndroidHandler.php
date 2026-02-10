<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use AwardWallet\MainBundle\Worker\PushNotification\DTO\DeviceAction;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\MainBundle\Worker\PushNotification\LogHelper;
use AwardWallet\MainBundle\Worker\PushNotification\NotificationHelper;
use Buzz\Message\Response;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\QuotaExceeded;
use Kreait\Firebase\Exception\Messaging\ServerError;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use Psr\Log\LoggerInterface;
use RMS\PushNotificationsBundle\Message\AndroidMessage;

class AndroidHandler implements PlatformHandlerInterface
{
    public const MAX_TIME_TO_LIVE = 2419200;
    /**
     * @see https://pushpad.xyz/blog/fcm-returns-404-for-stale-push-subscriptions
     */
    private const INACTIVE_SUBSCRIPTION_FOR_270_DAYS_REGEXP_1 = '/A valid push subscription endpoint should be specified in the URL as such/';
    private const INACTIVE_SUBSCRIPTION_FOR_270_DAYS_REGEXP_2 = '/404 Not Found/';
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
        $message = new AndroidMessage();
        $message->setGCM(true);
        $message->setDeviceIdentifier($notification->getDeviceKey());
        $message->setMessage($notification->getMessage());

        $data = $notification->getPayload();

        if (!isset($data['title'])) {
            $data['title'] = 'AwardWallet';
        }

        $message->setData($data);
        $collapseKey = 'AwardWallet';
        $message->setCollapseKey($collapseKey);
        $androidConfigOptions = [
            'collapse_key' => $collapseKey,
        ];

        if (null !== ($deadlineTimestamp = $notification->getOptions()->getDeadlineTimestamp())) {
            $androidConfigOptions['ttl'] = \max(
                1,
                min(
                    $deadlineTimestamp - time(),
                    self::MAX_TIME_TO_LIVE - 1
                )
            );
        }

        $androidConfigOptions['notification'] = [
            'channel_id' => $data['channel'],
        ];
        $message->setGCMOptions($androidConfigOptions);

        return $message;
    }

    /**
     * @param Response[] $responses
     */
    public function checkResponses(Notification $notificationDTO, array $responses)
    {
        foreach ($responses as $response) {
            if ($response instanceof NotFound) {
                $this->logger->info("removing web push device as not registered: " . $response->getMessage(), $this->logHelper->getFailContext($notificationDTO, ["deviceId" => $notificationDTO->getDeviceId()]));
                $this->notificationHelper->deviceAction($notificationDTO, DeviceAction::REMOVE);

                continue;
            }

            if ($response instanceof QuotaExceeded) {
                $this->logger->info("QuotaExceeded: " . $response->getMessage(), $this->logHelper->getFailContext($notificationDTO, ["deviceId" => $notificationDTO->getDeviceId()]));
                $this->retry($response->retryAfter(), $notificationDTO);

                continue;
            }

            if ($response instanceof ServerUnavailable) {
                $this->logger->warning("ServerUnavailable: " . $response->getMessage(), $this->logHelper->getFailContext($notificationDTO, ["deviceId" => $notificationDTO->getDeviceId()]));

                if (self::isInactiveSubscriptionFor270Days($response->getMessage())) {
                    $this->notificationHelper->deviceAction($notificationDTO, DeviceAction::REMOVE);
                } else {
                    $this->retry($response->retryAfter(), $notificationDTO);
                }

                continue;
            }

            if ($response instanceof ServerError) {
                $this->logger->warning("ServerError: " . $response->getMessage(), $this->logHelper->getFailContext($notificationDTO, ["deviceId" => $notificationDTO->getDeviceId()]));
                $this->retry(new \DateTimeImmutable("+5 minute"), $notificationDTO);

                continue;
            }

            if (is_array($response) && isset($response['name'])) {
                $this->logger->info('google push successfully sent', array_merge(
                    [
                        "message" => $notificationDTO->getMessage(),
                        "payload" => $notificationDTO->getPayload(),
                    ],
                    $this->logHelper->getSuccessContext($notificationDTO)
                ));

                continue;
            }

            throw new \Exception("unknown response type");
        }
    }

    private function retry(?\DateTimeImmutable $retryAfter, Notification $notificationDTO)
    {
        if ($retryAfter === null) {
            $this->notificationHelper->retry($notificationDTO);
        } else {
            $this->notificationHelper->retryAfter($retryAfter->getTimestamp() - time(), $notificationDTO);
        }
    }

    /**
     * @see https://pushpad.xyz/blog/fcm-returns-404-for-stale-push-subscriptions
     */
    private static function isInactiveSubscriptionFor270Days(string $error): bool
    {
        return
            \preg_match(self::INACTIVE_SUBSCRIPTION_FOR_270_DAYS_REGEXP_1, $error)
            && \preg_match(self::INACTIVE_SUBSCRIPTION_FOR_270_DAYS_REGEXP_2, $error);
    }
}

/**
 * Class GCMResponse.
 *
 * @property int $failure
 * @property int $success
 * @property int $canonical_ids
 * @property GCMResponseResult[] $results
 */
class GCMResponse extends \ArrayObject
{
}

/**
 * Class GCMResponseResult.
 *
 * @property string $message_id
 * @property string $registration_id
 * @property string $error
 */
class GCMResponseResult extends \ArrayObject
{
}
