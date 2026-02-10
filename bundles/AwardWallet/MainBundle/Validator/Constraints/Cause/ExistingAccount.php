<?php

namespace AwardWallet\MainBundle\Validator\Constraints\Cause;

use AwardWallet\MainBundle\Entity\Account;

final class ExistingAccount implements CauseAwareInterface
{
    private $existingAccount;

    public function __construct(Account $existingAccount)
    {
        $this->existingAccount = $existingAccount;
    }

    public function getCause()
    {
        return $this->existingAccount;
    }
}
