<?php

namespace AwardWallet\MainBundle\Manager\CardImage\RegexpHandler;

class MatchResult
{
    public const STATUS_MATCH = 1;
    public const STATUS_NO_MATCH = 2;
    public const STATUS_STOP_MATCH = 3;

    /**
     * @var int
     */
    private $status;
    /**
     * @var array|null
     */
    private $matchData;

    public function __construct(int $status, ?array $matchData = null)
    {
        $this->status = $status;
        $this->matchData = $matchData;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array|null
     */
    public function getMatchData()
    {
        return $this->matchData;
    }

    public function isMatch(): bool
    {
        return self::STATUS_MATCH === $this->status;
    }

    public function isStopMatch(): bool
    {
        return self::STATUS_STOP_MATCH === $this->status;
    }

    public function isNoMatch(): bool
    {
        return self::STATUS_NO_MATCH === $this->status;
    }
}
