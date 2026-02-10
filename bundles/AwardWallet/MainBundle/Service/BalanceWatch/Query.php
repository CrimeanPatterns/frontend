<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use Doctrine\ORM\EntityManagerInterface;

class Query
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getAccountBalanceWatch(Account $account): ?BalanceWatch
    {
        if (null === $account->getBalanceWatchStartDate()) {
            return null;
        }

        $query = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\BalanceWatch::class)
            ->createQueryBuilder('bw')
            ->where('bw.account = :account')->setParameter('account', $account)
            ->andWhere('bw.creationDate > :cdate')->setParameter('cdate', new \DateTime('@' . (time() - (Constants::TIMEOUT_SECONDS + (60 * 60)))))
            ->orderBy('bw.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        return $query->getOneOrNullResult();
    }
}
