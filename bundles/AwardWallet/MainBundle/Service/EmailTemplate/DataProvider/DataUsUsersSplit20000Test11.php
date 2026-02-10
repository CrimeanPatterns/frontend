<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;

class DataUsUsersSplit20000Test11 extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        return DataUsUsersSplit20000Test1::getSplitOptions(parent::getQueryOptions(), 11);
    }

    public function getDescription(): string
    {
        return DataUsUsersSplit20000Test1::parametrizedDescription(11);
    }

    public function getTitle(): string
    {
        return DataUsUsersSplit20000Test1::parametrizedTitle(11);
    }

    public function getGroup(): string
    {
        return Group::GROUPS_14_US;
    }
}
