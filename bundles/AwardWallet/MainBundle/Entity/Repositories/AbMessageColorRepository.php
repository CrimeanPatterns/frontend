<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

class AbMessageColorRepository extends EntityRepository
{
    public function getColorsForBooker(Usr $booker)
    {
        return $this->findBy(['Booker' => $booker]);
    }
}
