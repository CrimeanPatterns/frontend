<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\Common\Entity\Aircode;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\Circle;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\Miles;
use AwardWallet\MainBundle\Service\EmailTemplate\Geo\Point;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class FlyingBluePromoCitiesV2 extends AbstractFailTolerantDataProvider
{
    protected const AIR_CODES_LIST = [
        'ATL',
        'AUS',
        'BOS',
        'DEN',
        'DTW',
        'IAH',
        'MSP',
        'YUL',
        'SLC',
        'IAD',
    ];

    public function getQueryOptions()
    {
        $airCodeRepo = $this->container->get('doctrine.orm.entity_manager')->getRepository(Aircode::class);
        $options = parent::getQueryOptions();
        array_walk($options, function (Options $option) use ($airCodeRepo) {
            $option->nearPoints =
                it(self::AIR_CODES_LIST)
                ->map(fn (string $iataCode) => new Circle(
                    Point::fromAirCode($airCodeRepo->findOneBy(['aircode' => $iataCode])),
                    new Miles(100)
                ))
                ->toArray();

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
                and AW member lives within 100 miles of the airports ATL, AUS, BOS, DEN, DTW, IAH, MSP, YUL, SLC and IAD';
    }
}
