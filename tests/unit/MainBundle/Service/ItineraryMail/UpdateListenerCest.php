<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\ItineraryMail;

use AwardWallet\MainBundle\Event\ItineraryUpdateEvent;
use AwardWallet\MainBundle\Service\ItineraryMail\UpdateListener;
use Codeception\Example;
use Codeception\Util\Stub;

/**
 * @group frontend-unit
 */
class UpdateListenerCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;

    /**
     * @dataProvider skipSilentDataProvider
     */
    public function testSkipSilent(\TestSymfonyGuy $I, Example $example)
    {
        /** @var UpdateListener $listener */
        $listener = $I->createInstance(UpdateListener::class, ['enable' => true]);

        /** @var ItineraryUpdateEvent $mockedEvent */
        $mockedEvent = $I->stubMake(ItineraryUpdateEvent::class, [
            'getAdded' => $example['silent'] ? Stub::never() : [],
            'getChanged' => $example['silent'] ? Stub::never() : [],
            'isSilent' => $example['silent'],
        ]);

        $listener->onItineraryUpdate($mockedEvent);
    }

    protected function skipSilentDataProvider(): array
    {
        return [
            ["silent" => true, "expectFind" => false],
            ["silent" => false, "expectFind" => true],
        ];
    }
}
