<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\BusinessTransaction;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

class BusinessTransactionRepository extends EntityRepository
{
    public function getLastTransaction(Usr $business, $sourceId = null, $types = [])
    {
        $q = $this->createQueryBuilder('t');
        $q->where($q->expr()->eq('t.user', ':user'))
            ->setParameter('user', $business);
        $types = array_map(function ($type) {
            return "t INSTANCE OF AwardWallet\MainBundle\Entity\BusinessTransaction\\{$type}";
        }, $types);

        if (sizeof($types)) {
            $q->andWhere(implode(" or ", $types));
        }

        if (isset($sourceId)) {
            $q->andWhere($q->expr()->eq('t.sourceID', ':source'))
                ->setParameter('source', $sourceId);
        }
        $query = $q->orderBy('t.createDate', 'desc')
            ->addOrderBy('t.id', 'desc')
            ->setMaxResults(1)
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getTransactionQuery(Usr $business)
    {
        $builder = $this->_em->createQueryBuilder();

        return $builder
            ->select('t')
            ->from(BusinessTransaction::class, 't')
            ->andWhere('t.user = :business')->setParameter('business', $business)
            ->orderBy('t.createDate', 'DESC')
            ->orderBy('t.id', 'DESC');
    }

    public function getClosedRequestsCount(Usr $business)
    {
        $builder = $this->_em->createQueryBuilder();
        $fromTime = new \DateTime('-1 month');

        return $builder
            ->select('count(t)')
            ->from(BusinessTransaction\AbRequestClosed::class, 't')
            ->andWhere('t.user = :business')->setParameter('business', $business)
            ->andWhere('t.createDate > :fromTime')->setParameter(':fromTime', $fromTime)
            ->getQuery()->getSingleScalarResult();
    }
}
