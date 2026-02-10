<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\MainBundle\Email\ParsedEmailSource;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\Airline;
use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\FlightSegment;
use AwardWallet\Schema\Itineraries\MarketingCarrier;
use AwardWallet\Schema\Itineraries\Person;
use AwardWallet\Schema\Itineraries\PricingInfo;
use AwardWallet\Schema\Itineraries\ProviderInfo;
use AwardWallet\Schema\Itineraries\TripLocation;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\Tests\Modules\Utils\ClosureEvaluator\create;

/**
 * @group frontend-functional
 */
class ItinerariesForFamilyMembersCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    private ?Usr $user;

    private ?ItinerariesProcessor $itinerariesProcessor;
    /**
     * @var Flight
     */
    private $flight;
    /**
     * @var int
     */
    private $fmId;
    /**
     * @var Useragent
     */
    private $fm;
    /**
     * @var int
     */
    private $userTripId;
    /**
     * @var int
     */
    private $userId;
    /**
     * @var int
     */
    private $accountId;
    /**
     * @var Account
     */
    private $account;

    public function _before(\TestSymfonyGuy $I)
    {
        $this->userId = $I->createAwUser();
        $this->fmId = $I->createFamilyMember($this->userId, "Jessica", "Smith");
        $this->accountId = $I->createAwAccount($this->userId, "testprovider", "somelogin");

        $this->itinerariesProcessor = $I->grabService(ItinerariesProcessor::class);

        /** @var EntityManagerInterface $em */
        $em = $I->getContainer()->get("doctrine.orm.entity_manager");
        $em->clear();

        $this->flight = $this->createFlight();

        // save to main user
        $em->clear();
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($this->userId);
        $this->account = $I->grabService('doctrine')->getRepository(Account::class)->find($this->accountId);
        $this->itinerariesProcessor->save([$this->flight], SavingOptions::savingByAccount($this->account, true));
        $this->userTripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->user->getUserid()]);
        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["TripID" => $this->userTripId]));
        $I->assertEquals(null, $I->grabFromDatabase("Trip", "UserAgentID", ["TripID" => $this->userTripId]));

        $em->clear();
        $this->user = $I->grabService('doctrine')->getRepository(Usr::class)->find($this->userId);
        $this->fm = $em->find(Useragent::class, $this->fmId);
        $this->account = $I->grabService('doctrine')->getRepository(Account::class)->find($this->accountId);
    }

    public function _after(\TestSymfonyGuy $I)
    {
        $this->itinerariesProcessor = null;
    }

    public function saveToFamilyMemberByName(\TestSymfonyGuy $I)
    {
        // save to family member, should not touch user trip, because there are only one traveler in trip data
        $this->flight->pricingInfo->total = 110;
        $this->flight->travelers = [create(function (Person $person) {
            $person->name = 'Jessica Smith';
            $person->full = true;
        })];

        $this->itinerariesProcessor->save([$this->flight], SavingOptions::savingByEmail(new Owner($this->user, $this->fm), 123, new ParsedEmailSource(ParsedEmailSource::SOURCE_PLANS, 'test@test.com')));

        $fmTripId = $I->grabFromDatabase("Trip", "TripID", ["UserID" => $this->user->getUserid(), "UserAgentID" => $this->fmId]);
        $I->assertEquals(110, $I->grabFromDatabase("Trip", "Total", ["TripID" => $fmTripId]));
        $I->assertEquals(100, $I->grabFromDatabase("Trip", "Total", ["TripID" => $this->userTripId]));
        $I->assertEquals(null, $I->grabFromDatabase("Trip", "UserAgentID", ["TripID" => $this->userTripId]));
    }

    public function doNotMoveBackMovedTrip(\TestSymfonyGuy $I)
    {
        $I->executeQuery("update Trip set UserAgentID = {$this->fmId}, Moved = 1 where TripID = {$this->userTripId}");

        $this->flight->pricingInfo->total = 110;

        $report = $this->itinerariesProcessor->save([$this->flight], SavingOptions::savingByAccount($this->account, true));
        $I->assertCount(0, $report->getAdded());
        $I->assertCount(1, $report->getUpdated());
        $I->assertCount(0, $report->getRemoved());

        $I->assertEquals(110, $I->grabFromDatabase("Trip", "Total", ["TripID" => $this->userTripId]));
        $I->assertEquals(0, $I->grabFromDatabase("Trip", "Hidden", ["TripID" => $this->userTripId]));
        $I->assertEquals($this->fmId, $I->grabFromDatabase("Trip", "UserAgentID", ["TripID" => $this->userTripId]));
    }

    private function createFlight(): Flight
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return create(function (Flight $flight) {
            $depDate = strtotime("+1 day 15:00");
            $arrDate = strtotime("+3 hour", $depDate);

            $airlineProviderCode = "p" . bin2hex(random_bytes(7));
            $airlineProviderName = "n" . $airlineProviderCode;

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
            $flight->travelers = [create(function (Person $person) {
                $person->name = 'John Smith';
                $person->full = true;
            })];
        });
    }
}
