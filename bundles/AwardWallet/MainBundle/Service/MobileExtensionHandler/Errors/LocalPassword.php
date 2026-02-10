<?php

namespace AwardWallet\MainBundle\Service\MobileExtensionHandler\Errors;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Account;

/**
 * @NoDI
 */
class LocalPassword
{
    /**
     * @var Account
     */
    private $account;

    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }
}
