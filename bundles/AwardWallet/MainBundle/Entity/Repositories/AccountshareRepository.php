<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Accountshare;
use AwardWallet\MainBundle\Entity\Useragent;
use Doctrine\ORM\EntityRepository;

class AccountshareRepository extends EntityRepository
{
    public function addAccountShare(Account $account, Useragent $userAgent)
    {
        $share = $this->findOneBy(['accountid' => $account, 'useragentid' => $userAgent]);

        if (empty($share)) {
            $em = $this->getEntityManager();
            $accountShare = new Accountshare();
            $accountShare->setUseragentid($userAgent);
            $accountShare->setAccountid($account);
            $em->persist($accountShare);
            $em->flush();
        }
    }

    public function addShare(Account $account, Useragent $userAgent)
    {
        $this->addAccountShare($account, $userAgent);
    }

    public function removeSharedAccount(Account $account, Useragent $userAgent)
    {
        $share = $this->findOneBy(['accountid' => $account, 'useragentid' => $userAgent]);

        if ($share) {
            $em = $this->getEntityManager();
            $em->remove($share);
            $em->flush();
        }
    }

    public function removeShare(Account $account, Useragent $userAgent)
    {
        $this->removeSharedAccount($account, $userAgent);
    }

    public function shareAccounts($accountIds, Useragent $userAgent)
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();

        $shared = $connection->executeQuery("SELECT AccountID FROM AccountShare WHERE AccountID in (?) and UserAgentID = ?",
            [$accountIds, $userAgent->getUseragentid()],
            [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT]
        )->fetchAll(\PDO::FETCH_ASSOC);
        $shared = array_map(function ($v) {return $v['AccountID']; }, $shared);
        $accountIds = array_filter($accountIds, function ($v) use ($shared) { return !in_array($v, $shared); });

        $smt = $connection->prepare("INSERT INTO AccountShare(AccountID, UserAgentID) values(?, ?)");

        foreach ($accountIds as $accountId) {
            $smt->execute([$accountId, $userAgent->getUseragentid()]);
        }
    }
}
