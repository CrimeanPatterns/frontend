<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

class HttpRequestTask extends Task
{
    /**
     * @var string
     */
    private $serializedRequest;
    /**
     * @var string
     */
    private $responseChannel;
    /**
     * @var int
     */
    private $userId;

    public function __construct(string $serializedRequest, string $responseChannel, int $userId)
    {
        parent::__construct(HttpRequestExecutor::class, bin2hex(random_bytes(10)));
        $this->serializedRequest = $serializedRequest;
        $this->responseChannel = $responseChannel;
        $this->userId = $userId;
    }

    public function getSerializedRequest(): string
    {
        return $this->serializedRequest;
    }

    public function getResponseChannel(): string
    {
        return $this->responseChannel;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
