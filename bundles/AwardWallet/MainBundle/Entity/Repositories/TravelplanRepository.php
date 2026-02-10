<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;

class TravelplanRepository extends EntityRepository
{
    public function ItenarariesSQL($filter = [])
    {
        return $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Trip::class)->TripsSQL($filter)
            . " UNION " . $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Rental::class)->RentalsSQL($filter)
            . " UNION " . $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Parking::class)->ParkingsSQL($filter)
            . " UNION " . $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Reservation::class)->ReservationsSQL($filter)
            . " UNION " . $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Restaurant::class)->RestaurantsSQL($filter)
        . " ORDER BY Date(StartDate), SortIndex, StartDate";
    }
}
