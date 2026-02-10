<?php

namespace AwardWallet\MainBundle\Factory;

use AwardWallet\Common\PasswordCrypt\PasswordDecryptor;
use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;
use AwardWallet\MainBundle\Entity\Account;

class AccountFactory
{
    private PasswordEncryptor $passwordEncryptor;

    private PasswordDecryptor $passwordDecryptor;

    public function __construct(PasswordEncryptor $passwordEncryptor, PasswordDecryptor $passwordDecryptor)
    {
        $this->passwordEncryptor = $passwordEncryptor;
        $this->passwordDecryptor = $passwordDecryptor;
    }

    public function create(): Account
    {
        $result = new Account();

        $result
            ->setPasswordDecryptor($this->passwordDecryptor)
            ->setPasswordEncryptor($this->passwordEncryptor)
        ;

        return $result;
    }
}
