<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;

class Notification implements \JsonSerializable
{
    use FilterNull;
    /**
     * @var string
     */
    public $message;
    /**
     * @var array
     */
    public $payload;
    /**
     * @var array
     */
    public $callAction;

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return Notification
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param array $payload
     * @return Notification
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @return array
     */
    public function getCallAction()
    {
        return $this->callAction;
    }

    /**
     * @param array $callAction
     * @return $this
     */
    public function setCallAction($callAction)
    {
        $this->callAction = $callAction;

        return $this;
    }
}
