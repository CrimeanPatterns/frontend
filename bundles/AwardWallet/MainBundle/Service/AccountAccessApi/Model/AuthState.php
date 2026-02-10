<?php

namespace AwardWallet\MainBundle\Service\AccountAccessApi\Model;

class AuthState
{
    /**
     * @var int
     */
    private $accessLevel;
    /**
     * @var int
     */
    private $businessId;
    /**
     * @var string|null
     */
    private $state;

    public function __construct(int $accessLevel, int $businessId, ?string $state)
    {
        $this->accessLevel = $accessLevel;
        $this->businessId = $businessId;
        $this->state = $state;
    }

    public function getAccessLevel(): int
    {
        return $this->accessLevel;
    }

    public function getBusinessId(): int
    {
        return $this->businessId;
    }

    public function getState(): ?string
    {
        return $this->state;
    }
}
