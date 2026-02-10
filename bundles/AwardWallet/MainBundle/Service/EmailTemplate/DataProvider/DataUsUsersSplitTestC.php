<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;

class DataUsUsersSplitTestC extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        return DataUsUsersSplitTestA::getSplitOptions(parent::getQueryOptions(), 'c');
    }

    public function getDescription(): string
    {
        return DataUsUsersSplitTestA::parametrizedDescription(3);
    }

    public function getTitle(): string
    {
        return DataUsUsersSplitTestA::parametrizedTitle(3);
    }

    public function getGroup(): string
    {
        return Group::GROUPS_3_US;
    }
}
