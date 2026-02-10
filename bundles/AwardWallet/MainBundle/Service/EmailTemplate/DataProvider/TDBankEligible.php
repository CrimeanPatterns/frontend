<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class TDBankEligible extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function (Options $option) {
            $option->statesCodes = [
                'CT', // Connecticut
                'DC', // District of Columbia
                'DE', // Delaware
                'FL', // Florida
                'MA', // Massachusetts
                'MD', // Maryland
                'ME', // Maine
                'NC', // North Carolina
                'NH', // New Hampshire
                'NJ', // New Jersey
                'NY', // New York
                'PA', // Pennsylvania
                'RI', // Rhode Island
                'SC', // South Carolina
                'VA', // Virginia
                'VT', // Vermont
            ];
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'Bank eligible AW members who live in selected states';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/24739" target="_blank">#24739</a><br/>
                AW members who live in the following states: CT, DC, DE, FL, MA, MD, ME, NC, NH, NJ, NY, PA, RI, SC, VA, VT';
    }
}
