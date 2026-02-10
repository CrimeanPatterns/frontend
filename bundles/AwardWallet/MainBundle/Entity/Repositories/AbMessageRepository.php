<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Entity\AbMessage;
use Doctrine\ORM\EntityRepository;

class AbMessageRepository extends EntityRepository
{
    public function allMessageCountForRequests($requests)
    {
        if (!is_array($requests)) {
            return [];
        }

        if (!count($requests)) {
            return [];
        }
        $query = $this->_em->createQueryBuilder()->select(['IDENTITY(m.RequestID)', 'count(m.AbMessageID)'])->from(AbMessage::class, 'm');
        $query->where($query->expr()->in('m.RequestID', $requests))
            ->andWhere('m.Type >= ' . AbMessage::TYPE_COMMON)
            ->andWhere('m.Type <> ' . AbMessage::TYPE_INTERNAL)
            ->andWhere('m.Type <> ' . AbMessage::TYPE_SHARE_ACCOUNTS_INTERNAL)
            ->groupBy('m.RequestID')
        ;
        $counts = $query->getQuery()->getArrayResult();
        $counts = array_replace(array_fill_keys($requests, 0), array_combine(array_column($counts, 1), array_column($counts, 2)));

        return $counts;
    }

    public function internalMessageCountForRequests($requests)
    {
        if (!is_array($requests)) {
            return [];
        }

        if (!count($requests)) {
            return [];
        }
        $query = $this->_em->createQueryBuilder()->select(['IDENTITY(m.RequestID)', 'count(m.AbMessageID)'])->from(AbMessage::class, 'm');
        $query->where($query->expr()->in('m.RequestID', $requests))
            ->andWhere('m.Type in (' . AbMessage::TYPE_INTERNAL . ', ' . AbMessage::TYPE_SHARE_ACCOUNTS_INTERNAL . ')')
            ->groupBy('m.RequestID')
        ;
        $counts = $query->getQuery()->getArrayResult();
        $counts = array_replace(array_fill_keys($requests, 0), array_combine(array_column($counts, 1), array_column($counts, 2)));

        return $counts;
    }

    public function isNewInternalForRequests($requests, Entity\Usr $user)
    {
        if (!is_array($requests)) {
            return [];
        }

        if (!count($requests)) {
            return [];
        }
        $qb = $this->_em->createQueryBuilder();
        $e = $qb->expr();
        $qb
            ->select([
                'IDENTITY(abm.RequestID)',
                'count(abm.AbMessageID)',
            ])
            ->from(AbMessage::class, 'abm')
            ->leftJoin(Entity\AbRequestMark::class, 'abrm', 'WITH', 'abm.RequestID = abrm.RequestID')
            ->where($e->in('abm.RequestID', $requests))
            ->andWhere('abrm.UserID = ' . $user->getId())
            ->andWhere($e->gt('abm.CreateDate', 'abrm.ReadDate'))
            ->andWhere('abm.Type = ' . AbMessage::TYPE_INTERNAL)
            ->groupBy('abm.RequestID')
        ;
        $counts = $qb->getQuery()->getArrayResult();
        $counts = array_replace(array_fill_keys($requests, 0), array_combine(array_column($counts, 1), array_column($counts, 2)));

        return $counts;
    }
}
