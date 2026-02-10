<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

class Task
{
    /**
     * worker id in container.
     *
     * @var string
     */
    public $serviceId;
    /**
     * @var string
     */
    public $requestId;
    /**
     * @var null
     */
    public $method;
    /**
     * @var null
     */
    public $parameters;
    /**
     * @var int
     */
    public $retry = 0;

    /**
     * @param $delegateServiceId - container service id
     */
    public function __construct($delegateServiceId, $requestId, $method = null, $parameters = [])
    {
        $this->serviceId = $delegateServiceId;
        $this->requestId = $requestId;
        $this->method = $method;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return sha1($this->requestId);
    }

    public function getMaxRetriesCount(): int
    {
        return 0;
    }
}
