<?php

namespace AwardWallet\MainBundle\Entity\Query;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Trip;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

class ItineraryChecker
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function isUnique(Itinerary $it)
    {
        if (empty($it->getConfirmationNumber())) {
            return true;
        }

        $criteria = new Criteria();

        if ($it->getId()) {
            $criteria->where($criteria->expr()->neq('id', $it->getId()));
        }

        if ($it->getAccount()) {
            $criteria->andWhere($criteria->expr()->eq('account', $it->getAccount()));
        } else {
            $criteria->andWhere($criteria->expr()->isNull('account'));
        }

        if ($it->getUser()) {
            $criteria->andWhere($criteria->expr()->eq('user', $it->getUser()));
        } else {
            $criteria->andWhere($criteria->expr()->isNull('user'));
        }

        if ($it->getUserAgent()) {
            $criteria->andWhere($criteria->expr()->eq('userAgent', $it->getUserAgent()));
        } else {
            $criteria->andWhere($criteria->expr()->isNull('userAgent'));
        }

        $criteria->andWhere($criteria->expr()->eq('confirmationNumber', $it->getConfirmationNumber()));

        if ($it instanceof Trip) {
            $criteria->andWhere($criteria->expr()->andX(
                $criteria->expr()->eq('direction', $it->getDirection())
            ));
        }

        $criteria->setMaxResults(1);
        $result = $this->em->getRepository(get_class($it))
            ->matching($criteria);

        if (!sizeof($result)) {
            return true;
        }

        return $result;
    }
}
