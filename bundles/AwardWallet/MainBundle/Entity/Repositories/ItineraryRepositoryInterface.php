<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\Common\Collections\Criteria;

interface ItineraryRepositoryInterface
{
    /**
     * @return Itinerary[]
     * @throws LooseConditionsException
     */
    public function findMatchingCandidates(Usr $owner, $schemaItinerary): array;

    /**
     * @return Criteria $criteria
     */
    public function getFutureCriteria(): Criteria;
}
