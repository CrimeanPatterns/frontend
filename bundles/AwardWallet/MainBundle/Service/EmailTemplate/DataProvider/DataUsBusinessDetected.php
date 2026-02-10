<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataUsBusinessDetected extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->businessDetected = true;
            $option->countries = ['us', 'unknown'];
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'Users from US or from unknown country (detected by last logon ip (if any) or registration ip) <br/> 
                who have business (detected) cards (issue <a href="https://redmine.awardwallet.com/issues/18647" target="_blank">#18647</a>): <br/>
                <ul>
                    <li>card name should contain "business"</li>
                    <b>OR</b><br/>
                    <li>account\'s history records should contain "business"</li>
                </ul>
                Group is scheduled to update every day at ~03:00 UTC (as the part of database backup process)';
    }

    public function getTitle(): string
    {
        return 'US users with business';
    }

    public function getSortPriority(): int
    {
        return 99;
    }
}
