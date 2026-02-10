<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use Apns;
use Apns\Exception\ApnsException;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\DeviceAction;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;
use AwardWallet\MainBundle\Worker\PushNotification\LogHelper;
use AwardWallet\MainBundle\Worker\PushNotification\NotificationHelper;
use Psr\Log\LoggerInterface;
use RMS\PushNotificationsBundle\Message\MessageInterface;

class IosHandler implements PlatformHandlerInterface
{
    /**
     * @see https://developer.apple.com/library/archive/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/CommunicatingwithAPNs.html#//apple_ref/doc/uid/TP40008194-CH11-SW1
     */
    public const CODE_UNKNOWN_ERROR = 0;
    public const CODE_BAD_REQUEST = 400;
    public const CODE_UNREGISTERED = 410;
    public const CODE_PAYLOAD_TOO_LARGE = 413;
    public const CODE_SHUTDOWN = 503;

    public const REASON_BAD_DEVICE_TOKEN = 'BadDeviceToken';
    public const REASON_UNREGISTERED = 'Unregistered';
    public const REASON_PAYLOAD_EMPTY = 'PayloadEmpty';
    public const REASON_PAYLOAD_TOO_LARGE = 'PayloadTooLarge';
    public const REASON_SHUTDOWN = 'Shutdown';

    public const PAYLOAD_INTERRUPTION_LEVEL = 'interruption-level';
    protected const APNS_BUNDLE_ID = 'com.awardwallet.iphone';

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
     * @var IosSender
     */
    protected $iosSender;

    public function __construct(NotificationHelper $notificationHelper, LogHelper $logHelper, LoggerInterface $logger, IosSender $iosSender)
    {
        $this->notificationHelper = $notificationHelper;
        $this->logHelper = $logHelper;
        $this->logger = $logger;
        $this->iosSender = $iosSender;
    }

    /**
     * @return MessageInterface
     */
    public function prepareMessage(Notification $data)
    {
        $payload = $data->getPayload();
        $message = new IOSHttp2ApnsMessage($data->getDeviceKey());
        $message->setData($payload);

        if (isset($payload['title']) && !empty($payload['title'])) {
            $message->setAlert(
                (new Apns\MessageAlert())
                    ->setTitle($payload['title'])
                    ->setBody($data->getMessage())
            );
        } else {
            $message->setAlert($data->getMessage());
        }

        $message->setDeviceIdentifier($data->getDeviceKey());
        $message->setAPSSound('default');
        $message->setTopic(self::APNS_BUNDLE_ID);

        $options = $data->getOptions();

        if (null !== ($deadlineTimestamp = $options->getDeadlineTimestamp())) {
            $message->setExpiry(\min($deadlineTimestamp, \time() + SECONDS_PER_DAY * 90));
        }

        if (null !== ($interruptionLevel = $options->getInterruptionLevel())) {
            $message->setAPSInterruptionLevel($interruptionLevel);
        }

        return new iOSMessage($message, $data->getDeviceAppVersion());
    }

    /**
     * @param array[int|GuzzleResponse] $responses
     */
    public function checkResponses(Notification $notificationDTO, array $responses)
    {
        if (!$responses) {
            return;
        }

        $response = current($responses);

        if (true === $response) {
            $this->logger->info('ios push successfully sent', array_merge(
                [
                    "message" => $notificationDTO->getMessage(),
                    "payload" => $notificationDTO->getPayload(),
                ],
                $this->logHelper->getSuccessContext($notificationDTO)
            ));

            return;
        }

        if ($response instanceof ApnsException) {
            $exception = $response;
            $statusCode = $exception->getCode();
            $reason = $exception->getMessage();
            $previousException = $response->getPrevious();

            switch (true) {
                case [self::CODE_BAD_REQUEST,  self::REASON_BAD_DEVICE_TOKEN] === [$statusCode, $reason]:
                case [self::CODE_UNREGISTERED, self::REASON_UNREGISTERED] === [$statusCode, $reason]:
                    $this->notificationHelper->deviceAction($notificationDTO, DeviceAction::REMOVE);

                    break;

                case [self::CODE_PAYLOAD_TOO_LARGE, self::REASON_PAYLOAD_TOO_LARGE] === [$statusCode, $reason]:
                case [self::CODE_BAD_REQUEST,       self::REASON_PAYLOAD_EMPTY] === [$statusCode, $reason]:
                    $this->logger->error("Invalid payload.", $this->logHelper->getFailContext($notificationDTO));

                    break;

                case self::CODE_UNKNOWN_ERROR === $statusCode:
                    $this->logger->error($exception->getMessage(), \array_merge(
                        $this->logHelper->getFailContext($notificationDTO),
                        ['apns_error_class' => \get_class($response)],
                        $previousException ?
                            [
                                'previous_exception_message' => $previousException->getMessage(),
                                'previous_exception_code' => $previousException->getCode(),
                                'previous_exception_class' => \get_class($previousException),
                            ] : []
                    ));
                    $this->notificationHelper->retry($notificationDTO);

                    break;

                case [self::CODE_SHUTDOWN, self::REASON_SHUTDOWN] === [$statusCode, $reason]:
                    $this->logger->error($text = 'Received shutdown response from apple.', $this->logHelper->getFailContext($notificationDTO));
                    $this->notificationHelper->retry($notificationDTO);
                    $this->iosSender->reconnect();

                    break;

                default:
                    $this->logger->error(sprintf('Unhandled ios error, code: %s, reason: %s', $statusCode, $exception->getMessage()), $this->logHelper->getFailContext($notificationDTO));
                    $this->notificationHelper->retry($notificationDTO);

                    break;
            }
        }
    }
}
