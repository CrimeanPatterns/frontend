<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

interface PropertySourceInterface
{
    /**
     * should return associative array. keys are sourceId fields.
     *
     * @param int $accountId
     * @return Properties[]
     */
    public function getProperties($accountId);

    /**
     * should return associative array. keys are sourceId fields.
     *
     * @param string $itineraryId
     * @return Properties[]
     */
    public function getItineraryProperties($itineraryId);

    /**
     * will be called when changes detected. you have a chance to do something.
     */
    public function recordChanges(Properties $properties, \DateTime $changeDate);

    /**
     * returns entity properties belongs to.
     */
    public function getEntity(Properties $properies);
}
