<?php

namespace AwardWallet\MainBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PassportExpiredEvent extends Event
{
    /**
     * @var int
     */
    private $userId;

    /**
     * @var int
     */
    private $passportId;

    /**
     * @var int
     */
    private $months;

    /**
     * @var string
     */
    private $userName;

    public function __construct(int $userId, int $passportId, int $months, string $userName)
    {
        $this->userId = $userId;
        $this->passportId = $passportId;
        $this->months = $months;
        $this->userName = $userName;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getPassportId(): int
    {
        return $this->passportId;
    }

    public function getMonths(): int
    {
        return $this->months;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }
}
