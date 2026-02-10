<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\Airline;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\FlightSegment;
use AwardWallet\Schema\Itineraries\MarketingCarrier;
use AwardWallet\Schema\Itineraries\PricingInfo;
use AwardWallet\Schema\Itineraries\ProviderInfo;
use AwardWallet\Schema\Itineraries\TripLocation;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\Tests\Modules\Utils\ClosureEvaluator\create;

/**
 * @group frontend-functional
 */
class ItinerariesFromAccountAndEmailCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?Usr $user;

    private ?ItinerariesProcessor $itinerariesProcessor;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($I->createAwUser());
        $this->itinerariesProcessor = $I->grabService(ItinerariesProcessor::class);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->itinerariesProcessor = null;
    }

    /**
     * this test will grab reservation from account and email
     * check serialization.
     */
    public function accountAndEmail(\TestSymfonyGuy $I)
    {
        $depDate = strtotime("+1 day 15:00");
        $arrDate = strtotime("+3 hour", $depDate);

        $account1Id = $I->createAwAccount($this->user->getUserid(), "testprovider", "login1");
        /** @var EntityManagerInterface $em */
        $em = $I->getContainer()->get("doctrine.orm.entity_manager");
        $em->clear();
        /** @var Account $account1 */
        $account1 = $em->find(Account::class, $account1Id);

        $airlineProviderCode = "p" . bin2hex(random_bytes(7));
        $airlineProviderName = "n" . $airlineProviderCode;

        /** @var Flight $flight1 */
        $flight1 = create(function (Flight $flight) use ($depDate, $arrDate, $airlineProviderCode, $airlineProviderName) {
            $flight->segments = [
                create(function (FlightSegment $segment) use ($depDate, $arrDate) {
                    $segment->marketingCarrier = create(function (MarketingCarrier $carrier) {
                        $carrier->flightNumber = '2156';
                        $carrier->confirmationNumber = 'RECLOC1';
                        $carrier->airline = create(function (Airline $airline) {
                            $airline->name = "British Airways";
                            $airline->iata = "BA";
                            $airline->icao = "BAQ";
                        });
                    });
                    $segment->departure = create(function (TripLocation $location) use ($depDate) {
                        $location->airportCode = "MSY";
                        $location->name = "Louis Armstrong New Orleans International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "MSY";
                        });
                        $location->localDateTime = date("c", $depDate - 8 * 3600);
                    });
                    $segment->arrival = create(function (TripLocation $location) use ($arrDate) {
                        $location->airportCode = "PHL";
                        $location->name = "Philadelphia International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "PHL";
                        });
                        $location->localDateTime = date("c", $arrDate - 6 * 3600);
                    });
                }),
            ];
            $flight->pricingInfo = create(function (PricingInfo $pricingInfo) {
                $pricingInfo->total = 100;
            });
            $flight->providerInfo = create(function (ProviderInfo $providerInfo) use ($airlineProviderCode, $airlineProviderName) {
                $providerInfo->code = $airlineProviderCode;
                $providerInfo->name = $airlineProviderName;
            });
        });

        // save from account
        $this->itinerariesProcessor->save([$flight1], SavingOptions::savingByAccount($account1, true));
        $tripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->user->getUserid(), "AccountID" => $account1Id]);
        $I->assertEquals(1, $I->grabCountFromDatabase("TripSegment", ["TripID" => $tripId]));
        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripID" => $tripId, "DepCode" => "MSY"]));
        $json = $I->grabFromDatabase("TripSegment", "Sources", ["TripID" => $tripId, "DepCode" => "MSY"]);
        $I->assertCount(1, json_decode($json, true)['data']);
        $I->assertNotEmpty($json);
        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["TripID" => $tripId]));

        $flight1->pricingInfo->total = 150;

        // save from email
        $em->clear();
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($this->user->getUserid());
        $this->itinerariesProcessor->save([$flight1], SavingOptions::savingByEmail(new Owner($this->user), 123, new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com')));
        $I->assertEquals(150, $I->grabFromDatabase("Trip", "Total", ["TripID" => $tripId]));
        $I->assertEquals(1, $I->grabCountFromDatabase("TripSegment", ["TripID" => $tripId]));
        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripID" => $tripId, "DepCode" => "MSY"]));
        $json = $I->grabFromDatabase("TripSegment", "Sources", ["TripID" => $tripId, "DepCode" => "MSY"]);
        $I->assertCount(2, json_decode($json, true)['data']);

        // check deserialization
        $em->clear();
        $flight1->pricingInfo->total = 170;
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($this->user->getUserid());
        $this->itinerariesProcessor->save([$flight1], SavingOptions::savingByEmail(new Owner($this->user), 123, new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com')));
        $I->assertEquals(170, $I->grabFromDatabase("Trip", "Total", ["TripID" => $tripId]));
        $json = $I->grabFromDatabase("TripSegment", "Sources", ["TripID" => $tripId, "DepCode" => "MSY"]);
        $I->assertCount(2, json_decode($json, true)['data']);
    }
}
