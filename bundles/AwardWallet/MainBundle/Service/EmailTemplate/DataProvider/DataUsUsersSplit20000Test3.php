<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;

class DataUsUsersSplit20000Test3 extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        return DataUsUsersSplit20000Test1::getSplitOptions(parent::getQueryOptions(), 3);
    }

    public function getDescription(): string
    {
        return DataUsUsersSplit20000Test1::parametrizedDescription(3);
    }

    public function getTitle(): string
    {
        return DataUsUsersSplit20000Test1::parametrizedTitle(3);
    }

    public function getGroup(): string
    {
        return Group::GROUPS_14_US;
    }
}
