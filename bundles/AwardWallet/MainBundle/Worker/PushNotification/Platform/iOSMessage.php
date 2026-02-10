<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\Platform;

use Apns;
use RMS\PushNotificationsBundle\Device\Types;
use RMS\PushNotificationsBundle\Message\MessageInterface;

class iOSMessage implements MessageInterface
{
    /**
     * @var Apns\Message
     */
    protected $innerMessage;

    /**
     * @var ?string
     */
    protected $version;

    public function __construct(Apns\Message $innerMessage, ?string $version)
    {
        $this->innerMessage = $innerMessage;
        $this->version = $version;
    }

    public function setMessage($message)
    {
        $this->throwUnimplementedException(__METHOD__);
    }

    public function setData($data)
    {
        $this->throwUnimplementedException(__METHOD__);
    }

    public function setDeviceIdentifier($identifier)
    {
        $this->throwUnimplementedException(__METHOD__);
    }

    public function getMessageBody()
    {
        $this->throwUnimplementedException(__METHOD__);
    }

    public function getDeviceIdentifier()
    {
        $this->throwUnimplementedException(__METHOD__);
    }

    public function getInnerMessage(): Apns\Message
    {
        return $this->innerMessage;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getTargetOS()
    {
        return Types::OS_IOS;
    }

    protected function throwUnimplementedException(string $method)
    {
        throw new \LogicException(sprintf("Method %s intentionally unimplemented, use inner Apns\\Message instance.", $method));
    }
}
