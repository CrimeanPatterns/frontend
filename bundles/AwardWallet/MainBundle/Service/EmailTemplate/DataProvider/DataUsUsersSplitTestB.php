<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;

class DataUsUsersSplitTestB extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        return DataUsUsersSplitTestA::getSplitOptions(parent::getQueryOptions(), 'b');
    }

    public function getDescription(): string
    {
        return DataUsUsersSplitTestA::parametrizedDescription(2);
    }

    public function getTitle(): string
    {
        return DataUsUsersSplitTestA::parametrizedTitle(2);
    }

    public function getGroup(): string
    {
        return Group::GROUPS_3_US;
    }
}
