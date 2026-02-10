<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\CommonData;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataUsUsersWithoutFoundersCardWithElite extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();

        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->countries = ['us', 'unknown'];
            $option->notRefCode = CommonData::USERS_WITH_FOUNDERS_CARD_APPROVED_REF_CODES_LIST;
            $option->minEliteLevel = 1;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return '
            Users from US or from unknown country (detected by last logon ip (if any) or registration ip)  <br/>
            and who were\'t included in Founders Card list (date: 18 April 2019) from <a href="https://redmine.awardwallet.com/issues/17751#note-5" target="_blank">#17751</a><br>
            and who have at least level 1 elite status.';
    }

    public function getTitle(): string
    {
        return 'US users NOT included in Founders Card list with elite (date: 18 April 2019)';
    }

    public function getGroup(): string
    {
        return Group::FOUNDERS_CARD;
    }
}
