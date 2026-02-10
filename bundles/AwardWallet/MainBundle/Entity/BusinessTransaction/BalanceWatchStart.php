<?php

namespace AwardWallet\MainBundle\Entity\BusinessTransaction;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BusinessTransaction;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\BalanceWatch\Constants;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class BalanceWatchStart extends BusinessTransaction
{
    public const TYPE = 50;
    public const AMOUNT = BalanceWatchCredit::PRICE;

    public function __construct(Account $account, Usr $payerUser)
    {
        parent::__construct();
        $this->amount = Constants::TRANSACTION_COST * self::AMOUNT;

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
