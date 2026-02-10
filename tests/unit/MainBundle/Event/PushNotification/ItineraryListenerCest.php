<?php

namespace AwardWallet\Tests\Unit\MainBundle\Event\PushNotification;

use AwardWallet\MainBundle\Entity\Repositories\CountryRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Event\PushNotification\ItineraryListener;
use Codeception\Example;
use Codeception\Util\Stub;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group frontend-unit
 */
class ItineraryListenerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider skipSilentDataProvider
     */
    public function testSkipSilent(\TestSymfonyGuy $I, Example $example)
    {
        $usrRepo = $I->stubMake(UsrRepository::class, [
            'find' => $example["expectFind"] ? null : Stub::never(),
        ]);
        $countryRepo = $I->stubMake(CountryRepository::class, [
            'find' => $example["expectFind"] ? null : Stub::never(),
        ]);

        $em = $I->stubMakeEmpty(EntityManagerInterface::class, [
            'getRepository' => Stub::consecutive($usrRepo, $countryRepo),
        ]);

        /** @var ItineraryListener $listener */
        $listener = $I->createInstance(ItineraryListener::class, [
            'entityManager' => $em,
        ]);

        $listener->onItineraryUpdate(new ItineraryUpdateEvent(1, [], [], [], [], [], $example["silent"]));
    }

    protected function skipSilentDataProvider(): array
    {
        return [
            ["silent" => true, "expectFind" => false],
            ["silent" => false, "expectFind" => true],
        ];
    }
}
