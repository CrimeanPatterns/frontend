<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries as Schema;
use Codeception\Example;
use JMS\Serializer\SerializerInterface;

/**
 * @group frontend-functional
 */
class LastSyncCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?ItinerariesProcessor $itinerariesProcessor;

    private ?SerializerInterface $serializer;

    private ?Usr $user;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->itinerariesProcessor = $I->grabService(ItinerariesProcessor::class);
        $this->serializer = $I->grabService('jms_serializer');
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
    }

    /**
     * @dataProvider dataProvider
     */
    public function lastSyncIt(\TestSymfonyGuy $I, Example $example)
    {
        $options = SavingOptions::savingByEmail(new Owner($this->user), 1, new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com'));
        $schemaItinerary = $this->serializer->deserialize($example['itineraryData'], $example['type'], 'json');

        // Save and see LastParseDate is now
        $report = $this->itinerariesProcessor->save([$schemaItinerary], $options);
        $I->assertNotEmpty($report->getAdded());
        /** @var Itinerary $itinerary */
        $itinerary = array_values($report->getAdded())[0];
        $I->assertEqualsWithDelta(new \DateTime(), $itinerary->getLastParseDate(), 5, '');

        // Move LastParseDate one hour back, update, and see it is now again
        $itinerary->setLastParseDate(new \DateTime("1 hour ago"));
        $report = $this->itinerariesProcessor->save([$schemaItinerary], $options);
        $I->assertNotEmpty($report->getUpdated());
        /** @var Itinerary $itinerary */
        $itinerary = array_values($report->getUpdated())[0];
        $I->assertEqualsWithDelta(new \DateTime(), $itinerary->getLastParseDate(), 5, '');

        // Move again, but this time Modified is true, so it remains 1 hour ago
        //        $itinerary->setLastParseDate(new \DateTime("1 hour ago"));
        //        $itinerary->setModified(true);
        //        $this->itinerariesProcessor->save([$schemaItinerary], $options);
        //        $I->assertEquals(new \DateTime('1 hour ago'), $itinerary->getLastParseDate(), '', 5);
    }

    protected function dataProvider(): array
    {
        return [
            ['itineraryData' => file_get_contents(codecept_data_dir() . "/itineraries/schemaFlight.json"), 'type' => Schema\Flight::class],
            ['itineraryData' => file_get_contents(codecept_data_dir() . "/itineraries/schemaReservation.json"), 'type' => Schema\HotelReservation::class],
            ['itineraryData' => file_get_contents(codecept_data_dir() . "/itineraries/schemaRental.json"), 'type' => Schema\CarRental::class],
            ['itineraryData' => file_get_contents(codecept_data_dir() . "/itineraries/schemaEvent.json"), 'type' => Schema\Event::class],
        ];
    }
}
