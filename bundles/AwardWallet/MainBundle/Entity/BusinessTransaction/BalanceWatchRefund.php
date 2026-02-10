<?php

namespace AwardWallet\MainBundle\Entity\BusinessTransaction;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BusinessTransaction;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class BalanceWatchRefund extends BusinessTransaction
{
    public const TYPE = 51;

    public function __construct(Account $account, Usr $payerUser)
    {
        parent::__construct();
        $this
            ->setSourceID($account->getId())
            ->setSourceDesc(\json_encode([
                'payerUid' => $payerUser->getUserid(),
                'provider' => $account->getProviderid()->getShortname(),
                'login' => $account->getLogin(),
                'username' => $account->getUser()->getFullName(),
            ]));
    }

    public function getSourceDesc(): ?array
    {
        return \json_decode($this->sourceDesc, true);
    }
}
