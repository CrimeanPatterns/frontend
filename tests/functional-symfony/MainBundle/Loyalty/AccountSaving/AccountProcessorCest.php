<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Loyalty\AccountSaving;

use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\LoggedIn;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;

/**
 * @group frontend-functional
 */
class AccountProcessorCest extends BaseTraitCest
{
    use \Awardwallet\Tests\Modules\AutoVerifyMocksTrait;
    use StaffUser;
    use LoggedIn;

    private const STAGE_ALL = 1;
    private const STAGE_ONLY_FLIGHT_3 = 2;
    private const STAGE_NO_FLIGHTS_AND_CANCELLED = 3;
    private const STAGE_NO_FLIGHTS = 4;
    private const STAGE_NO_FLIGHTS_AND_NO_ITINERARIES = 5;

    public function removeObsoleteItineraries(\TestSymfonyGuy $I)
    {
        $providerName = 'p' . $I->grabRandomString(9);
        $itineraries = $this->getItineraries();
        $stage = null;
        $providerId = $I->createAwProvider(
            $providerName,
            $providerName,
            [],
            [
                'ParseItineraries' => function () use ($itineraries, &$stage) {
                    if (AccountProcessorCest::STAGE_ONLY_FLIGHT_3 === $stage) {
                        // Remove FLIGHT1 and FLIGHT2 on second check
                        unset($itineraries[0]);
                        unset($itineraries[1]);
                    }

                    if (AccountProcessorCest::STAGE_NO_FLIGHTS_AND_CANCELLED === $stage) {
                        // Remove all flights, but this update will be ignored, because noItineraries not set
                        $itineraries = [
                            // cancelled itinerary should not spoiler noItineraries = false logic
                            [
                                "Kind" => "T",
                                "RecordLocator" => "CANCEL1",
                                "Cancelled" => true,
                            ],
                        ];
                    }

                    if (AccountProcessorCest::STAGE_NO_FLIGHTS === $stage) {
                        // Remove all flights, but this update will be ignored, because noItineraries not set
                        $itineraries = [];
                    }

                    if (AccountProcessorCest::STAGE_NO_FLIGHTS_AND_NO_ITINERARIES === $stage) {
                        // Remove all flights, set no itineraris on third check
                        return $this->noItinerariesArr();
                    }

                    return $itineraries;
                },
            ]
        );

        $stage = self::STAGE_ALL;
        $accountId = $I->createAwAccount($this->user->getUserid(), $providerId, 'login');
        $I->checkAccount($accountId);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT1', 'Hidden' => 0, 'Parsed' => 1, 'AccountID' => $accountId]);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT2', 'Hidden' => 0, 'Parsed' => 1, 'AccountID' => $accountId]);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT3', 'Hidden' => 0, 'Parsed' => 1, 'AccountID' => $accountId]);

        $stage = self::STAGE_ONLY_FLIGHT_3;
        $I->checkAccount($accountId);
        // past trip, untouched
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT1', 'Hidden' => 0, 'Parsed' => 1, 'AccountID' => $accountId]);
        // removed, hidden
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT2', 'Hidden' => 1, 'Parsed' => 0, 'AccountID' => $accountId]);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT3', 'Hidden' => 0, 'Parsed' => 1, 'AccountID' => $accountId]);
        $I->seeInDatabase('Account', ['AccountID' => $accountId, 'Itineraries' => 1]);

        $stage = self::STAGE_NO_FLIGHTS_AND_CANCELLED;
        $I->checkAccount($accountId);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT1', 'Hidden' => 0, 'Parsed' => 1, 'AccountID' => $accountId]);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT2', 'Hidden' => 1, 'Parsed' => 0, 'AccountID' => $accountId]);
        // still here, despite it has been removed from parser
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT3', 'Hidden' => 0, 'Parsed' => 0, 'AccountID' => $accountId]);
        $I->seeInDatabase('Account', ['AccountID' => $accountId, 'Itineraries' => 1]);

        $stage = self::STAGE_NO_FLIGHTS;
        $I->checkAccount($accountId);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT1', 'Hidden' => 0, 'Parsed' => 1, 'AccountID' => $accountId]);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT2', 'Hidden' => 1, 'Parsed' => 0, 'AccountID' => $accountId]);
        // still here, despite it has been removed from parser
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT3', 'Hidden' => 0, 'Parsed' => 0, 'AccountID' => $accountId]);
        $I->seeInDatabase('Account', ['AccountID' => $accountId, 'Itineraries' => 1]);

        $stage = self::STAGE_NO_FLIGHTS_AND_NO_ITINERARIES;
        $I->checkAccount($accountId);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT1', 'Hidden' => 0, 'Parsed' => 1, 'AccountID' => $accountId]);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT2', 'Hidden' => 1, 'Parsed' => 0, 'AccountID' => $accountId]);
        $I->seeInDatabase('Trip', ['RecordLocator' => 'FLIGHT3', 'Hidden' => 1, 'Parsed' => 0, 'AccountID' => $accountId]);
        $I->seeInDatabase('Account', ['AccountID' => $accountId, 'Itineraries' => -1]);

        // itineraries field should be updated despite checking without itineraries
        $stage = self::STAGE_NO_FLIGHTS;
        $I->checkAccount($accountId, false);
        $I->assertEquals(0, $I->grabFromDatabase('Account', 'Itineraries', ['AccountID' => $accountId]));
    }

    private function getItineraries()
    {
        return [
            [
                'Kind' => 'T',
                'RecordLocator' => 'FLIGHT1',
                'TripSegments' => [
                    [
                        'AirlineName' => 'Air Transat',
                        'FlightNumber' => '10051',
                        'DepCode' => 'LAX',
                        'DepName' => 'LAX',
                        'DepDate' => (new \DateTime('1 week ago 10:00'))->getTimestamp(),
                        'ArrCode' => 'JFK',
                        'ArrName' => 'JFK',
                        'ArrDate' => (new \DateTime('1 week ago 11:00'))->getTimestamp(),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'FLIGHT2',
                'TripSegments' => [
                    [
                        'AirlineName' => 'Air Transat',
                        'FlightNumber' => '10052',
                        'DepCode' => 'LAX',
                        'DepName' => 'LAX',
                        'DepDate' => (new \DateTime('tomorrow 10:00'))->getTimestamp(),
                        'ArrCode' => 'JFK',
                        'ArrName' => 'JFK',
                        'ArrDate' => (new \DateTime('tomorrow 11:00'))->getTimestamp(),
                    ],
                ],
            ],
            [
                'Kind' => 'T',
                'RecordLocator' => 'FLIGHT3',
                'TripSegments' => [
                    [
                        'AirlineName' => 'Air Transat',
                        'FlightNumber' => '10053',
                        'DepCode' => 'LAX',
                        'DepName' => 'LAX',
                        'DepDate' => (new \DateTime('+2 days 10:00'))->getTimestamp(),
                        'ArrCode' => 'JFK',
                        'ArrName' => 'JFK',
                        'ArrDate' => (new \DateTime('+2 days 11:00'))->getTimestamp(),
                    ],
                ],
            ],
        ];
    }
}
