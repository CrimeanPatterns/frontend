<?php

namespace AwardWallet\MainBundle\Service\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountproperty;
use AwardWallet\MainBundle\Entity\Elitelevel;
use AwardWallet\MainBundle\Entity\Providervote;
use AwardWallet\MainBundle\Entity\Repositories\ElitelevelRepository;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use Doctrine\ORM\EntityManagerInterface;

class ManualUpdateHelper
{
    private EntityManagerInterface $entityManager;
    private ElitelevelRepository $eliteLevelRepository;
    private UserMailboxCounter $mailboxCounter;

    public function __construct(
        EntityManagerInterface $entityManager,
        ElitelevelRepository $eliteLevelRepository,
        UserMailboxCounter $mailboxCounter
    ) {
        $this->entityManager = $entityManager;
        $this->eliteLevelRepository = $eliteLevelRepository;
        $this->mailboxCounter = $mailboxCounter;
    }

    public function getData(Account $account): ManualUpdateResult
    {
        $levelOptions = $this->getEliteLevelOptions($account);

        return new ManualUpdateResult(
            $this->getEliteLevel($account),
            !empty($levelOptions) ? $levelOptions : null,
            $this->isMailboxConnected($account),
            $this->isNotifyMe($account)
        );
    }

    private function getEliteLevel(Account $account): ?string
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select(['ap.accountpropertyid', 'ap.val'])
            ->from(Accountproperty::class, 'ap');

        $result = $queryBuilder
            ->join('ap.providerpropertyid', 'pp')
            ->where('ap.accountid = :accountId', 'pp.kind = :kind')
            ->setParameters([
                ':accountId' => $account->getId(),
                ':kind' => PROPERTY_KIND_STATUS,
            ])
            ->getQuery()
            ->getOneOrNullResult();

        if (isset($result['val'])) {
            $eliteLevelFields = $this->eliteLevelRepository->getEliteLevelFields($account->getProviderid()->getId(), $result['val']);

            return $eliteLevelFields['Name'] ?? $result['val'];
        }

        return null;
    }

    private function getEliteLevelOptions(Account $account): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('el')
            ->from(Elitelevel::class, 'el');

        $result = $queryBuilder
            ->where('el.providerid = :providerId')
            ->setParameters([':providerId' => $account->getProviderid()->getId()])
            ->orderBy('el.rank', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(function ($level) {
            return $level['name'];
        }, $result);
    }

    private function isMailboxConnected(Account $account): bool
    {
        return $this->mailboxCounter->total($account->getUser()->getId()) > 0;
    }

    private function isNotifyMe(Account $account): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('pv')
            ->from(Providervote::class, 'pv');

        $result = $queryBuilder
            ->where('pv.providerid = :providerId', 'pv.userid = :userId')
            ->setParameters([
                ':providerId' => $account->getProviderid()->getId(),
                ':userId' => $account->getUser()->getId(),
            ])
            ->getQuery()
            ->getArrayResult();

        return count($result) > 0;
    }
}
