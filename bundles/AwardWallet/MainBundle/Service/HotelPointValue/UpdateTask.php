<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class UpdateTask extends Task
{
    /**
     * @var int
     */
    private $reservationId;
    /**
     * @var int
     */
    private $attempt;

    public function __construct(int $reservationId, int $attempt = 0)
    {
        parent::__construct(UpdateExecutor::class, bin2hex(random_bytes(10)));
        $this->reservationId = $reservationId;
        $this->attempt = $attempt;
    }

    public function getReservationId(): int
    {
        return $this->reservationId;
    }

    public function getAttempt(): int
    {
        return $this->attempt;
    }
}
