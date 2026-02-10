<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;

class ItineraryTracker extends Tracker
{
    public function addSource(PropertySourceInterface $source)
    {
        $this->sources[] = $source;
    }

    /**
     * use this function to grab state of itinerary before making changes to itinerary.
     *
     * @param string $itineraryId
     * @return Properties[]
     */
    public function getProperties($itineraryId)
    {
        $result = [];

        foreach ($this->sources as $source) {
            $result = array_merge(
                $result,
                array_filter(
                    $source->getItineraryProperties($itineraryId),
                    function (Properties $properties) { return !empty($properties->values); }
                )
            );
        }

        return $result;
    }

    /**
     * use this function to record changes after you have changed itinerary.
     *
     * @param Properties[] $oldProperties - array from getProperties call
     * @param int $itineraryId
     * @param bool $suppressUpdateEvent
     * @return int
     */
    public function recordChanges(array $oldProperties, $itineraryId, $userId, $suppressUpdateEvent = false, $silent = false)
    {
        $newProperties = $this->getProperties($itineraryId);
        [$added, $removed, $changed, $chagedOld, $changedNames] = $this->compareAndRecord($oldProperties, $newProperties);

        if (!$suppressUpdateEvent) {
            if (
                count($added) > 0
                || count($removed) > 0
                || count($changed) > 0
            ) {
                $this->eventDispatcher->dispatch(new ItineraryUpdateEvent($userId, $added, $removed, $changed, $chagedOld, $changedNames, $silent), ItineraryUpdateEvent::NAME);
            }
        }

        return count($changed);
    }
}
