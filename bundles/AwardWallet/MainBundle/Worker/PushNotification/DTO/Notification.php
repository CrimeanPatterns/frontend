<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\DTO;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Service\Notification\TransformedContent;

class Notification
{
    use Versioning;

    public const VERSION = 8;
    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var int
     */
    private $deviceId;

    /**
     * @var string
     */
    private $deviceKey;

    /**
     * @var string
     */
    private $deviceLang;

    /**
     * @var int
     */
    private $deviceType;

    /**
     * @var int
     */
    private $retries = 0;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $deviceAppVersion;
    /**
     * @var Options
     */
    private $options;
    /**
     * @var int
     */
    private $createdAt;

    public function __construct(
        $message,
        array $payload,
        MobileDevice $device,
        $routingKey = '',
        $type = '',
        ?Options $options = null,
        ?int $createdAt = null
    ) {
        $this->setVersion(self::VERSION);

        $this->message = $message;
        $this->payload = $payload;
        $this->deviceId = $device->getMobileDeviceId();
        $this->deviceKey = $device->getDeviceKey();
        $this->deviceType = $device->getDeviceType();
        $this->deviceLang = $device->getLang();
        $this->deviceAppVersion = $device->getAppVersion();
        $this->userId = $device->getUser() ? $device->getUser()->getUserid() : null;

        $this->routingKey = $routingKey;
        $this->type = $type;
        $this->options = $options ?: new Options();
        $this->createdAt = $createdAt;
    }

    public static function createFromTransformedContent(
        TransformedContent $transformedContent,
        MobileDevice $device,
        $routingKey = '',
        ?int $createdAt = null
    ) {
        return new self(
            $transformedContent->message,
            $transformedContent->payload,
            $device,
            $routingKey,
            $transformedContent->type,
            $transformedContent->options,
            $createdAt
        );
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    public function addRetries($retries)
    {
        $this->retries += $retries;
    }

    /**
     * @return int
     */
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * @param int $retries
     */
    public function setRetries($retries)
    {
        $this->retries = $retries;
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * @return string
     */
    public function getDeviceLang()
    {
        return $this->deviceLang;
    }

    /**
     * @return int
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * @return string
     */
    public function getDeviceKey()
    {
        return $this->deviceKey;
    }

    /**
     * @return int
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getDeviceAppVersion()
    {
        return $this->deviceAppVersion;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Notification
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }
}
