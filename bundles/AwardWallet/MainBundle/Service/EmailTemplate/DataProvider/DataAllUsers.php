<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;

class DataAllUsers extends AbstractFailTolerantDataProvider
{
    public function getDescription(): string
    {
        return 'All users';
    }

    public function getTitle(): string
    {
        return 'All users';
    }
}
