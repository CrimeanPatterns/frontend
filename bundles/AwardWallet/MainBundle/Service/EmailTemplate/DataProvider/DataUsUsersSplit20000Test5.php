<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;

class DataUsUsersSplit20000Test5 extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        return DataUsUsersSplit20000Test1::getSplitOptions(parent::getQueryOptions(), 5);
    }

    public function getDescription(): string
    {
        return DataUsUsersSplit20000Test1::parametrizedDescription(5);
    }

    public function getTitle(): string
    {
        return DataUsUsersSplit20000Test1::parametrizedTitle(5);
    }

    public function getGroup(): string
    {
        return Group::GROUPS_14_US;
    }
}
