<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Itinerary;

class ProcessingReport
{
    /**
     * @var Itinerary[]
     */
    private $added = [];

    /**
     * @var Itinerary[]
     */
    private $updated = [];

    /**
     * @var Itinerary[]
     */
    private $removed = [];

    /**
     * @param Itinerary[] $added
     * @param Itinerary[] $updated
     * @param Itinerary[] $removed
     */
    public function __construct(array $added = [], array $updated = [], array $removed = [])
    {
        $this->added = $added;
        $this->updated = $updated;
        $this->removed = $removed;
    }

    /**
     * @return Itinerary[]
     */
    public function getAdded(): array
    {
        return $this->added;
    }

    /**
     * @return Itinerary[]
     */
    public function getUpdated(): array
    {
        return $this->updated;
    }

    /**
     * @return Itinerary[]
     */
    public function getRemoved(): array
    {
        return $this->removed;
    }

    public function merge(ProcessingReport $report): ProcessingReport
    {
        return new ProcessingReport(
            array_merge($this->getAdded(), $report->getAdded()),
            array_merge($this->getUpdated(), $report->getUpdated()),
            array_merge($this->getRemoved(), $report->getRemoved())
        );
    }
}
