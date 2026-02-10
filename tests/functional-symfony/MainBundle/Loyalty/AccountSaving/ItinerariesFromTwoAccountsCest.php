<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Entity\Account;
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
class ItinerariesFromTwoAccountsCest
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
     * this test will grab 2 segment reservation from one account
     * then 1 segment of the same reservation from another account
     * we expect that second segment to be removed because it was grabbed from another account, which is the full source.
     *
     * @see #14065
     */
    public function twoAccounts(\TestSymfonyGuy $I)
    {
        $depDate = strtotime("+1 day 15:00");
        $arrDate = strtotime("+3 hour", $depDate);
        /** @var EntityManagerInterface $em */
        $em = $I->getContainer()->get("doctrine.orm.entity_manager");

        $account1Id = $I->createAwAccount($this->user->getId(), "testprovider", "login1");
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
                create(function (FlightSegment $segment) use ($depDate, $arrDate) {
                    $segment->marketingCarrier = create(function (MarketingCarrier $carrier) {
                        $carrier->flightNumber = '728';
                        $carrier->airline = create(function (Airline $airline) {
                            $airline->name = "British Airways";
                            $airline->iata = "BA";
                            $airline->icao = "BAQ";
                        });
                        $carrier->confirmationNumber = 'RECLOC1';
                    });
                    $segment->departure = create(function (TripLocation $location) use ($depDate) {
                        $location->airportCode = "PHL";
                        $location->name = "Philadelphia International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "PHL";
                        });
                        $location->localDateTime = date("c", $depDate);
                    });
                    $segment->arrival = create(function (TripLocation $location) use ($arrDate) {
                        $location->airportCode = "DOH";
                        $location->name = "Hamad International Airport";
                        $location->address = create(function (Address $address) {
                            $address->text = "DOH";
                        });
                        $location->localDateTime = date("c", $arrDate);
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

        $this->itinerariesProcessor->save([$flight1], SavingOptions::savingByAccount($account1, true));
        $tripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->user->getUserid(), "AccountID" => $account1Id]);
        $I->assertEquals(2, $I->grabCountFromDatabase("TripSegment", ["TripID" => $tripId]));
        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripID" => $tripId, "DepCode" => "MSY"]));
        $json = $I->grabFromDatabase("TripSegment", "Sources", ["TripID" => $tripId, "DepCode" => "MSY"]);
        $I->assertNotEmpty($json);
        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripID" => $tripId, "DepCode" => "PHL"]));
        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["TripID" => $tripId]));

        $account2Id = $I->createAwAccount($this->user->getId(), "testprovider", "login2");
        $account2 = $em->find(Account::class, $account2Id);

        // only one segment instead of two
        array_pop($flight1->segments);
        $flight1->pricingInfo->total = 150;

        // second segment must be removed because it has another full source
        $this->itinerariesProcessor->save([$flight1], SavingOptions::savingByAccount($account2, true));
        $I->assertEquals(150, $I->grabFromDatabase("Trip", "Total", ["TripID" => $tripId]));
        $I->assertEquals(2, $I->grabCountFromDatabase("TripSegment", ["TripID" => $tripId]));
        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripID" => $tripId, "DepCode" => "MSY"]));
        $I->assertEquals(1, $I->grabFromDatabase("TripSegment", "Hidden", ["TripID" => $tripId, "DepCode" => "PHL"]));

        // remove second segment from first account, it should be marked as hidden, no active sources
        $flight1->pricingInfo->total = 170;
        $this->itinerariesProcessor->save([$flight1], SavingOptions::savingByAccount($account1, true));
        $I->assertEquals(170, $I->grabFromDatabase("Trip", "Total", ["TripID" => $tripId]));
        $I->assertEquals(2, $I->grabCountFromDatabase("TripSegment", ["TripID" => $tripId]));
        $I->assertEquals(0, $I->grabFromDatabase("TripSegment", "Hidden", ["TripID" => $tripId, "DepCode" => "MSY"]));
        $I->assertEquals(1, $I->grabFromDatabase("TripSegment", "Hidden", ["TripID" => $tripId, "DepCode" => "PHL"]));

        // remove outdated source
        $I->assertEquals(2, count(json_decode($I->grabFromDatabase("TripSegment", "Sources", ["TripID" => $tripId, "DepCode" => "MSY"]), true)));
        $em->remove($account2);
        $em->flush();
        $this->itinerariesProcessor->save([$flight1], SavingOptions::savingByAccount($account1, true));
        $json = $I->grabFromDatabase("TripSegment", "Sources", ["TripID" => $tripId, "DepCode" => "MSY"]);
        $I->assertEquals(1, count(json_decode($json, true)["data"]));
    }
}
