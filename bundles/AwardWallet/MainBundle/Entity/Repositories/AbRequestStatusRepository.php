<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

class AbRequestStatusRepository extends EntityRepository
{
    public function getStatusesForBooker(Usr $booker)
    {
        return $this->findBy(['Booker' => $booker], ['SortIndex' => 'asc']);
    }
}
