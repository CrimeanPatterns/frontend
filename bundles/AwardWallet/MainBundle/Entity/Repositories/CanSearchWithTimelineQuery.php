<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Timeline\QueryOptions;

interface CanSearchWithTimelineQuery
{
    /**
     * @return Itinerary[]
     */
    public function findByTimelineQuery(Usr $user, QueryOptions $options): array;
}
