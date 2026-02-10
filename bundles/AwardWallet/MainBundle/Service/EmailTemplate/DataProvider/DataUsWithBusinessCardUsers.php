<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class DataUsWithBusinessCardUsers extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function ($option) {
            /** @var Options $option */
            $option->countries = ['us', 'unknown'];
            $option->hasBusinessCard = true;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return '
            Users from US or from unknown country (detected by last logon ip (if any) or registration ip)  <br/>
            who have business (detected) cards (issue <a href="https://redmine.awardwallet.com/issues/17851" target="_blank">#17851</a>): 
               <ul>
                <li>Account should be from corporate provider (Delta (SkyBonus Corporate), British Airways (On Business, Corporate), Star Alliance Company (PlusAwards), United PerksPlus (Corporate Program), Qantas (Business Rewards), Alaska Airlines (EasyBiz Corporate), KLM BlueBiz (Corporate), Czech Airlines (OK Plus Corporate) etc...)</li>
                <b>OR</b>
                <li>Card name should contain "business" or "The Plum Card" and provider should be one of:
                    <ul>
                        <li>ThankYou Network</li>
                        <li>Bank Of America</li>
                        <li>Amex (Membership Rewards)</li>
                        <li>Chase (Ultimate Rewards)</li>
                        <li>US Bank (FlexPerks)</li>
                        <li>Capital One (Credit Cards)</li>
                        <li>Barclaycard</li>
                        <li>Citibank (ThankYou Rewards)</li>
                        <li>USAA (Rewards)</li>
                    </ul>
                </li>
            </ul>';
    }

    public function getTitle(): string
    {
        return 'US users with business cards';
    }
}
