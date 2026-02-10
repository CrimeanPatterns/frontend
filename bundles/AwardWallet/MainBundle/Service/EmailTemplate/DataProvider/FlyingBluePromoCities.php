<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\Circle;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\Miles;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\Point;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

class FlyingBluePromoCities extends AbstractFailTolerantDataProvider
{
    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, function (Options $option) {
            $option->nearPoints = [
                new Circle(
                    new Point(30.195838, -97.667390), // Austin
                    new Miles(100),
                    true,
                    false
                ),
                new Circle(
                    new Point(25.795535, -80.287599), // Miami
                    new Miles(100),
                    true,
                    false
                ),
            ];
            $option->hasAccountFromProviders = [Provider::KLM_ID, Provider::AIRFRANCE_ID];
            $option->accountFromProvidersLinkedToFamilyMember = false;
        });

        return $options;
    }

    public function getTitle(): string
    {
        return 'Flying Blue Promo Cities';
    }

    public function getDescription(): string
    {
        return 'Description: <a href="https://redmine.awardwallet.com/issues/22302" target="_blank">#22302</a><br/>
                AW members with a connected Flying Blue account (KLM and AirFrance)<br/>
                and AW member lives within 100 miles of the airports AUS or MIA';
    }
}
