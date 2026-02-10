<?php

namespace AwardWallet\MainBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ItineraryUpdateEvent extends Event
{
    public const NAME = 'aw.itinerary.update';

    /**
     * @var array|\AwardWallet\MainBundle\Timeline\Diff\Properties[]
     */
    private $added;

    /**
     * @var array|\AwardWallet\MainBundle\Timeline\Diff\Properties[]
     */
    private $removed;

    /**
     * @var array|\AwardWallet\MainBundle\Timeline\Diff\Properties[]
     */
    private $changed;

    /**
     * @var array|\AwardWallet\MainBundle\Timeline\Diff\Properties[]
     */
    private $changedOld;

    /**
     * @var array
     */
    private $changedNames;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var bool
     */
    private $silent;

    public function __construct($userId, array $added, array $removed, array $changed, array $changedOld, array $changedNames = [], bool $silent = false)
    {
        $this->added = $added;
        $this->removed = $removed;
        $this->changed = $changed;
        $this->changedOld = $changedOld;
        $this->changedNames = $changedNames;
        $this->userId = $userId;
        $this->silent = $silent;
    }

    /**
     * @return array|\AwardWallet\MainBundle\Timeline\Diff\Properties[]
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @return array|\AwardWallet\MainBundle\Timeline\Diff\Properties[]
     */
    public function getRemoved()
    {
        return $this->removed;
    }

    /**
     * @return array|\AwardWallet\MainBundle\Timeline\Diff\Properties[]
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @return array|\AwardWallet\MainBundle\Timeline\Diff\Properties[]
     */
    public function getChangedOld()
    {
        return $this->changedOld;
    }

    /**
     * @return array
     */
    public function getChangedNames()
    {
        return $this->changedNames;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    public function isSilent(): bool
    {
        return $this->silent;
    }
}
