<?php

namespace AwardWallet\MainBundle\Security;

class BotIpDetectorState
{
    /**
     * @var int
     */
    private $createdAt;
    /**
     * @var array
     */
    private $data;

    public function __construct(int $createdAt, array $data)
    {
        $this->createdAt = $createdAt;
        $this->data = $data;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
